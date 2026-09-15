<?php

namespace App\Support;

class ExamPaperPromptBuilder
{
    /**
     * Build the dynamic master exam-paper prompt with runtime context.
     *
     * @param  array{
     *     total_marks?: int|string,
     *     standard?: string,
     *     subject?: string,
     *     exam_date?: string,
     *     class_label?: string,
     *     syllabus_outline?: string,
     *     chapter_weightage?: string,
     *     difficulty_easy?: int|string,
     *     difficulty_medium?: int|string,
     *     difficulty_hard?: int|string,
     *     set_label?: string,
     *     available_questions_json?: string,
     *     previous_exam_summary?: string
     * }  $context
     */
    public static function build(array $context = []): string
    {
        $vars = array_merge([
            'total_marks' => '',
            'standard' => '',
            'subject' => '',
            'exam_date' => '',
            'class_label' => '',
            'syllabus_outline' => '(not provided)',
            'chapter_weightage' => '(balanced / not provided)',
            'difficulty_easy' => 30,
            'difficulty_medium' => 50,
            'difficulty_hard' => 20,
            'set_label' => 'Set A',
            'available_questions_json' => '[]',
            'previous_exam_summary' => '(none)',
        ], $context);

        return AiPrompt::get('exam_paper_generator', $vars);
    }
}
