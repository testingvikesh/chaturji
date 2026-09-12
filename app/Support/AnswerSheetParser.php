<?php

namespace App\Support;

class AnswerSheetParser
{
    /**
     * @return array<int, string>
     */
    public static function parse(string $text): array
    {
        return self::sanitize(self::parseRaw($text), GujaratiTextNormalizer::cleanOcr($text));
    }

    /**
     * @return array<int, string>
     */
    public static function parseRaw(string $text): array
    {
        $text = GujaratiTextNormalizer::cleanOcr($text);

        $answers = self::parseQaWithAnsLabels($text);

        if ($answers !== []) {
            return $answers;
        }

        $answers = self::parseNumberedBlocks($text);

        if ($answers !== []) {
            return $answers;
        }

        $answers = self::parseSingleLineFormat($text);

        if ($answers !== []) {
            return $answers;
        }

        return self::parseBlocks($text);
    }

    /**
     * Format used on handwritten sheets:
     * Q10. question...?
     * Ans - answer text...
     * Q11. question...?
     * Ans - answer text...
     *
     * @return array<int, string>
     */
    private static function parseQaWithAnsLabels(string $text): array
    {
        $answers = [];

        // Primary: Q{n} ... Ans - {answer} until next Q{n}
        if (preg_match_all(
            '/(?:^|\n)\s*Q\s*(\d{1,2})\s*[\.\)\:\-]?\s*[^\n]*\n\s*(?:Ans(?:wer)?|જવાબ)\s*[:\-–—]?\s*(.+?)(?=(?:\n\s*Q\s*\d{1,2}\b)|\z)/isu',
            "\n".$text,
            $pairs,
            PREG_SET_ORDER
        )) {
            foreach ($pairs as $pair) {
                $number = (int) $pair[1];
                $answer = self::normalizeAnswerText((string) $pair[2]);
                if ($number > 0 && $answer !== '' && ! self::isGarbageAnswer($answer, lenient: true)) {
                    $answers[$number] = $answer;
                }
            }
        }

        // Fallback: split by Q markers, then take Ans body (multi-line OK)
        if ($answers === [] && preg_match_all(
            '/(?:^|\n)\s*Q\s*(\d{1,2})\s*[\.\)\:\-]?\s*(.*?)(?=(?:\n\s*Q\s*\d{1,2}\s*[\.\)\:\-])|\z)/isu',
            "\n".$text,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                $number = (int) $match[1];
                $block = trim((string) ($match[2] ?? ''));

                if ($number < 1 || $block === '') {
                    continue;
                }

                if (preg_match('/(?:^|\n)\s*(?:Ans(?:wer)?|જવાબ)\s*[:\-–—]?\s*(.+)$/isu', $block, $ansMatch)) {
                    $answer = self::normalizeAnswerText((string) $ansMatch[1]);
                    if ($answer !== '' && ! self::isGarbageAnswer($answer, lenient: true)) {
                        $answers[$number] = $answer;
                    }
                }
            }
        }

        // Compact: "Ans - ..." on same or next line after Qn
        if ($answers === [] && preg_match_all(
            '/(?:^|\n)\s*Q\s*(\d{1,2})\s*[\.\)\:\-].*?(?:\n)\s*(?:Ans(?:wer)?|જવાબ)\s*[:\-–—]?\s*(.+?)(?=(?:\n\s*Q\s*\d{1,2}\b)|\z)/isu',
            "\n".$text,
            $pairs,
            PREG_SET_ORDER
        )) {
            foreach ($pairs as $pair) {
                $number = (int) $pair[1];
                $answer = self::normalizeAnswerText((string) $pair[2]);
                if ($number > 0 && $answer !== '' && ! self::isGarbageAnswer($answer, lenient: true)) {
                    $answers[$number] = $answer;
                }
            }
        }

        return $answers;
    }

