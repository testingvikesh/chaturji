<?php

namespace App\Support;

/**
 * Translate + English explain block for Sanskrit material topics.
 */
class SanskritTranslationStudy
{
    public static function isSanskrit(?string ...$names): bool
    {
        $hay = mb_strtolower(trim(implode(' ', array_filter($names))));

        return $hay !== '' && (
            str_contains($hay, 'sanskrit')
            || str_contains($hay, 'sanskrut')
            || str_contains($hay, 'samskrit')
            || str_contains($hay, 'संस्कृत')
            || str_contains($hay, 'સંસ્કૃત')
        );
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array{
     *     title: string,
     *     language: string,
     *     pairs: list<array{source: string, translation: string}>,
     *     original: list<string>,
     *     words: list<array{word: string, meaning: string}>,
     *     lines: list<array{original: string, translation: string}>
     * }|null
     */
    public static function extract(array $section): ?array
    {
        $translate = self::findBlock($section, ['translate', 'translation', 'anuvad', 'anuvād']);
        $explain = self::findBlock($section, ['explain', 'explanation', 'english_explain', 'english']);

        $pairs = self::pairsFrom($translate);
        if ($pairs === []) {
            $pairs = self::pairsFromPractice($section);
        }

        $original = self::stringList(self::child($explain, ['original', 'original_text', 'original_lines', 'text', 'lines']));
        if ($original === []) {
            $original = array_values(array_filter(array_column($pairs, 'source')));
        }

        $words = self::wordsFrom($explain);
        $lines = self::linePairsFrom($explain);
        if ($lines === []) {
            $lines = array_map(
                fn (array $pair) => ['original' => $pair['source'], 'translation' => $pair['translation']],
                array_values(array_filter($pairs, fn (array $pair) => $pair['translation'] !== ''))
            );
        }

        if ($pairs === [] && $original === [] && $words === [] && $lines === []) {
            return null;
        }

        return [
            'title' => self::label($translate, 'Translate'),
            'language' => self::label($explain, 'English'),
            'pairs' => $pairs,
            'original' => $original,
            'words' => $words,
            'lines' => $lines,
        ];
    }

    /**
     * @param  array<string, mixed>  $section
     * @param  list<string>  $keys
     */
    private static function findBlock(array $section, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $section) && $section[$key] !== null && $section[$key] !== '' && $section[$key] !== []) {
                return $section[$key];
            }
        }

        foreach (['translate_explain', 'translate_and_explain', 'translation_study', 'sanskrit'] as $wrapper) {
            $inner = $section[$wrapper] ?? null;
            if (! is_array($inner)) {
                continue;
            }
            $found = self::findBlock($inner, $keys);
            if ($found !== null && $found !== [] && $found !== '') {
                return $found;
            }
        }

        return null;
    }

    /**
     * @return list<array{source: string, translation: string}>
     */
    private static function pairsFrom(mixed $block): array
    {
        if (is_string($block) && trim($block) !== '') {
            return [['source' => trim($block), 'translation' => '']];
        }

        if (! is_array($block)) {
            return [];
        }

        $items = $block['items'] ?? $block['pairs'] ?? $block['questions'] ?? $block['lines'] ?? null;
        if (! is_array($items)) {
            $items = array_is_list($block) ? $block : [];
        }

        $pairs = [];
        foreach ($items as $item) {
            if (is_string($item) && trim($item) !== '') {
                $pairs[] = ['source' => trim($item), 'translation' => ''];
                continue;
            }
            if (! is_array($item)) {
                continue;
            }

            $source = self::firstString($item, ['source', 'original', 'text', 'question', 'line', 'sanskrit', 'gujarati']);
            $translation = self::firstString($item, ['translation', 'english', 'answer', 'meaning', 'explain']);
            if ($source === '' && $translation === '') {
                continue;
            }
            $pairs[] = ['source' => $source, 'translation' => $translation];
        }

        return $pairs;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return list<array{source: string, translation: string}>
     */
    private static function pairsFromPractice(array $section): array
    {
        $practice = $section['practice_examination'] ?? null;
        if (! is_array($practice)) {
            return [];
        }

        $blocks = is_array($practice['blocks'] ?? null) ? $practice['blocks'] : [];
        $parts = is_array($practice['parts'] ?? null) ? $practice['parts'] : $practice;
        $descriptive = is_array($blocks['descriptive'] ?? null) ? $blocks['descriptive'] : [];
        $subjective = is_array($parts['subjective'] ?? null) ? $parts['subjective'] : [];
        $items = $descriptive['translate']['questions']
            ?? $subjective['translate']['questions']
            ?? $practice['translate']['questions']
            ?? $practice['translate']['items']
            ?? [];

        return self::pairsFrom(is_array($items) ? $items : []);
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (is_string($value) && trim($value) !== '') {
            return [trim($value)];
        }
        if (! is_array($value)) {
            return [];
        }

        $lines = [];
        $items = $value['items'] ?? $value['lines'] ?? $value;
        if (! is_array($items)) {
            return [];
        }
        foreach ($items as $item) {
            if (is_string($item) && trim($item) !== '') {
                $lines[] = trim($item);
                continue;
            }
            if (is_array($item)) {
                $text = self::firstString($item, ['text', 'original', 'line', 'source', 'question']);
                if ($text !== '') {
                    $lines[] = $text;
                }
            }
        }

        return $lines;
    }

    /**
     * @return list<array{word: string, meaning: string}>
     */
    private static function wordsFrom(mixed $explain): array
    {
        $raw = self::child($explain, ['word_to_word', 'words', 'word_meanings', 'vocabulary', 'word_to_word_english']);
        if (! is_array($raw)) {
            return [];
        }
        $items = $raw['items'] ?? $raw['rows'] ?? $raw;
        if (! is_array($items)) {
            return [];
        }

        $words = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $word = self::firstString($item, ['word', 'english_word', 'source', 'term']);
            $meaning = self::firstString($item, ['meaning', 'english_meaning', 'english', 'translation']);
            if ($word === '' && $meaning === '') {
                continue;
            }
            $words[] = ['word' => $word, 'meaning' => $meaning];
        }

        return $words;
    }

    /**
     * @return list<array{original: string, translation: string}>
     */
    private static function linePairsFrom(mixed $explain): array
    {
        $raw = self::child($explain, ['line_to_line', 'lines', 'line_translation', 'line_to_line_english']);
        if (! is_array($raw)) {
            return [];
        }

        return array_map(
            fn (array $pair) => ['original' => $pair['source'], 'translation' => $pair['translation']],
            self::pairsFrom($raw)
        );
    }

    /**
     * @param  list<string>  $keys
     */
    private static function child(mixed $block, array $keys): mixed
    {
        if (! is_array($block)) {
            return null;
        }
        foreach ($keys as $key) {
            if (array_key_exists($key, $block) && $block[$key] !== null && $block[$key] !== '' && $block[$key] !== []) {
                return $block[$key];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<string>  $keys
     */
    private static function firstString(array $item, array $keys): string
    {
        foreach ($keys as $key) {
            $value = $item[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }

    private static function label(mixed $block, string $fallback): string
    {
        if (is_array($block)) {
            foreach (['title', 'label', 'heading'] as $key) {
                $value = $block[$key] ?? null;
                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }
        }

        return $fallback;
    }
}
