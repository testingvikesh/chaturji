<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\ChapterContent;
use App\Models\ChapterContentSection;
use App\Models\ChapterQuestion;
use App\Models\Topic;
use App\Support\SlugHelper;
use App\Support\TextSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChapterContentImporter
{
    public function __construct(
        private readonly MaterialJsonImporter $materialJsonImporter
    ) {}

    /**
     * @return array{content: ChapterContent, stats: array<string, int>}
     */
    public function import(
        Chapter $chapter,
        UploadedFile $file,
        int $uploadedBy,
        string $language = 'english',
        ?string $topicName = null,
        ?UploadedFile $originalPdf = null,
    ): array {
        $storedPath = $file->storeAs(
            'chapter-uploads/'.$chapter->id,
            time().'_'.$this->safeFilename($file->getClientOriginalName()),
            'local'
        );

        $sourceFilename = $file->getClientOriginalName();
        $absolutePath = Storage::disk('local')->path($storedPath);
        $contents = file_get_contents($absolutePath);

        if ($contents === false) {
            Storage::disk('local')->delete($storedPath);
            throw new \RuntimeException('Could not read uploaded JSON file.');
        }

        $json = json_decode($contents, true);

        if (! is_array($json)) {
            Storage::disk('local')->delete($storedPath);
            throw new \RuntimeException('Invalid JSON file. Please upload a valid material JSON file.');
        }

        try {
            $parsed = $this->materialJsonImporter->parse($json, $chapter->name, $language);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($storedPath);
            throw $e;
        }

        $resolvedLanguage = $this->materialJsonImporter->mapLanguage(
            $json['meta']['language'] ?? null,
            $language
        );

        $parsed['raw_text'] = TextSanitizer::forDatabase($parsed['raw_text']) ?? '';

        return DB::transaction(function () use ($chapter, $sourceFilename, $storedPath, $parsed, $uploadedBy, $resolvedLanguage, $topicName, $originalPdf) {
            $existing = $chapter->content;
            $keepPdfPath = null;
            $keepPdfName = null;

            if ($existing) {
                if ($originalPdf) {
                    $this->deleteStoredFile($existing->original_pdf_path);
                } else {
                    $keepPdfPath = $existing->original_pdf_path;
                    $keepPdfName = $existing->original_pdf_filename;
                }

                if ($existing->source_path && Storage::disk('local')->exists($existing->source_path)) {
                    Storage::disk('local')->delete($existing->source_path);
                }
                $existing->sections()->delete();
                $existing->questions()->delete();
                $existing->delete();
            }

            $content = ChapterContent::create([
                'chapter_id' => $chapter->id,
                'uploaded_by' => $uploadedBy,
                'title' => TextSanitizer::forDatabase($parsed['title']) ?: 'Untitled Chapter',
                'language' => $resolvedLanguage,
                'extraction_method' => 'material_json',
                'page_count' => 0,
                'overview' => TextSanitizer::forDatabase($parsed['overview'] ?? null),
                'total_questions' => $parsed['total_questions'],
                'source_filename' => TextSanitizer::forDatabase($sourceFilename) ?? 'upload.json',
                'source_path' => $storedPath,
                'original_pdf_path' => $keepPdfPath,
                'original_pdf_filename' => $keepPdfName,
                'raw_text' => $parsed['raw_text'],
            ]);

            if ($originalPdf) {
                $this->storeOriginalPdf($content, $originalPdf);
            }

            foreach ($parsed['sections'] as $section) {
                ChapterContentSection::create([
                    'chapter_content_id' => $content->id,
                    ...$section,
                ]);
            }

            foreach ($parsed['questions'] as $question) {
                ChapterQuestion::create([
                    'chapter_content_id' => $content->id,
                    ...$question,
                ]);
            }

            $topicsSynced = $this->syncTopics($chapter, $parsed['topics'] ?? [], $topicName);

            $stats = [
                'sections' => count($parsed['sections']),
                'questions' => count($parsed['questions']),
                'topics' => $topicsSynced,
                'total_questions' => $parsed['total_questions'],
            ];

            return ['content' => $content->fresh(['sections', 'questions']), 'stats' => $stats];
        });
    }

    public function storeOriginalPdf(ChapterContent $content, UploadedFile $pdf): void
    {
        $this->deleteStoredFile($content->original_pdf_path);

        $path = $pdf->storeAs(
            'chapter-uploads/'.$content->chapter_id.'/pdfs',
            time().'_'.$this->safeFilename($pdf->getClientOriginalName()),
            'local'
        );

        $content->update([
            'original_pdf_path' => $path,
            'original_pdf_filename' => TextSanitizer::forDatabase($pdf->getClientOriginalName()) ?? 'original.pdf',
        ]);
    }

    public function deleteStoredFile(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    private function syncTopics(Chapter $chapter, array $parsedTopics, ?string $topicName = null): int
    {
        $topics = collect($parsedTopics)
            ->map(fn (array $topic) => [
                'name' => TextSanitizer::forDatabase($topic['name'] ?? '') ?? '',
                'sort_order' => (int) ($topic['sort_order'] ?? 0),
            ])
            ->filter(fn (array $topic) => $topic['name'] !== '')
            ->values();

        if ($topicName) {
            $sanitized = TextSanitizer::forDatabase($topicName) ?? '';
            if ($sanitized !== '' && ! $topics->contains(fn (array $topic) => $topic['name'] === $sanitized)) {
                $topics->prepend(['name' => $sanitized, 'sort_order' => 1]);
            }
        }

        if ($topics->isEmpty() && $chapter->name) {
            $topics->push([
                'name' => TextSanitizer::forDatabase($chapter->name) ?? $chapter->name,
                'sort_order' => 1,
            ]);
        }

        $synced = 0;

        foreach ($topics->values() as $index => $topic) {
            $name = $topic['name'];
            $sortOrder = $topic['sort_order'] > 0 ? $topic['sort_order'] : $index + 1;

            $existing = $chapter->topics()->where('name', $name)->first();

            if ($existing) {
                $existing->update([
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ]);
                $synced++;

                continue;
            }

            $chapter->topics()->create([
                'name' => $name,
                'slug' => SlugHelper::unique($name, fn ($slug) => Topic::where('chapter_id', $chapter->id)->where('slug', $slug)->exists()),
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]);
            $synced++;
        }

        return $synced;
    }

    private function safeFilename(string $name): string
    {
        $safe = preg_replace('/[^\p{L}\p{N}\-_.]/u', '_', $name) ?? 'upload.json';

        return trim($safe, '_') ?: 'upload.json';
    }
}
