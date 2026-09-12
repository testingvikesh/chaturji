<?php

namespace App\Http\Controllers\Teacher\Concerns;

use App\Support\PaperTypeHelper;
use Illuminate\Http\Request;

trait HandlesPaperBuilder
{
    protected function paperMetaRules(bool $forExam): array
    {
        $rules = [
            'standard' => ['required', 'string', 'max:50'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'chapter_id' => ['required', 'integer', 'exists:chapters,id'],
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
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
        $meta['topic_id'] = filled($meta['topic_id'] ?? null) ? (int) $meta['topic_id'] : null;

        return $meta;
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
