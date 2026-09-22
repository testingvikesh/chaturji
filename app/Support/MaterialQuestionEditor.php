<?php

namespace App\Support;

use App\Models\Material;
use App\Models\MaterialQuestionEditLog;
use App\Models\MaterialTopic;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MaterialQuestionEditor
{
    public static function questionKey(string $type, string $text): string
    {
        return hash('sha256', strtolower(trim($type)).'|'.ChapterMaterialHelper::normalizeQuestionKey($text));
    }

    /**
     * @param  Collection<int, object>  $questions  Raw questions (before edits)
     * @return Collection<int, object>
     */
    public static function applyEdits(Collection $questions, ?array $edits): Collection
    {
        if (! is_array($edits) || $edits === []) {
            return $questions->map(function ($question) {
                $type = (string) ($question->question_type ?? '');
                $text = (string) ($question->question_text ?? '');
                $question->setAttribute('edit_key', self::questionKey($type, $text));

                return $question;
            });
        }

        return $questions->map(function ($question) use ($edits) {
            $type = (string) ($question->question_type ?? '');
            $text = (string) ($question->question_text ?? '');
            $key = self::questionKey($type, $text);
            $question->setAttribute('edit_key', $key);

            $edit = $edits[$key] ?? null;
            if (! is_array($edit)) {
                return $question;
            }

            if (array_key_exists('question_text', $edit)) {
                $question->question_text = (string) $edit['question_text'];
            }
            if (array_key_exists('answer', $edit)) {
                $question->answer = $edit['answer'];
            }
            if (array_key_exists('options', $edit)) {
                $question->options = $edit['options'];
            }

            return $question;
        });
    }

    /**
     * @param  array{question_text:string,answer:?string,options:?array}  $payload
     */
    public static function update(
        User $teacher,
        MaterialTopic $materialTopic,
        string $questionKey,
        array $payload
    ): void {
        $material = $materialTopic->relationLoaded('material')
            ? $materialTopic->material
            : $materialTopic->material()->first();

        $rawQuestions = MaterialTopicReader::rawUniqueQuestions($materialTopic);
        $match = $rawQuestions->first(function ($question) use ($questionKey) {
            return self::questionKey((string) $question->question_type, (string) $question->question_text) === $questionKey;
        });

        abort_unless($match, 404, 'Question not found in this material.');

        $type = (string) ($match->question_type ?? 'short_answer');
        $oldText = (string) ($match->question_text ?? '');
        $oldAnswer = is_array($match->answer ?? null)
            ? json_encode($match->answer, JSON_UNESCAPED_UNICODE)
            : (string) ($match->answer ?? '');
        $oldOptions = is_array($match->options ?? null) ? $match->options : null;

        $newText = TextSanitizer::forDatabase($payload['question_text']) ?? '';
        $newAnswer = TextSanitizer::forDatabase($payload['answer'] ?? null);
        $newOptions = $payload['options'] ?? null;

        DB::transaction(function () use (
            $teacher,
            $materialTopic,
            $material,
            $questionKey,
            $type,
            $oldText,
            $oldAnswer,
            $oldOptions,
            $newText,
            $newAnswer,
            $newOptions
        ) {
            $edits = is_array($materialTopic->question_edits) ? $materialTopic->question_edits : [];
            $previous = is_array($edits[$questionKey] ?? null) ? $edits[$questionKey] : null;

            $edits[$questionKey] = [
                'question_text' => $newText,
                'answer' => $newAnswer,
                'options' => $newOptions,
                'question_type' => $type,
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
                'question_key' => $questionKey,
                'question_type' => $type,
                'old_question_text' => $previous['question_text'] ?? $oldText,
                'new_question_text' => $newText,
                'old_answer' => $previous['answer'] ?? $oldAnswer,
                'new_answer' => $newAnswer,
                'old_options' => $previous['options'] ?? $oldOptions,
                'new_options' => $newOptions,
                'subject_name' => $material?->subject,
                'chapter_name' => method_exists($material, 'displayChapterName') ? $material->displayChapterName() : ($material?->chapter_name ?? null),
                'topic_title' => $materialTopic->displayName(),
                'medium' => Material::normalizeMedium($material?->medium) ?: $material?->medium,
            ]);
        });

        self::forgetReaderCache($materialTopic);
    }

    public static function forgetReaderCache(MaterialTopic $materialTopic): void
    {
        $version = optional($materialTopic->updated_at)?->getTimestamp() ?? 0;
        foreach (range(max(0, $version - 5), $version + 2) as $stamp) {
            try {
                Cache::forget('material-topic-reader:v2:'.$materialTopic->id.':'.$stamp);
                Cache::forget('material-topic-reader:v3:'.$materialTopic->id.':'.$stamp);
            } catch (\Throwable $e) {
                // ignore cache backend issues
            }
        }
    }
}
