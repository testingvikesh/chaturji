<?php

namespace App\Services;

use App\Support\TextSanitizer;

class MaterialJsonImporter
{
    /**
     * @return array{
     *     title: string,
     *     overview: ?string,
     *     total_questions: int,
     *     raw_text: string,
     *     extraction_method: string,
     *     sections: array<int, array{section_type: string, title: ?string, content: string, sort_order: int}>,
     *     questions: array<int, array{question_type: string, question_text: string, options: ?array, answer: ?string, metadata: ?array, sort_order: int}>,
     *     topics: array<int, array{name: string, sort_order: int}>
     * }
     */
    public function parse(array $json, ?string $fallbackTitle = null, string $language = 'english'): array
    {
        if (! isset($json['meta']) || ! isset($json['sections']) || ! is_array($json['sections'])) {
            throw new \InvalidArgumentException('Invalid material JSON. Expected meta and sections.');
        }

        $meta = $json['meta'];
        $title = $meta['title'] ?? $fallbackTitle ?? 'Untitled Chapter';
        $sections = [];
        $questions = [];
        $topics = [];
        $sortOrder = 0;
        $questionSort = 0;

        foreach ($json['sections'] as $materialSection) {
            $sectionOrder = (int) ($materialSection['order'] ?? ($sortOrder + 1));
            $sectionTitle = $materialSection['title_gu'] ?? $materialSection['title'] ?? $title;

            $topics[] = [
                'name' => $sectionTitle,
                'sort_order' => $sectionOrder,
            ];

            if (! empty($materialSection['introduction']['summary'])) {
                $sections[] = $this->section('introduction', 'Introduction', $materialSection['introduction']['summary'], $sortOrder++);
            }

            if (! empty($materialSection['trailer']['points'])) {
                $sections[] = $this->section('trailer', 'Trailer', $this->formatPoints($materialSection['trailer']['points']), $sortOrder++);
            }

            if (! empty($materialSection['importance_of_this_topic']['points'])) {
                $importance = $materialSection['importance_of_this_topic'];
                $sections[] = $this->section(
                    'importance_of_this_topic',
                    $importance['title'] ?? 'Importance of this topic',
                    $this->formatPoints($importance['points']),
                    $sortOrder++
                );
            }

            if (! empty($materialSection['what_i_like']['points'])) {
                $sections[] = $this->section('what_i_like', 'What I Like', $this->formatPoints($materialSection['what_i_like']['points']), $sortOrder++);
            }

            if (! empty($materialSection['what_i_learn'])) {
                foreach ($materialSection['what_i_learn']['columns'] ?? [] as $col) {
                    $key = strtolower((string) ($col['key'] ?? ''));
                    $sectionType = match ($key) {
                        'gun' => 'gun',
                        'kala' => 'kala',
                        'sanskar' => 'sankar',
                        default => 'what_i_learn',
                    };
                    $label = $col['label_gu'] ?? $col['label'] ?? ucfirst($key);
                    $content = $this->formatPoints($col['points'] ?? []);

                    if ($content !== '') {
                        $sections[] = $this->section($sectionType, $label, $content, $sortOrder++);
                    }
                }
            }

            if (! empty($materialSection['knowledge_ladder'])) {
                $kl = $materialSection['knowledge_ladder'];
                $sections[] = $this->section(
                    'knowledge_ladder',
                    'Knowledge Ladder',
                    'Total questions: '.($kl['total_questions'] ?? count($kl['items'] ?? [])),
                    $sortOrder++
                );

                foreach ($kl['items'] ?? [] as $item) {
                    $questions[] = $this->question(
                        'knowledge_ladder',
                        (string) ($item['question'] ?? ''),
                        null,
                        $item['answer'] ?? null,
                        [
                            'id' => $item['id'] ?? null,
                            'next_linked_question' => $item['next_linked_question'] ?? null,
                            'section_title' => $sectionTitle,
                        ],
                        $questionSort++
                    );
                }
            }

            if (! empty($materialSection['line_to_line']['items'])) {
                $ltl = $materialSection['line_to_line'];
                $sections[] = $this->section(
                    'line_to_line',
                    $ltl['title'] ?? 'Line to Line',
                    'Total questions: '.($ltl['total_questions'] ?? count($ltl['items'] ?? [])),
                    $sortOrder++
                );

                foreach ($ltl['items'] as $item) {
                    $questions[] = $this->question(
                        'line_to_line',
                        (string) ($item['question'] ?? ''),
                        null,
                        $item['answer'] ?? null,
                        [
                            'id' => $item['id'] ?? null,
                            'next_linked_question' => $item['next_linked_question'] ?? null,
                            'section_title' => $sectionTitle,
                        ],
                        $questionSort++
                    );
                }
            }

            if (! empty($materialSection['one_word']['items'])) {
                foreach ($materialSection['one_word']['items'] as $item) {
                    $questions[] = $this->question(
                        'one_word',
                        (string) ($item['question'] ?? ''),
                        $this->normalizeOptions($item['options'] ?? null),
                        $item['answer'] ?? null,
                        ['section_title' => $sectionTitle],
                        $questionSort++
                    );
                }
            }

            if (! empty($materialSection['practice_examination'])) {
                $pe = $materialSection['practice_examination'];
                $sections[] = $this->section(
                    'practice_examination',
                    $pe['title'] ?? 'Practice Examination',
                    'Total questions: '.($pe['total_questions'] ?? 0),
                    $sortOrder++
                );

                $questionSort = $this->importPracticeExamination($pe, $sectionTitle, $questions, $questionSort);
            }
        }

        if (! empty($json['textbook_exercises'])) {
            $tb = $json['textbook_exercises'];
            $sections[] = $this->section(
                'textbook_exercises',
                $tb['title'] ?? 'Textbook Exercises',
                count($tb['sections'] ?? []).' exercise section(s)',
                $sortOrder++
            );

            foreach ($tb['sections'] ?? [] as $exerciseSection) {
                $exTitle = $exerciseSection['title'] ?? 'Exercise';
                $exType = $exerciseSection['type'] ?? 'general';

                foreach ($exerciseSection['questions'] ?? [] as $item) {
                    $questions[] = $this->importTextbookQuestion($item, $exType, $exTitle, $questionSort++);
                }
            }
        }

        if (! empty($json['chapter_assessment'])) {
            $ca = $json['chapter_assessment'];
            $sections[] = $this->section(
                'chapter_assessment',
                $ca['title'] ?? 'Chapter Assessment',
                $this->formatChapterAssessment($ca),
                $sortOrder++
            );
        }

        $overview = null;
        foreach ($json['sections'] as $materialSection) {
            if (! empty($materialSection['introduction']['summary'])) {
                $overview = $materialSection['introduction']['summary'];
                break;
            }
        }

        $questions = $this->ensureChoiceOptions($questions);

        return [
            'title' => $title,
            'overview' => $overview,
            'total_questions' => count($questions),
            'raw_text' => json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'extraction_method' => 'material_json',
            'sections' => $sections,
            'questions' => $questions,
            'topics' => $topics,
        ];
    }

