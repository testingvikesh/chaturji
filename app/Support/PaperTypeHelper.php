<?php

namespace App\Support;

use App\Models\ChapterQuestion;

class PaperTypeHelper
{
    /** Question types available for exam/homework paper builder. */
    public static function types(): array
    {
        return [
            'one_word' => 'One Word Answer',
            'mcq' => 'MCQ',
            'fill_blank' => 'Fill in the Blank',
            'true_false' => 'True / False',
            'match' => 'Match the Following',
            'one_mark' => '1 Mark Question',
            'two_marks' => '2 Marks Question',
            'three_marks' => '3 Marks Question',
            'five_marks' => '5 Marks Question',
            'short_answer' => 'Short Answer',
            'long_answer' => 'Long Answer',
        ];
    }

    public static function label(string $type): string
    {
        return ChapterQuestion::labelForType($type);
    }

    public static function icon(string $type): string
    {
        return ChapterQuestion::iconForType($type);
    }

    /**
     * @param  array<string, int|string|null>  $input
     * @return array<string, int>
     */
    public static function normalizeCounts(array $input): array
    {
        $counts = [];

        foreach (array_keys(self::types()) as $type) {
            $value = (int) ($input[$type] ?? 0);
            if ($value > 0) {
                $counts[$type] = $value;
            }
        }

        return $counts;
    }

    /**
     * @param  array<string, int|string|null>  $input
     * @return array<string, int>
     */
    public static function normalizeMarksPerType(array $input, array $typeCounts): array
    {
        $marks = [];

        foreach ($typeCounts as $type => $count) {
            $value = (int) ($input[$type] ?? 1);
            $marks[$type] = max(1, $value);
        }

        return $marks;
    }

    public static function totalQuestions(array $typeCounts): int
    {
        return (int) array_sum($typeCounts);
    }

    public static function totalMarks(array $typeCounts, array $marksPerType): int
    {
        $total = 0;

        foreach ($typeCounts as $type => $count) {
            $total += $count * ($marksPerType[$type] ?? 1);
        }

        return $total;
    }

    /**
     * @return array<int, array{type: string, label: string, count: int, marks: int, subtotal: int}>
     */
    public static function breakdown(array $typeCounts, array $marksPerType = []): array
    {
        $rows = [];

        foreach (self::types() as $type => $label) {
            $count = (int) ($typeCounts[$type] ?? 0);
            if ($count <= 0) {
                continue;
            }

            $marks = (int) ($marksPerType[$type] ?? 1);
            $rows[] = [
                'type' => $type,
                'label' => $label,
                'count' => $count,
                'marks' => $marks,
                'subtotal' => $count * $marks,
            ];
        }

        return $rows;
    }

    /**
     * Group questions in standard paper section order.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, mixed>>
     */
    public static function groupInPaperOrder($questions): \Illuminate\Support\Collection
    {
        $grouped = $questions->groupBy('question_type');

        return collect(self::types())
            ->keys()
            ->filter(fn (string $type) => $grouped->has($type))
            ->mapWithKeys(fn (string $type) => [$type => $grouped->get($type)]);
    }

    /**
     * Group questions for student paper display (merges short + long answers).
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, mixed>>
     */
    public static function groupedForStudentDisplay($questions): \Illuminate\Support\Collection
    {
        $grouped = self::groupInPaperOrder($questions);
        $display = collect();

        foreach (array_keys(self::types()) as $type) {
            if ($grouped->has($type)) {
                $display->put($type, $grouped->get($type));
            }
        }

        return $display;
    }

    public static function sectionLabel(string $type): string
    {
        if ($type === \App\Support\SubjectiveQuestionTypes::MERGED_KEY) {
            return \App\Support\SubjectiveQuestionTypes::MERGED_LABEL;
        }

        return self::label($type);
    }

    public static function sectionIcon(string $type): string
    {
        if ($type === \App\Support\SubjectiveQuestionTypes::MERGED_KEY) {
            return '📝';
        }

        return self::icon($type);
    }

    /**
     * Flat question list in paper display order (for continuous numbering).
     *
     * @return \Illuminate\Support\Collection<int, mixed>
     */
    public static function flattenPaperOrder($questions): \Illuminate\Support\Collection
    {
        return self::groupInPaperOrder($questions)->flatten(1)->values();
    }
}
