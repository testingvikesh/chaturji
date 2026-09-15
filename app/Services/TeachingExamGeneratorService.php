<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamLog;
use App\Models\ExamQuestion;
use App\Models\User;
use App\Support\ExamPaperPromptBuilder;
use App\Support\ExamPaperWeightage;
use App\Support\PaperTypeHelper;
use App\Support\TeachingHomeworkLayout;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TeachingExamGeneratorService
{
    public function __construct(
        private readonly QuestionPaperGeneratorService $generator,
    ) {}

    /**
     * @param  Collection<int, ExamLog>  $logs
     * @param  array<string, int>  $typeCounts
     * @param  array<string, int>  $marksPerType
     * @return array<string, mixed>
     */
    public function preview(Collection $logs, int $targetMarks, array $typeCounts, array $marksPerType): array
    {
        $this->guardLogs($logs, $targetMarks);

        $typeCounts = PaperTypeHelper::normalizeCounts($typeCounts);
        $marksPerType = PaperTypeHelper::normalizeMarksPerType($marksPerType, $typeCounts);

        if ($typeCounts === []) {
            throw ValidationException::withMessages([
                'type_counts' => 'Enter at least one question quantity.',
            ]);
        }

        $logs = $logs->values();
        $topicSections = [];
        $allQuestions = collect();
        $combinedTypeCounts = [];
        $sources = [];

        foreach ($logs as $index => $log) {
            $topicTypeCounts = TeachingHomeworkLayout::splitForTopic($typeCounts, $index, $logs->count());

            if ($topicTypeCounts === []) {
                continue;
            }

            $generated = $this->generator->generate(
                (int) $log->chapter_id,
                $log->topic_id,
                $topicTypeCounts,
                $marksPerType
            );

            $allQuestions = $allQuestions->concat($generated['questions']);

            foreach ($topicTypeCounts as $type => $count) {
                $combinedTypeCounts[$type] = ($combinedTypeCounts[$type] ?? 0) + $count;
            }

            $topicSections[] = [
                'exam_log_id' => $log->id,
                'chapter_name' => $log->chapter?->name,
                'topic_name' => $log->topic?->name,
                'type_counts' => $topicTypeCounts,
                'breakdown' => $generated['breakdown'],
                'total_questions' => PaperTypeHelper::totalQuestions($topicTypeCounts),
                'total_marks' => $generated['total_marks'],
                'grouped' => $generated['grouped'],
            ];

            $sources[] = [
                'exam_log_id' => $log->id,
                'chapter_id' => $log->chapter_id,
                'topic_id' => $log->topic_id,
                'chapter_name' => $log->chapter?->name,
                'topic_name' => $log->topic?->name,
            ];
        }

        if ($allQuestions->isEmpty()) {
            throw ValidationException::withMessages([
                'type_counts' => 'No questions could be picked. Check quantities and question bank.',
            ]);
        }

        $first = $logs->first();
        $syllabusOutline = $logs->map(function (ExamLog $log) {
            return trim(($log->chapter?->name ?? 'Chapter').' / '.($log->topic?->name ?? 'Topic'));
        })->filter()->unique()->values()->implode("\n");

        $masterPrompt = ExamPaperPromptBuilder::build([
            'total_marks' => $targetMarks,
            'standard' => (string) ($first->standard ?? ''),
            'class_label' => (string) ($first->standard ?? ''),
            'subject' => (string) ($first->subject?->name ?? ''),
            'exam_date' => $first->exam_date?->format('d M Y') ?? now()->format('d M Y'),
            'syllabus_outline' => $syllabusOutline !== '' ? $syllabusOutline : '(not provided)',
            'chapter_weightage' => 'balanced across selected topics',
            'set_label' => 'Set A',
            'available_questions_json' => json_encode([
                'type_counts' => $combinedTypeCounts,
                'question_ids' => $allQuestions->pluck('id')->values()->all(),
            ], JSON_UNESCAPED_UNICODE),
        ]);

        return [
            'logs' => $logs,
            'target_marks' => $targetMarks,
            'type_counts' => $combinedTypeCounts,
            'marks_per_type' => $marksPerType,
            'total_marks' => PaperTypeHelper::totalMarks($combinedTypeCounts, $marksPerType),
            'total_questions' => $allQuestions->count(),
            'breakdown' => PaperTypeHelper::breakdown($combinedTypeCounts, $marksPerType),
            'topic_sections' => $topicSections,
            'question_ids' => $allQuestions->pluck('id')->all(),
            'sources' => $sources,
            'master_weightage' => ExamPaperWeightage::TYPE_PERCENTS,
            'ai_prompt' => $masterPrompt,
        ];
    }

    /**
     * @param  array<string, mixed>  $preview
     */
    public function generateFromPreview(array $preview, User $teacher): Exam
    {
        $logs = ExamLog::query()
            ->where('teacher_id', $teacher->id)
            ->whereIn('id', collect($preview['sources'])->pluck('exam_log_id'))
            ->with(['subject', 'chapter', 'topic'])
            ->get();

        $this->guardLogs($logs, (int) $preview['target_marks']);

        $first = $logs->first();
        $targetMarks = (int) $preview['target_marks'];
        $examDate = $first->exam_date?->format('d M Y') ?? now()->format('d M Y');
        $subjectName = $first->subject?->name ?? 'Subject';
        $title = "Today's Exam ({$targetMarks} marks) — {$subjectName} — {$examDate}";
        $duration = max(45, (int) round($targetMarks * 1.5));

        $exam = Exam::create([
            'teacher_id' => $teacher->id,
            'standard' => $first->standard,
            'subject_id' => $first->subject_id,
            'chapter_id' => $first->chapter_id,
            'topic_id' => $first->topic_id,
            'title' => $title,
            'description' => 'Generated from Today\'s Exam for '.count($preview['sources']).' topic(s).',
            'instructions' => 'Answer all questions. Good luck!',
            'duration_minutes' => $duration,
            'starts_at' => $first->exam_date?->copy()->startOfDay() ?? now(),
            'ends_at' => $first->exam_date?->copy()->endOfDay()->addDays(7) ?? now()->addDays(7)->endOfDay(),
            'status' => 'published',
            'total_marks' => $preview['total_marks'],
            'generation_config' => [
                'type_counts' => $preview['type_counts'],
                'marks_per_type' => $preview['marks_per_type'],
                'question_ids' => $preview['question_ids'],
                'target_marks' => $targetMarks,
                'exam_log_ids' => $logs->pluck('id')->all(),
                'sources' => $preview['sources'],
                'topic_sections' => collect($preview['topic_sections'])->map(fn (array $section) => [
                    'exam_log_id' => $section['exam_log_id'],
                    'chapter_name' => $section['chapter_name'],
                    'topic_name' => $section['topic_name'],
                    'type_counts' => $section['type_counts'],
                    'total_questions' => $section['total_questions'],
                    'total_marks' => $section['total_marks'],
                ])->all(),
                'generated_from' => 'todays_exam',
                'master_weightage' => ExamPaperWeightage::TYPE_PERCENTS,
                'ai_prompt_key' => 'exam_paper_generator',
                'ai_prompt' => $preview['ai_prompt'] ?? ExamPaperPromptBuilder::build([
                    'total_marks' => $targetMarks,
                    'standard' => (string) ($first->standard ?? ''),
                    'subject' => $subjectName,
                    'exam_date' => $examDate,
                ]),
            ],
        ]);

        $questions = $this->generator->questionsByIds($preview['question_ids']);
        $this->syncExamQuestions($exam, $questions, $preview['marks_per_type']);

        ExamLog::query()
            ->whereIn('id', $logs->pluck('id'))
            ->update([
                'exam_id' => $exam->id,
                'target_marks' => $targetMarks,
                'status' => ExamLog::STATUS_COMPLETED,
            ]);

        StudentNotificationService::examPublished($exam);

        return $exam;
    }

    /**
     * @param  Collection<int, ExamLog>  $logs
     */
    private function guardLogs(Collection $logs, int $targetMarks): void
    {
        if (! in_array($targetMarks, [20, 30, 40, 50], true)) {
            throw ValidationException::withMessages([
                'target_marks' => 'Please choose 20, 30, 40, or 50 marks.',
            ]);
        }

        if ($logs->isEmpty()) {
            throw ValidationException::withMessages([
                'exam_log_ids' => 'Select at least one completed topic.',
            ]);
        }

        if ($logs->pluck('subject_id')->unique()->count() > 1 || $logs->pluck('standard')->unique()->count() > 1) {
            throw ValidationException::withMessages([
                'exam_log_ids' => 'Selected items must be from the same standard and subject.',
            ]);
        }

        if ($logs->contains(fn (ExamLog $log) => $log->hasExam())) {
            throw ValidationException::withMessages([
                'exam_log_ids' => 'One or more selected topics already have an exam generated.',
            ]);
        }
    }

    private function syncExamQuestions(Exam $exam, Collection $questions, array $marksPerType): void
    {
        $exam->questions()->delete();

        $sort = 0;
        foreach ($questions as $question) {
            ExamQuestion::create([
                'exam_id' => $exam->id,
                'chapter_question_id' => $question->id,
                'question_type' => $question->question_type,
                'question_text' => $question->question_text,
                'options' => $question->options,
                'answer' => $question->answer,
                'marks' => $marksPerType[$question->question_type] ?? 1,
                'sort_order' => $sort++,
            ]);
        }
    }
}
