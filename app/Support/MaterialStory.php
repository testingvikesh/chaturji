<?php

namespace App\Support;

class MaterialStory
{
    /**
     * @param  array<string, mixed>|null  $section
     * @return array{
     *     headline: string,
     *     meta_line: string,
     *     label: string,
     *     title: string,
     *     topic_theme: string,
     *     paragraphs: list<string>,
     *     memory_chain: string,
     *     important_facts: list<string>,
     *     life_timeline: string,
     *     memory_line: string,
     *     imagination_note: string,
     *     moral: string,
     *     rows: list<array{wh: string, question: string, answer: string}>
     * }
     */
    public static function fromSection(?array $section): array
    {
        $story = is_array($section['story'] ?? null) ? $section['story'] : [];

        $paragraphs = [];
        foreach ($story['paragraphs'] ?? [] as $paragraph) {
            $paragraph = trim((string) $paragraph);
            if ($paragraph !== '') {
                $paragraphs[] = $paragraph;
            }
        }

        $importantFacts = [];
        foreach ($story['important_facts'] ?? [] as $fact) {
            $fact = trim((string) $fact);
            if ($fact !== '') {
                $importantFacts[] = $fact;
            }
        }

        $memoryChain = is_array($story['memory_chain'] ?? null)
            ? implode(' → ', array_values(array_filter(array_map(
                static fn ($item) => trim((string) $item),
                $story['memory_chain']
            ))))
            : trim((string) ($story['memory_chain'] ?? ''));

        $rows = [];
        foreach ($story['wh_table'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $wh = trim((string) ($row['wh_label'] ?? $row['wh'] ?? ''));
            $question = trim((string) ($row['question'] ?? ''));
            $answer = trim((string) ($row['answer'] ?? ''));
            if ($wh === '' && $question === '' && $answer === '') {
                continue;
            }
            $rows[] = [
                'wh' => $wh,
                'question' => $question,
                'answer' => $answer,
            ];
        }

        $label = trim((string) ($story['title'] ?? 'વાર્તા'));

        return [
            'headline' => trim((string) ($story['headline'] ?? '')),
            'meta_line' => trim((string) ($story['meta_line'] ?? '')),
            'label' => $label !== '' ? $label : 'વાર્તા',
            'title' => trim((string) ($story['story_title'] ?? '')),
            'topic_theme' => trim((string) ($story['topic_theme'] ?? '')),
            'paragraphs' => $paragraphs,
            'memory_chain' => $memoryChain,
            'important_facts' => $importantFacts,
            'life_timeline' => trim((string) ($story['life_timeline'] ?? '')),
            'memory_line' => trim((string) ($story['memory_line'] ?? '')),
            'imagination_note' => trim((string) ($story['imagination_note'] ?? '')),
            'moral' => trim((string) ($story['moral'] ?? '')),
            'rows' => $rows,
        ];
    }
}
