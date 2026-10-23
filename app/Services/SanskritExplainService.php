<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SanskritExplainService
{
    /**
     * @return array<string, mixed>|null
     */
    public static function remember(string $text): ?array
    {
        $text = trim($text);
        if ($text === '' || ! filled(config('services.openai.key'))) {
            return null;
        }

        $key = 'sanskrit-explain:v1:'.sha1($text);

        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $explain = self::generate($text);
        if ($explain === null) {
            return null;
        }

        Cache::put($key, $explain, now()->addDays(30));

        return $explain;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function generate(string $text): ?array
    {
        $response = Http::withToken((string) config('services.openai.key'))
            ->timeout(90)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-5.6-luna'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You explain a school Sanskrit passage in simple English for the student. Return only JSON with keys: words (list of {word, meaning} for each important Sanskrit word, meaning is one or two English words), lines (list of {original, english}), full_paragraph (one English paragraph), important_words (list of {word, meaning}), explanation ({meaning, main_idea, why_important, remember}), exam ({must_remember: list of short points, keywords: list of words, confusion: one sentence}), memory_trick (one sentence), revision (list of short points), recall (list of {question, answer}). Keep the original Sanskrit lines unchanged. Start every English meaning and English sentence with a capital letter.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $text,
                    ],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('Sanskrit explain failed: '.$response->status());

            return null;
        }

        $payload = json_decode((string) data_get($response->json(), 'choices.0.message.content', ''), true);

        return is_array($payload) ? $payload : null;
    }
}
