<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Concerns\HandlesPaperBuilder;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\MaterialTopic;
use App\Models\Standard;
use App\Services\SelfExamGeneratorService;
use App\Services\StudentNotificationService;
use App\Support\ActivityLogger;
use App\Support\ChapterMaterialHelper;
use App\Support\MaterialQuestionEditor;
use App\Support\MaterialTopicReader;
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
        $meta = $this->extractPaperMeta($request, true);
        [$typeCounts, $marksPerType] = $this->countsForExam($request, $meta);
        $optionalCounts = $this->extractOptionalCounts($request, $typeCounts);

        $generated = $this->generateFromMaterials($meta, $typeCounts, $marksPerType, $optionalCounts);

        $payload = [
            'meta' => $meta,
            'type_counts' => $typeCounts,
            'optional_counts' => $optionalCounts,
            'marks_per_type' => $generated['marks_per_type'],
            'questions' => $this->snapshotQuestions($generated['questions']),
            'breakdown' => $generated['breakdown'],
            'total_marks' => $generated['total_marks'],
            'target_marks' => (int) $request->input('target_marks'),
        ];

        $this->storePreview('exam', $payload);

        return view('teacher.exams.preview', [
            'meta' => $meta,
            'typeCounts' => $typeCounts,
            'optionalCounts' => $optionalCounts,
            'marksPerType' => $generated['marks_per_type'],
            'breakdown' => $generated['breakdown'],
            'grouped' => $generated['grouped'],
            'totalMarks' => $generated['total_marks'],
            'totalQuestions' => $generated['questions']->count(),
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

        [$materialIds, $topicIds] = $this->generationScope($preview['meta']);

        $exam = Exam::create([
            ...$this->persistablePaperMeta($preview['meta']),
            'teacher_id' => auth()->id(),
            'total_marks' => $preview['total_marks'],
            'generation_config' => [
                'type_counts' => $preview['type_counts'],
                'optional_counts' => $preview['optional_counts'] ?? [],
                'marks_per_type' => $preview['marks_per_type'],
                'material_ids' => $materialIds,
                'material_topic_ids' => $topicIds,
                'chapter_name' => $preview['meta']['chapter_name'] ?? null,
                'topic_name' => $preview['meta']['topic_name'] ?? null,
                'target_marks' => (int) ($preview['target_marks'] ?? 0),
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

    public function editQuestion(Exam $exam, ExamQuestion $question): View
    {
        $this->authorizeExam($exam);
        abort_unless((int) $question->exam_id === (int) $exam->id, 404);

        return view('teacher.exams.question-edit', [
            'exam' => $exam,
            'question' => $question,
            'optionsText' => $this->optionsText($question->options),
            'isMatch' => $this->isMatchOptions($question->options),
        ]);
    }

    public function updateQuestion(Request $request, Exam $exam, ExamQuestion $question): RedirectResponse
    {
        $this->authorizeExam($exam);
        abort_unless((int) $question->exam_id === (int) $exam->id, 404);

        $validated = $request->validate([
            'question_text' => ['required', 'string'],
            'answer' => ['nullable', 'string'],
            'options_text' => ['nullable', 'string'],
        ]);

        $oldText = (string) $question->question_text;
        $oldType = (string) $question->question_type;
        $isMatch = $this->isMatchOptions($question->options);
        $options = $question->options;

        if (! $isMatch) {
            $lines = array_values(array_filter(array_map(
                'trim',
                preg_split('/\r\n|\r|\n/', (string) ($validated['options_text'] ?? '')) ?: []
            )));
            $options = $lines !== [] ? $lines : null;
        }

        $located = $this->locateChapterQuestion($exam, $question);
        if ($located) {
            [$topic, $questionKey] = $located;
            MaterialQuestionEditor::update(auth()->user(), $topic->fresh(), $questionKey, [
                'question_text' => $validated['question_text'],
                'answer' => $validated['answer'] ?? null,
                'options' => $isMatch ? $question->options : $options,
            ]);
        }

        ExamQuestion::query()
            ->where('question_type', $oldType)
            ->where('question_text', $oldText)
            ->whereHas('exam', fn ($query) => $query->where('teacher_id', auth()->id()))
            ->get()
            ->each(function (ExamQuestion $row) use ($validated, $options, $isMatch) {
                $row->update([
                    'question_text' => $validated['question_text'],
                    'answer' => $validated['answer'] ?? null,
                    'options' => $isMatch ? $row->options : $options,
                ]);
            });

        $message = $located
            ? 'Question and answer updated on this paper and in the chapter.'
            : 'Question and answer updated on this paper. The matching chapter question was not found.';

        return redirect()
            ->route('teacher.exams.show', $exam)
            ->with($located ? 'success' : 'error', $message);
    }

    public function printPaper(Exam $exam): View
    {
        $this->authorizeExam($exam);

        $exam->load(['subject', 'chapter:id,name,sort_order', 'topic:id,name', 'questions']);
        $grouped = PaperTypeHelper::groupInPaperOrder($exam->questions);
        $breakdown = PaperTypeHelper::breakdown(
            $exam->generation_config['type_counts'] ?? $grouped->map->count()->all(),
            $exam->generation_config['marks_per_type'] ?? []
        );

        return view('teacher.exams.print', compact('exam', 'grouped', 'breakdown'));
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
        $meta = $this->extractPaperMeta($request, true);
        [$typeCounts, $marksPerType] = $this->countsForExam($request, $meta);
        $optionalCounts = $this->extractOptionalCounts($request, $typeCounts);
        [$materialIds, $topicIds] = $this->generationScope($meta);

        if ($this->paperSelectionUnchanged($exam, $meta, $typeCounts, $marksPerType, $optionalCounts, $materialIds, $topicIds)) {
            $exam->update($this->persistablePaperMeta($meta));

            if ($exam->isPublished()) {
                StudentNotificationService::examPublished($exam, $wasPublished ? 'updated' : 'created');
            }

            return redirect()
                ->route('teacher.exams.show', $exam)
                ->with('success', 'Exam name and date updated. Questions are unchanged.');
        }

        $generated = $this->generateFromMaterials($meta, $typeCounts, $marksPerType, $optionalCounts);
        $preview = [
            'marks_per_type' => $generated['marks_per_type'],
            'questions' => $this->snapshotQuestions($generated['questions']),
        ];

        $exam->update([
            ...$this->persistablePaperMeta($meta),
            'total_marks' => $generated['total_marks'],
            'generation_config' => [
                'type_counts' => $typeCounts,
                'optional_counts' => $optionalCounts,
                'marks_per_type' => $generated['marks_per_type'],
                'material_ids' => $materialIds,
                'material_topic_ids' => $topicIds,
                'chapter_name' => $meta['chapter_name'] ?? null,
                'topic_name' => $meta['topic_name'] ?? null,
                'target_marks' => (int) $request->input('target_marks'),
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

    /**
     * @return array{0: array<string, int>, 1: array<string, int>}
     */
    private function countsForExam(Request $request, array $meta): array
    {
        $typeCounts = $this->extractTypeCounts($request);
        $marksPerType = $this->extractMarksPerType($request, $typeCounts);

        if ($typeCounts !== []) {
            return [$typeCounts, $marksPerType];
        }

        $target = (int) $request->input('target_marks');
        if (! in_array($target, SelfExamGeneratorService::MARK_OPTIONS, true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'target_marks' => 'Select total marks: 20, 30, 50, 70 or 100.',
            ]);
        }

        $resolved = $this->resolvePaperMaterials($meta);
        $plan = app(SelfExamGeneratorService::class)->buildPlan(
            $resolved['materials'],
            $resolved['topics']->isEmpty() ? null : $resolved['topics'],
            $target
        );

        return [$plan['type_counts'], $plan['marks_per_type']];
    }

    /**
     * @param  array<string, int>  $typeCounts
     * @param  array<string, int>  $marksPerType
     * @param  array<string, int>  $optionalCounts
     * @param  list<int>  $materialIds
     * @param  list<int>  $topicIds
     */
    private function paperSelectionUnchanged(
        Exam $exam,
        array $meta,
        array $typeCounts,
        array $marksPerType,
        array $optionalCounts,
        array $materialIds,
        array $topicIds
    ): bool {
        $config = $exam->generation_config ?? [];
        $savedCounts = PaperTypeHelper::normalizeCounts($config['type_counts'] ?? []);
        $savedMarksInput = (array) ($config['marks_per_type'] ?? []);
        foreach ($savedCounts as $type => $count) {
            if (! array_key_exists($type, $savedMarksInput)) {
                $savedMarksInput[$type] = \App\Support\ExamPaperWeightage::MARKS_PER_QUESTION[$type] ?? 1;
            }
        }
        $savedMarks = PaperTypeHelper::normalizeMarksPerType($savedMarksInput, $savedCounts);
        $savedOptional = $this->storedOptionalCounts($config['optional_counts'] ?? null, $savedCounts);

        return (string) $exam->standard === (string) ($meta['standard'] ?? '')
            && (int) $exam->subject_id === (int) ($meta['subject_id'] ?? 0)
            && $this->sortedIds($config['material_ids'] ?? []) === $this->sortedIds($materialIds)
            && $this->sortedIds($config['material_topic_ids'] ?? []) === $this->sortedIds($topicIds)
            && $savedCounts === $typeCounts
            && $savedMarks === $marksPerType
            && $savedOptional === $optionalCounts;
    }

    /**
     * @param  array<string, int>|null  $stored
     * @param  array<string, int>  $typeCounts
     * @return array<string, int>
     */
    private function storedOptionalCounts(?array $stored, array $typeCounts): array
    {
        $optional = [];
        foreach (\App\Support\SubjectiveQuestionTypes::TYPES as $type) {
            if (! isset($typeCounts[$type])) {
                continue;
            }
            $value = is_array($stored) && array_key_exists($type, $stored)
                ? max(0, (int) $stored[$type])
                : 1;
            if ($value > 0) {
                $optional[$type] = $value;
            }
        }

        return $optional;
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return list<int>
     */
    private function sortedIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        sort($ids);

        return $ids;
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

    /**
     * @return array{0: MaterialTopic, 1: string}|null
     */
    private function locateChapterQuestion(Exam $exam, ExamQuestion $question): ?array
    {
        $materialIds = array_values(array_filter(array_map(
            'intval',
            (array) ($exam->generation_config['material_ids'] ?? [])
        )));

        if ($materialIds === []) {
            return null;
        }

        $needle = ChapterMaterialHelper::normalizeQuestionKey((string) $question->question_text);
        $type = (string) $question->question_type;
        $topics = MaterialTopic::query()
            ->whereIn('material_id', $materialIds)
            ->orderBy('topic_order')
            ->get();

        foreach ($topics as $topic) {
            if (! $topic->hasContent()) {
                continue;
            }

            $rawQuestions = MaterialTopicReader::rawUniqueQuestions($topic);
            $edits = is_array($topic->question_edits) ? $topic->question_edits : [];

            foreach ($rawQuestions as $rawQuestion) {
                if ((string) $rawQuestion->question_type !== $type) {
                    continue;
                }

                $key = MaterialQuestionEditor::questionKey($type, (string) $rawQuestion->question_text);
                $shown = (string) ($edits[$key]['question_text'] ?? $rawQuestion->question_text);
                if (ChapterMaterialHelper::normalizeQuestionKey($shown) !== $needle) {
                    continue;
                }

                return [$topic, $key];
            }
        }

        return null;
    }

    private function isMatchOptions(mixed $options): bool
    {
        return is_array($options) && (isset($options['column_a']) || isset($options['column_b']));
    }

    private function optionsText(mixed $options): string
    {
        if (is_string($options)) {
            $decoded = json_decode($options, true);
            $options = is_array($decoded) ? $decoded : null;
        }

        if (! is_array($options) || $this->isMatchOptions($options)) {
            return '';
        }

        return implode("\n", array_map(
            fn ($option) => is_array($option) ? (string) ($option['text'] ?? $option['label'] ?? '') : (string) $option,
            $options
        ));
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