    public function mapLanguage(?string $code, string $fallback = 'english'): string
    {
        return match (strtolower((string) $code)) {
            'gu', 'gujarati' => 'gujarati',
            'hi', 'hindi' => 'hindi',
            'en', 'english' => 'english',
            default => $fallback,
        };
    }

    private function importPracticeExamination(array $pe, string $sectionTitle, array &$questions, int $questionSort): int
    {
        $parts = is_array($pe['parts'] ?? null) ? $pe['parts'] : [];
        $blocks = is_array($pe['sections'] ?? null) ? $pe['sections'] : [];
        $seen = [];

        foreach ($parts['objective']['one_word']['questions'] ?? $parts['objective']['one_word']['items'] ?? [] as $item) {
            $questionSort = $this->pushQuestion(
                $questions,
                $seen,
                'one_word',
                (string) ($item['question'] ?? ''),
                $this->normalizeOptions($item['options'] ?? null),
                $item['answer'] ?? null,
                ['source' => 'practice_examination', 'paper_type' => 'objective', 'section' => 'one_word', 'section_title' => $sectionTitle],
                $questionSort
            );
        }

        foreach ($blocks['one_word']['questions'] ?? $blocks['one_word']['items'] ?? [] as $item) {
            $questionSort = $this->pushQuestion(
                $questions,
                $seen,
                'one_word',
                (string) ($item['question'] ?? ''),
                $this->normalizeOptions($item['options'] ?? null),
                $item['answer'] ?? null,
                ['source' => 'practice_examination', 'paper_type' => 'objective', 'section' => 'one_word', 'section_title' => $sectionTitle],
                $questionSort
            );
        }

        foreach ($blocks['mcq']['questions'] ?? $parts['objective']['mcq']['questions'] ?? [] as $item) {
            $questionSort = $this->pushQuestion(
                $questions,
                $seen,
                'mcq',
                (string) ($item['question'] ?? ''),
                $this->normalizeOptions($item['options'] ?? null),
                $item['answer'] ?? null,
                ['source' => 'practice_examination', 'paper_type' => 'objective', 'section' => 'mcq', 'section_title' => $sectionTitle],
                $questionSort
            );
        }

        foreach ($blocks['fill_blanks']['questions'] ?? $parts['objective']['fill_blanks']['questions'] ?? [] as $item) {
            $questionSort = $this->pushQuestion(
                $questions,
                $seen,
                'fill_blank',
                (string) ($item['question'] ?? ''),
                $this->normalizeOptions($item['options'] ?? null),
                $item['answer'] ?? null,
                ['source' => 'practice_examination', 'paper_type' => 'objective', 'section' => 'fill_blanks', 'section_title' => $sectionTitle],
                $questionSort
            );
        }

        foreach ($blocks['true_false']['questions'] ?? $parts['objective']['true_false']['questions'] ?? [] as $item) {
            $questionSort = $this->pushQuestion(
                $questions,
                $seen,
                'true_false',
                (string) ($item['question'] ?? ''),
                ['True', 'False'],
                $item['answer'] ?? null,
                ['source' => 'practice_examination', 'paper_type' => 'objective', 'section' => 'true_false', 'section_title' => $sectionTitle],
                $questionSort
            );
        }

        foreach ($blocks['match']['questions'] ?? $parts['objective']['match']['questions'] ?? [] as $item) {
            $questionSort = $this->pushQuestion(
                $questions,
                $seen,
                'match',
                (string) ($item['question'] ?? ''),
                [
                    'column_a' => $item['column_a'] ?? [],
                    'column_b' => $item['column_b'] ?? [],
                ],
                $item['answer'] ?? null,
                ['source' => 'practice_examination', 'paper_type' => 'objective', 'section' => 'match', 'section_title' => $sectionTitle],
                $questionSort
            );
        }

        $descriptive = is_array($blocks['descriptive'] ?? null) ? $blocks['descriptive'] : [];
        $subjective = is_array($parts['subjective'] ?? null) ? $parts['subjective'] : [];

        foreach ([
            'one_mark' => 'one_mark',
            'two_marks' => 'two_marks',
            'three_marks' => 'three_marks',
            'five_marks' => 'five_marks',
        ] as $key => $type) {
            $items = $descriptive[$key]['questions'] ?? $subjective[$key]['questions'] ?? [];

            foreach ($items as $item) {
                $questionSort = $this->pushQuestion(
                    $questions,
                    $seen,
                    $type,
                    (string) ($item['question'] ?? ''),
                    null,
                    $item['answer'] ?? null,
                    [
                        'source' => 'practice_examination',
                        'paper_type' => 'subjective',
                        'section' => $key,
                        'section_title' => $sectionTitle,
                    ],
                    $questionSort
                );
            }
        }

        return $questionSort;
    }

