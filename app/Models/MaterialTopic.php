<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialTopic extends Model
{
    public $timestamps = false;

    protected $table = 'material_topics';

    protected $fillable = [
        'material_id',
        'topic_order',
        'topic_key',
        'title',
        'title_gu',
        'generated',
        'section_json',
        'image_url',
        'updated_at',
    ];

    protected $casts = [
        'topic_order' => 'integer',
        'generated' => 'boolean',
        'updated_at' => 'datetime',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function displayName(): string
    {
        return $this->title_gu ?: $this->title ?: ('Topic '.$this->topic_order);
    }

    public function hasContent(): bool
    {
        return $this->generated && filled($this->section_json);
    }

    public function sectionData(): ?array
    {
        if (! filled($this->section_json)) {
            return null;
        }

        $data = json_decode($this->section_json, true);

        return is_array($data) ? $data : null;
    }
}
