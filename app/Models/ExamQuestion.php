<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamQuestion extends Model
{
    protected $fillable = [
        'exam_id',
        'chapter_question_id',
        'question_type',
        'question_text',
        'options',
        'answer',
        'marks',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function chapterQuestion(): BelongsTo
    {
        return $this->belongsTo(ChapterQuestion::class);
    }

    public function typeLabel(): string
    {
        return ChapterQuestion::labelForType($this->question_type);
    }
}
