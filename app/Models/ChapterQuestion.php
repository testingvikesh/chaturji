<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChapterQuestion extends Model
{
    protected $fillable = [
        'chapter_content_id',
        'question_type',
        'question_text',
        'options',
        'answer',
        'metadata',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'metadata' => 'array',
    ];

    public function chapterContent(): BelongsTo
    {
        return $this->belongsTo(ChapterContent::class);
    }

    public function typeLabel(): string
    {
        return self::labelForType($this->question_type);
    }

    public static function labelForType(string $type): string
    {
        return match ($type) {
            'preview_que' => 'Preview Question',
            'numbered' => 'Numbered Question',
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
            'one_word' => 'One Word Answer',
            'knowledge_ladder' => 'Knowledge Ladder',
            'line_to_line' => 'Line to Line',
            'textbook' => 'Textbook Exercise',
            'word_grammar' => 'Word / Grammar',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    public static function iconForType(string $type): string
    {
        return match ($type) {
            'one_word' => '🔤',
            'knowledge_ladder' => '🪜',
            'line_to_line' => '↔️',
            'mcq' => '☑️',
            'fill_blank' => '✏️',
            'true_false' => '✅',
            'match' => '🔗',
            'one_mark' => '📝',
            'two_marks' => '📝',
            'three_marks' => '📋',
            'five_marks' => '📋',
            'short_answer' => '📝',
            'long_answer' => '📋',
            'textbook' => '📖',
            default => '❓',
        };
    }

    /** Question types hidden in student/teacher material reader. */
    public const STUDENT_HIDDEN_TYPES = [
        'word_grammar',
    ];

    public const READER_HIDDEN_TYPES = self::STUDENT_HIDDEN_TYPES;

    public function isHiddenInReader(): bool
    {
        return in_array($this->question_type, self::READER_HIDDEN_TYPES, true);
    }

    public function isVisibleToStudent(): bool
    {
        return ! $this->isHiddenInReader();
    }

    public function scopeVisibleToStudent($query)
    {
        return $query->whereNotIn('question_type', self::READER_HIDDEN_TYPES);
    }

    /**
     * @param  array<string, mixed>  $groups
     * @return array<string, mixed>
     */
    public static function filterReaderGroups(array $groups): array
    {
        return array_filter(
            $groups,
            fn (string $type) => ! in_array($type, self::READER_HIDDEN_TYPES, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    /** Question types whose answers should render as point-wise lists. */
    public const POINTWISE_ANSWER_TYPES = [
        'two_marks',
        'three_marks',
        'four_marks',
        'five_marks',
        '2_marks',
        '3_marks',
        '4_marks',
        '5_marks',
    ];

    public static function usesPointwiseAnswer(?string $type): bool
    {
        $type = strtolower(trim((string) $type));

        if ($type === '') {
            return false;
        }

        if (in_array($type, self::POINTWISE_ANSWER_TYPES, true)) {
            return true;
        }

        return (bool) preg_match('/^(two|three|four|five|[2-5])[_\s-]?marks?$/', $type);
    }

    /**
     * Split descriptive answers into display points.
     *
     * @return list<string>
     */
    public static function answerPoints(mixed $answer): array
    {
        if (is_array($answer)) {
            $points = array_values(array_filter(array_map(
                fn ($item) => trim(is_scalar($item) ? (string) $item : ''),
                $answer
            )));

            return array_values(array_map([self::class, 'cleanAnswerPoint'], $points));
        }

        $text = trim((string) $answer);
        if ($text === '') {
            return [];
        }

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return self::answerPoints($decoded);
        }

        $parts = preg_split('/\r\n|\r|\n+/u', $text) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), fn ($part) => $part !== ''));

        if (count($parts) <= 1) {
            $bulletSplit = preg_split('/\s*(?:•|▪|►)\s*/u', $text) ?: [];
            $bulletSplit = array_values(array_filter(array_map('trim', $bulletSplit), fn ($part) => $part !== ''));
            if (count($bulletSplit) > 1) {
                $parts = $bulletSplit;
            }
        }

        if (count($parts) <= 1) {
            $sentenceSplit = preg_split('/(?<=[.!?۔])\s+(?=[A-ZԱ-Ֆ\p{Gujarati}\p{Devanagari}“"\'\(])/u', $text) ?: [];
            $sentenceSplit = array_values(array_filter(array_map('trim', $sentenceSplit), fn ($part) => $part !== ''));
            if (count($sentenceSplit) > 1) {
                $parts = $sentenceSplit;
            }
        }

        return array_values(array_map([self::class, 'cleanAnswerPoint'], $parts));
    }

    private static function cleanAnswerPoint(string $point): string
    {
        return trim(preg_replace('/^(?:[\-\*•▪►]+|\d+[\.\)\:])\s*/u', '', $point) ?? $point);
    }
}
