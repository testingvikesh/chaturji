<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Chapter extends Model
{
    protected $fillable = [
        'subject_id',
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)->orderBy('sort_order');
    }

    public function content(): HasOne
    {
        return $this->hasOne(ChapterContent::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function preferredMaterial(): ?Material
    {
        if ($this->relationLoaded('materials')) {
            return $this->materials
                ->sortBy([
                    fn (Material $m) => match ($m->status) {
                        'complete' => 0,
                        'partial' => 1,
                        default => 2,
                    },
                    fn (Material $m) => -1 * (int) $m->topics_done,
                    fn (Material $m) => -1 * (int) $m->id,
                ])
                ->first();
        }

        return $this->materials()->preferred()->first();
    }
}
