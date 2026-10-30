<?php

namespace App\Models;

use App\Support\ExamPaperWeightage;
use App\Support\PaperTypeHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class GeneratedPaper extends Model
{
    protected $fillable = [
        'user_id',
        'standard_id',
        'subject_id',
        'medium',
        'title',
        'instructions',
        'duration_minutes',
        'chapter_label',
        'chapter_ids',
        'type_counts',
        'questions',
        'total_marks',
        'total_questions',
    ];

    protected $casts = [
        'chapter_ids' => 'array',
        'type_counts' => 'array',
        'questions' => 'array',
        'duration_minutes' => 'integer',
        'total_marks' => 'integer',
        'total_questions' => 'integer',
    ];

    public function standard(): BelongsTo
    {
        return $this->belongsTo(Standard::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function mediumLabel(): string
    {
        $medium = Material::normalizeMedium($this->medium) ?: (string) $this->medium;

        return Standard::MEDIUMS[$medium] ?? ucfirst($medium);
    }

    /**
     * @return array<string, int>
     */
    public function marksPerType(): array
    {
        $marks = [];
        foreach ((array) $this->type_counts as $type => $count) {
            $marks[$type] = (int) (ExamPaperWeightage::MARKS_PER_QUESTION[$type] ?? 1);
        }

        return $marks;
    }

    public function breakdown(): array
    {
        return PaperTypeHelper::breakdown((array) $this->type_counts, $this->marksPerType());
    }

    /**
     * @return Collection<string, Collection<int, ChapterQuestion>>
     */
    public function groupedQuestions(): Collection
    {
        $questions = collect($this->questions ?? [])->map(function ($row) {
            $row = is_array($row) ? $row : [];
            $question = new ChapterQuestion([
                'question_type' => (string) ($row['question_type'] ?? 'one_word'),
                'question_text' => (string) ($row['question_text'] ?? ''),
                'options' => $row['options'] ?? null,
                'answer' => is_array($row['answer'] ?? null)
                    ? json_encode($row['answer'], JSON_UNESCAPED_UNICODE)
                    : ($row['answer'] ?? null),
            ]);
            $question->setAttribute('marks', (int) ($row['marks'] ?? 1));

            return $question;
        });

        return PaperTypeHelper::groupInPaperOrder($questions);
    }
}
