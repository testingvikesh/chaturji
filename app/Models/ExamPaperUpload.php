<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamPaperUpload extends Model
{
    protected $fillable = [
        'teacher_id',
        'standard_id',
        'subject_name',
        'title',
        'exam_date',
        'total_marks',
        'paper_path',
        'paper_name',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'total_marks' => 'integer',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(Standard::class);
    }

    public function sheets(): HasMany
    {
        return $this->hasMany(ExamAnswerSheet::class);
    }
}
