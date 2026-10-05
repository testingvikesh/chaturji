<?php

namespace App\Http\Controllers\Teacher\Concerns;

use App\Models\Material;
use App\Models\MaterialTopic;
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
            // chapter_id in the form = materials.id (Books chapter)
            'chapter_id' => ['required', 'integer', 'exists:materials,id'],
            // topic_id in the form = material_topics.id
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
        $materialId = (int) ($meta['chapter_id'] ?? 0);
        $materialTopicId = filled($meta['topic_id'] ?? null) ? (int) $meta['topic_id'] : null;

        $material = Material::query()->find($materialId);
        if (! $material) {
            throw ValidationException::withMessages([
                'chapter_id' => 'Selected chapter was not found.',
            ]);
        }

        if ($materialTopicId) {
            $belongs = MaterialTopic::query()
                ->where('material_id', $material->id)
                ->whereKey($materialTopicId)
                ->exists();
            if (! $belongs) {
                throw ValidationException::withMessages([
                    'topic_id' => 'Selected topic does not belong to this chapter.',
                ]);
            }
        }

        // Persist Books material ids in generation; keep FK columns nullable-safe.
        $meta['chapter_id'] = filled($material->chapter_id) ? (int) $material->chapter_id : null;
        $meta['topic_id'] = null;
        $meta['material_id'] = $material->id;
        $meta['material_topic_id'] = $materialTopicId;
        $meta['chapter_name'] = $material->displayChapterName();
        $meta['topic_name'] = $materialTopicId
            ? (MaterialTopic::query()->find($materialTopicId)?->displayName() ?: null)
            : null;

        return $meta;
    }

    /**
     * @return array{materials: Collection, topics: Collection}
     */
    protected function resolvePaperMaterials(array $meta): array
    {
        $material = Material::query()
            ->with(['topics' => fn ($q) => $q->orderBy('topic_order')])
            ->findOrFail((int) ($meta['material_id'] ?? 0));

        $materials = collect([$material]);
        $topics = collect();

        if (! empty($meta['material_topic_id'])) {
            $topic = $material->topics->firstWhere('id', (int) $meta['material_topic_id'])
                ?: MaterialTopic::query()->where('material_id', $material->id)->whereKey((int) $meta['material_topic_id'])->first();
            if ($topic) {
                $topics = collect([$topic]);
            }
        }

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
