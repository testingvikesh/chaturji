<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeworkSubmission extends Model
{
    protected $fillable = [
        'homework_id',
        'user_id',
        'pdf_path',
        'corrected_sheet_path',
        'extracted_text',
        'extraction_method',
        'evaluation',
        'score_awarded',
        'max_score',
        'status',
        'error_message',
        'submitted_at',
    ];

    protected $casts = [
        'evaluation' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function homework(): BelongsTo
    {
        return $this->belongsTo(Homework::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function correctedSheetUrl(): ?string
    {
        return \App\Support\PublicMedia::url($this->corrected_sheet_path);
    }

    public function uploadedSheetUrl(): ?string
    {
        return \App\Support\PublicMedia::url($this->pdf_path);
    }
}
