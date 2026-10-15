<?php

namespace App\Http\Controllers\Teacher\Concerns;

use App\Models\Exam;
use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\Subject;
use Illuminate\Support\Carbon;
use App\Support\MaterialPaperBank;
use App\Support\PaperTypeHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

trait HandlesPaperBuilder
{
    protected function paperMetaRules(bool $forExam): array
    {
        $rules = [
            'standard' => ['required', 'string', 'max:50'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'chapter_ids' => ['required_without:chapter_id', 'array', 'min:1'],
            'chapter_ids.*' => ['integer', 'exists:materials,id'],
            'chapter_id' => ['required_without:chapter_ids', 'nullable', 'integer', 'exists:materials,id'],
            'topic_ids' => ['nullable', 'array'],
            'topic_ids.*' => ['integer', 'exists:material_topics,id'],
            'topic_id' => ['nullable', 'integer', 'exists:material_topics,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published'],
            'type_counts' => ['required', 'array'],
            'type_counts.*' => ['nullable', 'integer', 'min:0', 'max:200'],
        ];

        if ($forExam) {
            $rules += [
                'instructions' => ['nullable', 'string'],
                'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
                'starts_at' => ['nullable', 'date'],
                'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
                'marks_per_type' => ['nullable', 'array'],
                'marks_per_type.*' => ['nullable', 'integer', 'min:1', 'max:100'],
            ];
        } else {
            $rules += [
                'due_at' => ['nullable', 'date'],
            ];
        }

        return $rules;
    }

    protected function extractPaperMeta(Request $request, bool $forExam): array
    {
        $keys = ['standard', 'subject_id', 'chapter_id', 'topic_id', 'title', 'description', 'status'];

        if ($forExam) {
            $keys = array_merge($keys, ['instructions', 'duration_minutes', 'starts_at', 'ends_at']);
        } else {
            $keys[] = 'due_at';
        }

        $meta = $request->only($keys);
        foreach (['starts_at', 'ends_at'] as $key) {
            $value = (string) ($meta[$key] ?? '');
            if ($value === '' || ! str_contains($value, 'T')) {
                continue;
            }
            $meta[$key] = Carbon::parse($value, Exam::SCHOOL_TIMEZONE)->utc()->toDateTimeString();
        }
        $materialIds = $this->requestIdList($request, 'chapter_ids', 'chapter_id');
        $topicIds = $this->requestIdList($request, 'topic_ids', 'topic_id');

        $subject = Subject::query()->with('standard')->find((int) ($meta['subject_id'] ?? 0));
        if (! $subject) {
            throw ValidationException::withMessages([
                'subject_id' => 'Selected subject was not found.',
            ]);
        }

        $materials = MaterialPaperBank::resolveMaterials($subject, null, $materialIds);
        $topics = MaterialPaperBank::resolveTopics($materials, $topicIds);

        $meta['material_ids'] = $materials->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $meta['material_topic_ids'] = $topics->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $meta['chapter_ids'] = $meta['material_ids'];
        $meta['topic_ids'] = $meta['material_topic_ids'];
        $meta['material_id'] = $meta['material_ids'][0] ?? null;
        $meta['material_topic_id'] = $meta['material_topic_ids'][0] ?? null;
        $meta['chapter_id'] = $materials->pluck('chapter_id')->filter()->map(fn ($id) => (int) $id)->first();
        $meta['topic_id'] = null;
        $meta['chapter_name'] = MaterialPaperBank::chapterLabel($materials);
        $meta['topic_name'] = MaterialPaperBank::topicLabel($topics);

        return $meta;
    }

    /**
     * @return array{0: list<int>, 1: list<int>}
     */
    protected function generationScope(array $meta): array
    {
        $materialIds = array_values(array_filter(array_map(
            'intval',
            (array) ($meta['material_ids'] ?? (isset($meta['material_id']) ? [$meta['material_id']] : []))
        )));
        $topicIds = array_values(array_filter(array_map(
            'intval',
            (array) ($meta['material_topic_ids'] ?? (isset($meta['material_topic_id']) ? [$meta['material_topic_id']] : []))
        )));

        return [$materialIds, $topicIds];
    }

    /**
     * @return list<int>
     */
    private function requestIdList(Request $request, string $listKey, string $singleKey): array
    {
        $values = $request->input($listKey, []);
        if (! is_array($values) || $values === []) {
            $values = $request->filled($singleKey) ? [$request->input($singleKey)] : [];
        }

        return collect($values)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{materials: Collection, topics: Collection}
     */
    protected function resolvePaperMaterials(array $meta): array
    {
        $materialIds = $meta['material_ids'] ?? (isset($meta['material_id']) ? [$meta['material_id']] : []);
        $materials = Material::query()
            ->with(['topics' => fn ($q) => $q->orderBy('topic_order')])
            ->whereIn('id', array_filter(array_map('intval', (array) $materialIds)))
            ->get();

        if ($materials->isEmpty()) {
            throw ValidationException::withMessages([
                'chapter_ids' => 'Select at least one chapter.',
            ]);
        }

        $topicIds = array_values(array_filter(array_map(
            'intval',
            (array) ($meta['material_topic_ids'] ?? (isset($meta['material_topic_id']) ? [$meta['material_topic_id']] : []))
        )));
        $topics = $topicIds === []
            ? collect()
            : MaterialTopic::query()
                ->whereIn('material_id', $materials->pluck('id'))
                ->whereIn('id', $topicIds)
                ->get();

        return compact('materials', 'topics');
    }

    protected function generateFromMaterials(array $meta, array $typeCounts, array $marksPerType = []): array
    {
        $resolved = $this->resolvePaperMaterials($meta);

        return MaterialPaperBank::generate(
            $resolved['materials'],
            $resolved['topics']->isEmpty() ? null : $resolved['topics'],
            $typeCounts,
            $marksPerType
        );
    }

    /**
     * @param  Collection<int, mixed>  $questions
     * @return list<array<string, mixed>>
     */
    protected function snapshotQuestions(Collection $questions): array
    {
        return $questions->map(fn ($question) => [
            'id' => $question->id ?: null,
            'question_type' => $question->question_type,
            'question_text' => $question->question_text,
            'options' => $question->options,
            'answer' => $question->answer,
        ])->values()->all();
    }

    protected function extractTypeCounts(Request $request): array
    {
        return PaperTypeHelper::normalizeCounts($request->input('type_counts', []));
    }

    protected function extractMarksPerType(Request $request, array $typeCounts): array
    {
        return PaperTypeHelper::normalizeMarksPerType($request->input('marks_per_type', []), $typeCounts);
    }

    protected function previewSessionKey(string $kind): string
    {
        return "teacher.{$kind}.preview";
    }

    protected function storePreview(string $kind, array $payload): void
    {
        session([$this->previewSessionKey($kind) => $payload]);
    }

    protected function pullPreview(string $kind): ?array
    {
        return session()->pull($this->previewSessionKey($kind));
    }
}
