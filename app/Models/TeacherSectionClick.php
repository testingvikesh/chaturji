<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSectionClick extends Model
{
    protected $fillable = [
        'teacher_id',
        'click_date',
        'subject_id',
        'subject_name',
        'chapter_id',
        'chapter_name',
        'material_topic_id',
        'topic_name',
        'section_key',
        'section_label',
        'points',
        'topic_points',
        'clicks',
    ];

    protected $casts = [
        'click_date' => 'date',
        'points' => 'integer',
        'topic_points' => 'integer',
        'clicks' => 'integer',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
