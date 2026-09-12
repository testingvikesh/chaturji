<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChapterContentSection extends Model
{
    protected $fillable = [
        'chapter_content_id',
        'section_type',
        'title',
        'content',
        'sort_order',
    ];

    public function chapterContent(): BelongsTo
    {
        return $this->belongsTo(ChapterContent::class);
    }

    public function typeLabel(): string
    {
        return match ($this->section_type) {
            'overview' => 'Overview',
            'objective' => 'Learning Objectives',
            'gun' => 'ગુણ (Values)',
            'kala' => 'કળા (Skills)',
            'sankar' => 'સંસ્કાર (Character)',
            'introduction' => 'Introduction',
            'trailer' => 'Trailer',
            'importance_of_this_topic' => 'Importance of this topic',
            'what_i_like' => 'What I Like',
            'what_i_learn' => 'What I Learn',
            'knowledge_ladder' => 'Knowledge Ladder',
            'line_to_line' => 'Line to Line',
            'practice_examination' => 'Practice Examination',
            'textbook_exercises' => 'Textbook Exercises',
            'chapter_assessment' => 'Chapter Assessment',
            'paragraph' => 'Paragraph',
            'page' => 'PDF Page',
            'full_lesson' => 'સંપૂર્ણ પાઠ (Full Lesson)',
            default => ucfirst(str_replace('_', ' ', $this->section_type)),
        };
    }

    public function displayIcon(): string
    {
        return match ($this->section_type) {
            'introduction' => '💡',
            'trailer' => '🎬',
            'importance_of_this_topic' => '⭐',
            'what_i_like' => '❤️',
            'gun' => '🌟',
            'kala' => '🎨',
            'sankar' => '🪷',
            'knowledge_ladder' => '🪜',
            'line_to_line' => '↔️',
            'practice_examination' => '📝',
            'textbook_exercises' => '📖',
            'chapter_assessment' => '✅',
            default => '📄',
        };
    }

    public function displayTitle(): string
    {
        return $this->title ?: $this->typeLabel();
    }

    public function showSubtitle(): bool
    {
        $title = $this->displayTitle();
        $label = $this->typeLabel();

        return $title !== $label && ! str_starts_with($title, $label);
    }

    /** @return array<int, string> */
    public function contentPoints(): array
    {
        $content = trim($this->content ?? '');

        if ($content === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $content);
        $points = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^\d+\.\s*(.+)$/u', $line, $matches)) {
                $points[] = $matches[1];

                continue;
            }

            if ($points !== []) {
                $points[array_key_last($points)] .= ' '.$line;
            }
        }

        return $points;
    }

    public function hasPointList(): bool
    {
        return $this->contentPoints() !== [];
    }

    public function sectionAccentClass(): string
    {
        return match ($this->section_type) {
            'introduction' => 'material-section--intro',
            'trailer' => 'material-section--trailer',
            'importance_of_this_topic' => 'material-section--importance',
            'what_i_like' => 'material-section--like',
            'gun' => 'material-section--gun',
            'kala' => 'material-section--kala',
            'sankar' => 'material-section--sankar',
            'line_to_line' => 'material-section--line-to-line',
            default => 'material-section--default',
        };
    }

    public const GKS_TYPES = ['gun', 'kala', 'sankar'];

    public function isGksSection(): bool
    {
        return in_array($this->section_type, self::GKS_TYPES, true);
    }

    /** Summary-only sections — hidden in reader; questions appear in question groups below. */
    public const STUDENT_HIDDEN_TYPES = [
        'chapter_assessment',
        'practice_examination',
        'knowledge_ladder',
        'line_to_line',
        'textbook_exercises',
        'questions_summary',
    ];

    public function isSummarySection(): bool
    {
        return in_array($this->section_type, self::STUDENT_HIDDEN_TYPES, true);
    }

    public function isVisibleToStudent(): bool
    {
        return ! $this->isSummarySection();
    }

    public function scopeVisibleToStudent($query)
    {
        return $query->whereNotIn('section_type', self::STUDENT_HIDDEN_TYPES);
    }
}
