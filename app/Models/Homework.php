<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Homework extends Model
{
    protected $table = 'homeworks';

    protected $fillable = [
        'teacher_id',
        'student_id',
        'is_self_homework',
        'standard',
        'subject_id',
        'chapter_id',
        'topic_id',
        'title',
        'description',
        'due_at',
        'status',
        'generation_config',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'generation_config' => 'array',
        'is_self_homework' => 'boolean',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
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

    public function questions(): HasMany
    {
        return $this->hasMany(HomeworkQuestion::class)->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(HomeworkSubmission::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeForStandard(Builder $query, ?string $standard): Builder
    {
        return $standard ? $query->where('standard', $standard) : $query;
    }

    public function isOverdue(): bool
    {
        return $this->due_at && now()->gt($this->due_at);
    }
}
