<?php

namespace App\Support;

use App\Models\ChapterQuestion;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Homework;
use App\Models\HomeworkQuestion;

class QuestionFormHelper
{
    public static function types(): array
    {
        return [
            'mcq' => 'MCQ',
            'fill_blank' => 'Fill in the Blank',
            'true_false' => 'True / False',
            'one_mark' => '1 Mark Question',
            'two_marks' => '2 Marks Question',
            'three_marks' => '3 Marks Question',
            'five_marks' => '5 Marks Question',
            'short_answer' => 'Short Answer',
            'long_answer' => 'Long Answer',
            'one_word' => 'One Word',
            'match' => 'Match',
        ];
    }

    public static function syncExamQuestions(Exam $exam, array $questions): int
    {
        $exam->questions()->delete();

        $totalMarks = 0;
        $sortOrder = 0;

        foreach ($questions as $row) {
            if (empty(trim($row['question_text'] ?? ''))) {
                continue;
            }

            $marks = (int) ($row['marks'] ?? 1);
            $totalMarks += $marks;

            ExamQuestion::create([
                'exam_id' => $exam->id,
                'question_type' => $row['question_type'] ?? 'short_answer',
                'question_text' => $row['question_text'],
                'options' => self::parseOptions($row['options'] ?? null, $row['question_type'] ?? ''),
                'answer' => $row['answer'] ?? null,
                'marks' => $marks,
                'sort_order' => $sortOrder++,
            ]);
        }

        return $totalMarks;
    }

    public static function syncHomeworkQuestions(Homework $homework, array $questions): void
    {
        $homework->questions()->delete();

        $sortOrder = 0;

        foreach ($questions as $row) {
            if (empty(trim($row['question_text'] ?? ''))) {
                continue;
            }

            HomeworkQuestion::create([
                'homework_id' => $homework->id,
                'question_type' => $row['question_type'] ?? 'short_answer',
                'question_text' => $row['question_text'],
                'options' => self::parseOptions($row['options'] ?? null, $row['question_type'] ?? ''),
                'answer' => $row['answer'] ?? null,
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    public static function optionsToText(?array $options): string
    {
        if (! $options) {
            return '';
        }

        if (isset($options['column_a']) || isset($options['column_b'])) {
            $lines = ['Column A:'];
            foreach ($options['column_a'] ?? [] as $item) {
                $lines[] = $item;
            }
            $lines[] = 'Column B:';
            foreach ($options['column_b'] ?? [] as $item) {
                $lines[] = $item;
            }

            return implode("\n", $lines);
        }

        $lines = [];
        foreach ($options as $key => $value) {
            if (is_string($value)) {
                $lines[] = is_string($key) && strlen($key) === 1 ? "{$key}. {$value}" : $value;
            }
        }

        return implode("\n", $lines);
    }

    private static function parseOptions(?string $raw, string $type): ?array
    {
        if (! $raw) {
            return null;
        }

        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw))));

        if ($lines === []) {
            return null;
        }

        if ($type === 'match') {
            $columnA = [];
            $columnB = [];
            $current = null;

            foreach ($lines as $line) {
                if (stripos($line, 'column a') === 0) {
                    $current = 'a';
                    continue;
                }
                if (stripos($line, 'column b') === 0) {
                    $current = 'b';
                    continue;
                }

                if ($current === 'a') {
                    $columnA[] = $line;
                } elseif ($current === 'b') {
                    $columnB[] = $line;
                }
            }

            return ['column_a' => $columnA, 'column_b' => $columnB];
        }

        $options = [];
        foreach ($lines as $index => $line) {
            if (preg_match('/^([A-Da-d])[.)]\s*(.+)$/', $line, $matches)) {
                $options[$matches[1]] = $matches[2];
            } else {
                $options[chr(65 + $index)] = $line;
            }
        }

        return $options;
    }
}
