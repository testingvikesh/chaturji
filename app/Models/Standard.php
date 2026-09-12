<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Standard extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'medium',
        'sort_order',
        'is_active',
    ];

    public const MEDIUMS = [
        'english' => 'English',
        'gujarati' => 'Gujarati',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function mediumLabel(): string
    {
        return self::MEDIUMS[$this->medium] ?? ucfirst((string) $this->medium);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class)->orderBy('sort_order');
    }

    public function activeSubjects(): HasMany
    {
        return $this->subjects()->where('is_active', true);
    }

    public function scopeOrderedByNumber($query)
    {
        return $query->orderByRaw("CAST(SUBSTRING_INDEX(COALESCE(slug, name), '-', -1) AS UNSIGNED)");
    }
}
