<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubjectiveAnswerGrader
{
    /** Generic phrases that must never be reused as every question's "missing points". */
    private const GENERIC_POINT_FRAGMENTS = [
        'write the complete meaning',
        'full sentences',
        'important keyword from the chapter',
        'suitable real-life example',
        'explain the answer with more detail',
        'main idea of the lesson',
        'clear, simple english',
        'connect the answer to the lesson',
        'keep handwriting neat',
        'add key points, keywords',
        'add more key points',
        'one clear example',
        'add one suitable',
        'include an important keyword',
    ];

    public function isConfigured(): bool
    {
        return filled(config('services.openai.key'));
    }

    /**
     * @return array{is_correct: bool, score_awarded: int, feedback: string, teacher_comment: string, missing_key_points: list<string>}
     */
    public function grade(
        string $questionText,
        string $correctAnswer,
        string $studentAnswer,
        int $maxScore,
        string $questionType = 'short_answer',
        string $language = 'en',
    ): array {
        $batch = $this->gradeMany([[
            'question_text' => $questionText,
            'correct_answer' => $correctAnswer,
            'student_answer' => $studentAnswer,
            'max_score' => $maxScore,
            'question_type' => $questionType,
        ]]);

        return $batch[0];
    }

    /**
     * @param  array<int, array{question_text?: string, correct_answer?: string, student_answer?: string, max_score?: int, question_type?: string}>  $items
     * @return array<int, array{is_correct: bool, score_awarded: int, feedback: string, teacher_comment: string, missing_key_points: list<string>}>
     */
    public function gradeMany(array $items): array
    {
        $results = [];
        $needAi = [];

        foreach (array_values($items) as $index => $item) {
            $studentAnswer = trim((string) ($item['student_answer'] ?? ''));
            $correctAnswer = trim((string) ($item['correct_answer'] ?? ''));
            $maxScore = max(1, (int) ($item['max_score'] ?? 1));
            $questionType = (string) ($item['question_type'] ?? 'short_answer');
            $questionText = (string) ($item['question_text'] ?? '');

            if ($studentAnswer === '' || $studentAnswer === '—') {
                $results[$index] = $this->result(
                    false,
                    0,
                    $maxScore,
                    'No answer was written for this question. Please write a clear answer next time.',
                    'Please write this answer clearly on your sheet. :)',
                    $this->pointsFromModelAnswer($questionText, $correctAnswer, false),
                );
                continue;
            }

            $needAi[$index] = [
                'question_text' => $questionText,
                'correct_answer' => $correctAnswer,
                'student_answer' => $studentAnswer,
                'max_score' => $maxScore,
                'question_type' => $questionType,
            ];
        }

        if ($needAi !== [] && $this->isConfigured()) {
            try {
                foreach (array_chunk($needAi, 6, true) as $chunk) {
                    $graded = $this->gradeBatchWithOpenAi($chunk);
                    foreach ($graded as $index => $row) {
                        $results[$index] = $row;
                    }
                }
            } catch (\Throwable $exception) {
                Log::warning('Subjective batch OpenAI grading failed: '.$exception->getMessage());
            }
        }

        foreach ($needAi as $index => $item) {
            if (isset($results[$index])) {
                continue;
            }
            try {
                if ($this->isConfigured()) {
                    $results[$index] = $this->gradeWithOpenAi(
                        $item['question_text'],
                        $item['correct_answer'],
                        $item['student_answer'],
                        $item['max_score'],
                        $item['question_type'],
                        'en'
                    );
                } else {
                    $results[$index] = $this->gradeLocally(
                        $item['question_text'],
                        $item['correct_answer'],
                        $item['student_answer'],
                        $item['max_score'],
                    );
                }
            } catch (\Throwable $exception) {
                Log::warning('Subjective OpenAI grading failed: '.$exception->getMessage());
                $results[$index] = $this->gradeLocally(
                    $item['question_text'],
                    $item['correct_answer'],
                    $item['student_answer'],
                    $item['max_score'],
                );
            }
        }

        ksort($results);
        $normalized = [];
        foreach ($results as $index => $row) {
            $item = $needAi[$index] ?? $items[$index] ?? [];
            $normalized[$index] = $this->ensureSpecificFeedback(
                $row,
                (string) ($item['question_text'] ?? ''),
                (string) ($item['correct_answer'] ?? ''),
                (string) ($item['student_answer'] ?? ''),
            );
        }

        return array_values($this->dedupeBatchPoints($normalized, $needAi));
    }

    /**
     * @param  array<int, array{question_text: string, correct_answer: string, student_answer: string, max_score: int, question_type: string}>  $chunk
     * @return array<int, array{is_correct: bool, score_awarded: int, feedback: string, teacher_comment: string, missing_key_points: list<string>}>
     */
    private function gradeBatchWithOpenAi(array $chunk): array
    {
        $payloadItems = [];
        foreach ($chunk as $index => $item) {
            $payloadItems[] = [
                'id' => $index,
                'question_type' => $item['question_type'],
                'max_score' => $item['max_score'],
                'question' => $item['question_text'],
                'model_answer' => $item['correct_answer'],
                'student_answer' => $item['student_answer'],
            ];
        }

        $response = Http::withToken((string) config('services.openai.key'))
            ->timeout(90)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.45,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->systemPrompt(batch: true),
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode([
                            'instruction' => 'Grade EACH item separately. Feedback and missing_key_points MUST be unique to that question and model_answer. Never reuse the same 4 generic tips for every question.',
                            'items' => $payloadItems,
                        ], JSON_UNESCAPED_UNICODE),
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI batch grading failed: '.$response->body());
        }

        $payload = json_decode((string) data_get($response->json(), 'choices.0.message.content', '{}'), true);
        if (! is_array($payload)) {
            throw new \RuntimeException('OpenAI batch grading returned invalid JSON.');
        }

        $out = [];
        foreach ($payload['results'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = (int) ($row['id'] ?? -1);
            if (! isset($chunk[$id])) {
                continue;
            }
            $maxScore = $chunk[$id]['max_score'];
            $score = max(0, min($maxScore, (int) ($row['score_awarded'] ?? 0)));
            $isCorrect = (bool) ($row['is_correct'] ?? ($score === $maxScore));

            $out[$id] = $this->result(
                $isCorrect,
                $score,
                $maxScore,
                (string) ($row['feedback'] ?? ''),
                (string) ($row['teacher_comment'] ?? ($row['feedback'] ?? '')),
                is_array($row['missing_key_points'] ?? null) ? $row['missing_key_points'] : [],
            );
        }

        if (count($out) < count($chunk)) {
            throw new \RuntimeException('OpenAI batch grading missed some questions.');
        }

        return $out;
    }

    /**
     * @return array{is_correct: bool, score_awarded: int, feedback: string, teacher_comment: string, missing_key_points: list<string>}
     */
    private function gradeWithOpenAi(
        string $questionText,
        string $correctAnswer,
        string $studentAnswer,
        int $maxScore,
        string $questionType,
        string $language,
    ): array {
        $response = Http::withToken((string) config('services.openai.key'))
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.45,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->systemPrompt(batch: false),
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode([
                            'response_language' => 'English',
                            'question_type' => $questionType,
                            'max_score' => $maxScore,
                            'question' => $questionText,
                            'model_answer' => $correctAnswer,
                            'student_answer' => $studentAnswer,
                        ], JSON_UNESCAPED_UNICODE),
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI grading failed: '.$response->body());
        }

        $payload = json_decode((string) data_get($response->json(), 'choices.0.message.content', '{}'), true);

        if (! is_array($payload)) {
            throw new \RuntimeException('OpenAI grading returned invalid JSON.');
        }

        $score = max(0, min($maxScore, (int) ($payload['score_awarded'] ?? 0)));
        $isCorrect = (bool) ($payload['is_correct'] ?? ($score === $maxScore));

        return $this->result(
            $isCorrect,
            $score,
            $maxScore,
            (string) ($payload['feedback'] ?? ''),
            (string) ($payload['teacher_comment'] ?? ($payload['feedback'] ?? '')),
            is_array($payload['missing_key_points'] ?? null) ? $payload['missing_key_points'] : [],
        );
    }

    private function systemPrompt(bool $batch): string
    {
        $shape = $batch
            ? '{"results":[{"id":0,"score_awarded":number,"is_correct":boolean,"feedback":"...","teacher_comment":"...","missing_key_points":["..."]}]}'
            : '{"score_awarded":number,"is_correct":boolean,"feedback":"...","teacher_comment":"...","missing_key_points":["..."]}';

        return trim(\App\Support\AiPrompt::get('grading'))
            .' Return JSON only: '.$shape.'.';
    }

    /**
     * @return array{is_correct: bool, score_awarded: int, feedback: string, teacher_comment: string, missing_key_points: list<string>}
     */
    private function gradeLocally(string $questionText, string $correctAnswer, string $studentAnswer, int $maxScore): array
    {
        $isExact = \App\Support\GujaratiTextNormalizer::answersMatch($studentAnswer, $correctAnswer);
        $overlap = $this->contentOverlapRatio($correctAnswer, $studentAnswer);
        $score = $isExact ? $maxScore : (int) round($maxScore * max(0, min(0.85, $overlap)));
        $isCorrect = $score >= $maxScore;

        $topic = $this->shortTopic($questionText);
        if ($isCorrect) {
            $feedback = 'Excellent on '.$topic.'! You covered the main facts clearly.';
            $comment = 'Fantastic work on '.$topic.'! Your points match the lesson beautifully. :)';
        } elseif ($score > 0) {
            $feedback = 'Lovely progress on '.$topic.'! One more fact will make it complete.';
            $comment = 'Wonderful effort on '.$topic.'! You are so close — keep going for full marks. :)';
        } else {
            $feedback = 'Proud of your try on '.$topic.'! Review the key facts and you will shine.';
            $comment = 'Great attitude on '.$topic.'! Keep practising — you will get this soon. :)';
        }

        return $this->result(
            $isCorrect,
            $score,
            $maxScore,
            $feedback,
            $comment,
            $this->pointsFromModelAnswer($questionText, $correctAnswer, $isCorrect || $score > 0),
        );
    }

    /**
     * @param  array{is_correct: bool, score_awarded: int, feedback: string, teacher_comment: string, missing_key_points: list<string>}  $row
     * @return array{is_correct: bool, score_awarded: int, feedback: string, teacher_comment: string, missing_key_points: list<string>}
     */
    private function ensureSpecificFeedback(array $row, string $questionText, string $correctAnswer, string $studentAnswer): array
    {
        $isCorrect = (bool) $row['is_correct'];
        $score = (int) $row['score_awarded'];
        $max = max(1, (int) ($row['max_score'] ?? 1));
        $points = $this->filterSpecificPoints($row['missing_key_points'] ?? []);

        if (count($points) < 2) {
            $points = $this->pointsFromModelAnswer($questionText, $correctAnswer, $isCorrect || $score > 0);
        }

        $topic = $this->shortTopic($questionText);
        $feedback = trim((string) $row['feedback']);
        $comment = trim((string) $row['teacher_comment']);

        if ($feedback === '' || $this->isGenericText($feedback)) {
            $first = $points[0] ?? 'the main fact from the lesson';
            $feedback = $isCorrect || $score >= $max
                ? 'Excellent coverage of '.$topic.'!'
                : ($score > 0
                    ? 'Nice progress on '.$topic.'! Also include: '.$this->clip($first, 70)
                    : 'You can do it on '.$topic.'! Focus on: '.$this->clip($first, 70));
        }

        if ($comment === '' || $this->isGenericText($comment) || strlen($comment) < 28) {
            if ($isCorrect || $score >= $max) {
                $comment = 'Excellent on '.$topic.'! You explained the key idea clearly. :)';
            } elseif ($score > 0) {
                $comment = 'Wonderful effort on '.$topic.'! A little more detail will make it shine. :)';
            } else {
                $comment = 'Proud of your try on '.$topic.'! Keep practising — you will get this soon. :)';
            }
        }

        if (! str_contains($comment, ':)')) {
            $comment .= ' :)';
        }

        return $this->result($isCorrect, $score, $max, $feedback, $comment, $points);
    }

    /**
     * @param  array<int, array{is_correct: bool, score_awarded: int, feedback: string, teacher_comment: string, missing_key_points: list<string>}>  $rows
     * @param  array<int, array{question_text: string, correct_answer: string, student_answer: string, max_score: int, question_type: string}>  $items
     * @return array<int, array{is_correct: bool, score_awarded: int, feedback: string, teacher_comment: string, missing_key_points: list<string>}>
     */
    private function dedupeBatchPoints(array $rows, array $items): array
    {
        $seenFingerprints = [];

        foreach ($rows as $index => $row) {
            $fingerprint = mb_strtolower(implode('|', $row['missing_key_points'] ?? []));
            if ($fingerprint === '' || ! isset($seenFingerprints[$fingerprint])) {
                $seenFingerprints[$fingerprint] = true;
                continue;
            }

            $item = $items[$index] ?? [];
            $rows[$index]['missing_key_points'] = $this->pointsFromModelAnswer(
                (string) ($item['question_text'] ?? ''),
                (string) ($item['correct_answer'] ?? ''),
                (bool) ($row['is_correct'] ?? false),
            );
            $rows[$index] = $this->ensureSpecificFeedback(
                $rows[$index],
                (string) ($item['question_text'] ?? ''),
                (string) ($item['correct_answer'] ?? ''),
                (string) ($item['student_answer'] ?? ''),
            );
        }

        return $rows;
    }

    /**
     * @param  list<mixed>  $points
     * @return list<string>
     */
    private function filterSpecificPoints(array $points): array
    {
        $out = [];
        foreach ($points as $point) {
            if (! is_string($point)) {
                continue;
            }
            $clean = trim(preg_replace('/\s+/u', ' ', $point) ?? $point);
            if ($clean === '' || mb_strlen($clean) < 12 || $this->isGenericText($clean)) {
                continue;
            }
            $out[] = $this->clip($clean, 90);
        }

        return array_values(array_unique($out));
    }

    private function isGenericText(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        foreach (self::GENERIC_POINT_FRAGMENTS as $fragment) {
            if (str_contains($t, $fragment)) {
                return true;
            }
        }

        return (bool) preg_match('/^(good attempt|answer is incomplete|add key points|write (the )?complete|include an important)/iu', $t);
    }

    /**
     * Build unique points from the model answer / question (not generic tips).
     *
     * @return list<string>
     */
    public function pointsFromModelAnswer(string $questionText, string $correctAnswer, bool $coveredStyle = false): array
    {
        $source = trim($correctAnswer);
        if ($source === '' || $source === '—') {
            $source = trim($questionText);
        }

        $chunks = preg_split('/(?<=[.!?।])\s+|;\s+|\n+/u', $source) ?: [];
        $points = [];

        foreach ($chunks as $chunk) {
            $chunk = trim(preg_replace('/\s+/u', ' ', $chunk) ?? $chunk);
            $chunk = trim($chunk, " \t\n\r\0\x0B-–—");
            if (mb_strlen($chunk) < 12 || $this->isGenericText($chunk)) {
                continue;
            }

            // Split long clauses on "and/or/which/that" for more specific bullets
            $clauses = preg_split('/\s+(?:and|or|which|that|because|while)\s+/iu', $chunk) ?: [$chunk];
            foreach ($clauses as $clause) {
                $clause = trim($clause, " \t\n\r\0\x0B,.");
                if (mb_strlen($clause) < 16 || $this->isGenericText($clause)) {
                    continue;
                }
                // Capitalize first letter for sheet readability
                $clause = mb_strtoupper(mb_substr($clause, 0, 1)).mb_substr($clause, 1);
                $points[] = $this->clip($clause, 90);
                if (count($points) >= 4) {
                    break 2;
                }
            }
        }

        if (count($points) < 2 && mb_strlen($source) >= 16) {
            $points[] = $this->clip(mb_strtoupper(mb_substr($source, 0, 1)).mb_substr($source, 1), 90);
        }

        if (count($points) < 2) {
            $topic = $this->shortTopic($questionText !== '' ? $questionText : $source);
            $points = $coveredStyle
                ? [
                    'Explained the main idea of '.$topic,
                    'Mentioned an important fact related to '.$topic,
                    'Linked the answer to the lesson on '.$topic,
                ]
                : [
                    'State the main historical fact about '.$topic,
                    'Add one supporting detail about '.$topic,
                    'Explain why '.$topic.' was important',
                ];
        }

        return array_slice(array_values(array_unique($points)), 0, 4);
    }

    private function shortTopic(string $questionText): string
    {
        $q = trim(preg_replace('/\s+/u', ' ', $questionText) ?? $questionText);
        $q = preg_replace('/^(what|why|how|when|where|who|which|explain|analyze|analyse|describe|discuss|define|write|state|give|list|compare)\b[\s,:]*/iu', '', $q) ?? $q;
        $q = trim($q, " \t\n\r\0\x0B?؟.");
        if ($q === '' || \App\Support\IndicScript::containsIndic($q)) {
            return 'this answer';
        }

        return $this->clip($q, 42);
    }

    private function contentOverlapRatio(string $correct, string $student): float
    {
        $correctWords = $this->significantWords($correct);
        $studentWords = $this->significantWords($student);
        if ($correctWords === [] || $studentWords === []) {
            return 0.0;
        }
        $hit = 0;
        foreach ($correctWords as $word) {
            if (isset($studentWords[$word])) {
                $hit++;
            }
        }

        return $hit / max(1, count($correctWords));
    }

    /**
     * @return array<string, true>
     */
    private function significantWords(string $text): array
    {
        $text = mb_strtolower($text);
        $parts = preg_split('/[^a-z0-9]+/i', $text) ?: [];
        $stop = ['the', 'and', 'for', 'was', 'were', 'that', 'this', 'with', 'from', 'into', 'a', 'an', 'of', 'to', 'in', 'on', 'it', 'is', 'are', 'be', 'as', 'by'];
        $out = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if (strlen($part) < 4 || in_array($part, $stop, true)) {
                continue;
            }
            $out[$part] = true;
        }

        return $out;
    }

    private function clip(string $text, int $max): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max - 1)).'…';
    }

    /**
     * @param  list<mixed>  $missingKeyPoints
     * @return array{is_correct: bool, score_awarded: int, max_score: int, feedback: string, teacher_comment: string, missing_key_points: list<string>}
     */
    private function result(bool $isCorrect, int $score, int $maxScore, string $feedback, string $teacherComment, array $missingKeyPoints = []): array
    {
        $points = $this->filterSpecificPoints($missingKeyPoints);

        return [
            'is_correct' => $isCorrect,
            'score_awarded' => $score,
            'max_score' => $maxScore,
            'feedback' => trim($feedback),
            'teacher_comment' => trim($teacherComment),
            'missing_key_points' => array_slice($points, 0, 4),
        ];
    }
}
