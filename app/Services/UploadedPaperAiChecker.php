<?php

namespace App\Services;

use App\Models\ExamAnswerSheet;
use App\Models\ExamPaperUpload;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class UploadedPaperAiChecker
{
    public function __construct(
        private readonly PdfPageRasterizer $rasterizer,
    ) {}

    /**
     * @return array{total_awarded: float, remarks: string, questions: array<int, array<string, mixed>>}
     */
    public function check(ExamPaperUpload $paper, ExamAnswerSheet $sheet): array
    {
        $key = config('services.openai.key');

        if (! filled($key)) {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $paper->loadMissing('standard');
        $paperPath = Storage::disk('local')->path($paper->paper_path);
        $sheetPath = Storage::disk('local')->path($sheet->file_path);

        if (! is_file($paperPath) || ! is_file($sheetPath)) {
            throw new RuntimeException('Question paper or answer sheet file was not found.');
        }

        $cleanup = [];

        try {
            $paperParts = $this->documentParts($paperPath, 'QUESTION PAPER', 4, $cleanup);
            $sheetParts = $this->documentParts($sheetPath, 'STUDENT ANSWER SHEET', 6, $cleanup);
            $result = $this->requestCheck($key, $paper, $paperParts, $sheetParts);
        } finally {
            $this->cleanup($cleanup);
        }

        return $this->normalize($result, (float) $paper->total_marks);
    }

    /**
     * @param  array<int, string>  $cleanup
     * @return array<int, string>
     */
    private function pageImages(string $path, int $limit, array &$cleanup): array
    {
        $images = $this->rasterizer->toImagePaths($path, 130);
        $selected = [];

        foreach (array_slice($images, 0, $limit) as $image) {
            $prepared = $this->shrink($image);
            $selected[] = $prepared;
            if ($prepared !== $image) {
                $cleanup[] = $prepared;
            }
            if ($image !== $path && str_starts_with($image, sys_get_temp_dir())) {
                $cleanup[] = $image;
            }
        }

        return $selected;
    }

    /**
     * Page images when the server can make them. A PDF is sent as-is when exec() or Imagick is unavailable.
     *
     * @param  array<int, string>  $cleanup
     * @return array<int, array<string, mixed>>
     */
    private function documentParts(string $path, string $label, int $limit, array &$cleanup): array
    {
        try {
            $images = $this->pageImages($path, $limit, $cleanup);
        } catch (Throwable) {
            $images = [];
        }

        if ($images !== []) {
            $parts = [];
            foreach ($images as $index => $image) {
                $parts[] = ['type' => 'text', 'text' => $label.' page '.($index + 1).'.'];
                $parts[] = $this->imagePart($image);
            }

            return $parts;
        }

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'pdf') {
            throw new RuntimeException('Could not read the '.$label.' file.');
        }

        $bytes = file_get_contents($path);
        if ($bytes === false || $bytes === '') {
            throw new RuntimeException('Could not read the '.$label.' file.');
        }

        return [
            ['type' => 'text', 'text' => $label.' PDF. Read every page.'],
            [
                'type' => 'file',
                'file' => [
                    'filename' => $label.'.pdf',
                    'file_data' => 'data:application/pdf;base64,'.base64_encode($bytes),
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $paperParts
     * @param  array<int, array<string, mixed>>  $sheetParts
     * @return array<string, mixed>
     */
    private function requestCheck(string $key, ExamPaperUpload $paper, array $paperParts, array $sheetParts): array
    {
        $language = $paper->standard?->mediumLabel() ?: 'English';
        $content = [[
            'type' => 'text',
            'text' => $this->prompt($paper, $language),
        ]];

        array_push($content, ...$paperParts, ...$sheetParts);

        $response = Http::withToken($key)
            ->timeout(120)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.vision_model', 'gpt-5.6-luna'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'user', 'content' => $content],
                ],
            ]);

        if ($response->status() === 400) {
            $response = Http::withToken($key)
                ->timeout(120)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.vision_model', 'gpt-5.6-luna'),
                    'messages' => [
                        ['role' => 'user', 'content' => $content],
                    ],
                ]);
        }

        if (! $response->successful()) {
            throw new RuntimeException('AI paper check failed.');
        }

        $raw = data_get($response->json(), 'choices.0.message.content', '{}');
        $payload = is_array($raw) ? $raw : json_decode((string) $raw, true);

        if (! is_array($payload)) {
            throw new RuntimeException('AI paper check returned invalid JSON.');
        }

        return $payload;
    }

    private function prompt(ExamPaperUpload $paper, string $language): string
    {
        $standard = trim(($paper->standard?->name ?: '').' '.$language);

        return <<<PROMPT
You are a school exam checker. The question paper comes first. One student's handwritten answer sheet comes after it. Each may be page images or a PDF.

Paper: {$paper->title}
Class: {$standard}
Subject: {$paper->subject_name}
Total marks: {$paper->total_marks}

Check every question you can read.
- Use the marks printed on the question paper. If a question has no printed marks, share the remaining marks fairly. The sum of max_marks must equal {$paper->total_marks}.
- Read Gujarati and English handwriting. Copy only what the student actually wrote into student_answer. If a question is blank, student_answer is empty and awarded is 0.
- Objective questions (MCQ, true/false, fill the blank, match, one word): full marks only for the correct option or word, otherwise 0.
- Subjective questions: give partial marks for each correct point. A vague line does not get full marks.
- correct_answer is the expected answer for that question, from the paper's answer key when one is printed, otherwise from the class syllabus.
- awarded is a number from 0 to max_marks. Halves are allowed.
- covered is a list of the exact points the student wrote correctly. Use an empty list when nothing is correct.
- missing is a list of the important points the answer still needs. Use an empty list when the answer is complete.
- comment is one encouraging teacher sentence in {$language}. Name the point that earned the marks. Full marks can say Excellent. Partial marks can say Nice try.
- tip is one extra point the student should add next time. Leave tip empty when the answer already has full marks.
- Do not skip a question that is on the paper. Do not add a question that is not on the paper.

Return JSON only:
{"remarks":"two sentence overall comment in {$language}","questions":[{"number":"1","question":"...","max_marks":2,"awarded":1,"student_answer":"...","correct_answer":"...","covered":["point the student wrote"],"missing":["point still needed"],"comment":"Nice try. The answer names the main idea.","tip":"Also add one example."}]}
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{total_awarded: float, remarks: string, questions: array<int, array<string, mixed>>}
     */
    private function normalize(array $payload, float $paperTotal): array
    {
        $questions = [];

        foreach ($payload['questions'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $max = $this->marks($row['max_marks'] ?? 0);
            $awarded = min($max, $this->marks($row['awarded'] ?? $row['marks'] ?? 0));
            $studentAnswer = trim((string) ($row['student_answer'] ?? ''));

            if ($studentAnswer === '' || preg_match('/^(blank|not written|no answer|unanswered)$/i', $studentAnswer)) {
                $studentAnswer = '';
                $awarded = 0;
            }

            if ($max <= 0) {
                continue;
            }

            $questions[] = [
                'number' => trim((string) ($row['number'] ?? count($questions) + 1)),
                'question' => trim((string) ($row['question'] ?? '')),
                'max_marks' => $max,
                'awarded' => $awarded,
                'student_answer' => $studentAnswer,
                'correct_answer' => trim((string) ($row['correct_answer'] ?? '')),
                'covered' => $studentAnswer === '' ? [] : $this->lines($row['covered'] ?? $row['key_points_covered'] ?? []),
                'missing' => $this->lines($row['missing'] ?? $row['key_points_missing'] ?? []),
                'comment' => trim((string) ($row['comment'] ?? '')),
                'tip' => $awarded >= $max ? '' : trim((string) ($row['tip'] ?? $row['also'] ?? '')),
            ];
        }

        if ($questions === []) {
            throw new RuntimeException('AI could not find questions to check on this paper.');
        }

        $sum = array_sum(array_column($questions, 'awarded'));

        if ($paperTotal > 0 && $sum > $paperTotal) {
            $factor = $paperTotal / $sum;
            foreach ($questions as $index => $question) {
                $questions[$index]['awarded'] = $this->marks($question['awarded'] * $factor);
            }
            $sum = array_sum(array_column($questions, 'awarded'));
            $overflow = round($sum - $paperTotal, 2);
            if ($overflow > 0) {
                foreach ($questions as $index => $question) {
                    $cut = min($question['awarded'], $overflow);
                    $questions[$index]['awarded'] = round($question['awarded'] - $cut, 2);
                    $overflow = round($overflow - $cut, 2);
                    if ($overflow <= 0) {
                        break;
                    }
                }
            }
        }

        $total = round(array_sum(array_column($questions, 'awarded')), 2);
        $percentage = $paperTotal > 0 ? (int) round(($total / $paperTotal) * 100) : 0;

        return [
            'total_awarded' => $total,
            'total_marks' => $paperTotal,
            'percentage' => $percentage,
            'grade' => $this->grade($percentage),
            'remarks' => mb_substr(trim((string) ($payload['remarks'] ?? '')), 0, 1000),
            'questions' => $questions,
        ];
    }

    private function grade(int $percentage): string
    {
        return match (true) {
            $percentage >= 80 => 'A',
            $percentage >= 60 => 'B',
            $percentage >= 40 => 'C',
            default => 'D',
        };
    }

    /**
     * @return array<int, string>
     */
    private function lines(mixed $value): array
    {
        $items = is_array($value) ? $value : preg_split('/\r\n|\r|\n|;/u', (string) $value);

        return array_values(array_filter(array_map(
            fn (mixed $line) => trim((string) $line),
            $items ?: []
        )));
    }

    private function marks(mixed $value): float
    {
        $number = round(((float) $value) * 2) / 2;

        return max(0, $number);
    }

    /**
     * @return array{type: string, image_url: array{url: string, detail: string}}
     */
    private function imagePart(string $path): array
    {
        $bytes = file_get_contents($path);

        if ($bytes === false) {
            throw new RuntimeException('Could not read a page image for AI checking.');
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/png',
        };

        return [
            'type' => 'image_url',
            'image_url' => [
                'url' => 'data:'.$mime.';base64,'.base64_encode($bytes),
                'detail' => 'high',
            ],
        ];
    }

    private function shrink(string $path): string
    {
        if (! function_exists('imagecreatefromstring')) {
            return $path;
        }

        $info = @getimagesize($path);
        if (! is_array($info) || ($info[0] ?? 0) <= 1600) {
            return $path;
        }

        $bytes = file_get_contents($path);
        if ($bytes === false) {
            return $path;
        }

        $source = @imagecreatefromstring($bytes);
        if ($source === false) {
            return $path;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $targetWidth = 1600;
        $targetHeight = (int) max(1, round($height * ($targetWidth / $width)));
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($canvas === false) {
            imagedestroy($source);

            return $path;
        }

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        $temp = tempnam(sys_get_temp_dir(), 'ai_sheet_');
        imagedestroy($source);

        if ($temp === false) {
            imagedestroy($canvas);

            return $path;
        }

        $jpeg = $temp.'.jpg';
        imagejpeg($canvas, $jpeg, 82);
        imagedestroy($canvas);
        @unlink($temp);

        return is_file($jpeg) ? $jpeg : $path;
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function cleanup(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            if (is_file($path) && str_starts_with($path, sys_get_temp_dir())) {
                @unlink($path);
            }
        }
    }
}
