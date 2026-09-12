<?php

namespace App\Support;

class SubjectiveQuestionTypes
{
    /** @var list<string> */
    public const TYPES = [
        'one_mark',
        'two_marks',
        'three_marks',
        'five_marks',
        'short_answer',
        'long_answer',
    ];

    public const MERGED_KEY = 'written_answers';

    public const MERGED_LABEL = 'Written Answers';

    public static function isSubjective(?string $type): bool
    {
        return in_array((string) $type, self::TYPES, true);
    }

    /**
     * @param  iterable<mixed>  $questions
     * @param  array<string, int>  $marksPerType
     * @return array{count: int, max_marks: int, numbers: list<int>}
     */
    public static function stats(iterable $questions, array $marksPerType = []): array
    {
        $count = 0;
        $maxMarks = 0;
        $numbers = [];
        $index = 0;

        foreach (PaperTypeHelper::flattenPaperOrder(collect($questions)) as $question) {
            $index++;

            if (! self::isSubjective($question->question_type ?? null)) {
                continue;
            }

            $answer = ObjectiveQuestionResolver::answer($question);
            if (blank($answer)) {
                continue;
            }

            $count++;
            $numbers[] = $index;
            $maxMarks += (int) ($marksPerType[$question->question_type] ?? $question->marks ?? 1);
        }

        return [
            'count' => $count,
            'max_marks' => $maxMarks,
            'numbers' => $numbers,
        ];
    }
}