    private static function normalizeAnswerText(string $answer): string
    {
        $answer = trim(preg_replace('/\s+/u', ' ', $answer) ?? $answer);
        // Drop trailing next-question leak
        $answer = preg_replace('/\s+Q\s*\d{1,2}\b.*$/iu', '', $answer) ?? $answer;

        return trim($answer);
    }

    /**
     * @param  array<int, string>  $answers
     * @return array<int, string>
     */
    public static function sanitizeLenient(array $answers, ?string $sourceText = null): array
    {
        return self::filterValidAnswers($answers, lenient: true);
    }

    /**
     * @param  array<int, string>  $answers
     * @return array<int, string>
     */
    public static function sanitize(array $answers, ?string $sourceText = null): array
    {
        return self::filterValidAnswers($answers, lenient: false);
    }

    /**
     * @return array<int, string>
     */
    public static function uploadedNumbers(string $text): array
    {
        return array_keys(self::parse($text));
    }

    public static function isGarbageAnswer(string $answer, bool $lenient = false): bool
    {
        $answer = trim($answer);

        if ($answer === '' || $answer === '—') {
            return true;
        }

        if (preg_match('/^[\-–—\.\s]+$/u', $answer)) {
            return true;
        }

        if (preg_match('/^Q\s*\d{1,2}\s*[\.\)\:\-]*\s*$/iu', $answer)) {
            return true;
        }

        // Explicit answer labels only — not the written answer body
        if (preg_match('/^(?:Ans(?:wer)?|જવાબ)\s*[:\-–—]*\s*$/iu', $answer)) {
            return true;
        }

        $maxLen = $lenient ? 800 : 600;
        if (mb_strlen($answer) > $maxLen) {
            return true;
        }

        // Question sentences usually end with ?
        if (preg_match('/[?؟]\s*$/u', $answer)) {
            return true;
        }

        // Standalone question-like prompts (not student answers)
        if (self::looksLikeQuestionPrompt($answer)) {
            return true;
        }

        if ($lenient) {
            return (preg_match_all('/\p{L}/u', $answer) ?: 0) === 0;
        }

        return (preg_match_all('/\p{L}/u', $answer) ?: 0) === 0;
    }

    public static function qualityScore(array $answers): int
    {
        if ($answers === []) {
            return 0;
        }

        $score = count($answers) * 10;

        foreach ($answers as $answer) {
            if (self::isGarbageAnswer($answer, lenient: true)) {
                $score -= 30;
            } else {
                $score += 12;
            }

            $len = mb_strlen(trim($answer));
            if ($len >= 8 && $len <= 220) {
                $score += 4;
            }
        }

        $numbers = array_keys($answers);
        sort($numbers);

        // Reward contiguous runs (1..n or 10..13), do not require starting at 1
        $contiguous = true;
        for ($i = 1; $i < count($numbers); $i++) {
            if ($numbers[$i] !== $numbers[$i - 1] + 1) {
                $contiguous = false;
                break;
            }
        }
        $score += $contiguous ? 15 : -5;

        return $score;
    }

    /**
     * @param  array<int, string>  $primary
     * @param  array<int, string>  $secondary
     * @return array<int, string>
     */
    public static function pickBest(array $primary, array $secondary, ?string $sourceText = null): array
    {
        $primary = self::sanitize($primary, $sourceText);
        $secondary = self::sanitize($secondary, $sourceText);

        if ($primary === [] && $secondary !== []) {
            return $secondary;
        }

        if ($secondary === [] && $primary !== []) {
            return $primary;
        }

        if ($primary === [] && $secondary === []) {
            return [];
        }

        $primaryScore = self::qualityScore($primary);
        $secondaryScore = self::qualityScore($secondary);

        if (count($primary) > count($secondary) && count($secondary) >= 1) {
            $smallerBonus = (count($primary) - count($secondary)) * 18;

            if (($secondaryScore + $smallerBonus) >= $primaryScore) {
                return $secondary;
            }
        }

        if (count($secondary) > count($primary) && count($primary) >= 1) {
            $smallerBonus = (count($secondary) - count($primary)) * 18;

            if (($primaryScore + $smallerBonus) >= $secondaryScore) {
                return $primary;
            }
        }

        return $primaryScore >= $secondaryScore ? $primary : $secondary;
    }

