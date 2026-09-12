<?php

namespace App\Services;

use App\Support\TextSanitizer;
use RuntimeException;

class GoogleVisionService
{
    public function __construct(
        private readonly PdfPageRasterizer $pageRasterizer,
    ) {}

    public function isConfigured(): bool
    {
        return filled(config('services.google_vision.key'));
    }

    public function extractTextFromImage(string $imagePath): string
    {
        $key = config('services.google_vision.key');

        if (! filled($key)) {
            throw new RuntimeException('Google Vision API key is not configured.');
        }

        $content = base64_encode((string) file_get_contents($imagePath));

        $response = \Illuminate\Support\Facades\Http::timeout(90)->post(
            'https://vision.googleapis.com/v1/images:annotate?key='.$key,
            [
                'requests' => [[
                    'image' => ['content' => $content],
                    'features' => [['type' => 'DOCUMENT_TEXT_DETECTION']],
                    'imageContext' => ['languageHints' => ['gu', 'en']],
                ]],
            ]
        );

        if (! $response->successful()) {
            throw new RuntimeException('Google Vision request failed: '.$response->body());
        }

        return trim((string) data_get($response->json(), 'responses.0.fullTextAnnotation.text', ''));
    }

    public function extractTextFromPdf(string $pdfPath): string
    {
        $images = $this->pageRasterizer->rasterize($pdfPath);
        $textParts = [];

        try {
            foreach ($images as $imagePath) {
                $pageText = $this->extractTextFromImage($imagePath);

                if ($pageText !== '') {
                    $textParts[] = $pageText;
                }
            }
        } finally {
            foreach ($images as $imagePath) {
                @unlink($imagePath);
            }
        }

        $text = trim(implode("\n\n", $textParts));

        if ($text === '' || ! TextSanitizer::isReadableText($text)) {
            throw new RuntimeException('Google Vision could not read text from this PDF.');
        }

        return $text;
    }
}
