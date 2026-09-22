<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialQuestionEditLog extends Model
{
    protected $fillable = [
        'teacher_id',
        'material_topic_id',
        'material_id',
        'question_key',
        'question_type',
        'old_question_text',
        'new_question_text',
        'old_answer',
        'new_answer',
        'old_options',
        'new_options',
        'subject_name',
        'chapter_name',
        'topic_title',
        'medium',
    ];

    protected $casts = [
        'old_options' => 'array',
        'new_options' => 'array',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function materialTopic(): BelongsTo
    {
        return $this->belongsTo(MaterialTopic::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