    /**
     * Handwritten formats:
     * Q1 -
     * answer line
     *
     * or:
     * 1
     * question line?
     * answer line
     *
     * @return array<int, string>
     */
    private static function parseNumberedBlocks(string $text): array
    {
        $answers = [];
        $currentNumber = null;
        $currentLines = [];

        foreach (self::lines($text) as $line) {
            if (self::isNoiseLine($line)) {
                continue;
            }

            $marker = self::parseMarkerLine($line);

            if ($marker !== null) {
                if ($currentNumber !== null) {
                    $answer = self::answerFromBlock($currentLines);

                    if ($answer !== '') {
                        $answers[$currentNumber] = $answer;
                    }
                }

                $currentNumber = $marker['number'];
                $currentLines = $marker['content'] !== null && $marker['content'] !== ''
                    ? [trim($marker['content'])]
                    : [];

                continue;
            }

            if ($currentNumber !== null) {
                $currentLines[] = $line;
            }
        }

        if ($currentNumber !== null) {
            $answer = self::answerFromBlock($currentLines);

            if ($answer !== '') {
                $answers[$currentNumber] = $answer;
            }
        }

        return $answers;
    }

    /**
     * @return array{number: int, content: ?string}|null
     */
    private static function parseMarkerLine(string $line): ?array
    {
        $line = trim($line);

        if (preg_match('/^[QO0]\s*(\d{1,2})\s*[\.\)\:\-]?\s*$/iu', $line, $matches)) {
            return ['number' => (int) $matches[1], 'content' => null];
        }

        if (preg_match('/^(\d{1,2})\s*[\.\)\:\-]\s*$/u', $line, $matches)) {
            return ['number' => (int) $matches[1], 'content' => null];
        }

        if (preg_match('/^(\d{1,2})[\.\)\:\-]?\s*$/u', $line, $matches)) {
            return ['number' => (int) $matches[1], 'content' => null];
        }

        if (preg_match('/^[QO0]\s*(\d{1,2})\s*[\.\)\:\-]+\s*(.+)$/iu', $line, $matches)) {
            return ['number' => (int) $matches[1], 'content' => trim($matches[2])];
        }

        if (preg_match('/^[QO0]\s*(\d{1,2})\s+(.+)$/iu', $line, $matches)) {
            $content = trim($matches[2]);

            if ($content === '' || preg_match('/^[\-–—]+$/u', $content)) {
                return ['number' => (int) $matches[1], 'content' => null];
            }

            return ['number' => (int) $matches[1], 'content' => $content];
        }

        if (preg_match('/^Q\s*(\d{1,2})\s+(.+)$/iu', $line, $matches)) {
            $content = trim($matches[2]);

            if ($content === '' || preg_match('/^[\-–—]+$/u', $content)) {
                return ['number' => (int) $matches[1], 'content' => null];
            }

            return ['number' => (int) $matches[1], 'content' => $content];
        }

        if (preg_match('/^(\d{1,2})[\.\)\:\-\s]+(.+)$/u', $line, $matches)) {
            return ['number' => (int) $matches[1], 'content' => trim($matches[2])];
        }

        return null;
    }

