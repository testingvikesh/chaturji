<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Concerns\HandlesPaperBuilder;
use App\Models\Homework;
use App\Models\HomeworkQuestion;
use App\Models\Standard;
use App\Services\StudentNotificationService;
use App\Support\ActivityLogger;
use App\Support\PaperTypeHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeworkController extends Controller
{
    use HandlesPaperBuilder;

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

        $generated = $this->generateFromMaterials($meta, $typeCounts);

        $payload = [
            'meta' => $meta,
            'type_counts' => $typeCounts,
            'marks_per_type' => [],
            'questions' => $this->snapshotQuestions($generated['questions']),
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
            ...$this->persistablePaperMeta($preview['meta']),
            'teacher_id' => auth()->id(),
            'generation_config' => [
                'type_counts' => $preview['type_counts'],
                'material_ids' => [($preview['meta']['material_id'] ?? null)],
                'material_topic_ids' => array_values(array_filter([($preview['meta']['material_topic_id'] ?? null)])),
                'chapter_name' => $preview['meta']['chapter_name'] ?? null,
                'topic_name' => $preview['meta']['topic_name'] ?? null,
                'generated_from' => 'teacher_homework_materials',
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
            'selectedChapterId' => old('chapter_id', $homework->generation_config['material_ids'][0] ?? ''),
            'selectedTopicId' => old('topic_id', $homework->generation_config['material_topic_ids'][0] ?? ''),
        ]);
    }

    public function update(Request $request, Homework $homework): RedirectResponse
    {
        $this->authorizeHomework($homework);

        $wasPublished = $homework->isPublished();
        $request->validate($this->paperMetaRules(false));
        $typeCounts = $this->extractTypeCounts($request);
        $meta = $this->extractPaperMeta($request, false);

        $generated = $this->generateFromMaterials($meta, $typeCounts);
        $preview = [
            'questions' => $this->snapshotQuestions($generated['questions']),
        ];

        $homework->update([
            ...$this->persistablePaperMeta($meta),
            'generation_config' => [
                'type_counts' => $typeCounts,
                'material_ids' => [$meta['material_id'] ?? null],
                'material_topic_ids' => array_values(array_filter([$meta['material_topic_id'] ?? null])),
                'chapter_name' => $meta['chapter_name'] ?? null,
                'topic_name' => $meta['topic_name'] ?? null,
                'generated_from' => 'teacher_homework_materials',
            ],
        ]);

        $this->syncGeneratedQuestions($homework, $preview);

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
            'due_at',
        ])->all();
    }

    private function syncGeneratedQuestions(Homework $homework, array $preview): void
    {
        $rows = $preview['questions'] ?? [];
        $homework->questions()->delete();

        $sort = 0;
        foreach ($rows as $question) {
            HomeworkQuestion::create([
                'homework_id' => $homework->id,
                'chapter_question_id' => $question['id'] ?? null,
                'question_type' => $question['question_type'],
                'question_text' => $question['question_text'],
                'options' => $question['options'] ?? null,
                'answer' => $question['answer'] ?? null,
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
