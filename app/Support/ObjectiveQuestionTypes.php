<?php

namespace App\Support;

class ObjectiveQuestionTypes
{
  /** @var list<string> */
    public const TYPES = [
        'one_word',
        'mcq',
        'true_false',
        'fill_blank',
    ];

    public static function isObjective(?string $type): bool
    {
        return in_array((string) $type, self::TYPES, true);
    }

    /**
     * @param  iterable<mixed>  $questions
     * @param  array<string, int>  $marksPerType
     * @return array{count: int, max_marks: int}
     */
    public static function stats(iterable $questions, array $marksPerType = []): array
    {
        $count = 0;
        $maxMarks = 0;

        foreach ($questions as $question) {
            $answer = ObjectiveQuestionResolver::answer($question);

            if (! self::isObjective($question->question_type ?? null) || blank($answer)) {
                continue;
            }

            $count++;
            $maxMarks += (int) ($marksPerType[$question->question_type] ?? $question->marks ?? 1);
        }

        return [
            'count' => $count,
            'max_marks' => $maxMarks,
        ];
    }

    /**
     * @param  iterable<mixed>  $questions
     * @param  array<string, int>  $marksPerType
     */
    public static function applyMarks(iterable $questions, array $marksPerType = []): void
    {
        foreach ($questions as $question) {
            $question->marks = (int) ($marksPerType[$question->question_type] ?? 1);
        }
    }
}
