<?php

namespace App\Services;

class ChapterContentParser
{
    public function __construct(
        private readonly PdfTextExtractor $extractor
    ) {}

    /**
     * @return array{
     *     title: string,
     *     overview: ?string,
     *     total_questions: int,
     *     raw_text: string,
     *     sections: array<int, array{section_type: string, title: ?string, content: string, sort_order: int}>,
     *     questions: array<int, array{question_type: string, question_text: string, options: ?array, answer: ?string, metadata: ?array, sort_order: int}>
     * }
     */
    public function parseFile(string $path, ?string $fallbackTitle = null): array
    {
        $rawText = $this->extractor->extract($path);

        return $this->parseText($rawText, $fallbackTitle);
    }

    /**
     * @return array{
     *     title: string,
     *     overview: ?string,
     *     total_questions: int,
     *     raw_text: string,
     *     sections: array<int, array{section_type: string, title: ?string, content: string, sort_order: int}>,
     *     questions: array<int, array{question_type: string, question_text: string, options: ?array, answer: ?string, metadata: ?array, sort_order: int}>
     * }
     */
    public function parseText(string $rawText, ?string $fallbackTitle = null): array
    {
        $text = $this->cleanText($rawText);
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), fn ($line) => $line !== ''));

        $title = $fallbackTitle ?: ($lines[0] ?? 'Untitled Chapter');
        $sections = [];
        $questions = [];
        $sortOrder = 0;
        $questionSort = 0;

        $overview = $this->extractOverview($lines, $title);
        if ($overview) {
            $sections[] = [
                'section_type' => 'overview',
                'title' => 'Overview',
                'content' => $overview,
                'sort_order' => $sortOrder++,
            ];
        }

        foreach ($this->extractPreviewQuestions($text) as $item) {
            $questions[] = [
                'question_type' => 'preview_que',
                'question_text' => $item['question'],
                'options' => null,
                'answer' => null,
                'metadata' => ['number' => $item['number']],
                'sort_order' => $questionSort++,
            ];
        }

        $objectives = $this->extractBlockBeforeMarker($text, ['Que 1', 'Que 1 '], ['ગુણ', 'Gun', '7 Questions']);
        if ($objectives) {
            $sections[] = [
                'section_type' => 'objective',
                'title' => 'Learning Objectives',
                'content' => $objectives,
                'sort_order' => $sortOrder++,
            ];
        }

        foreach (['gun' => 'ગુણ', 'kala' => 'કળા', 'sankar' => 'સંસ્કાર'] as $type => $marker) {
            $content = $this->extractGujaratiSection($text, $marker);
            if (! $content && $type === 'sankar') {
                $content = $this->extractGujaratiSection($text, 'સંકાર');
            }
            if ($content) {
                $sections[] = [
                    'section_type' => $type,
                    'title' => $marker,
                    'content' => $content,
                    'sort_order' => $sortOrder++,
                ];
            }
        }

        $numberedSummary = $this->extractNumberedQuestionsSection($text);
        if ($numberedSummary) {
            $sections[] = [
                'section_type' => 'questions_summary',
                'title' => 'Questions Summary',
                'content' => $numberedSummary['content'],
                'sort_order' => $sortOrder++,
            ];

            foreach ($numberedSummary['questions'] as $q) {
                $questions[] = [
                    'question_type' => 'numbered',
                    'question_text' => $q,
                    'options' => null,
                    'answer' => null,
                    'metadata' => null,
                    'sort_order' => $questionSort++,
                ];
            }
        }

        $fullLessonMarker = $this->findFullLessonStart($text);
        if ($fullLessonMarker) {
            $lessonText = substr($text, $fullLessonMarker);
            $sections[] = [
                'section_type' => 'full_lesson',
                'title' => 'સંપૂર્ણ પાઠ',
                'content' => $this->truncateForSection($lessonText, 5000),
                'sort_order' => $sortOrder++,
            ];

            foreach ($this->extractMcqs($lessonText) as $mcq) {
                $questions[] = [
                    'question_type' => 'mcq',
                    'question_text' => $mcq['question'],
                    'options' => $mcq['options'],
                    'answer' => $mcq['answer'],
                    'metadata' => null,
                    'sort_order' => $questionSort++,
                ];
            }

            foreach ($this->extractFillBlanks($lessonText) as $item) {
                $questions[] = [
                    'question_type' => 'fill_blank',
                    'question_text' => $item['question'],
                    'options' => $item['options'] ?? null,
                    'answer' => $item['answer'],
                    'metadata' => null,
                    'sort_order' => $questionSort++,
                ];
            }

            foreach ($this->extractTrueFalse($lessonText) as $item) {
                $questions[] = [
                    'question_type' => 'true_false',
                    'question_text' => $item['question'],
                    'options' => ['True', 'False'],
                    'answer' => $item['answer'],
                    'metadata' => null,
                    'sort_order' => $questionSort++,
                ];
            }

            foreach ($this->extractMatchQuestions($lessonText) as $item) {
                $questions[] = [
                    'question_type' => 'match',
                    'question_text' => $item['question'],
                    'options' => $item['pairs'] ?? null,
                    'answer' => $item['answer'],
                    'metadata' => $item['metadata'] ?? null,
                    'sort_order' => $questionSort++,
                ];
            }

            foreach ($this->extractShortLongAnswers($lessonText) as $item) {
                $questions[] = [
                    'question_type' => $item['type'],
                    'question_text' => $item['question'],
                    'options' => null,
                    'answer' => $item['answer'],
                    'metadata' => null,
                    'sort_order' => $questionSort++,
                ];
            }
        }

        $totalQuestions = $this->extractTotalQuestions($text) ?: count($questions);

        return [
            'title' => $title,
            'overview' => $overview,
            'total_questions' => $totalQuestions,
            'raw_text' => $text,
            'sections' => $sections,
            'questions' => $questions,
        ];
    }

    private function cleanText(string $text): string
    {
        $text = preg_replace('/--\s*\d+\s+of\s+\d+\s*--/i', "\n", $text) ?? $text;
        $text = preg_replace('/\x{FFFD}/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function extractOverview(array $lines, string $title): ?string
    {
        $parts = [];
        $started = false;

        foreach ($lines as $line) {
            if (! $started) {
                if (strcasecmp($line, $title) === 0) {
                    $started = true;
                }
                continue;
            }

            if (preg_match('/^Que\s+\d+/i', $line)) {
                break;
            }

            if (preg_match('/^(ગુણ|કળા|સંસ્કાર|સંકાર|7 Questions|Total Questions)/u', $line)) {
                break;
            }

            $parts[] = $line;
        }

        $overview = trim(implode("\n", $parts));

        return $overview !== '' ? $overview : null;
    }

    /**
     * @return array<int, array{number: int, question: string}>
     */
    private function extractPreviewQuestions(string $text): array
    {
        $questions = [];

        if (preg_match_all('/Que\s*(\d+)\s*[\x{FFFD}\-–—]?\s*(.*?)(?=Que\s*\d+|--\s*\d+\s+of|$)/isu', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $question = preg_replace('/\s+/', ' ', trim($match[2])) ?? trim($match[2]);
                if ($question !== '') {
                    $questions[] = [
                        'number' => (int) $match[1],
                        'question' => $question,
                    ];
                }
            }
        }

        return $questions;
    }

    private function extractBlockBeforeMarker(string $text, array $startMarkers, array $endMarkers): ?string
    {
        $startPos = false;
        foreach ($startMarkers as $marker) {
            $pos = stripos($text, $marker);
            if ($pos !== false) {
                $startPos = $pos;
                break;
            }
        }

        if ($startPos === false) {
            return null;
        }

        $endPos = strlen($text);
        foreach ($endMarkers as $marker) {
            $pos = mb_stripos($text, $marker, $startPos + 1);
            if ($pos !== false && $pos < $endPos) {
                $endPos = $pos;
            }
        }

        $block = trim(substr($text, $startPos, $endPos - $startPos));
        $block = preg_replace('/^Que\s+\d+.*$/im', '', $block) ?? $block;

        return trim($block) !== '' ? trim($block) : null;
    }

    private function extractGujaratiSection(string $text, string $marker): ?string
    {
        $pattern = '/'.preg_quote($marker, '/').'\s*(.*?)(?=(ગુણ|કળા|સંસ્કાર|સંકાર|7 Questions|Total Questions|સંપૂર્ણ|$))/su';
        $altPattern = '/'.preg_quote(mb_substr($marker, 0, 2), '/').'.*?'.preg_quote(mb_substr($marker, -1), '/').'\s*(.*?)(?=(ગુણ|કળા|સંસ્કાર|સંકાર|7 Questions|Total Questions|સંપૂર્ણ|$))/su';

        if (preg_match($pattern, $text, $match)) {
            return trim($match[1]) ?: null;
        }

        if (preg_match($altPattern, $text, $match)) {
            return trim($match[1]) ?: null;
        }

        return null;
    }

    /**
     * @return array{content: string, questions: array<int, string>}|null
     */
    private function extractNumberedQuestionsSection(string $text): ?array
    {
        if (! preg_match('/(\d+)\s+Questions(.*?)(?=Total Questions|સંપૂર્ણ|$)/su', $text, $sectionMatch)) {
            return null;
        }

        $content = trim($sectionMatch[0]);
        $body = trim($sectionMatch[2]);
        $questions = [];

        if (preg_match_all('/^\s*(\d+)\s+(.+?)(?=^\s*\d+\s+|Total Questions|સંપૂર્ણ|$)/ms', $body, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $q = preg_replace('/\s+/', ' ', trim($match[2])) ?? trim($match[2]);
                if ($q !== '') {
                    $questions[] = $q;
                }
            }
        }

        return ['content' => $content, 'questions' => $questions];
    }

    private function findFullLessonStart(string $text): int|false
    {
        foreach (['સંપૂર્ણ પાઠ', 'સંપૂર્ણ', 'Total Questions'] as $marker) {
            $pos = mb_stripos($text, $marker);
            if ($pos !== false) {
                return $pos;
            }
        }

        return false;
    }

    private function extractTotalQuestions(string $text): ?int
    {
        if (preg_match('/Total Questions:\s*(\d+)/i', $text, $match)) {
            return (int) $match[1];
        }

        return null;
    }

    /**
     * @return array<int, array{question: string, options: array<int, string>, answer: string}>
     */
    private function extractMcqs(string $text): array
    {
        $mcqs = [];

        if (preg_match_all('/(.+?\?)\s*((?:[•\-\*]\s*.+\n?)+)\s*Ans:\s*(.+?)(?=\n[A-Z]|\n\d+\.|\n•|\nWhat|\nWhich|\nProduction|\nA flower|\nThe fusion|\nSeed dispersal|\nThe mature|\nAll plants|\nPollination|\nFruits|\nAsexual|\nSelf-pollination|\nMatch the following|$)/su', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $options = [];
                if (preg_match_all('/[•\-\*]\s*(.+)/u', $match[2], $optMatches)) {
                    $options = array_map('trim', $optMatches[1]);
                }

                $mcqs[] = [
                    'question' => trim($match[1]),
                    'options' => $options,
                    'answer' => trim($match[3]),
                ];
            }
        }

        return $mcqs;
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    private function extractFillBlanks(string $text): array
    {
        $items = [];

        if (preg_match_all('/(.{10,}?_{3,}.*?)\s*Ans:\s*(.+?)(?=\n[A-Z]|\nAll plants|\nPollination|\nMatch|$)/su', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $items[] = [
                    'question' => trim($match[1]),
                    'answer' => trim($match[2]),
                ];
            }
        }

        return $items;
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    private function extractTrueFalse(string $text): array
    {
        $items = [];

        if (preg_match_all('/(.{15,}?)\s*[\x{FFFD}]?(True|False)[\x{FFFD}]?/iu', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $question = trim($match[1]);
                if (str_contains(strtolower($question), 'ans:')) {
                    continue;
                }
                $items[] = [
                    'question' => $question,
                    'answer' => ucfirst(strtolower($match[2])),
                ];
            }
        }

        return $items;
    }

    /**
     * @return array<int, array{question: string, pairs: ?array, answer: string, metadata: ?array}>
     */
    private function extractMatchQuestions(string $text): array
    {
        $items = [];

        if (preg_match('/Match the following.*?(Ans:\s*.+?)(?=\nWhat is|\nExplain|\nDescribe|\nList the|\nDiscuss|$)/su', $text, $match)) {
            $block = trim($match[0]);
            $answer = trim(preg_replace('/^Ans:\s*/i', '', $match[1]) ?? $match[1]);

            $items[] = [
                'question' => 'Match the following terms with their definitions.',
                'pairs' => null,
                'answer' => $answer,
                'metadata' => ['raw_block' => $block],
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array{type: string, question: string, answer: string}>
     */
    private function extractShortLongAnswers(string $text): array
    {
        $items = [];

        if (preg_match_all('/((?:What|Explain|Describe|List|Discuss).+?)\s*Ans:\s*(.+?)(?=\n(?:What|Explain|Describe|List|Discuss)|$)/su', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $question = trim($match[1]);
                $answer = trim($match[2]);
                $type = strlen($answer) > 180 ? 'long_answer' : 'short_answer';

                $items[] = [
                    'type' => $type,
                    'question' => $question,
                    'answer' => $answer,
                ];
            }
        }

        return $items;
    }

    private function truncateForSection(string $text, int $limit): string
    {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return mb_substr($text, 0, $limit).'...';
    }
}
