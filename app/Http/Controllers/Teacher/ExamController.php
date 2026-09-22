<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Concerns\HandlesPaperBuilder;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Standard;
use App\Services\QuestionPaperGeneratorService;
use App\Services\StudentNotificationService;
use App\Support\ActivityLogger;
use App\Support\PaperTypeHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    use HandlesPaperBuilder;

    public function __construct(
        private readonly QuestionPaperGeneratorService $generator
    ) {}

    public function index(): View
    {
        $exams = Exam::query()
            ->where('teacher_id', auth()->id())
            ->excludeSelfExams()
            ->with(['subject:id,name', 'chapter:id,name'])
            ->withCount('questions')
            ->latest()
            ->get();

        return view('teacher.exams.index', compact('exams'));
    }

    public function create(): View
    {
        return view('teacher.exams.create', [
            'standards' => $this->standardOptions(),
            'paperTypes' => PaperTypeHelper::types(),
        ]);
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $validated = $request->validate($this->paperMetaRules(true));
        $typeCounts = $this->extractTypeCounts($request);
        $marksPerType = $this->extractMarksPerType($request, $typeCounts);
        $meta = $this->extractPaperMeta($request, true);

        $generated = $this->generator->generate(
            (int) $meta['chapter_id'],
            $meta['topic_id'],
            $typeCounts,
            $marksPerType
        );

        $payload = [
            'meta' => $meta,
            'type_counts' => $typeCounts,
            'marks_per_type' => $generated['marks_per_type'],
            'question_ids' => $generated['questions']->pluck('id')->all(),
            'breakdown' => $generated['breakdown'],
            'total_marks' => $generated['total_marks'],
        ];

        $this->storePreview('exam', $payload);

        return view('teacher.exams.preview', [
            'meta' => $meta,
            'typeCounts' => $typeCounts,
            'marksPerType' => $generated['marks_per_type'],
            'breakdown' => $generated['breakdown'],
            'grouped' => $generated['grouped'],
            'totalMarks' => $generated['total_marks'],
            'totalQuestions' => PaperTypeHelper::totalQuestions($typeCounts),
            'paperTypes' => PaperTypeHelper::types(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $preview = $this->pullPreview('exam');

        if (! $preview) {
            return redirect()->route('teacher.exams.create')
                ->with('error', 'Preview expired. Please configure and preview the exam again.');
        }

        $exam = Exam::create([
            ...$preview['meta'],
            'teacher_id' => auth()->id(),
            'total_marks' => $preview['total_marks'],
            'generation_config' => [
                'type_counts' => $preview['type_counts'],
                'marks_per_type' => $preview['marks_per_type'],
                'question_ids' => $preview['question_ids'],
            ],
        ]);

        $this->syncGeneratedQuestions($exam, $preview);

        if ($exam->isPublished()) {
            StudentNotificationService::examPublished($exam);
        }

        ActivityLogger::log(
            'teacher.exam.create',
            'Created exam: '.$exam->title,
            $exam,
            ['title' => $exam->title, 'status' => $exam->status ?? null]
        );

        return redirect()->route('teacher.exams.show', $exam)
            ->with('success', 'Exam created successfully.');
    }

    public function show(Exam $exam): View
    {
        $this->authorizeExam($exam);

        $exam->load(['subject', 'chapter', 'topic', 'questions']);
        $grouped = PaperTypeHelper::groupInPaperOrder($exam->questions);
        $breakdown = PaperTypeHelper::breakdown(
            $exam->generation_config['type_counts'] ?? $grouped->map->count()->all(),
            $exam->generation_config['marks_per_type'] ?? []
        );

        return view('teacher.exams.show', compact('exam', 'grouped', 'breakdown'));
    }

    public function edit(Exam $exam): View
    {
        $this->authorizeExam($exam);
        $exam->load(['subject', 'chapter', 'topic']);

        return view('teacher.exams.edit', [
            'exam' => $exam,
            'standards' => $this->standardOptions(),
            'paperTypes' => PaperTypeHelper::types(),
        ]);
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        $wasPublished = $exam->isPublished();
        $validated = $request->validate($this->paperMetaRules(true));
        $typeCounts = $this->extractTypeCounts($request);
        $marksPerType = $this->extractMarksPerType($request, $typeCounts);
        $meta = $this->extractPaperMeta($request, true);

        $generated = $this->generator->generate(
            (int) $meta['chapter_id'],
            $meta['topic_id'],
            $typeCounts,
            $marksPerType
        );

        $exam->update([
            ...$meta,
            'total_marks' => $generated['total_marks'],
            'generation_config' => [
                'type_counts' => $typeCounts,
                'marks_per_type' => $generated['marks_per_type'],
                'question_ids' => $generated['questions']->pluck('id')->all(),
            ],
        ]);

        $this->syncGeneratedQuestions($exam, [
            'question_ids' => $generated['questions']->pluck('id')->all(),
            'marks_per_type' => $generated['marks_per_type'],
        ], $generated['questions']);

        if ($exam->isPublished()) {
            StudentNotificationService::examPublished($exam, $wasPublished ? 'updated' : 'created');
        }

        return redirect()->route('teacher.exams.show', $exam)->with('success', 'Exam updated successfully.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);
        $exam->delete();

        return redirect()->route('teacher.exams.index')->with('success', 'Exam deleted successfully.');
    }

    private function authorizeExam(Exam $exam): void
    {
        abort_unless($exam->teacher_id === auth()->id(), 403);
    }

    private function syncGeneratedQuestions(Exam $exam, array $preview, $questions = null): void
    {
        $questions = $questions ?? \App\Models\ChapterQuestion::query()
            ->whereIn('id', $preview['question_ids'])
            ->get()
            ->sortBy(fn ($q) => array_search($q->id, $preview['question_ids'], true));

        $exam->questions()->delete();

        $sort = 0;
        foreach ($questions as $question) {
            ExamQuestion::create([
                'exam_id' => $exam->id,
                'chapter_question_id' => $question->id,
                'question_type' => $question->question_type,
                'question_text' => $question->question_text,
                'options' => $question->options,
                'answer' => $question->answer,
                'marks' => $preview['marks_per_type'][$question->question_type] ?? 1,
                'sort_order' => $sort++,
            ]);
        }
    }

    private function standardOptions(): array
    {
        $standards = Standard::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'slug')
            ->all();

        return $standards !== [] ? $standards : collect(range(1, 12))
            ->mapWithKeys(fn ($i) => ["standard_{$i}" => "Standard {$i}"])
            ->all();
    }
}