    private function importTextbookQuestion(array $item, string $exType, string $exTitle, int $sort): array
    {
        $questionText = (string) ($item['question'] ?? $item['sentence'] ?? $item['passage'] ?? $item['word'] ?? '');
        $answer = $this->normalizeAnswer($item['answer'] ?? $item['meaning'] ?? null);

        $type = match ($exType) {
            'conversation', 'comprehension' => 'short_answer',
            'true_choice' => 'mcq',
            'word_grammar' => isset($item['sentence']) && str_contains($item['sentence'], '___') ? 'fill_blank' : 'word_grammar',
            default => 'textbook',
        };

        $options = null;
        if (isset($item['options'])) {
            $options = $this->normalizeOptions($item['options']);
        } elseif ($exType === 'true_choice' && isset($item['choices'])) {
            $options = $this->normalizeOptions($item['choices']);
        }

        return $this->question(
            $type,
            $questionText,
            $options,
            $answer,
            [
                'source' => 'textbook_exercises',
                'exercise_type' => $exType,
                'exercise_title' => $exTitle,
                'word' => $item['word'] ?? null,
                'sentence' => $item['sentence'] ?? null,
                'passage' => $item['passage'] ?? null,
            ],
            $sort
        );
    }

    /**
     * Ensure fill_blank / one_word always have 4 A/B/C/D style options.
     * Uses JSON options when present; otherwise builds from sibling answers.
     *
     * @param  array<int, array{question_type: string, question_text: string, options: ?array, answer: ?string, metadata: ?array, sort_order: int}>  $questions
     * @return array<int, array{question_type: string, question_text: string, options: ?array, answer: ?string, metadata: ?array, sort_order: int}>
     */
    public function ensureChoiceOptions(array $questions): array
    {
        $pool = [];
        foreach ($questions as $question) {
            $type = (string) ($question['question_type'] ?? '');
            if (! in_array($type, ['one_word', 'fill_blank', 'mcq'], true)) {
                continue;
            }

            $answer = trim((string) ($question['answer'] ?? ''));
            if ($answer !== '') {
                $pool[] = $answer;
            }

            foreach ($this->normalizeOptions($question['options'] ?? null) ?? [] as $option) {
                if (is_string($option) && trim($option) !== '') {
                    $pool[] = trim($option);
                }
            }
        }

        $pool = array_values(array_unique($pool));

        foreach ($questions as $index => $question) {
            $type = (string) ($question['question_type'] ?? '');
            if (! in_array($type, ['one_word', 'fill_blank'], true)) {
                continue;
            }

            $answer = trim((string) ($question['answer'] ?? ''));
            if ($answer === '') {
                continue;
            }

            $options = $this->buildFourOptions($answer, $this->normalizeOptions($question['options'] ?? null) ?? [], $pool);
            $questions[$index]['options'] = $options;
        }

        return $questions;
    }

