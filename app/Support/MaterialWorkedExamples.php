<?php

namespace App\Support;

use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\Subject;
use Illuminate\Support\Collection;

class MaterialWorkedExamples
{
    /**
     * Maths / Account / Statics chapters show solved examples instead of page/topic lists.
     */
    public static function isExampleSubject(Subject|string|null $subject): bool
    {
        $name = mb_strtolower(trim(is_object($subject) ? (string) $subject->name : (string) $subject));

        if ($name === '') {
            return false;
        }

        return (bool) preg_match('/^(maths?|mathematics|accounts?|accountancy|statics?|statistics)\b/u', $name);
    }

    /**
     * @return Collection<int, array{
     *     uid: string,
     *     topic_id: int,
     *     page: string,
     *     label: string,
     *     item: array<string, mixed>
     * }>
     */
    public static function fromTopic(MaterialTopic $topic): Collection
    {
        return self::itemsFromSection(
            $topic->sectionData(),
            (int) $topic->id,
            (int) $topic->topic_order
        );
    }

    /**
     * All worked examples in a chapter (across pages), in reading order.
     *
     * @return Collection<int, array{
     *     uid: string,
     *     topic_id: int,
     *     page: string,
     *     label: string,
     *     item: array<string, mixed>
     * }>
     */
    public static function fromMaterial(Material $material): Collection
    {
        // Always fetch section_json here — nav/list queries omit it for speed.
        $topics = $material->topics()
            ->orderBy('topic_order')
            ->get([
                'id',
                'material_id',
                'topic_order',
                'title',
                'title_gu',
                'generated',
                'section_json',
            ]);

        return $topics
            ->flatMap(fn (MaterialTopic $topic) => self::fromTopic($topic))
            ->values();
    }

    public static function countForMaterial(Material $material): int
    {
        return self::fromMaterial($material)->count();
    }

    /**
     * Page numbers that actually have examples, sorted.
     *
     * @param  Collection<int, array{page?: string}>|null  $examples
     * @return Collection<int, string>
     */
    public static function pagesForMaterial(Material $material, ?Collection $examples = null): Collection
    {
        $examples ??= self::fromMaterial($material);

        return $examples
            ->map(fn ($row) => self::normalizePage($row['page'] ?? ''))
            ->filter(fn ($page) => $page !== '' && $page !== '0')
            ->unique()
            ->sortBy(fn ($page) => sprintf('%010d|%s', (int) $page, $page))
            ->values();
    }

    public static function normalizePage(?string $page): string
    {
        $page = trim((string) $page);
        if ($page === '') {
            return '';
        }

        $digits = strtr($page, [
            '૦' => '0', '૧' => '1', '૨' => '2', '૩' => '3', '૪' => '4',
            '૫' => '5', '૬' => '6', '૭' => '7', '૮' => '8', '૯' => '9',
            '०' => '0', '१' => '1', '२' => '2', '३' => '3', '४' => '4',
            '५' => '5', '६' => '6', '७' => '7', '८' => '8', '९' => '9',
        ]);

        if (preg_match('/\d+/', $digits, $match)) {
            return (string) ((int) $match[0]);
        }

        return $page;
    }

    /**
     * @param  array<string, mixed>|null  $section
     * @return Collection<int, array{uid: string, topic_id: int, page: string, label: string, item: array<string, mixed>}>
     */
    private static function itemsFromSection(?array $section, int $topicId, int $topicOrder = 0): Collection
    {
        $rawItems = $section['worked_examples']['items'] ?? null;
        if (! is_array($rawItems) || $rawItems === []) {
            return collect();
        }

        $pageFallback = self::normalizePage($section['page_no'] ?? $section['page'] ?? '');
        if ($pageFallback === '' && $topicOrder > 0) {
            $pageFallback = (string) $topicOrder;
        }

        return collect($rawItems)
            ->values()
            ->map(function ($item, int $index) use ($topicId, $pageFallback, $topicOrder) {
                if (! is_array($item)) {
                    return null;
                }

                $question = trim((string) ($item['question_text'] ?? ''));
                $hasSolution = is_array($item['solution'] ?? null) && $item['solution'] !== [];
                $answer = trim((string) ($item['final_answer'] ?? ''));

                if ($question === '' && ! $hasSolution && $answer === '') {
                    return null;
                }

                $page = $topicOrder > 0
                    ? (string) $topicOrder
                    : self::normalizePage($item['page'] ?? $item['source_page'] ?? '');
                if ($page === '') {
                    $page = $pageFallback;
                }

                $label = trim((string) ($item['question_no'] ?? ''));
                if ($label === '' || preg_match('/^unnumbered/i', $label)) {
                    $label = 'Example '.($index + 1);
                }

                $itemId = (string) ($item['id'] ?? ($index + 1));

                return [
                    'uid' => 'example-'.$topicId.'-'.$itemId,
                    'topic_id' => $topicId,
                    'page' => $page,
                    'label' => $label,
                    'item' => $item,
                ];
            })
            ->filter()
            ->values();
    }

