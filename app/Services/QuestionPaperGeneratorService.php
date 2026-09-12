<?php

namespace App\Services;

use App\Models\ChapterContent;
use App\Models\ChapterQuestion;
use App\Models\Topic;
use App\Support\PaperTypeHelper;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class QuestionPaperGeneratorService
{
    /**
     * @return array<string, int>
     */
    public function availableCounts(int $chapterId, ?int $topicId = null): array
    {
        $questions = $this->poolQuery($chapterId, $topicId)->get();
        $counts = array_fill_keys(array_keys(PaperTypeHelper::types()), 0);

        foreach ($questions->groupBy('question_type') as $type => $group) {
            if (array_key_exists($type, $counts)) {
                $counts[$type] = $group->count();
            }
        }

        return $counts;
    }

    /**
     * Sum available question counts across chapters (optionally limited to topics).
     *
     * @param  list<int>  $chapterIds
     * @param  list<int>  $topicIds  Empty = all topics in selected chapters
     * @return array<string, int>
     */
    public function availableCountsForChapters(array $chapterIds, array $topicIds = []): array
    {
        $counts = array_fill_keys(array_keys(PaperTypeHelper::types()), 0);

        foreach ($this->poolQueryForScope($chapterIds, $topicIds)->get()->groupBy('question_type') as $type => $group) {
            if (array_key_exists($type, $counts)) {
                $counts[$type] = $group->count();
            }
        }

        return $counts;
    }

    /**
     * Pick questions from a pooled multi-chapter / multi-topic bank.
     *
     * @param  list<int>  $chapterIds
     * @param  list<int>  $topicIds  Empty = all topics
     * @param  array<string, int>  $typeCounts
     * @param  array<string, int>  $marksPerType
     * @return array{
     *     questions: Collection<int, ChapterQuestion>,
     *     grouped: Collection<string, Collection<int, ChapterQuestion>>,
     *     breakdown: array<int, array{type: string, label: string, count: int, marks: int, subtotal: int}>,
     *     total_marks: int,
     *     marks_per_type: array<string, int>
     * }
     */
    public function generateFromChapters(array $chapterIds, array $typeCounts, array $marksPerType = [], array $topicIds = []): array
    {
        $chapterIds = array_values(array_unique(array_filter(array_map('intval', $chapterIds))));
        $topicIds = array_values(array_unique(array_filter(array_map('intval', $topicIds))));

        if ($chapterIds === []) {
            throw ValidationException::withMessages([
                'chapter_ids' => 'Please select at least one chapter.',
            ]);
        }

        if ($typeCounts === []) {
            throw ValidationException::withMessages([
                'type_counts' => 'Enter at least one question quantity.',
            ]);
        }

        $available = $this->availableCountsForChapters($chapterIds, $topicIds);
        $selected = collect();
        $usedIds = [];

        foreach ($typeCounts as $type => $count) {
            if (! array_key_exists($type, PaperTypeHelper::types()) || $count <= 0) {
                continue;
            }

            if ($count > ($available[$type] ?? 0)) {
                throw ValidationException::withMessages([
                    "type_counts.{$type}" => PaperTypeHelper::label($type).' has only '.($available[$type] ?? 0).' question(s) available for the selected scope, but you requested '.$count.'.',
                ]);
            }

            $pool = $this->poolQueryForScope($chapterIds, $topicIds)
                ->where('question_type', $type)
                ->whereNotIn('id', $usedIds)
                ->inRandomOrder()
                ->limit($count)
                ->get();

            if ($pool->count() < $count) {
                throw ValidationException::withMessages([
                    "type_counts.{$type}" => PaperTypeHelper::label($type).' does not have enough unused questions in the selected chapters/topics.',
                ]);
            }

            $usedIds = array_merge($usedIds, $pool->pluck('id')->all());
            $selected = $selected->concat($pool);
        }

        $marksPerType = PaperTypeHelper::normalizeMarksPerType($marksPerType, $typeCounts);

        return [
            'questions' => $selected->values(),
            'grouped' => PaperTypeHelper::groupInPaperOrder($selected),
            'breakdown' => PaperTypeHelper::breakdown($typeCounts, $marksPerType),
            'total_marks' => PaperTypeHelper::totalMarks($typeCounts, $marksPerType),
            'marks_per_type' => $marksPerType,
        ];
    }

    /**
     * @param  array<string, int>  $typeCounts
     * @param  array<string, int>  $marksPerType
     * @return array{
     *     questions: Collection<int, ChapterQuestion>,
     *     grouped: Collection<string, Collection<int, ChapterQuestion>>,
     *     breakdown: array<int, array{type: string, label: string, count: int, marks: int, subtotal: int}>,
     *     total_marks: int
     * }
     */
    public function generate(int $chapterId, ?int $topicId, array $typeCounts, array $marksPerType = []): array
    {
        if ($typeCounts === []) {
            throw ValidationException::withMessages([
                'type_counts' => 'Enter at least one question quantity.',
            ]);
        }

        $content = ChapterContent::query()->where('chapter_id', $chapterId)->first();

        if (! $content) {
            throw ValidationException::withMessages([
                'chapter_id' => 'No question bank uploaded for this chapter yet. Ask admin to upload material.',
            ]);
        }

        $selected = collect();
        $available = $this->availableCounts($chapterId, $topicId);

        foreach ($typeCounts as $type => $count) {
            if (! array_key_exists($type, PaperTypeHelper::types())) {
                continue;
            }

            if ($count > ($available[$type] ?? 0)) {
                throw ValidationException::withMessages([
                    "type_counts.{$type}" => PaperTypeHelper::label($type).' has only '.($available[$type] ?? 0).' question(s) available, but you requested '.$count.'.',
                ]);
            }

            $picked = $this->poolQuery($chapterId, $topicId)
                ->where('question_type', $type)
                ->inRandomOrder()
                ->limit($count)
                ->get();

            $selected = $selected->concat($picked);
        }

        $marksPerType = PaperTypeHelper::normalizeMarksPerType($marksPerType, $typeCounts);

        return [
            'questions' => $selected->values(),
            'grouped' => PaperTypeHelper::groupInPaperOrder($selected),
            'breakdown' => PaperTypeHelper::breakdown($typeCounts, $marksPerType),
            'total_marks' => PaperTypeHelper::totalMarks($typeCounts, $marksPerType),
            'marks_per_type' => $marksPerType,
        ];
    }

    private function poolQuery(int $chapterId, ?int $topicId)
    {
        $contentId = ChapterContent::query()->where('chapter_id', $chapterId)->value('id');

        if (! $contentId) {
            return ChapterQuestion::query()->whereRaw('0 = 1');
        }

        $query = ChapterQuestion::query()
            ->where('chapter_content_id', $contentId)
            ->whereIn('question_type', array_keys(PaperTypeHelper::types()));

        if ($topicId) {
            $topic = Topic::query()->where('chapter_id', $chapterId)->find($topicId);

            if ($topic) {
                $query->where('metadata->section_title', $topic->name);
            }
        }

        return $query;
    }

    /**
     * Question pool for selected chapters; empty $topicIds = all topics.
     *
     * @param  list<int>  $chapterIds
     * @param  list<int>  $topicIds
     */
    private function poolQueryForScope(array $chapterIds, array $topicIds = [])
    {
        $chapterIds = array_values(array_unique(array_filter(array_map('intval', $chapterIds))));

        if ($chapterIds === []) {
            return ChapterQuestion::query()->whereRaw('0 = 1');
        }

        $contentIds = ChapterContent::query()->whereIn('chapter_id', $chapterIds)->pluck('id');

        if ($contentIds->isEmpty()) {
            return ChapterQuestion::query()->whereRaw('0 = 1');
        }

        $query = ChapterQuestion::query()
            ->whereIn('chapter_content_id', $contentIds)
            ->whereIn('question_type', array_keys(PaperTypeHelper::types()));

        $topicIds = array_values(array_unique(array_filter(array_map('intval', $topicIds))));

        if ($topicIds !== []) {
            $topics = Topic::query()
                ->whereIn('id', $topicIds)
                ->whereIn('chapter_id', $chapterIds)
                ->where('is_active', true)
                ->get(['id', 'chapter_id', 'name']);

            if ($topics->isEmpty()) {
                return ChapterQuestion::query()->whereRaw('0 = 1');
            }

            // Match questions by chapter content + topic section title (same as single-topic pool).
            $query->where(function ($outer) use ($topics) {
                foreach ($topics->groupBy('chapter_id') as $chapterId => $chapterTopics) {
                    $contentId = ChapterContent::query()->where('chapter_id', (int) $chapterId)->value('id');
                    if (! $contentId) {
                        continue;
                    }
                    $names = $chapterTopics->pluck('name')->unique()->filter()->values()->all();
                    if ($names === []) {
                        continue;
                    }
                    $outer->orWhere(function ($inner) use ($contentId, $names) {
                        $inner->where('chapter_content_id', $contentId)
                            ->whereIn('metadata->section_title', $names);
                    });
                }
            });
        }

        return $query;
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, ChapterQuestion>
     */
    public function questionsByIds(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $questions = ChapterQuestion::query()->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)
            ->map(fn (int $id) => $questions->get($id))
            ->filter()
            ->values();
    }

    public function resolveChapterId(?int $chapterId, ?int $topicId): int
    {
        if ($chapterId) {
            return $chapterId;
        }

        if ($topicId) {
            $chapterId = Topic::query()->whereKey($topicId)->value('chapter_id');
            if ($chapterId) {
                return (int) $chapterId;
            }
        }

        throw ValidationException::withMessages([
            'chapter_id' => 'Please select a chapter.',
        ]);
    }
}