    /**
     * @param  list<string>  $existing
     * @param  list<string>  $pool
     * @return list<string>
     */
    private function buildFourOptions(string $answer, array $existing, array $pool): array
    {
        $options = [];
        foreach ($existing as $option) {
            $text = trim((string) $option);
            if ($text !== '' && ! in_array($text, $options, true)) {
                $options[] = $text;
            }
        }

        if (! in_array($answer, $options, true)) {
            array_unshift($options, $answer);
        }

        foreach ($pool as $candidate) {
            if (count($options) >= 4) {
                break;
            }
            $candidate = trim((string) $candidate);
            if ($candidate === '' || in_array($candidate, $options, true) || mb_strlen($candidate) > 48) {
                continue;
            }
            $options[] = $candidate;
        }

        foreach (['સાચું નથી', 'બંને', 'કોઈ નહીં', 'ઉપરોક્ત બધા'] as $fallback) {
            if (count($options) >= 4) {
                break;
            }
            if (! in_array($fallback, $options, true)) {
                $options[] = $fallback;
            }
        }

        $others = array_values(array_filter($options, fn (string $option) => $option !== $answer));
        shuffle($others);
        $final = array_slice(array_merge([$answer], $others), 0, 4);
        shuffle($final);

        while (count($final) < 4) {
            $final[] = 'વિકલ્પ '.(count($final) + 1);
        }

        return array_values($final);
    }

