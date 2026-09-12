<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamSubmission;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\User;
use App\Support\AnswerSheetParser;
use App\Support\GujaratiTextNormalizer;
use App\Support\PaperTypeHelper;
use App\Support\TextSanitizer;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AnswerSheetProcessor
{
    public function __construct(
        private readonly PdfTextExtractor $pdfTextExtractor,
        private readonly GoogleVisionService $googleVision,
        private readonly OpenAiVisionOcrService $openAiVisionOcr,
        private readonly PdfPageRasterizer $pageRasterizer,
        private readonly AnswerSheetImageEnhancer $imageEnhancer,
        private readonly OpenAiAnswerEvaluator $openAiAnswerEvaluator,
        private readonly CorrectedAnswerSheetRenderer $correctedAnswerSheetRenderer,
        private readonly AnswerSheetImageMerger $imageMerger,
    ) {}

    public function processExam(Exam $exam, User $user, UploadedFile $file): ExamSubmission
    {
        $submission = $this->startExamSubmission($exam, $user, $file);

        return $this->finishExamSubmission($submission);
    }

    /**
     * @param  array<int, UploadedFile>  $extraImages
     */
    public function processExamWithImages(Exam $exam, User $user, UploadedFile $primaryFile, array $extraImages = []): ExamSubmission
    {
        return $this->processExam($exam, $user, $this->mergeUploads($primaryFile, $extraImages));
    }

    /**
     * Fast path for Hostinger: store upload + create processing row, then finish in afterResponse.
     *
     * @param  array<int, UploadedFile>  $extraImages
     */
    public function startExamSubmission(Exam $exam, User $user, UploadedFile $primaryFile, array $extraImages = []): ExamSubmission
    {
        $exam->loadMissing(['questions.chapterQuestion']);
        $file = $this->mergeUploads($primaryFile, $extraImages);
        $path = $file->store('exam-submissions/'.$exam->id, 'public');

        return ExamSubmission::create([
            'exam_id' => $exam->id,
            'user_id' => $user->id,
            'pdf_path' => $path,
            'status' => 'processing',
            'submitted_at' => now(),
        ]);
    }

    public function finishExamSubmission(ExamSubmission $submission): ExamSubmission
    {
        $exam = $submission->exam()->with(['questions.chapterQuestion'])->firstOrFail();
        $absolutePath = Storage::disk('public')->path($submission->pdf_path);

        if (! is_file($absolutePath)) {
            throw new RuntimeException('Uploaded answer sheet file was not found on the server.');
        }

        try {
            $extraction = $this->extractSheet($absolutePath, $exam->questions);

            if ($extraction['answers'] === []) {
                throw new RuntimeException($this->buildFailureMessage($extraction['candidates'] ?? []));
            }

            $evaluation = $this->openAiAnswerEvaluator->evaluate(
                $exam,
                $extraction['text'],
                $extraction['answers'],
                $extraction['items'] ?? []
            );

            $correctedPath = $this->renderCorrectedSheet(
                $extraction['preview_images'] ?? [$extraction['preview_image'] ?? $absolutePath],
                $evaluation,
                'exam-submissions/'.$exam->id,
                $absolutePath
            );

            $this->completeSubmission($submission, $extraction, $evaluation, $correctedPath, 'exam_submissions');
        } catch (\Throwable $exception) {
            $submission->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $submission->fresh();
    }

    public function processHomework(Homework $homework, User $user, UploadedFile $file): HomeworkSubmission
    {
        $submission = $this->startHomeworkSubmission($homework, $user, $file);

        return $this->finishHomeworkSubmission($submission);
    }

    /**
     * @param  array<int, UploadedFile>  $extraImages
     */
    public function processHomeworkWithImages(Homework $homework, User $user, UploadedFile $primaryFile, array $extraImages = []): HomeworkSubmission
    {
        return $this->processHomework($homework, $user, $this->mergeUploads($primaryFile, $extraImages));
    }

    /**
     * @param  array<int, UploadedFile>  $extraImages
     */
    public function startHomeworkSubmission(Homework $homework, User $user, UploadedFile $primaryFile, array $extraImages = []): HomeworkSubmission
    {
        $homework->loadMissing(['questions.chapterQuestion']);
        $file = $this->mergeUploads($primaryFile, $extraImages);
        $path = $file->store('homework-submissions/'.$homework->id, 'public');

        return HomeworkSubmission::create([
            'homework_id' => $homework->id,
            'user_id' => $user->id,
            'pdf_path' => $path,
            'status' => 'processing',
            'submitted_at' => now(),
        ]);
    }

    public function finishHomeworkSubmission(HomeworkSubmission $submission): HomeworkSubmission
    {
        $homework = $submission->homework()->with(['questions.chapterQuestion'])->firstOrFail();
        $absolutePath = Storage::disk('public')->path($submission->pdf_path);

        if (! is_file($absolutePath)) {
            throw new RuntimeException('Uploaded answer sheet file was not found on the server.');
        }

        try {
            $extraction = $this->extractSheet($absolutePath, $homework->questions);

            if ($extraction['answers'] === []) {
                throw new RuntimeException($this->buildFailureMessage($extraction['candidates'] ?? []));
            }

            $evaluation = $this->openAiAnswerEvaluator->evaluateHomework(
                $homework,
                $extraction['text'],
                $extraction['answers'],
                $extraction['items'] ?? []
            );

            $correctedPath = $this->renderCorrectedSheet(
                $extraction['preview_images'] ?? [$extraction['preview_image'] ?? $absolutePath],
                $evaluation,
                'homework-submissions/'.$homework->id,
                $absolutePath
            );

            $this->completeSubmission($submission, $extraction, $evaluation, $correctedPath, 'homework_submissions');
        } catch (\Throwable $exception) {
            $submission->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $submission->fresh();
    }

    /**
     * @param  ExamSubmission|HomeworkSubmission  $submission
     * @param  array{method: string, text: string, answers: array<int, string>}  $extraction
     * @param  array{summary: array<string, mixed>, questions: array<int, array<string, mixed>>}  $evaluation
     */
    private function completeSubmission(
        ExamSubmission|HomeworkSubmission $submission,
        array $extraction,
        array $evaluation,
        ?string $correctedPath,
        string $table,
    ): void {
        $payload = [
            'extracted_text' => $extraction['text'],
            'extraction_method' => $extraction['method'],
            'evaluation' => $evaluation,
            'score_awarded' => $evaluation['summary']['total_score'] ?? null,
            'max_score' => $evaluation['summary']['max_score'] ?? null,
            'status' => 'completed',
            'error_message' => null,
        ];

        if (Schema::hasColumn($table, 'corrected_sheet_path')) {
            $payload['corrected_sheet_path'] = $correctedPath;
        } elseif ($correctedPath) {
            Log::error('DB column corrected_sheet_path missing on '.$table.'. Run: php artisan homework:fix-answer-sheet');
        }

        $submission->update($payload);

        if ($correctedPath && Schema::hasColumn($table, 'corrected_sheet_path')) {
            \App\Support\PublicMedia::publish($correctedPath);
        }
    }

    /**
     * @param  array<int, UploadedFile>  $extraImages
     */
    private function mergeUploads(UploadedFile $primaryFile, array $extraImages): UploadedFile
    {
        $paths = [$primaryFile->getRealPath() ?: $primaryFile->path()];

        foreach ($extraImages as $image) {
            $paths[] = $image->getRealPath() ?: $image->path();
        }

        if (count($paths) === 1) {
            return $primaryFile;
        }

        $mergedPath = $this->imageMerger->merge($paths);

        return new UploadedFile(
            $mergedPath,
            'merged-answer-sheet.jpg',
            'image/jpeg',
            null,
            true
        );
    }

    /**
     * @param  string|list<string>  $sourceImagePath
     * @param  array{summary: array<string, mixed>, questions: array<int, array<string, mixed>>}  $evaluation
     */
    private function renderCorrectedSheet(
        string|array $sourceImagePath,
        array $evaluation,
        string $folder,
        ?string $fallbackImagePath = null,
    ): ?string {
        $paths = is_array($sourceImagePath) ? $sourceImagePath : [$sourceImagePath];
        $paths = array_values(array_filter($paths, fn ($p) => is_string($p) && $p !== '' && is_file($p)));

        $imageCandidates = array_values(array_filter($paths, function (string $path) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
        }));

        // Only use original upload when no durable preview page exists.
        // Never stack the same photo twice (preview copy + original = fake 2nd page).
        if ($imageCandidates === [] && is_string($fallbackImagePath) && $fallbackImagePath !== '' && is_file($fallbackImagePath)) {
            $ext = strtolower(pathinfo($fallbackImagePath, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                $imageCandidates = [$fallbackImagePath];
            }
        }

        $imageCandidates = $this->dedupeImagePaths($imageCandidates);

        if ($imageCandidates !== []) {
            try {
                $path = $this->correctedAnswerSheetRenderer->render(
                    $imageCandidates,
                    $evaluation['questions'] ?? [],
                    $evaluation['summary'] ?? [],
                    $folder
                );

                if ($path) {
                    \App\Support\PublicMedia::publish($path);

                    return $path;
                }
            } catch (\Throwable $exception) {
                Log::error('Corrected sheet render failed: '.$exception->getMessage(), [
                    'folder' => $folder,
                    'sources' => $imageCandidates,
                ]);
            }

            // Try one-by-one if stacking failed
            foreach ($imageCandidates as $candidatePath) {
                try {
                    $path = $this->correctedAnswerSheetRenderer->render(
                        $candidatePath,
                        $evaluation['questions'] ?? [],
                        $evaluation['summary'] ?? [],
                        $folder
                    );

                    if ($path) {
                        \App\Support\PublicMedia::publish($path);

                        return $path;
                    }
                } catch (\Throwable $exception) {
                    Log::error('Corrected sheet render failed: '.$exception->getMessage(), [
                        'folder' => $folder,
                        'source' => $candidatePath,
                    ]);
                }
            }
        }

        // Last resort: publish original upload as corrected so UI always gets an image
        foreach ($imageCandidates as $candidatePath) {
            $fallback = $this->storeFallbackCorrectedImage($candidatePath, $folder);
            if ($fallback) {
                \App\Support\PublicMedia::publish($fallback);
                Log::warning('Used fallback corrected sheet (copy of upload)', ['folder' => $folder]);

                return $fallback;
            }
        }

        Log::warning('Corrected sheet renderer returned null', [
            'folder' => $folder,
            'gd' => extension_loaded('gd'),
            'sources' => array_map(fn ($p) => ['path' => $p, 'exists' => is_file($p)], $paths),
        ]);

        return null;
    }

    /**
     * Keep unique page images only (same photo saved twice must not become 2 pages).
     *
     * @param  list<string>  $paths
     * @return list<string>
     */
    private function dedupeImagePaths(array $paths): array
    {
        $unique = [];
        $seen = [];

        foreach ($paths as $path) {
            if (! is_file($path)) {
                continue;
            }

            $hash = @md5_file($path);
            if ($hash === false) {
                $hash = (string) filesize($path).'|'.(string) filemtime($path).'|'.basename($path);
            }

            if (isset($seen[$hash])) {
                continue;
            }

            $seen[$hash] = true;
            $unique[] = $path;
        }

        return $unique;
    }

    private function storeFallbackCorrectedImage(string $sourceImagePath, string $folder): ?string
    {
        $ext = strtolower(pathinfo($sourceImagePath, PATHINFO_EXTENSION));
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return null;
        }

        $relativePath = trim($folder, '/').'/corrected_fallback_'.time().'_'.bin2hex(random_bytes(3)).'.'.$ext;
        $absolutePath = Storage::disk('public')->path($relativePath);
        $directory = dirname($absolutePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return @copy($sourceImagePath, $absolutePath) ? $relativePath : null;
    }

    /**
     * @return array{method: string, text: string, answers: array<int, string>, preview_image?: string, preview_images?: array<int, string>, candidates?: array<int, array<string, mixed>>}
     */
    private function extractSheet(string $absolutePath, EloquentCollection $questions): array
    {
        $imagePaths = [];
        $ocrPaths = [];
        $candidates = [];
        $errors = [];
        $answerHints = $this->answerHints($questions);

        $conversion = $this->pageRasterizer->toImagePathsWithDiagnostics($absolutePath);
        $imagePaths = $conversion['images'];
        $errors = array_merge($errors, $conversion['errors']);

        // Faster Hostinger path: fewer pages, smaller batches of larger groups.
        $ocrImagePaths = array_slice($imagePaths, 0, 8);
        $previewSourcePaths = array_slice($imagePaths !== [] ? $imagePaths : [$absolutePath], 0, 10);
        $previewImages = $this->persistDurablePreviews($previewSourcePaths);
        $previewImage = $previewImages[0] ?? $absolutePath;

        if ($ocrImagePaths !== []) {
            // One OCR pass only (enhanced if possible) — dual passes caused 504 timeouts.
            $ocrPaths = $this->imageEnhancer->enhanceAll($ocrImagePaths);
            $primaryOcrPaths = $ocrPaths !== [] ? $ocrPaths : $ocrImagePaths;

            if ($this->openAiVisionOcr->isConfigured()) {
                try {
                    $sheet = $this->extractSheetDataInBatches($primaryOcrPaths, $answerHints);
                    $transcription = $sheet['transcription'];
                    $parsedFromText = AnswerSheetParser::parse($transcription);
                    $structured = AnswerSheetParser::sanitize($sheet['answers'], $transcription);
                    $answers = AnswerSheetParser::pickBest($structured, $parsedFromText, $transcription);

                    $candidates[] = $this->makeCandidate(
                        'openai_vision',
                        $transcription,
                        $answers,
                        30,
                        $sheet['items'] ?? []
                    );
                } catch (\Throwable $exception) {
                    $errors[] = 'OpenAI vision: '.$exception->getMessage();

                    try {
                        $transcription = GujaratiTextNormalizer::cleanOcr(
                            $this->openAiVisionOcr->extractTextFromImages(array_slice($primaryOcrPaths, 0, 4))
                        );
                        $answers = AnswerSheetParser::parse($transcription);
                        $candidates[] = $this->makeCandidate('openai_vision', $transcription, $answers, 15);
                    } catch (\Throwable $fallbackException) {
                        $errors[] = 'OpenAI text OCR: '.$fallbackException->getMessage();
                    }
                }
            } else {
                $errors[] = 'OPENAI_API_KEY is empty on the server. Add it to .env and run: php artisan config:clear';
            }

            if ($this->googleVision->isConfigured() && ! $this->hasOpenAiCandidate($candidates)) {
                try {
                    $transcription = GujaratiTextNormalizer::cleanOcr(
                        $this->extractGoogleVisionText($primaryOcrPaths)
                    );
                    $answers = AnswerSheetParser::parse($transcription);

                    $candidates[] = $this->makeCandidate('google_vision', $transcription, $answers, 0);
                } catch (\Throwable $exception) {
                    $errors[] = 'Google Vision: '.$exception->getMessage();
                }
            }
        }

        $this->imageEnhancer->cleanup($ocrPaths, $imagePaths);
        $this->cleanupTempImages($imagePaths, $absolutePath);

        // Skip slow PDF-text pass when vision already found answers.
        if (! $this->hasOpenAiCandidate($candidates)) {
            try {
                $transcription = GujaratiTextNormalizer::cleanOcr($this->pdfTextExtractor->extract($absolutePath));

                if ($transcription !== '' && TextSanitizer::isReadableText($transcription)) {
                    $answers = AnswerSheetParser::parse($transcription);

                    $candidates[] = $this->makeCandidate('pdf_text', $transcription, $answers, 0);
                }
            } catch (\Throwable $exception) {
                $errors[] = 'PDF text: '.$exception->getMessage();
            }
        }

        if ($candidates === []) {
            throw new RuntimeException($this->buildExtractionFailureMessage($errors));
        }

        $best = $this->selectBestCandidate($candidates);

        return [
            'method' => $best['method'],
            'text' => $best['text'],
            'answers' => $best['answers'],
            'items' => $best['items'] ?? [],
            'preview_image' => $previewImage,
            'preview_images' => $previewImages,
            'candidates' => $candidates,
        ];
    }

    private function persistPreviewImage(string $path): string
    {
        return $this->persistDurablePreview($path);
    }

    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    private function persistDurablePreviews(array $paths): array
    {
        $saved = [];
        $seenHashes = [];

        foreach ($paths as $path) {
            $durable = $this->persistDurablePreview($path);
            $ext = strtolower(pathinfo($durable, PATHINFO_EXTENSION));
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) || ! is_file($durable)) {
                continue;
            }

            $hash = @md5_file($durable) ?: ($durable.'|'.filesize($durable));
            if (isset($seenHashes[$hash])) {
                continue;
            }
            $seenHashes[$hash] = true;
            $saved[] = $durable;
        }

        return array_values($saved);
    }

    /**
     * Copy upload/raster page into storage/app/public so corrected-sheet rendering
     * never depends on a temp file that OCR cleanup may remove.
     */
    private function persistDurablePreview(string $path): string
    {
        if (! is_file($path)) {
            return $path;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            // Do not pretend a PDF is a JPG — GD cannot load it.
            return $path;
        }

        $relative = 'answer-sheet-previews/preview_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
        $destination = Storage::disk('public')->path($relative);
        $directory = dirname($destination);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (@copy($path, $destination) && is_file($destination)) {
            return $destination;
        }

        // Local temp fallback
        $copy = tempnam(sys_get_temp_dir(), 'sheet_preview_');
        if ($copy !== false) {
            $copy .= '.'.$ext;
            if (@copy($path, $copy)) {
                return $copy;
            }
        }

        return $path;
    }

    /**
     * OCR long notebooks in small page batches, then merge all Q items.
     *
     * @param  list<string>  $imagePaths
     * @param  array<int, array{number: int, expected: string}>  $answerHints
     * @return array{transcription: string, answers: array<int, string>, items: array<int, array<string, mixed>>}
     */
    private function extractSheetDataInBatches(array $imagePaths, array $answerHints = []): array
    {
        $mergedAnswers = [];
        $mergedItems = [];
        $transcriptions = [];
        $batchSize = 6;
        $pageOffset = 0;

        foreach (array_chunk($imagePaths, $batchSize) as $batch) {
            $sheet = $this->openAiVisionOcr->extractSheetData($batch, $answerHints);
            if (trim((string) ($sheet['transcription'] ?? '')) !== '') {
                $transcriptions[] = trim((string) $sheet['transcription']);
            }

            foreach ($sheet['answers'] ?? [] as $number => $answer) {
                $number = (int) $number;
                $answer = trim((string) $answer);
                if ($number > 0 && $answer !== '') {
                    $mergedAnswers[$number] = $answer;
                }
            }

            foreach ($sheet['items'] ?? [] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $number = (int) ($item['number'] ?? 0);
                $answer = trim((string) ($item['answer'] ?? ''));
                if ($number < 1 || $answer === '') {
                    continue;
                }
                $localPage = max(0, (int) ($item['page_index'] ?? 0));
                $mergedItems[$number] = [
                    'number' => $number,
                    'question' => trim((string) ($item['question'] ?? '')),
                    'answer' => $answer,
                    'page_index' => $pageOffset + $localPage,
                    'y_percent' => max(5.0, min(95.0, (float) ($item['y_percent'] ?? 50))),
                ];
            }

            $pageOffset += count($batch);
        }

        ksort($mergedAnswers);
        ksort($mergedItems);

        if ($mergedAnswers === [] && $transcriptions === []) {
            throw new RuntimeException('OpenAI vision could not extract answers from the sheet.');
        }

        return [
            'transcription' => implode("\n\n", $transcriptions),
            'answers' => $mergedAnswers,
            'items' => array_values($mergedItems),
        ];
    }

    private function answerHints(EloquentCollection $questions): array
    {
        return PaperTypeHelper::flattenPaperOrder($questions)->map(function ($question, int $index) {
            return [
                'number' => $index + 1,
                'question' => trim((string) ($question->question_text ?? '')),
                'expected' => trim((string) \App\Support\ObjectiveQuestionResolver::answer($question)),
            ];
        })->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $candidates
     */
    private function hasOpenAiCandidate(array $candidates): bool
    {
        foreach ($candidates as $candidate) {
            if (($candidate['method'] ?? '') !== 'openai_vision') {
                continue;
            }
            $answers = $candidate['answers'] ?? [];
            if (is_array($answers) && $answers !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function buildExtractionFailureMessage(array $errors): string
    {
        $lines = [
            'Could not read the uploaded answer sheet.',
        ];

        if (! $this->openAiVisionOcr->isConfigured()) {
            $lines[] = 'OPENAI_API_KEY is not loaded. Add it to .env on the server, then run: php artisan config:clear';
        }

        if (! $this->googleVision->isConfigured()) {
            $lines[] = 'GOOGLE_VISION_API_KEY is also not loaded (optional, but OpenAI is recommended).';
        }

        foreach (array_slice(array_unique($errors), 0, 4) as $error) {
            $lines[] = $error;
        }

        $lines[] = 'Tip: upload a clear JPG/PNG photo if PDF conversion keeps failing.';

        return implode(' ', $lines);
    }

    /**
     * @param  array<int, array{method: string, text: string, answers: array<int, string>, score: int}>  $candidates
     * @return array{method: string, text: string, answers: array<int, string>, score: int}
     */
    private function selectBestCandidate(array $candidates): array
    {
        $withAnswers = array_values(array_filter(
            $candidates,
            fn (array $candidate) => $candidate['answers'] !== []
        ));

        if ($withAnswers !== []) {
            usort($withAnswers, function (array $a, array $b) {
                $openAiPreference = match (true) {
                    $a['method'] === 'openai_vision' && $b['method'] !== 'openai_vision' => -1,
                    $b['method'] === 'openai_vision' && $a['method'] !== 'openai_vision' => 1,
                    default => 0,
                };

                if ($openAiPreference !== 0) {
                    return $openAiPreference;
                }

                return $b['score'] <=> $a['score'];
            });

            return $withAnswers[0];
        }

        foreach ($candidates as $candidate) {
            $lenient = AnswerSheetParser::sanitizeLenient(
                AnswerSheetParser::parseRaw($candidate['text']),
                $candidate['text']
            );

            if ($lenient !== []) {
                return [
                    'method' => $candidate['method'],
                    'text' => $candidate['text'],
                    'answers' => $lenient,
                    'items' => $candidate['items'] ?? [],
                    'score' => AnswerSheetParser::qualityScore($lenient),
                ];
            }
        }

        usort($candidates, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return $candidates[0];
    }

    /**
     * @param  array<int, array<string, mixed>>  $candidates
     */
    private function buildFailureMessage(array $candidates): string
    {
        foreach ($candidates as $candidate) {
            $text = (string) ($candidate['text'] ?? '');
            $markers = AnswerSheetParser::countSequentialMarkers($text);

            if ($markers !== null && $markers > 0) {
                return "Found Q1-Q{$markers} on your sheet but no readable answers below them. "
                    .'Write each answer on the line below Q1 -, Q2 -, Q3 - (in Gujarati).';
            }
        }

        return 'Could not read answers. Use format: Q1. question? then Ans- your answer (Gujarati is OK).';
    }

    /**
     * @param  array<int, string>  $answers
     * @param  array<int, array{number?: int, question?: string, answer?: string}>  $items
     * @return array{method: string, text: string, answers: array<int, string>, items: array<int, array{number: int, question: string, answer: string}>, score: int}
     */
    private function makeCandidate(string $method, string $text, array $answers, int $bonus = 0, array $items = []): array
    {
        $strict = AnswerSheetParser::sanitize($answers, $text);
        $fromText = AnswerSheetParser::parse($text);
        $lenient = AnswerSheetParser::sanitizeLenient(AnswerSheetParser::parseRaw($text), $text);
        $final = AnswerSheetParser::pickBest(
            AnswerSheetParser::pickBest($strict, $fromText, $text),
            $lenient,
            $text
        );

        $score = AnswerSheetParser::qualityScore($final) + $bonus;
        $markers = AnswerSheetParser::countSequentialMarkers($text);

        if ($markers !== null && count($final) < $markers) {
            $score -= ($markers - count($final)) * 35;
        }

        if ($method === 'google_vision') {
            $score -= 40;
        }

        $normalizedItems = [];
        foreach ($items as $item) {
            $number = (int) ($item['number'] ?? 0);
            $answer = trim((string) ($item['answer'] ?? ($final[$number] ?? '')));
            if ($number < 1 || $answer === '') {
                continue;
            }
            $normalizedItems[$number] = [
                'number' => $number,
                'question' => trim((string) ($item['question'] ?? '')),
                'answer' => $answer,
            ];
        }

        if ($normalizedItems === []) {
            foreach ($final as $number => $answer) {
                $normalizedItems[(int) $number] = [
                    'number' => (int) $number,
                    'question' => '',
                    'answer' => (string) $answer,
                ];
            }
        }

        ksort($normalizedItems);

        return [
            'method' => $method,
            'text' => $text,
            'answers' => $final,
            'items' => array_values($normalizedItems),
            'score' => $score,
        ];
    }

    /**
     * @param  array<int, string>  $imagePaths
     * @return array<int, string>
     */
    private function extractGoogleVisionText(array $imagePaths): string
    {
        $parts = [];

        foreach ($imagePaths as $imagePath) {
            $pageText = trim($this->googleVision->extractTextFromImage($imagePath));

            if ($pageText !== '') {
                $parts[] = $pageText;
            }
        }

        $text = trim(implode("\n\n", $parts));

        if ($text === '' || ! TextSanitizer::isReadableText($text)) {
            throw new RuntimeException('Google Vision could not read text from this file.');
        }

        return $text;
    }

    /**
     * @param  array<int, string>  $imagePaths
     */
    private function cleanupTempImages(array $imagePaths, string $originalPath): void
    {
        foreach ($imagePaths as $imagePath) {
            if ($imagePath === $originalPath) {
                continue;
            }

            if (str_starts_with($imagePath, sys_get_temp_dir())) {
                @unlink($imagePath);
            }
        }
    }
}
