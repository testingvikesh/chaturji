<?php

namespace App\Console\Commands;

use App\Models\ChapterContent;
use App\Models\ChapterQuestion;
use App\Models\ExamQuestion;
use App\Models\HomeworkQuestion;
use App\Services\MaterialJsonImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FixChapterQuestionOptionsCommand extends Command
{
    protected $signature = 'homework:fix-question-options {--chapter-content=} {--force : Rebuild options even if already present}';

    protected $description = 'Backfill 4 options for fill_blank and one_word questions';

    public function handle(MaterialJsonImporter $importer): int
    {
        $query = ChapterContent::query()->orderBy('id');
        if ($this->option('chapter-content')) {
            $query->where('id', (int) $this->option('chapter-content'));
        }

        $updated = 0;
        $checked = 0;
        $force = (bool) $this->option('force');

        foreach ($query->cursor() as $content) {
            $byText = $this->optionsFromJson($importer, $content);

            $questions = ChapterQuestion::query()
                ->where('chapter_content_id', $content->id)
                ->whereIn('question_type', ['fill_blank', 'one_word'])
                ->orderBy('sort_order')
                ->get();

            // Also build from DB sibling answers when JSON has none
            $seed = $questions->map(fn (ChapterQuestion $q) => [
                'question_type' => $q->question_type,
                'question_text' => $q->question_text,
                'options' => $q->options,
                'answer' => $q->answer,
                'metadata' => $q->metadata,
                'sort_order' => $q->sort_order,
            ])->all();
            $seed = $importer->ensureChoiceOptions($seed);
            foreach ($seed as $row) {
                $key = $row['question_type'].'|'.$this->normalizeText((string) ($row['question_text'] ?? ''));
                if (! empty($row['options'])) {
                    $byText[$key] = $row['options'];
                }
            }

            foreach ($questions as $question) {
                $checked++;
                $existingCount = is_array($question->options) ? count($question->options) : 0;
                if (! $force && $existingCount >= 4) {
                    continue;
                }

                $key = $question->question_type.'|'.$this->normalizeText((string) $question->question_text);
                $options = $byText[$key] ?? null;
                if (! is_array($options) || count($options) < 2) {
                    continue;
                }

                $question->options = array_values($options);
                $question->save();
                $updated++;

                foreach ([HomeworkQuestion::class, ExamQuestion::class] as $modelClass) {
                    $modelClass::query()
                        ->where('chapter_question_id', $question->id)
                        ->get()
                        ->each(function ($paperQuestion) use ($options, $force) {
                            $count = is_array($paperQuestion->options) ? count($paperQuestion->options) : 0;
                            if (! $force && $count >= 4) {
                                return;
                            }
                            $paperQuestion->options = array_values($options);
                            $paperQuestion->save();
                        });
                }
            }
        }

        $this->info("Checked {$checked} fill_blank/one_word row(s). Updated options on {$updated}.");

        return self::SUCCESS;
    }

    /**
     * @return array<string, list<string>>
     */
    private function optionsFromJson(MaterialJsonImporter $importer, ChapterContent $content): array
    {
        $json = $this->loadJson($content);
        if ($json === null) {
            return [];
        }

        try {
            $parsed = $importer->parse($json, $content->title ?? 'Chapter', $content->language ?? 'english');
        } catch (\Throwable $e) {
            $this->warn("JSON parse skip chapter_content #{$content->id}: ".$e->getMessage());

            return [];
        }

        $byText = [];
        foreach ($parsed['questions'] as $row) {
            $type = (string) ($row['question_type'] ?? '');
            if (! in_array($type, ['fill_blank', 'one_word'], true) || empty($row['options'])) {
                continue;
            }
            $key = $type.'|'.$this->normalizeText((string) ($row['question_text'] ?? ''));
            $byText[$key] = array_values($row['options']);
        }

        return $byText;
    }

    private function loadJson(ChapterContent $content): ?array
    {
        if ($content->source_path && Storage::disk('local')->exists($content->source_path)) {
            $raw = Storage::disk('local')->get($content->source_path);
            $json = json_decode((string) $raw, true);
            if (is_array($json)) {
                return $json;
            }
        }

        $rawText = (string) ($content->raw_text ?? '');
        if ($rawText !== '') {
            $json = json_decode($rawText, true);
            if (is_array($json)) {
                return $json;
            }
        }

        return null;
    }

    private function normalizeText(string $text): string
    {
        $text = preg_replace('/\s+/u', '', $text) ?? $text;

        return mb_strtolower(trim($text));
    }
}