    /**
     * @param  mixed  $options
     * @return list<string>|array<string, mixed>|null
     */
    private function normalizeOptions(mixed $options): ?array
    {
        if (! is_array($options) || $options === []) {
            return null;
        }

        // Keep match-style structures as-is
        if (isset($options['column_a']) || isset($options['column_b'])) {
            return $options;
        }

        $normalized = [];
        foreach ($options as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $text = trim((string) $value);
            if ($text === '') {
                continue;
            }

            if (is_string($key) && preg_match('/^[A-Da-d]$/', $key)) {
                $normalized[strtoupper($key)] = $text;
            } else {
                $normalized[] = $text;
            }
        }

        if ($normalized === []) {
            return null;
        }

        // Prefer a simple list for MCQ-style storage / display
        return array_values($normalized);
    }

    private function section(string $type, string $title, string $content, int $sortOrder): array
    {
        return [
            'section_type' => $type,
            'title' => TextSanitizer::forDatabase($title),
            'content' => TextSanitizer::forDatabase($content) ?? '',
            'sort_order' => $sortOrder,
        ];
    }

    private function question(string $type, string $text, ?array $options, mixed $answer, ?array $metadata, int $sortOrder): array
    {
        return [
            'question_type' => $type,
            'question_text' => TextSanitizer::forDatabase($text) ?? '',
            'options' => $this->normalizeOptions($options),
            'answer' => TextSanitizer::forDatabase($this->normalizeAnswer($answer)),
            'metadata' => $metadata,
            'sort_order' => $sortOrder,
        ];
    }

    /**
     * Avoid importing duplicate practice questions when JSON provides both
     * `parts` and flattened `sections` representations.
     *
     * @param  array<int, array<string, mixed>>  $questions
     * @param  array<string, bool>  $seen
     */
    private function pushQuestion(array &$questions, array &$seen, string $type, string $text, ?array $options, mixed $answer, ?array $metadata, int $sortOrder): int
    {
        $key = $this->questionFingerprint($type, $text);

        if (isset($seen[$key])) {
            return $sortOrder;
        }

        $questions[] = $this->question($type, $text, $options, $answer, $metadata, $sortOrder);
        $seen[$key] = true;

        return $sortOrder + 1;
    }

    private function questionFingerprint(string $type, string $text): string
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $text) ?? ''));

        return $type.'|'.$normalized;
    }

    private function normalizeAnswer(mixed $answer): ?string
    {
        if ($answer === null || $answer === '') {
            return null;
        }

        if (is_bool($answer)) {
            return $answer ? 'True' : 'False';
        }

        if (is_array($answer)) {
            $lines = [];

            foreach ($answer as $item) {
                $text = is_array($item)
                    ? $this->normalizeAnswer($item)
                    : trim((string) $item);

                if ($text !== null && $text !== '') {
                    $lines[] = $text;
                }
            }

            return $lines === [] ? null : implode("\n", $lines);
        }

        $text = trim((string) $answer);

        return $text === '' ? null : $text;
    }

    private function formatPoints(array $points): string
    {
        $lines = [];

        foreach ($points as $index => $point) {
            $text = is_array($point)
                ? $this->normalizeAnswer($point)
                : trim((string) $point);

            if ($text === null || $text === '') {
                continue;
            }

            $lines[] = ($index + 1).'. '.$text;
        }

        return implode("\n", $lines);
    }

    private function formatChapterAssessment(array $data): string
    {
        $lines = [
            'Total questions: '.($data['total_questions'] ?? 0),
            'Total marks: '.($data['total_marks'] ?? 0),
        ];

        foreach ($data['instructions'] ?? [] as $instruction) {
            $text = is_array($instruction)
                ? $this->normalizeAnswer($instruction)
                : trim((string) $instruction);

            if ($text !== null && $text !== '') {
                $lines[] = '• '.$text;
            }
        }

        if (! empty($data['note'])) {
            $lines[] = '';
            $lines[] = $this->normalizeAnswer($data['note']) ?? '';
        }

        return implode("\n", $lines);
    }
}
