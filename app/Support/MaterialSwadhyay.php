<?php

namespace App\Support;

use App\Models\Material;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MaterialSwadhyay
{
    /**
     * Textbook સ્વાધ્યાય for this chapter (the *-swadhyay.json file), not topic drills.
     *
     * @return array{title: string, sections: list<array{title: string, type: string, questions: list<array<string, mixed>>}>, total: int}
     */
    public static function forMaterial(Material $material): array
    {
        $empty = ['title' => 'સ્વાધ્યાય', 'sections' => [], 'total' => 0];
        $filename = self::filename($material);
        if ($filename === null) {
            return $empty;
        }

        $cacheKey = 'material-swadhyay:v1:'.$material->id.':'.$filename;

        try {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $pack = self::load($filename) ?? $empty;

        try {
            Cache::put($cacheKey, $pack, $pack['total'] > 0 ? 600 : 60);
        } catch (\Throwable $e) {
            report($e);
        }

        return $pack;
    }

    public static function filename(Material $material): ?string
    {
        $name = trim((string) $material->material_json_name);
        if ($name !== '') {
            $slug = preg_replace('/-material-ai\.json$/i', '', $name) ?? $name;
            $slug = preg_replace('/\.json$/i', '', $slug) ?? $slug;
            $slug = trim($slug);

            return $slug !== '' ? $slug.'-swadhyay.json' : null;
        }

        $medium = Material::normalizeMedium($material->medium);
        $standard = preg_replace('/\D+/', '', (string) $material->standard) ?: '';
        $subject = strtolower(trim((string) $material->subject));
        $subject = preg_replace('/[^a-z0-9]+/', '', $subject) ?? '';
        $chapter = preg_replace('/\D+/', '', (string) $material->chapter_no) ?: '';

        if ($medium === null || $standard === '' || $subject === '' || $chapter === '') {
            return null;
        }

        return "{$medium}-std{$standard}-{$subject}-ch{$chapter}-chapter-{$chapter}-swadhyay.json";
    }

    /**
     * @return array{title: string, sections: list<array{title: string, type: string, questions: list<array<string, mixed>>}>, total: int}|null
     */
    private static function load(string $filename): ?array
    {
        $base = rtrim((string) config('materials.public_url', ''), '/');
        if ($base === '') {
            return null;
        }

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->get($base.'/output/'.$filename);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();
        if (! is_array($json)) {
            return null;
        }

        return self::parse($json);
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array{title: string, sections: list<array{title: string, type: string, questions: list<array<string, mixed>>}>, total: int}|null
     */
    public static function parse(array $json): ?array
    {
        $block = is_array($json['swadhyay'] ?? null) ? $json['swadhyay'] : null;
        if ($block === null || ! is_array($block['sections'] ?? null)) {
            return null;
        }

        $sections = [];
        $total = 0;

        foreach ($block['sections'] as $section) {
            if (! is_array($section)) {
                continue;
            }

            $questions = [];
            foreach ($section['questions'] ?? [] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $question = trim((string) ($item['question'] ?? ''));
                $verse = trim((string) ($item['verse'] ?? ''));
                if ($question === '' && $verse === '') {
                    continue;
                }

                $points = [];
                foreach ($item['points'] ?? [] as $point) {
                    $point = trim((string) $point);
                    if ($point !== '') {
                        $points[] = $point;
                    }
                }

                $questions[] = [
                    'no' => trim((string) ($item['question_no'] ?? '')),
                    'question' => $question,
                    'verse' => $verse,
                    'options' => self::options($item['options'] ?? null),
                    'correct' => strtoupper(trim((string) ($item['correct'] ?? ''))),
                    'answer' => trim((string) ($item['answer'] ?? '')),
                    'points' => $points,
                ];
            }

            if ($questions === []) {
                continue;
            }

            $total += count($questions);
            $sections[] = [
                'title' => trim((string) ($section['title'] ?? 'સ્વાધ્યાય')),
                'type' => (string) ($section['type'] ?? ''),
                'questions' => $questions,
            ];
        }

        if ($sections === []) {
            return null;
        }

        return [
            'title' => trim((string) ($block['title'] ?? '')) ?: 'સ્વાધ્યાય',
            'sections' => $sections,
            'total' => $total,
        ];
    }

    /**
     * @return list<array{key: string, text: string}>
     */
    private static function options(mixed $options): array
    {
        if (! is_array($options)) {
            return [];
        }

        $entries = [];
        $index = 0;
        foreach ($options as $key => $text) {
            $text = trim((string) $text);
            if ($text === '') {
                $index++;

                continue;
            }

            $letter = is_int($key) ? chr(65 + $index) : strtoupper((string) $key);
            $entries[] = ['key' => $letter, 'text' => $text];
            $index++;
        }

        return $entries;
    }
}
