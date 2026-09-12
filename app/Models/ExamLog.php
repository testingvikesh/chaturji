<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamLog extends Model
{
    public const STATUS_REMAINING = 'remaining';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'teacher_id',
        'exam_date',
        'standard',
        'subject_id',
        'chapter_id',
        'topic_id',
        'status',
        'exam_id',
        'target_marks',
        'notes',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'target_marks' => 'integer',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isRemaining(): bool
    {
        return $this->status === self::STATUS_REMAINING;
    }

    public function hasExam(): bool
    {
        return $this->exam_id !== null;
    }
}