    /**
     * @param  array<int, string>  $lines
     */
    private static function answerFromBlock(array $lines): string
    {
        $lines = array_values(array_filter(array_map('trim', $lines)));

        if ($lines === []) {
            return '';
        }

        // Prefer explicit Ans: / Answer: lines (handwritten sheet format)
        $ansParts = [];
        $capturing = false;

        foreach ($lines as $line) {
            if (preg_match('/^(?:Ans(?:wer)?|જવાબ)\s*[:\-–—]?\s*(.*)$/iu', $line, $matches)) {
                $capturing = true;
                $tail = trim((string) ($matches[1] ?? ''));
                if ($tail !== '') {
                    $ansParts[] = $tail;
                }
                continue;
            }

            if ($capturing) {
                if (self::parseMarkerLine($line) !== null) {
                    break;
                }
                // Keep multi-line Gujarati answers even if long
                if (preg_match('/^(?:Ans(?:wer)?|જવાબ)\b/iu', $line)) {
                    continue;
                }
                $ansParts[] = $line;
            }
        }

        if ($ansParts !== []) {
            return trim(implode(' ', $ansParts));
        }

        if (count($lines) === 1) {
            return self::extractStudentAnswer($lines[0]);
        }

        $answerLines = array_values(array_filter(
            $lines,
            fn (string $line) => ! self::looksLikeQuestionLine($line)
        ));

        if ($answerLines !== []) {
            return trim(implode(' ', $answerLines));
        }

        return self::extractStudentAnswer((string) end($lines));
    }

    /**
     * @return array<int, string>
     */
    private static function parseSingleLineFormat(string $text): array
    {
        $answers = [];

        foreach (self::mergeNumberedLines($text) as $line) {
            $marker = self::parseMarkerLine(trim($line));

            if ($marker === null || $marker['content'] === null || trim((string) $marker['content']) === '') {
                continue;
            }

            $answers[$marker['number']] = self::extractStudentAnswer((string) $marker['content']);
        }

        return $answers;
    }

    /**
     * @return array<int, string>
     */
    private static function parseBlocks(string $text): array
    {
        $answers = [];

        if (! preg_match_all('/(?:^|\n)\s*(?:Q\s*)?(\d{1,2})[\.\)\:\-]?\s*(.+?)(?=\n\s*(?:Q\s*)?\d{1,2}[\.\)\:\-]?\s|\z)/su', $text, $matches, PREG_SET_ORDER)) {
            return [];
        }

        foreach ($matches as $match) {
            $number = (int) $match[1];
            $content = trim($match[2]);

            if ($number > 0 && $content !== '') {
                $answers[$number] = self::answerFromBlock(preg_split('/\R+/u', $content) ?: []);
            }
        }

        return $answers;
    }

    /**
     * @param  array<int, string>  $answers
     * @return array<int, string>
     */
    private static function filterValidAnswers(array $answers, bool $lenient = false): array
    {
        ksort($answers);

        $filtered = [];

        foreach ($answers as $number => $answer) {
            $answer = trim($answer);

            // Allow paper Q numbers up to 50 (subjective often starts mid-paper e.g. Q10+)
            if ($number < 1 || $number > 50 || self::isGarbageAnswer($answer, $lenient)) {
                continue;
            }

            $filtered[(int) $number] = $answer;
        }

        return $filtered;
    }

    /**
     * @return array<int, string>
     */
    private static function lines(string $text): array
    {
        return array_map('trim', preg_split('/\R+/u', $text) ?: []);
    }

    /**
     * @return array<int, string>
     */
    private static function mergeNumberedLines(string $text): array
    {
        $merged = [];
        $current = null;

        foreach (self::lines($text) as $line) {
            if ($line === '' || self::isNoiseLine($line)) {
                continue;
            }

            if (self::parseMarkerLine($line) !== null || preg_match('/^(\d{1,2})[\.\)\:\-]?\s*$/u', $line) || preg_match('/^(\d{1,2})[\.\)\:\-\s]+/u', $line)) {
                if ($current !== null) {
                    $merged[] = $current;
                }

                $current = $line;

                continue;
            }

            if ($current !== null) {
                $current .= ' '.$line;
            }
        }

        if ($current !== null) {
            $merged[] = $current;
        }

        return $merged;
    }

    private static function isNoiseLine(string $line): bool
    {
        if ($line === '') {
            return true;
        }

        if (self::isHeaderLine($line)) {
            return true;
        }

        return (bool) preg_match('/yogidham|gurukul|rajkot|યોગીધામ/iu', $line);
    }

