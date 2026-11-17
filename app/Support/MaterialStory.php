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
     *     paragraphs: list<string>,
     *     memory_line: string,
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
            'paragraphs' => $paragraphs,
            'memory_line' => trim((string) ($story['memory_line'] ?? '')),
            'moral' => trim((string) ($story['moral'] ?? '')),
            'rows' => $rows,
        ];
    }
}
