<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChapterContent extends Model
{
    public const LANGUAGES = [
        'english' => 'English',
        'hindi' => 'Hindi',
        'gujarati' => 'Gujarati',
    ];

    protected $fillable = [
        'chapter_id',
        'uploaded_by',
        'title',
        'language',
        'extraction_method',
        'page_count',
        'overview',
        'total_questions',
        'source_filename',
        'source_path',
        'original_pdf_path',
        'original_pdf_filename',
        'raw_text',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ChapterContentSection::class)->orderBy('sort_order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ChapterQuestion::class)->orderBy('sort_order');
    }

    public function hasOriginalPdf(): bool
    {
        return filled($this->original_pdf_path)
            && \Illuminate\Support\Facades\Storage::disk('local')->exists($this->original_pdf_path);
    }

    public function languageLabel(): string
    {
        return self::LANGUAGES[$this->language] ?? ucfirst($this->language);
    }

    public function extractionLabel(): string
    {
        return match ($this->extraction_method) {
            'pdf_text' => 'PDF Text',
            'txt' => 'Text File',
            'pasted_text' => 'Pasted Text',
            'material_json' => 'Material JSON',
            default => $this->extraction_method ? ucfirst(str_replace('_', ' ', $this->extraction_method)) : '—',
        };
    }
}
