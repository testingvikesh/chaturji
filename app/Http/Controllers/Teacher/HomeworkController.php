<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Concerns\HandlesPaperBuilder;
use App\Models\Homework;
use App\Models\HomeworkQuestion;
use App\Models\Standard;
use App\Services\QuestionPaperGeneratorService;
use App\Services\StudentNotificationService;
use App\Support\ActivityLogger;
use App\Support\PaperTypeHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeworkController extends Controller
{
    use HandlesPaperBuilder;

    public function __construct(
        private readonly QuestionPaperGeneratorService $generator
    ) {}

    public function index(): View
    {
        $homeworks = Homework::query()
            ->where('teacher_id', auth()->id())
            ->where(function ($q) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('homeworks', 'is_self_homework')) {
                    $q->where('is_self_homework', false);
                }
            })
            ->with(['subject:id,name', 'chapter:id,name'])
            ->withCount('questions')
            ->latest()
            ->get();

        return view('teacher.homework.index', compact('homeworks'));
    }

    public function create(): View
    {
        return view('teacher.homework.create', [
            'standards' => $this->standardOptions(),
            'paperTypes' => PaperTypeHelper::types(),
        ]);
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $request->validate($this->paperMetaRules(false));
        $typeCounts = $this->extractTypeCounts($request);
        $meta = $this->extractPaperMeta($request, false);

        $generated = $this->generator->generate(
            (int) $meta['chapter_id'],
            $meta['topic_id'],
            $typeCounts
        );

        $payload = [
            'meta' => $meta,
            'type_counts' => $typeCounts,
            'marks_per_type' => [],
            'question_ids' => $generated['questions']->pluck('id')->all(),
            'breakdown' => $generated['breakdown'],
            'total_marks' => 0,
        ];

        $this->storePreview('homework', $payload);

        return view('teacher.homework.preview', [
            'meta' => $meta,
            'typeCounts' => $typeCounts,
            'breakdown' => $generated['breakdown'],
            'grouped' => $generated['grouped'],
            'totalQuestions' => PaperTypeHelper::totalQuestions($typeCounts),
            'paperTypes' => PaperTypeHelper::types(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $preview = $this->pullPreview('homework');

        if (! $preview) {
            return redirect()->route('teacher.homework.create')
                ->with('error', 'Preview expired. Please configure and preview the homework again.');
        }

        $homework = Homework::create([
            ...$preview['meta'],
            'teacher_id' => auth()->id(),
            'generation_config' => [
                'type_counts' => $preview['type_counts'],
                'question_ids' => $preview['question_ids'],
            ],
        ]);

        $this->syncGeneratedQuestions($homework, $preview);

        if ($homework->isPublished()) {
            StudentNotificationService::homeworkAssigned($homework);
        }

        ActivityLogger::log(
            'teacher.homework.create',
            'Created homework: '.$homework->title,
            $homework,
            ['title' => $homework->title, 'status' => $homework->status ?? null]
        );

        return redirect()->route('teacher.homework.show', $homework)
            ->with('success', 'Homework created successfully.');
    }

    public function show(Homework $homework): View
    {
        $this->authorizeHomework($homework);

        $homework->load(['subject', 'chapter', 'topic', 'questions']);
        $grouped = PaperTypeHelper::groupInPaperOrder($homework->questions);
        $breakdown = PaperTypeHelper::breakdown(
            $homework->generation_config['type_counts'] ?? $grouped->map->count()->all()
        );

        return view('teacher.homework.show', compact('homework', 'grouped', 'breakdown'));
    }

    public function edit(Homework $homework): View
    {
        $this->authorizeHomework($homework);
        $homework->load(['subject', 'chapter', 'topic']);

        return view('teacher.homework.edit', [
            'homework' => $homework,
            'standards' => $this->standardOptions(),
            'paperTypes' => PaperTypeHelper::types(),
        ]);
    }

    public function update(Request $request, Homework $homework): RedirectResponse
    {
        $this->authorizeHomework($homework);

        $wasPublished = $homework->isPublished();
        $request->validate($this->paperMetaRules(false));
        $typeCounts = $this->extractTypeCounts($request);
        $meta = $this->extractPaperMeta($request, false);

        $generated = $this->generator->generate(
            (int) $meta['chapter_id'],
            $meta['topic_id'],
            $typeCounts
        );

        $homework->update([
            ...$meta,
            'generation_config' => [
                'type_counts' => $typeCounts,
                'question_ids' => $generated['questions']->pluck('id')->all(),
            ],
        ]);

        $this->syncGeneratedQuestions($homework, [
            'question_ids' => $generated['questions']->pluck('id')->all(),
        ], $generated['questions']);

        if ($homework->isPublished()) {
            StudentNotificationService::homeworkAssigned($homework, $wasPublished ? 'updated' : 'created');
        }

        return redirect()->route('teacher.homework.show', $homework)->with('success', 'Homework updated successfully.');
    }

    public function destroy(Homework $homework): RedirectResponse
    {
        $this->authorizeHomework($homework);
        $homework->delete();

        return redirect()->route('teacher.homework.index')->with('success', 'Homework deleted successfully.');
    }

    private function authorizeHomework(Homework $homework): void
    {
        abort_unless($homework->teacher_id === auth()->id(), 403);
    }

    private function syncGeneratedQuestions(Homework $homework, array $preview, $questions = null): void
    {
        $questions = $questions ?? \App\Models\ChapterQuestion::query()
            ->whereIn('id', $preview['question_ids'])
            ->get()
            ->sortBy(fn ($q) => array_search($q->id, $preview['question_ids'], true));

        $homework->questions()->delete();

        $sort = 0;
        foreach ($questions as $question) {
            HomeworkQuestion::create([
                'homework_id' => $homework->id,
                'chapter_question_id' => $question->id,
                'question_type' => $question->question_type,
                'question_text' => $question->question_text,
                'options' => $question->options,
                'answer' => $question->answer,
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
