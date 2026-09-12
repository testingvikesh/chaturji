<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\Homework;
use App\Support\AnswerSheetParser;
use App\Support\GujaratiTextNormalizer;
use App\Support\IndicScript;
use App\Support\ObjectiveQuestionResolver;
use App\Support\PaperLanguage;
use App\Support\PaperTypeHelper;
use App\Support\SubjectiveQuestionTypes;
use Illuminate\Support\Collection;
use RuntimeException;

class OpenAiAnswerEvaluator
{
    public function __construct(
        private readonly SubjectiveAnswerGrader $subjectiveAnswerGrader,
    ) {}

    public function isConfigured(): bool
    {
        return filled(config('services.openai.key'));
    }

    /**
     * @param  array<int, string>|null  $parsedAnswers
     * @param  array<int, array{number?: int, question?: string, answer?: string}>  $sheetItems
     * @return array{summary: array<string, mixed>, questions: array<int, array<string, mixed>>}
     */
    public function evaluate(Exam $exam, string $extractedText, ?array $parsedAnswers = null, array $sheetItems = []): array
    {
        $questions = $this->questionPayload($exam);
        $parsedAnswers = AnswerSheetParser::pickBest(
            AnswerSheetParser::sanitize(
                $parsedAnswers ?? AnswerSheetParser::parse($extractedText),
                $extractedText
            ),
            AnswerSheetParser::sanitizeLenient(
                $parsedAnswers ?? AnswerSheetParser::parseRaw($extractedText),
                $extractedText
            ),
            $extractedText
        );

        if ($parsedAnswers === [] && $sheetItems === []) {
            throw new RuntimeException('No numbered answers found in the uploaded PDF.');
        }

        $sheetItems = $this->normalizeSheetItems($sheetItems, $parsedAnswers, $extractedText);

        return $this->evaluateFromSheetItems($questions, $sheetItems, $exam->questions->count(), $extractedText);
    }

    /**
     * @param  array<int, string>|null  $parsedAnswers
     * @param  array<int, array{number?: int, question?: string, answer?: string}>  $sheetItems
     * @return array{summary: array<string, mixed>, questions: array<int, array<string, mixed>>}
     */
    public function evaluateHomework(Homework $homework, string $extractedText, ?array $parsedAnswers = null, array $sheetItems = []): array
    {
        $marksPerType = $homework->generation_config['marks_per_type'] ?? [];
        $questions = $this->homeworkQuestionPayload($homework, $marksPerType);
        $parsedAnswers = AnswerSheetParser::pickBest(
            AnswerSheetParser::sanitize(
                $parsedAnswers ?? AnswerSheetParser::parse($extractedText),
                $extractedText
            ),
            AnswerSheetParser::sanitizeLenient(
                $parsedAnswers ?? AnswerSheetParser::parseRaw($extractedText),
                $extractedText
            ),
            $extractedText
        );

        if ($parsedAnswers === [] && $sheetItems === []) {
            throw new RuntimeException('No numbered answers found in the uploaded sheet.');
        }

        $sheetItems = $this->normalizeSheetItems($sheetItems, $parsedAnswers, $extractedText);

        return $this->evaluateFromSheetItems($questions, $sheetItems, $homework->questions->count(), $extractedText);
    }

