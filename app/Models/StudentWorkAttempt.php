<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentWorkAttempt extends Model
{
    public const TYPE_EXAM_OBJECTIVE = 'exam_objective';

    public const TYPE_HOMEWORK_OBJECTIVE = 'homework_objective';

    public const TYPE_EXAM_SHEET = 'exam_sheet';

    public const TYPE_HOMEWORK_SHEET = 'homework_sheet';

    protected $fillable = [
        'user_id',
        'work_type',
        'paper_type',
        'paper_id',
        'answered_count',
        'correct_count',
        'total_objective',
        'earned_marks',
        'max_marks',
        'status',
        'attempted_at',
    ];

    protected $casts = [
        'answered_count' => 'integer',
        'correct_count' => 'integer',
        'total_objective' => 'integer',
        'earned_marks' => 'float',
        'max_marks' => 'float',
        'attempted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isObjective(): bool
    {
        return in_array($this->work_type, [self::TYPE_EXAM_OBJECTIVE, self::TYPE_HOMEWORK_OBJECTIVE], true);
    }

    public function label(): string
    {
        return match ($this->work_type) {
            self::TYPE_EXAM_OBJECTIVE => 'Exam · Objective',
            self::TYPE_HOMEWORK_OBJECTIVE => 'Homework · Objective',
            self::TYPE_EXAM_SHEET => 'Exam · Answer sheet',
            self::TYPE_HOMEWORK_SHEET => 'Homework · Answer sheet',
            default => ucfirst(str_replace('_', ' ', (string) $this->work_type)),
        };
    }
}
