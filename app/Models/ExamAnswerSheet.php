<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAnswerSheet extends Model
{
    protected $fillable = [
        'exam_paper_upload_id',
        'student_id',
        'file_path',
        'file_name',
        'marks_obtained',
        'remarks',
        'ai_result',
        'ai_error',
        'checked_at',
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'ai_result' => 'array',
        'checked_at' => 'datetime',
    ];

    public function paper(): BelongsTo
    {
        return $this->belongsTo(ExamPaperUpload::class, 'exam_paper_upload_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function isChecked(): bool
    {
        return $this->checked_at !== null;
    }
}
