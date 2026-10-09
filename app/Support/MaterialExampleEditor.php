<?php

namespace App\Support;

use App\Models\Material;
use App\Models\MaterialQuestionEditLog;
use App\Models\MaterialTopic;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MaterialExampleEditor
{
    public static function exampleKey(int $topicId, string $itemId, string $questionText): string
    {
        return hash(
            'sha256',
            'worked_example|'.$topicId.'|'.$itemId.'|'.ChapterMaterialHelper::normalizeQuestionKey($questionText)
        );
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>|null  $edits
     * @return array<string, mixed>
     */
    public static function applyItemEdits(array $item, ?array $edits, int $topicId, string $itemId): array
    {
        $question = trim((string) ($item['question_text'] ?? ''));
        $key = self::exampleKey($topicId, $itemId, $question);
        $item['_edit_key'] = $key;

        $edit = is_array($edits) ? ($edits[$key] ?? null) : null;
        if (! is_array($edit)) {
            return $item;
        }

        foreach (['question_text', 'find', 'given', 'to_prove', 'formula', 'concept', 'solution_overview', 'construction', 'shortcut', 'conclusion', 'final_answer'] as $field) {
            if (array_key_exists($field, $edit)) {
                $item[$field] = $edit[$field];
            }
        }

        if (array_key_exists('solution', $edit) && is_array($edit['solution'])) {
            $item['solution'] = $edit['solution'];
        }

        return $item;
    }

    /**
     * @param  array{
     *     question_text?:string,
     *     find?:string,
     *     given?:string,
     *     final_answer?:string,
     *     solution_overview?:string,
     *     solution_text?:string
     * }  $payload
     */
    public static function update(
        User $teacher,
        MaterialTopic $materialTopic,
        string $exampleKey,
        array $payload
    ): void {
        $material = $materialTopic->relationLoaded('material')
            ? $materialTopic->material
            : $materialTopic->material()->first();

        $examples = MaterialWorkedExamples::rawFromTopic($materialTopic);
        $match = $examples->first(fn (array $row) => ($row['edit_key'] ?? '') === $exampleKey);
        abort_unless($match, 404, 'Example not found in this material.');

        $item = is_array($match['item'] ?? null) ? $match['item'] : [];
        $oldQuestion = trim((string) ($item['question_text'] ?? ''));
        $oldAnswer = trim((string) ($item['final_answer'] ?? ''));

        $newQuestion = TextSanitizer::forDatabase($payload['question_text'] ?? '') ?? '';
        $newFind = TextSanitizer::forDatabase($payload['find'] ?? null);
        $newGiven = TextSanitizer::forDatabase($payload['given'] ?? null);
        $newAnswer = TextSanitizer::forDatabase($payload['final_answer'] ?? null);
        $newOverview = TextSanitizer::forDatabase($payload['solution_overview'] ?? null);
        $newSolution = self::parseSolutionText((string) ($payload['solution_text'] ?? ''));

        DB::transaction(function () use (
            $teacher,
            $materialTopic,
            $material,
            $exampleKey,
            $oldQuestion,
            $oldAnswer,
            $newQuestion,
            $newFind,
            $newGiven,
            $newAnswer,
            $newOverview,
            $newSolution
        ) {
            $edits = is_array($materialTopic->question_edits) ? $materialTopic->question_edits : [];
            $previous = is_array($edits[$exampleKey] ?? null) ? $edits[$exampleKey] : null;

            $edits[$exampleKey] = [
                'question_type' => 'worked_example',
                'question_text' => $newQuestion,
                'find' => $newFind,
                'given' => $newGiven,
                'final_answer' => $newAnswer,
                'solution_overview' => $newOverview,
                'solution' => $newSolution,
                'answer' => $newAnswer,
                'updated_at' => now()->toIso8601String(),
                'updated_by' => $teacher->id,
            ];

            $materialTopic->forceFill([
                'question_edits' => $edits,
                'updated_at' => now(),
            ])->save();

            MaterialQuestionEditLog::query()->create([
                'teacher_id' => $teacher->id,
                'material_topic_id' => $materialTopic->id,
                'material_id' => $material?->id,
                'question_key' => $exampleKey,
                'question_type' => 'worked_example',
                'old_question_text' => $previous['question_text'] ?? $oldQuestion,
                'new_question_text' => $newQuestion,
                'old_answer' => $previous['final_answer'] ?? $previous['answer'] ?? $oldAnswer,
                'new_answer' => $newAnswer,
                'old_options' => null,
                'new_options' => null,
                'subject_name' => $material?->subject,
                'chapter_name' => method_exists($material, 'displayChapterName') ? $material->displayChapterName() : ($material?->chapter_name ?? null),
                'topic_title' => $materialTopic->displayName(),
                'medium' => Material::normalizeMedium($material?->medium) ?: $material?->medium,
            ]);
        });

        ActivityLogger::log(
            'teacher.example.update',
            'Edited worked example on '.$materialTopic->displayName(),
            $materialTopic,
            [
                'example_key' => $exampleKey,
                'topic_title' => $materialTopic->displayName(),
            ],
            $teacher
        );

        MaterialQuestionEditor::forgetReaderCache($materialTopic);
        foreach (range(0, 3) as $stamp) {
            try {
                Cache::forget('material-topic-reader:v2:'.$materialTopic->id.':'.((optional($materialTopic->updated_at)?->getTimestamp() ?? 0) - $stamp));
            } catch (\Throwable) {
                // ignore
            }
        }
    }

    /**
     * @return list<array{step:int,equation:string,note:string,final:bool}>
     */
    public static function parseSolutionText(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $steps = [];
        $n = 1;
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }
            $equation = $line;
            $note = '';
            if (str_contains($line, '::')) {
                [$equation, $note] = array_map('trim', explode('::', $line, 2));
            }
            $steps[] = [
                'step' => $n,
                'equation' => $equation,
                'note' => $note,
                'final' => false,
            ];
            $n++;
        }

        if ($steps !== []) {
            $steps[array_key_last($steps)]['final'] = true;
        }

        return $steps;
    }

    /**
     * @param  list<array<string, mixed>>|array<int, mixed>  $steps
     */
    public static function solutionToText(array $steps): string
    {
        $lines = [];
        foreach ($steps as $step) {
            if (! is_array($step)) {
                $lines[] = trim((string) $step);
                continue;
            }
            $equation = trim((string) ($step['equation'] ?? ''));
            $note = trim((string) ($step['note'] ?? ''));
            if ($equation === '' && $note === '') {
                continue;
            }
            $lines[] = $note !== '' ? $equation.' :: '.$note : $equation;
        }

        return implode("\n", $lines);
    }
}
