<?php

namespace App\Support;

class TeachingHomeworkLayout
{
    /** @return array<string, array{label: string, marks: int, group: string}> */
    public static function buckets(): array
    {
        return [
            'one_word' => ['label' => 'One Word', 'marks' => 1, 'group' => 'objective'],
            'mcq' => ['label' => 'MCQ', 'marks' => 1, 'group' => 'objective'],
            'true_false' => ['label' => 'True / False', 'marks' => 1, 'group' => 'objective'],
            'match' => ['label' => 'Match', 'marks' => 1, 'group' => 'objective'],
            'fill_blank' => ['label' => 'Fill in the Blank', 'marks' => 1, 'group' => 'objective'],
            'one_mark' => ['label' => '1 Mark', 'marks' => 1, 'group' => 'subjective'],
            'two_marks' => ['label' => '2 Marks', 'marks' => 2, 'group' => 'subjective'],
            'three_marks' => ['label' => '3 Marks', 'marks' => 3, 'group' => 'subjective'],
            'five_marks' => ['label' => '5 Marks', 'marks' => 5, 'group' => 'subjective'],
        ];
    }

    /**
     * @return array{type_counts: array<string, int>, marks_per_type: array<string, int>, total_marks: int}
     */
    public static function autoLayout(int $targetMarks): array
    {
        $targetMarks = max(1, $targetMarks);
        $objectiveMarks = (int) round($targetMarks * 0.45);
        $subjectiveMarks = $targetMarks - $objectiveMarks;

        $typeCounts = array_merge(
            self::splitEvenly($objectiveMarks, ['one_word', 'mcq', 'true_false', 'match']),
            self::splitByMarkWeights($subjectiveMarks, [
                'one_mark' => 1,
                'two_marks' => 2,
                'three_marks' => 3,
                'five_marks' => 5,
            ])
        );

        $typeCounts = PaperTypeHelper::normalizeCounts($typeCounts);
        $marksPerType = self::marksPerType();

        return [
            'type_counts' => $typeCounts,
            'marks_per_type' => PaperTypeHelper::normalizeMarksPerType($marksPerType, $typeCounts),
            'total_marks' => PaperTypeHelper::totalMarks($typeCounts, $marksPerType),
        ];
    }

    /**
     * @return array<string, int>
     */
    public static function marksPerType(): array
    {
        return collect(self::buckets())
            ->mapWithKeys(fn (array $bucket, string $type) => [$type => $bucket['marks']])
            ->all();
    }

    /**
     * @param  array<string, int|string|null>  $input
     * @return array{type_counts: array<string, int>, marks_per_type: array<string, int>, total_marks: int}
     */
    public static function fromInput(array $input, int $targetMarks): array
    {
        $typeCounts = PaperTypeHelper::normalizeCounts($input);
        $marksPerType = PaperTypeHelper::normalizeMarksPerType(self::marksPerType(), $typeCounts);

        return [
            'type_counts' => $typeCounts,
            'marks_per_type' => $marksPerType,
            'total_marks' => PaperTypeHelper::totalMarks($typeCounts, $marksPerType),
        ];
    }

    /**
     * @param  array<string, int>  $typeCounts
     * @return array<string, int>
     */
    public static function splitForTopic(array $typeCounts, int $topicIndex, int $topicCount): array
    {
        if ($topicCount <= 1) {
            return $typeCounts;
        }

        $split = [];

        foreach ($typeCounts as $type => $count) {
            $base = intdiv($count, $topicCount);
            $extra = $count % $topicCount;
            $split[$type] = $base + ($topicIndex < $extra ? 1 : 0);
        }

        return PaperTypeHelper::normalizeCounts($split);
    }

    /**
     * @param  list<string>  $types
     * @return array<string, int>
     */
    private static function splitEvenly(int $totalQuestions, array $types): array
    {
        $counts = array_fill_keys($types, 0);

        if ($totalQuestions <= 0 || $types === []) {
            return $counts;
        }

        $base = intdiv($totalQuestions, count($types));
        $extra = $totalQuestions % count($types);

        foreach ($types as $index => $type) {
            $counts[$type] = $base + ($index < $extra ? 1 : 0);
        }

        return $counts;
    }

    /**
     * @param  array<string, int>  $typeMarkWeights
     * @return array<string, int>
     */
    private static function splitByMarkWeights(int $targetMarks, array $typeMarkWeights): array
    {
        $counts = array_fill_keys(array_keys($typeMarkWeights), 0);

        if ($targetMarks <= 0) {
            return $counts;
        }

        $remaining = $targetMarks;
        $types = array_keys($typeMarkWeights);

        foreach ($types as $type) {
            $weight = $typeMarkWeights[$type];

            if ($remaining >= $weight) {
                $counts[$type] = 1;
                $remaining -= $weight;
            }
        }

        while ($remaining > 0) {
            $placed = false;

            foreach ($types as $type) {
                $weight = $typeMarkWeights[$type];

                if ($remaining >= $weight) {
                    $counts[$type]++;
                    $remaining -= $weight;
                    $placed = true;
                    break;
                }
            }

            if (! $placed) {
                break;
            }
        }

        return $counts;
    }
}
