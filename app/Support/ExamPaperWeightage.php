<?php

namespace App\Support;

/**
 * Master exam paper question-type weightage (must total 100%).
 */
class ExamPaperWeightage
{
    /**
     * Percent of total exam marks per question type.
     *
     * @var array<string, int>
     */
    public const TYPE_PERCENTS = [
        'mcq' => 10,
        'one_word' => 10,
        'fill_blank' => 10,
        'match' => 10,
        'true_false' => 10,
        'one_mark' => 10,
        'two_marks' => 10,
        'three_marks' => 10,
        'long_answer' => 20,
    ];

    /**
     * Default marks awarded per question of each type.
     *
     * @var array<string, int>
     */
    public const MARKS_PER_QUESTION = [
        'mcq' => 1,
        'one_word' => 1,
        'fill_blank' => 1,
        'match' => 1,
        'true_false' => 1,
        'one_mark' => 1,
        'two_marks' => 2,
        'three_marks' => 3,
        'long_answer' => 4,
    ];

    /**
     * Convert total exam marks into type counts + marks-per-type using master weightage.
     *
     * @return array{
     *     type_counts: array<string, int>,
     *     marks_per_type: array<string, int>,
     *     type_marks: array<string, int>,
     *     total_marks: int
     * }
     */
    public static function layoutForTotalMarks(int $totalMarks): array
    {
        $totalMarks = max(1, $totalMarks);
        $typeMarks = self::allocateMarkBuckets($totalMarks);

        $typeCounts = [];
        $marksPerType = [];

        foreach ($typeMarks as $type => $bucketMarks) {
            if ($bucketMarks <= 0) {
                continue;
            }

            $perQuestion = max(1, (int) (self::MARKS_PER_QUESTION[$type] ?? 1));

            if (($bucketMarks % $perQuestion) === 0) {
                $count = intdiv($bucketMarks, $perQuestion);
                $marksPerType[$type] = $perQuestion;
            } else {
                $count = $bucketMarks;
                $marksPerType[$type] = 1;
            }

            if ($count > 0) {
                $typeCounts[$type] = $count;
            }
        }

        $typeCounts = PaperTypeHelper::normalizeCounts($typeCounts);
        $marksPerType = PaperTypeHelper::normalizeMarksPerType($marksPerType, $typeCounts);

        return [
            'type_counts' => $typeCounts,
            'marks_per_type' => $marksPerType,
            'type_marks' => $typeMarks,
            'total_marks' => PaperTypeHelper::totalMarks($typeCounts, $marksPerType),
        ];
    }

    /**
     * @return array<string, int>
     */
    public static function allocateMarkBuckets(int $totalMarks): array
    {
        $raw = [];
        $sum = 0;
        $largestType = 'long_answer';
        $largestValue = -1;

        foreach (self::TYPE_PERCENTS as $type => $percent) {
            $value = (int) round($totalMarks * ($percent / 100));
            $raw[$type] = $value;
            $sum += $value;
            if ($value >= $largestValue) {
                $largestValue = $value;
                $largestType = $type;
            }
        }

        $diff = $totalMarks - $sum;
        $raw[$largestType] = max(0, $raw[$largestType] + $diff);

        return $raw;
    }
}