    /**
     * Match uploaded sheet answers to paper questions by question number first,
     * then question-text similarity. Upload check is subjective-only.
     *
     * @param  Collection<int, array<string, mixed>>  $questions
     * @param  array<int, array{number: int, question: string, answer: string}>  $sheetItems
     * @return array{summary: array<string, mixed>, questions: array<int, array<string, mixed>>}
     */
    private function evaluateFromSheetItems(Collection $questions, array $sheetItems, int $paperTotalQuestions, string $extractedText = ''): array
    {
        // Upload PDF/image check grades subjective answers only.
        $subjectiveQuestions = $questions
            ->filter(fn (array $q) => SubjectiveQuestionTypes::isSubjective($q['type'] ?? null))
            ->values();

        if ($subjectiveQuestions->isEmpty()) {
            throw new RuntimeException('This paper has no subjective questions to check from the uploaded sheet.');
        }

        $pairs = $this->matchSheetItemsToPaper($subjectiveQuestions, $sheetItems);

        if ($pairs === [] && $sheetItems === []) {
            throw new RuntimeException('Could not match uploaded answers to subjective questions.');
        }

        $detectBits = [$extractedText];
        foreach ($subjectiveQuestions as $q) {
            $detectBits[] = (string) ($q['question'] ?? '');
            $detectBits[] = (string) ($q['correct_answer'] ?? '');
        }
        foreach ($sheetItems as $item) {
            $detectBits[] = (string) ($item['question'] ?? '');
            $detectBits[] = (string) ($item['answer'] ?? '');
        }
        $language = PaperLanguage::detect(...$detectBits);
        // Teacher comments / key points / tips always English for reliable GD rendering on Hostinger.
        $feedbackLang = 'en';
        $L = PaperLanguage::labels($feedbackLang);

        $matchedPaperNumbers = [];
        foreach ($pairs as $pair) {
            $matchedPaperNumbers[(int) $pair['paper']['number']] = true;
        }

        // Include unanswered subjective questions so missing Qs show as 0 marks.
        foreach ($subjectiveQuestions as $paper) {
            $paperNo = (int) ($paper['number'] ?? 0);
            if ($paperNo < 1 || isset($matchedPaperNumbers[$paperNo])) {
                continue;
            }
            $pairs[] = [
                'sheet' => [
                    'number' => $paperNo,
                    'question' => (string) ($paper['question'] ?? ''),
                    'answer' => '',
                    'page_index' => null,
                    'y_percent' => null,
                ],
                'paper' => $paper,
            ];
            $matchedPaperNumbers[$paperNo] = true;
        }

        if ($pairs === []) {
            throw new RuntimeException('Could not match uploaded answers to subjective questions.');
        }

        usort($pairs, fn (array $a, array $b) => ((int) $a['paper']['number']) <=> ((int) $b['paper']['number']));

        $gradeInputs = [];
        $meta = [];
        foreach ($pairs as $index => $pair) {
            $question = $pair['paper'];
            $sheet = $pair['sheet'];
            $studentAnswer = trim((string) ($sheet['answer'] ?? ''));
            $correctAnswer = trim((string) ($question['correct_answer'] ?? ''));
            $marks = max(1, (int) ($question['marks'] ?? 1));
            $type = (string) ($question['type'] ?? '');
            $sheetQuestion = trim((string) ($sheet['question'] ?? ''));
            $displayQuestion = $sheetQuestion !== '' ? $sheetQuestion : (string) ($question['question'] ?? '');
            $displayNumber = (int) ($question['number'] ?? $sheet['number'] ?? 0);

            $meta[$index] = [
                'question' => $question,
                'sheet' => $sheet,
                'student_answer' => $studentAnswer,
                'correct_answer' => $correctAnswer,
                'marks' => $marks,
                'type' => $type,
                'display_question' => $displayQuestion,
                'display_number' => $displayNumber,
                'use_subjective' => true,
            ];

            $gradeInputs[$index] = [
                'question_text' => (string) ($question['question'] ?? $displayQuestion),
                'correct_answer' => $correctAnswer,
                'student_answer' => $studentAnswer,
                'max_score' => $marks,
                'question_type' => $type !== '' ? $type : 'short_answer',
            ];
        }

        $gradedBatch = $this->subjectiveAnswerGrader->gradeMany(array_values($gradeInputs));
        $gradedByPairIndex = [];
        foreach (array_keys($meta) as $i => $pairIndex) {
            $gradedByPairIndex[$pairIndex] = $gradedBatch[$i] ?? null;
        }

        $rows = collect($meta)->map(function (array $rowMeta, int $index) use ($language, $feedbackLang, $gradedByPairIndex) {
            $studentAnswer = $rowMeta['student_answer'];
            $correctAnswer = $rowMeta['correct_answer'];
            $marks = $rowMeta['marks'];
            $type = $rowMeta['type'];
            $displayQuestion = $rowMeta['display_question'];
            $displayNumber = $rowMeta['display_number'];
            $sheet = $rowMeta['sheet'];

            $graded = $gradedByPairIndex[$index] ?? $this->subjectiveAnswerGrader->grade(
                $displayQuestion,
                $correctAnswer,
                $studentAnswer,
                $marks,
                $type !== '' ? $type : 'short_answer',
                $feedbackLang,
            );

            return [
                'question_number' => $displayNumber,
                'paper_number' => (int) $rowMeta['question']['number'],
                'question_text' => $displayQuestion,
                'paper_question_text' => (string) ($rowMeta['question']['question'] ?? ''),
                'question_type' => $type,
                'student_answer' => $studentAnswer !== '' ? $studentAnswer : '—',
                'correct_answer' => $correctAnswer !== '' ? $correctAnswer : '—',
                'is_correct' => $graded['is_correct'],
                'score_awarded' => $graded['score_awarded'],
                'max_score' => $marks,
                'feedback' => $graded['feedback'],
                'teacher_comment' => $graded['teacher_comment'],
                'missing_key_points' => $graded['missing_key_points'] ?? [],
                'language' => $language,
                'feedback_language' => $feedbackLang,
                'page_index' => $sheet['page_index'] ?? null,
                'y_percent' => $sheet['y_percent'] ?? null,
            ];
        })->values();

        $evaluatedQuestions = $rows->map(fn (array $row) => [
            'marks' => (int) ($row['max_score'] ?? 1),
        ]);

        $detected = [];
        foreach ($sheetItems as $item) {
            $detected[(int) $item['number']] = (string) $item['answer'];
        }

        return $this->buildSummary($rows, $evaluatedQuestions, count($sheetItems), $paperTotalQuestions, $detected, $language);
    }

