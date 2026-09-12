<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingLog extends Model
{
    public const STATUS_REMAINING = 'remaining';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PARTIAL = 'partial';

    protected $fillable = [
        'teacher_id',
        'teaching_date',
        'standard',
        'subject_id',
        'chapter_id',
        'topic_id',
        'status',
        'homework_id',
        'target_marks',
        'notes',
        'concept_covered',
        'homework_given',
        'material_shared',
        'self_test_given',
        'period_label',
        'section',
    ];

    protected $casts = [
        'teaching_date' => 'date',
        'target_marks' => 'integer',
        'homework_given' => 'boolean',
        'material_shared' => 'boolean',
        'self_test_given' => 'boolean',
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

    public function homework(): BelongsTo
    {
        return $this->belongsTo(Homework::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPartial(): bool
    {
        return $this->status === self::STATUS_PARTIAL;
    }

    public function isRemaining(): bool
    {
        return $this->status === self::STATUS_REMAINING;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_PARTIAL => 'Partially Completed',
            default => 'Not Completed',
        };
    }

    public function hasHomework(): bool
    {
        return $this->homework_id !== null;
    }
}
