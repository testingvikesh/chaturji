<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolPeriod extends Model
{
    protected $fillable = [
        'period_no',
        'name',
        'start_time',
        'end_time',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'period_no' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function timetables(): HasMany
    {
        return $this->hasMany(TeacherTimetable::class, 'period_id');
    }

    public function displayLabel(): string
    {
        $label = $this->name ?: ('Period '.$this->period_no);
        if ($this->start_time && $this->end_time) {
            return $label.' ('.substr((string) $this->start_time, 0, 5).'–'.substr((string) $this->end_time, 0, 5).')';
        }

        return $label;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('period_no');
    }
}