    /**
     * Prefer exact paper question number (Q10 → paper Q10), then text similarity.
     * Only subjective questions are considered.
     *
     * @param  Collection<int, array<string, mixed>>  $questions
     * @param  array<int, array{number: int, question: string, answer: string}>  $sheetItems
     * @return array<int, array{sheet: array{number: int, question: string, answer: string}, paper: array<string, mixed>}>
     */
    private function matchSheetItemsToPaper(Collection $questions, array $sheetItems): array
    {
        $subjectivePool = $questions
            ->filter(fn (array $q) => SubjectiveQuestionTypes::isSubjective($q['type'] ?? null))
            ->values()
            ->all();

        if ($subjectivePool === []) {
            return [];
        }

        $byNumber = [];
        foreach ($subjectivePool as $paper) {
            $byNumber[(int) $paper['number']] = $paper;
        }

        $usedPaperNumbers = [];
        $pairs = [];

        foreach ($sheetItems as $sheet) {
            $sheetNo = (int) ($sheet['number'] ?? 0);
            $best = null;
            $bestScore = -1.0;

            // 1) Exact question-number match (Q10 sheet → Q10 paper)
            if ($sheetNo > 0 && isset($byNumber[$sheetNo]) && ! isset($usedPaperNumbers[$sheetNo])) {
                $best = $byNumber[$sheetNo];
                $bestScore = 1.0;
            } else {
                foreach ($subjectivePool as $paper) {
                    $paperNo = (int) $paper['number'];
                    if (isset($usedPaperNumbers[$paperNo])) {
                        continue;
                    }

                    $score = $this->pairScore($sheet, $paper);
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $best = $paper;
                    }
                }
            }

            // 2) Fallback: next unused subjective in paper order
            if ($best === null || $bestScore < 0.18) {
                foreach ($subjectivePool as $paper) {
                    $paperNo = (int) $paper['number'];
                    if (! isset($usedPaperNumbers[$paperNo])) {
                        $best = $paper;
                        break;
                    }
                }
            }

            if ($best === null) {
                continue;
            }

            $usedPaperNumbers[(int) $best['number']] = true;
            $pairs[] = [
                'sheet' => $sheet,
                'paper' => $best,
            ];
        }

