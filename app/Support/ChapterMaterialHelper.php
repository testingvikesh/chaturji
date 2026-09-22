<?php

namespace App\Support;

use App\Models\ChapterQuestion;
use Illuminate\Support\Collection;

class ChapterMaterialHelper
{
    /** @var list<string> */
    public const QUESTION_TYPE_ORDER = [
        'preview_que',
        'knowledge_ladder',
        'line_to_line',
        'one_word',
        'mcq',
        'fill_blank',
        'true_false',
        'match',
        'one_mark',
        'two_marks',
        'three_marks',
        'five_marks',
        'short_answer',
        'long_answer',
        'textbook',
        'numbered',
    ];

    /**
     * @return array<string, \Illuminate\Support\Collection<int, mixed>>
     */
    public static function groupQuestions(Collection $questions): array
    {
        $questions = self::uniqueQuestions($questions);
        $grouped = $questions->groupBy('question_type');
        $result = [];

        foreach (self::QUESTION_TYPE_ORDER as $type) {
            if (in_array($type, ChapterQuestion::READER_HIDDEN_TYPES, true)) {
                continue;
            }

            if ($grouped->has($type)) {
                $result[$type] = $grouped->get($type);
            }
        }

        foreach ($grouped as $type => $items) {
            if (in_array($type, ChapterQuestion::READER_HIDDEN_TYPES, true)) {
                continue;
            }

            if (! isset($result[$type])) {
                $result[$type] = $items;
            }
        }

        return ChapterQuestion::filterReaderGroups($result);
    }

    /**
     * Keep first occurrence only when the same question text appears more than once.
     *
     * @param  Collection<int, mixed>  $questions
     * @return Collection<int, mixed>
     */
    public static function uniqueQuestions(Collection $questions): Collection
    {
        $seen = [];

        return $questions
            ->filter(function ($question) use (&$seen) {
                $raw = is_object($question)
                    ? (string) ($question->question_text ?? '')
                    : (string) ($question['question_text'] ?? '');

                $key = self::normalizeQuestionKey($raw);
                if ($key === '') {
                    return true;
                }

                if (isset($seen[$key])) {
                    return false;
                }

                $seen[$key] = true;

                return true;
            })
            ->values();
    }

    public static function normalizeQuestionKey(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = preg_replace('/^(?:q(?:uestion)?\s*)?\d+[\.\)\:\-\s]*/iu', '', $text) ?? $text;
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * @param  array<string, mixed>  $groups
     * @return array<string, mixed>
     */
    public static function orderQuestionGroups(array $groups): array
    {
        $ordered = [];

        foreach (self::QUESTION_TYPE_ORDER as $type) {
            if (isset($groups[$type])) {
                $ordered[$type] = $groups[$type];
            }
        }

        foreach ($groups as $type => $items) {
            if (! isset($ordered[$type])) {
                $ordered[$type] = $items;
            }
        }

        return $ordered;
    }

    /**
     * @return array{
     *     introduction: mixed,
     *     trailer: mixed,
     *     importance: mixed,
     *     body: Collection<int, mixed>
     * }
     */
    public static function partitionSections(Collection $sections): array
    {
        $visible = $sections->filter(function ($section) {
            if (is_object($section) && method_exists($section, 'isVisibleToStudent')) {
                return $section->isVisibleToStudent();
            }

            $type = is_object($section)
                ? ($section->section_type ?? null)
                : ($section['section_type'] ?? null);

            return ! in_array($type, \App\Models\ChapterContentSection::STUDENT_HIDDEN_TYPES, true);
        })->values();

        $featuredTypes = ['introduction', 'trailer', 'importance_of_this_topic'];

        return [
            'introduction' => $visible->firstWhere('section_type', 'introduction'),
            'trailer' => $visible->firstWhere('section_type', 'trailer'),
            'importance' => $visible->firstWhere('section_type', 'importance_of_this_topic'),
            'body' => $visible->reject(
                fn ($section) => in_array(
                    is_object($section) ? ($section->section_type ?? null) : ($section['section_type'] ?? null),
                    $featuredTypes,
                    true
                )
            )->values(),
        ];
    }

    /**
     * @param  array<string, mixed>  $groups
     * @return array{0: mixed, 1: array<string, mixed>}
     */
    public static function splitKnowledgeLadder(array $groups): array
    {
        return self::splitQuestionGroup($groups, 'knowledge_ladder');
    }

    /**
     * @param  array<string, mixed>  $groups
     * @return array{0: mixed, 1: array<string, mixed>}
     */
    public static function splitQuestionGroup(array $groups, string $type): array
    {
        $selected = $groups[$type] ?? null;
        $rest = $groups;
        unset($rest[$type]);

        return [$selected, $rest];
    }

    /**
     * @param  Collection<int, mixed>  $bodySections
     */
    public static function gksSections(Collection $bodySections): Collection
    {
        return $bodySections
            ->whereIn('section_type', \App\Models\ChapterContentSection::GKS_TYPES)
            ->keyBy('section_type');
    }

    public const GKS_PILL_LABEL = '32 ગુણ - 64 કળા - 16 સંસ્કાર';

    public static function gksPillLabel(Collection $gksSections): string
    {
        return self::GKS_PILL_LABEL;
    }
}
