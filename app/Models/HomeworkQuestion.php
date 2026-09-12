<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeworkQuestion extends Model
{
    protected $fillable = [
        'homework_id',
        'chapter_question_id',
        'question_type',
        'question_text',
        'options',
        'answer',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function homework(): BelongsTo
    {
        return $this->belongsTo(Homework::class);
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