        return $pairs;
    }

    /**
     * @param  array{number: int, question: string, answer: string}  $sheet
     * @param  array<string, mixed>  $paper
     */
    private function pairScore(array $sheet, array $paper): float
    {
        $sheetQ = GujaratiTextNormalizer::normalizeForMatch((string) ($sheet['question'] ?? ''));
        $paperQ = GujaratiTextNormalizer::normalizeForMatch((string) ($paper['question'] ?? ''));
        $sheetA = GujaratiTextNormalizer::normalizeForMatch((string) ($sheet['answer'] ?? ''));
        $paperA = GujaratiTextNormalizer::normalizeForMatch((string) ($paper['correct_answer'] ?? ''));

        $score = 0.0;

        // Strong boost for exact Q-number match
        if ((int) $sheet['number'] === (int) $paper['number']) {
            $score += 0.85;
        }

        if ($sheetQ !== '' && $paperQ !== '') {
            similar_text($sheetQ, $paperQ, $percent);
            $score += ($percent / 100) * 0.75;

            if (str_contains($paperQ, $sheetQ) || str_contains($sheetQ, $paperQ)) {
                $score += 0.2;
            }
        }

        if ($sheetA !== '' && $paperA !== '') {
            if (GujaratiTextNormalizer::answersMatch($sheetA, $paperA)) {
                $score += 0.35;
            } else {
                similar_text($sheetA, $paperA, $aPercent);
                $score += ($aPercent / 100) * 0.15;
            }
        }

        return $score;
    }

    /**
     * @param  array<int, array{number?: int, question?: string, answer?: string}>  $sheetItems
     * @param  array<int, string>  $parsedAnswers
     * @return array<int, array{number: int, question: string, answer: string}>
     */
    private function normalizeSheetItems(array $sheetItems, array $parsedAnswers, string $extractedText): array
    {
        $normalized = [];

        foreach ($sheetItems as $item) {
            $number = (int) ($item['number'] ?? 0);
            $answer = trim((string) ($item['answer'] ?? ''));
            if ($number < 1 || $answer === '') {
                continue;
            }
            $normalized[$number] = [
                'number' => $number,
                'question' => trim((string) ($item['question'] ?? '')),
                'answer' => $answer,
                'page_index' => array_key_exists('page_index', $item) ? (int) $item['page_index'] : null,
                'y_percent' => array_key_exists('y_percent', $item) ? (float) $item['y_percent'] : null,
            ];
        }

        if ($normalized === []) {
            foreach ($parsedAnswers as $number => $answer) {
                $normalized[(int) $number] = [
                    'number' => (int) $number,
                    'question' => $this->questionFromTranscription($extractedText, (int) $number),
                    'answer' => trim((string) $answer),
                    'page_index' => null,
                    'y_percent' => null,
                ];
            }
        }

        // Enrich empty question text from transcription
        foreach ($normalized as $number => $item) {
            if ($item['question'] === '') {
                $normalized[$number]['question'] = $this->questionFromTranscription($extractedText, $number);
            }
        }

        ksort($normalized);

        return array_values($normalized);
    }

    private function questionFromTranscription(string $text, int $number): string
    {
        $pattern = '/(?:^|\n)\s*Q?\s*'.$number.'\s*[\.\)\-:]?\s*(.+?)(?=\n\s*(?:Ans|Answer|જવાબ|Q?\s*\d+)|\z)/isu';

        if (! preg_match($pattern, $text, $matches)) {
            return '';
        }

        $chunk = trim((string) ($matches[1] ?? ''));
        $chunk = preg_replace('/^(Ans|Answer|જવાબ).*/isu', '', $chunk) ?? $chunk;
        $chunk = trim($chunk);

        // Keep first line that looks like a question
        foreach (preg_split('/\R+/u', $chunk) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '' && ! preg_match('/^(Ans|Answer|જવાબ)\b/iu', $line)) {
                return $line;
            }
        }

        return $chunk;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function questionPayload(Exam $exam): Collection
    {
        return PaperTypeHelper::flattenPaperOrder($exam->questions)->map(function ($question, int $index) {
            return [
                'number' => $index + 1,
                'type' => $question->question_type,
                'question' => $question->question_text,
                'correct_answer' => trim((string) ObjectiveQuestionResolver::answer($question)),
                'marks' => (int) ($question->marks ?? 1),
            ];
        });
    }

    /**
     * @param  array<string, int>  $marksPerType
     * @return Collection<int, array<string, mixed>>
     */
    private function homeworkQuestionPayload(Homework $homework, array $marksPerType): Collection
    {
        return PaperTypeHelper::flattenPaperOrder($homework->questions)->map(function ($question, int $index) use ($marksPerType) {
            return [
                'number' => $index + 1,
                'type' => $question->question_type,
                'question' => $question->question_text,
                'correct_answer' => trim((string) ObjectiveQuestionResolver::answer($question)),
                'marks' => (int) ($marksPerType[$question->question_type] ?? $question->marks ?? 1),
            ];
        });
    }

    /**
     * @return array{summary: array<string, mixed>, questions: array<int, array<string, mixed>>}
     */
    private function buildSummary(Collection $rows, Collection $evaluatedQuestions, int $uploadedCount, int $paperTotalQuestions, array $parsedAnswers = [], string $language = 'en'): array
    {
        $maxScore = (int) $evaluatedQuestions->sum('marks');
        $totalScore = (int) $rows->sum('score_awarded');
        $percentage = $maxScore > 0 ? (int) round(($totalScore / $maxScore) * 100) : 0;
        $appreciation = PaperLanguage::appreciationPair('en', $percentage);

        ksort($parsedAnswers);

        return [
            'summary' => [
                'total_score' => $totalScore,
                'max_score' => $maxScore,
                'percentage' => $percentage,
                'grade' => $this->gradeLabel($percentage),
                'questions_checked' => $uploadedCount,
                'paper_total_questions' => $paperTotalQuestions,
                'exam_total_questions' => $paperTotalQuestions,
                'detected_answers' => $parsedAnswers,
                'appreciation_emoji' => $appreciation['emoji'],
                'appreciation_message' => $appreciation['message'],
                'language' => PaperLanguage::normalize($language),
                'feedback_language' => PaperLanguage::normalize($language),
            ],
            'questions' => $rows->values()->all(),
        ];
    }

    public function gradeLabel(int $percentage): string
    {
        return match (true) {
            $percentage >= 90 => 'A+',
            $percentage >= 75 => 'A',
            $percentage >= 60 => 'B',
            $percentage >= 45 => 'C',
            $percentage >= 30 => 'D',
            default => 'E',
        };
    }

    /**
     * @return array{emoji: string, message: string}
     */
    public function appreciationForScore(int $percentage): array
    {
        return match (true) {
            $percentage >= 90 => [
                'emoji' => '🏆',
                'message' => 'Outstanding! You attempted all the questions very well. Keep this confidence and stay focused. Good Luck!',
            ],
            $percentage >= 75 => [
                'emoji' => '🎉',
                'message' => 'Very good work! You understood most answers. Review any mistakes and keep practicing regularly. Good Luck!',
            ],
            $percentage >= 50 => [
                'emoji' => '👍',
                'message' => 'You attempted the questions. Keep trying and never give up. Practice regularly and read more. I believe you can do much better.',
            ],
            default => [
                'emoji' => '💪',
                'message' => 'You attempted all the questions. Keep trying and never give up. Practice regularly and read more. I believe you can do much better. Keep your hard work and stay focused. Good Luck!',
            ],
        };
    }
}
