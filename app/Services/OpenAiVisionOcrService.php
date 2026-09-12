<?php

namespace App\Services;

use App\Support\AnswerSheetParser;
use App\Support\GujaratiTextNormalizer;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiVisionOcrService
{
    public function isConfigured(): bool
    {
        return filled(config('services.openai.key'));
    }

    /**
     * @param  array<int, string>  $imagePaths
     * @param  array<int, array{number: int, expected: string}>  $answerHints
     * @return array{transcription: string, answers: array<int, string>}
     */
    public function extractSheetData(array $imagePaths, array $answerHints = []): array
    {
        $key = config('services.openai.key');

        if (! filled($key)) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        if ($imagePaths === []) {
            throw new RuntimeException('No images available for OCR.');
        }

        $hintText = $this->buildHintText($answerHints);

        $content = [
            [
                'type' => 'text',
                'text' => trim(\App\Support\AiPrompt::get('ocr_sheet').' '.$hintText),
            ],
        ];

        foreach ($imagePaths as $path) {
            $bytes = file_get_contents($path);

            if ($bytes === false) {
                continue;
            }

            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => 'data:'.$this->mimeForPath($path).';base64,'.base64_encode($bytes),
                    'detail' => 'low',
                ],
            ];
        }

        $response = Http::withToken($key)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.vision_model', 'gpt-4o-mini'),
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0,
                'messages' => [
                    ['role' => 'user', 'content' => $content],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI vision OCR failed: '.$response->body());
        }

        $rawContent = data_get($response->json(), 'choices.0.message.content', '{}');
        $payload = is_array($rawContent)
            ? $rawContent
            : json_decode((string) $rawContent, true);

        if (! is_array($payload)) {
            throw new RuntimeException('OpenAI vision returned invalid JSON.');
        }

        $transcription = GujaratiTextNormalizer::cleanOcr($this->stringValue($payload['transcription'] ?? ''));
        $answers = [];
        $items = [];

        foreach ($payload['items'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $number = (int) ($row['number'] ?? 0);
            $question = $this->stringValue($row['question'] ?? '');
            $answer = $this->stringValue($row['answer'] ?? '');
            $pageIndex = max(0, (int) ($row['page_index'] ?? $row['page'] ?? 0));
            $yPercent = (float) ($row['y_percent'] ?? $row['y'] ?? 50);
            $yPercent = max(5.0, min(95.0, $yPercent));

            if ($number > 0 && $answer !== '') {
                $answers[$number] = $answer;
                $items[$number] = [
                    'number' => $number,
                    'question' => $question,
                    'answer' => $answer,
                    'page_index' => $pageIndex,
                    'y_percent' => $yPercent,
                ];
            }
        }

        foreach ($payload['answers'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $number = (int) ($row['number'] ?? 0);
            $answer = $this->stringValue($row['answer'] ?? '');

            if ($number > 0 && $answer !== '') {
                $answers[$number] = $answers[$number] ?? $answer;
                if (! isset($items[$number])) {
                    $items[$number] = [
                        'number' => $number,
                        'question' => '',
                        'answer' => $answer,
                        'page_index' => 0,
                        'y_percent' => 50.0,
                    ];
                }
            }
        }

        if ($transcription === '' && $answers === []) {
            throw new RuntimeException('OpenAI vision could not extract answers from the sheet.');
        }

        if ($transcription === '' && $answers !== []) {
            $transcription = $this->buildTextFromAnswers($answers);
        }

        ksort($answers);
        ksort($items);

        return [
            'transcription' => $transcription,
            'answers' => $answers,
            'items' => array_values($items),
        ];
    }

    /**
     * @param  array<int, array{number: int, expected: string}>  $answerHints
     */
    private function buildHintText(array $answerHints): string
    {
        if ($answerHints === []) {
            return '';
        }

        $lines = [];

        foreach (array_slice($answerHints, 0, 16) as $hint) {
            $number = (int) ($hint['number'] ?? 0);
            $expected = trim((string) ($hint['expected'] ?? ''));
            if ($number < 1) {
                continue;
            }
            $short = $expected !== ''
                ? mb_substr(preg_replace('/\s+/u', ' ', $expected) ?? $expected, 0, 60)
                : '';
            $lines[] = $short !== '' ? "Q{$number}≈{$short}" : "Q{$number}";
        }

        if ($lines === []) {
            return '';
        }

        return 'Paper question numbers to find: '.implode('; ', $lines).'. ';
    }

    /**
     * @param  array<int, string>  $imagePaths
     * @return array<int, string>
     */
    public function extractStructuredAnswers(array $imagePaths, array $answerHints = []): array
    {
        $sheet = $this->extractSheetData($imagePaths, $answerHints);

        return AnswerSheetParser::pickBest(
            AnswerSheetParser::sanitize($sheet['answers'], $sheet['transcription']),
            AnswerSheetParser::sanitizeLenient($sheet['answers'], $sheet['transcription']),
            $sheet['transcription']
        );
    }

    /**
     * @param  array<int, string>  $imagePaths
     */
    public function extractTextFromImages(array $imagePaths): string
    {
        $key = config('services.openai.key');

        if (! filled($key)) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $content = [
            [
                'type' => 'text',
                'text' => \App\Support\AiPrompt::get('ocr_text'),
            ],
        ];

        foreach ($imagePaths as $path) {
            $bytes = file_get_contents($path);

            if ($bytes === false) {
                continue;
            }

            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => 'data:'.$this->mimeForPath($path).';base64,'.base64_encode($bytes),
                    'detail' => 'low',
                ],
            ];
        }

        $response = Http::withToken($key)
            ->timeout(55)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.vision_model', 'gpt-4o-mini'),
                'temperature' => 0,
                'messages' => [
                    ['role' => 'user', 'content' => $content],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI vision OCR failed: '.$response->body());
        }

        $text = trim((string) data_get($response->json(), 'choices.0.message.content', ''));

        if ($text === '') {
            throw new RuntimeException('OpenAI vision could not read text from the uploaded sheet.');
        }

        return $text;
    }

    /**
     * @param  array<int, string>  $answers
     */
    private function buildTextFromAnswers(array $answers): string
    {
        ksort($answers);
        $lines = [];

        foreach ($answers as $number => $answer) {
            $lines[] = "Q{$number} - {$answer}";
        }

        return implode("\n", $lines);
    }

    private function mimeForPath(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/png',
        };
    }

    private function stringValue(mixed $value): string
    {
        if (is_array($value)) {
            $lines = array_map(
                fn (mixed $line) => is_scalar($line) ? trim((string) $line) : '',
                $value
            );

            return trim(implode("\n", array_filter($lines)));
        }

        return trim((string) $value);
    }
}
