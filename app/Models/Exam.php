<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Exam extends Model
{
    public const SELF_MARK_OPTIONS = [20, 30, 50, 70, 100];

    public const SCHOOL_TIMEZONE = 'Asia/Kolkata';

    protected $fillable = [
        'teacher_id',
        'student_id',
        'is_self_exam',
        'standard',
        'subject_id',
        'chapter_id',
        'topic_id',
        'title',
        'description',
        'instructions',
        'duration_minutes',
        'starts_at',
        'ends_at',
        'status',
        'total_marks',
        'generation_config',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'generation_config' => 'array',
        'is_self_exam' => 'boolean',
    ];

    public static function hasSelfExamColumns(): bool
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        try {
            $table = (new static)->getTable();
            $cached = Schema::hasColumn($table, 'is_self_exam')
                && Schema::hasColumn($table, 'student_id');
        } catch (\Throwable) {
            $cached = false;
        }

        return $cached;
    }

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
        return $this->hasMany(ExamQuestion::class)->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ExamSubmission::class);
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

    public function scopeTeacherPublished(Builder $query): Builder
    {
        $query->published();

        if (static::hasSelfExamColumns()) {
            $query->where('is_self_exam', false);
        }

        return $query;
    }

    public function scopeSelfExamsFor(Builder $query, int $studentId): Builder
    {
        if (! static::hasSelfExamColumns()) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('is_self_exam', true)->where('student_id', $studentId);
    }

    public function scopeExcludeSelfExams(Builder $query): Builder
    {
        if (static::hasSelfExamColumns()) {
            $query->where('is_self_exam', false);
        }

        return $query;
    }

    public function isSelfExam(): bool
    {
        if (! static::hasSelfExamColumns()) {
            return false;
        }

        return (bool) $this->is_self_exam;
    }

    public function isActive(): bool
    {
        if (! $this->isPublished()) {
            return false;
        }

        return in_array($this->schedulePhase(), ['open', 'live'], true);
    }

    public function isTeacherScheduled(): bool
    {
        return ! $this->isSelfExam() && $this->starts_at !== null;
    }

    public function effectiveEndsAt(): ?\Illuminate\Support\Carbon
    {
        if ($this->ends_at) {
            return $this->ends_at;
        }

        if ($this->starts_at && $this->duration_minutes) {
            return $this->starts_at->copy()->addMinutes((int) $this->duration_minutes);
        }

        return null;
    }

    /**
     * open: not a timed teacher exam, or started with no end time.
     * upcoming / live / ended: teacher exam with a start time.
     */
    public function schedulePhase(): string
    {
        if (! $this->isTeacherScheduled()) {
            return 'open';
        }

        $now = now();

        if ($now->lt($this->starts_at)) {
            return 'upcoming';
        }

        $end = $this->effectiveEndsAt();

        if ($end && $now->gt($end)) {
            return 'ended';
        }

        if (! $end) {
            return 'open';
        }

        return 'live';
    }

    public function localStart(): ?\Illuminate\Support\Carbon
    {
        return $this->starts_at?->copy()->timezone(self::SCHOOL_TIMEZONE);
    }

    public function localEnd(): ?\Illuminate\Support\Carbon
    {
        return $this->effectiveEndsAt()?->copy()->timezone(self::SCHOOL_TIMEZONE);
    }

    public function regeneratedAt(): ?\Illuminate\Support\Carbon
    {
        $value = $this->generation_config['regenerated_at'] ?? null;

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->timezone(self::SCHOOL_TIMEZONE);
        } catch (\Throwable) {
            return null;
        }
    }
}
