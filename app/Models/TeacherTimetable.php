<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherTimetable extends Model
{
    public const WEEKDAYS = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    protected $fillable = [
        'teacher_id',
        'weekday',
        'period_id',
        'medium',
        'standard_id',
        'subject_id',
        'section',
        'is_active',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'is_active' => 'boolean',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(SchoolPeriod::class, 'period_id');
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(Standard::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function weekdayLabel(): string
    {
        return self::WEEKDAYS[$this->weekday] ?? ('Day '.$this->weekday);
    }

    public function mediumLabel(): string
    {
        $medium = Material::normalizeMedium($this->medium) ?: $this->medium;

        return Standard::MEDIUMS[$medium] ?? ucfirst((string) $medium);
    }

    public function assignmentKey(): string
    {
        $medium = Material::normalizeMedium($this->medium) ?: $this->medium;

        return 'tt|'.$this->id;
    }

    public function subjectAssignmentKey(): string
    {
        $medium = Material::normalizeMedium($this->medium) ?: $this->medium;

        return $medium.'|'.$this->standard_id.'|'.$this->subject_id;
    }

    public function optionLabel(): string
    {
        $parts = [
            $this->period?->displayLabel() ?: ('P'.$this->period_id),
            $this->mediumLabel(),
            $this->standard?->name,
            $this->subject?->name,
        ];
        if (filled($this->section)) {
            $parts[] = 'Sec '.$this->section;
        }

        return collect($parts)->filter()->implode(' · ');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForWeekday($query, int $weekday)
    {
        return $query->where('weekday', $weekday);
    }
}