    public static function latex(?string $text): string
    {
        $text = trim(html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return str_replace(['\\(', '\\)', '\\[', '\\]'], '', $text);
    }

    public static function looksLikeLatex(?string $text): bool
    {
        $text = (string) $text;

        return (bool) preg_match('/\\\\[a-zA-Z]+|[_^{}]|\\\\boxed|\\\\frac|\\\\text/', $text);
    }

    /**
     * Split a paragraph into short readable points.
     *
     * @return list<string>
     */
    public static function toPoints(?string $text): array
    {
        $text = trim(html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($text === '') {
            return [];
        }

        $text = preg_replace("/\r\n|\r/", "\n", $text) ?? $text;
        $text = self::readable($text);
        $chunks = preg_split('/\n+/', $text) ?: [$text];
        $points = [];

        foreach ($chunks as $chunk) {
            $chunk = trim((string) $chunk);
            if ($chunk === '') {
                continue;
            }

            $parts = preg_split('/\s*[;；।]\s*/u', $chunk) ?: [$chunk];

            foreach ($parts as $part) {
                $part = trim((string) $part);
                $part = preg_replace('/^(?:[\-\*•–]+|(?:\d+|[૧૨૩૪૫૬૭૮૯૦]+)[.)])\s*/u', '', $part) ?? $part;
                $part = trim($part, " \t\n\r\0\x0B-–•");
                if ($part === '') {
                    continue;
                }

                $sentences = preg_split('/(?<=[।?!])\s+|(?<=[^0-9૦-૯०-९])\.(?=\s+\S)/u', $part) ?: [$part];
                foreach ($sentences as $sentence) {
                    $sentence = trim((string) $sentence, " \t\n\r\0\x0B.");
                    if ($sentence === '') {
                        continue;
                    }
                    $points[] = $sentence;
                }
            }
        }

        return array_values(array_unique($points));
    }

    /**
     * Insert missing spaces so Gujarati/English example text is readable.
     */
    public static function readable(?string $text): string
    {
        $text = trim(html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($text === '' || self::looksLikeLatex($text)) {
            return $text;
        }

        $text = preg_replace('/([0-9૦-૯०-९]),\s+([0-9૦-૯०-९])/u', '$1,$2', $text) ?? $text;
        $text = preg_replace('/\s*([:=])\s*/u', ' $1 ', $text) ?? $text;
        $text = preg_replace('/\s*[–—]\s*/u', ' – ', $text) ?? $text;
        $text = preg_replace('/([\p{L}])(?=[0-9૦-૯०-९])/u', '$1 ', $text) ?? $text;
        $text = preg_replace('/([0-9૦-૯०-९])(?=\p{L})/u', '$1 ', $text) ?? $text;
        $text = preg_replace('/(?<=\S)(રૂ\.?|Rs\.?|₹)/u', ' $1', $text) ?? $text;
        $text = preg_replace('/(રૂ\.?|Rs\.?|₹)(?=\S)/u', '$1 ', $text) ?? $text;

        $markers = [
            'વિનિયોજન', 'ભાગીદારો', 'ભાગીદાર', 'નુકસાન', 'ખાતું',
            'મૂડી', 'નફાની', 'નફો', 'નફા', 'વ્યાજ', 'ગુણોત્તર', 'પ્રમાણ',
            'તૈયાર', 'કુલ', 'ભાગ',
        ];
        usort($markers, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($markers as $word) {
            $quoted = preg_quote($word, '/');
            $text = preg_replace('/(?<=\S)('.$quoted.')(?!\p{M})/u', ' $1', $text) ?? $text;
        }

        return trim(preg_replace('/[ \t]+/u', ' ', $text) ?? $text);
    }
}