    private static function isHeaderLine(string $line): bool
    {
        // Do not treat "Ans- ..." answer lines as headers
        if (preg_match('/^(?:Ans(?:wer)?|જવાબ)\s*[:\-–—]/iu', $line)) {
            return false;
        }

        return (bool) preg_match('/ક્રમાંક|પ્રશ્ન|question|serial/i', $line)
            && ! preg_match('/^(?:Q\s*)?\d+[\.\)\:\-]/iu', $line);
    }

    private static function looksLikeQuestionLine(string $line): bool
    {
        return self::looksLikeQuestionPrompt($line);
    }

    /**
     * Detect question prompts (not student answers).
     * Do NOT reject normal English sentences used as answers.
     */
    private static function looksLikeQuestionPrompt(string $text): bool
    {
        $text = trim($text);

        if ($text === '') {
            return false;
        }

        if (preg_match('/[?؟]\s*$/u', $text)) {
            return true;
        }

        // Typical exam prompt verbs at start
        if (preg_match('/^(what|why|how|when|where|who|which|explain|analyze|analyse|describe|discuss|define|write|state|give|list|compare)\b/iu', $text)) {
            return true;
        }

        return (bool) preg_match('/(કઈ|ક્યાં|કેમ|કોણ|શું|કયા|રીતે|શા માટે|જણાવો|લખો)/u', $text);
    }

    private static function looksLikeQuestionNotAnswer(string $text): bool
    {
        return self::looksLikeQuestionPrompt($text);
    }

    /**
     * Count circled/standalone question numbers (supports Q10..Q13 sheets).
     */
    public static function countSequentialMarkers(string $text): ?int
    {
        $text = GujaratiTextNormalizer::cleanOcr($text);
        $markers = [];

        foreach (self::lines($text) as $line) {
            if (self::isNoiseLine($line)) {
                continue;
            }

            $marker = self::parseMarkerLine($line);

            if ($marker !== null) {
                $markers[] = $marker['number'];
            } elseif (preg_match('/^(\d{1,2})[\.\)\:\-]?\s*$/u', $line, $matches)) {
                $markers[] = (int) $matches[1];
            }
        }

        if ($markers === []) {
            return null;
        }

        $markers = array_values(array_unique($markers));
        sort($markers);

        return count($markers);
    }

    /**
     * @param  array<int, string>  $answers
     * @return array<int, string>
     */
    private static function trimAtFirstInvalid(array $answers, bool $lenient = false): array
    {
        ksort($answers);

        $trimmed = [];

        foreach ($answers as $number => $answer) {
            if ($number < 1 || self::isGarbageAnswer($answer, $lenient)) {
                continue;
            }

            $trimmed[(int) $number] = trim($answer);
        }

        return $trimmed;
    }

    private static function extractStudentAnswer(string $content): string
    {
        $content = GujaratiTextNormalizer::cleanOcr($content);
        $content = preg_replace('/^Q\s*\d{1,2}\s*[\.\)\:\-]+\s*/iu', '', $content) ?? $content;
        $content = trim($content);

        foreach (['?', '؟'] as $separator) {
            if (str_contains($content, $separator)) {
                $parts = explode($separator, $content);
                $tail = trim((string) end($parts));

                if ($tail !== '' && self::looksLikeAnswer($tail, $content)) {
                    return $tail;
                }
            }
        }

        $words = preg_split('/\s+/u', $content, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($words) >= 2) {
            $tail = trim(implode(' ', array_slice($words, -min(4, max(1, (int) ceil(count($words) * 0.3))))));

            if (self::looksLikeAnswer($tail, $content)) {
                return $tail;
            }
        }

        return $content;
    }

    private static function looksLikeAnswer(string $candidate, string $fullLine): bool
    {
        $candidate = trim($candidate);

        if ($candidate === '' || $candidate === $fullLine) {
            return false;
        }

        if (mb_strlen($candidate) > 80 || preg_match('/[?؟]/u', $candidate)) {
            return false;
        }

        return true;
    }
}
