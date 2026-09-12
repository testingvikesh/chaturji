<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

class ObjectiveQuestionResolver
{
    public static function answer(Model $question): ?string
    {
        if (filled($question->answer ?? null)) {
            return (string) $question->answer;
        }

        if (method_exists($question, 'chapterQuestion')) {
            $question->loadMissing('chapterQuestion');

            if (filled($question->chapterQuestion?->answer ?? null)) {
                return (string) $question->chapterQuestion->answer;
            }
        }

        return null;
    }
}
