<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Concerns\HandlesPaperBuilder;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Standard;
use App\Services\StudentNotificationService;
use App\Support\ActivityLogger;
use App\Support\PaperTypeHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    use HandlesPaperBuilder;

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
        $request->validate($this->paperMetaRules(true));
        $typeCounts = $this->extractTypeCounts($request);
        $marksPerType = $this->extractMarksPerType($request, $typeCounts);
        $meta = $this->extractPaperMeta($request, true);

        $generated = $this->generateFromMaterials($meta, $typeCounts, $marksPerType);

        $payload = [
            'meta' => $meta,
            'type_counts' => $typeCounts,
            'marks_per_type' => $generated['marks_per_type'],
            'questions' => $this->snapshotQuestions($generated['questions']),
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
            ...$this->persistablePaperMeta($preview['meta']),
            'teacher_id' => auth()->id(),
            'total_marks' => $preview['total_marks'],
            'generation_config' => [
                'type_counts' => $preview['type_counts'],
                'marks_per_type' => $preview['marks_per_type'],
                'material_ids' => [($preview['meta']['material_id'] ?? null)],
                'material_topic_ids' => array_values(array_filter([($preview['meta']['material_topic_id'] ?? null)])),
                'chapter_name' => $preview['meta']['chapter_name'] ?? null,
                'topic_name' => $preview['meta']['topic_name'] ?? null,
                'generated_from' => 'teacher_exam_materials',
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
            'selectedChapterId' => old('chapter_id', $exam->generation_config['material_ids'][0] ?? ''),
            'selectedTopicId' => old('topic_id', $exam->generation_config['material_topic_ids'][0] ?? ''),
        ]);
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        $wasPublished = $exam->isPublished();
        $request->validate($this->paperMetaRules(true));
        $typeCounts = $this->extractTypeCounts($request);
        $marksPerType = $this->extractMarksPerType($request, $typeCounts);
        $meta = $this->extractPaperMeta($request, true);

        $generated = $this->generateFromMaterials($meta, $typeCounts, $marksPerType);
        $preview = [
            'marks_per_type' => $generated['marks_per_type'],
            'questions' => $this->snapshotQuestions($generated['questions']),
        ];

        $exam->update([
            ...$this->persistablePaperMeta($meta),
            'total_marks' => $generated['total_marks'],
            'generation_config' => [
                'type_counts' => $typeCounts,
                'marks_per_type' => $generated['marks_per_type'],
                'material_ids' => [$meta['material_id'] ?? null],
                'material_topic_ids' => array_values(array_filter([$meta['material_topic_id'] ?? null])),
                'chapter_name' => $meta['chapter_name'] ?? null,
                'topic_name' => $meta['topic_name'] ?? null,
                'generated_from' => 'teacher_exam_materials',
            ],
        ]);

        $this->syncGeneratedQuestions($exam, $preview);

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

    private function persistablePaperMeta(array $meta): array
    {
        return collect($meta)->only([
            'standard',
            'subject_id',
            'chapter_id',
            'topic_id',
            'title',
            'description',
            'status',
            'instructions',
            'duration_minutes',
            'starts_at',
            'ends_at',
        ])->all();
    }

    private function syncGeneratedQuestions(Exam $exam, array $preview): void
    {
        $rows = $preview['questions'] ?? [];
        $exam->questions()->delete();

        $sort = 0;
        foreach ($rows as $question) {
            ExamQuestion::create([
                'exam_id' => $exam->id,
                'chapter_question_id' => $question['id'] ?? null,
                'question_type' => $question['question_type'],
                'question_text' => $question['question_text'],
                'options' => $question['options'] ?? null,
                'answer' => $question['answer'] ?? null,
                'marks' => $preview['marks_per_type'][$question['question_type']] ?? 1,
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
