<?php

namespace App\Services;

use App\Models\Homework;
use App\Models\HomeworkQuestion;
use App\Models\TeachingLog;
use App\Models\User;
use App\Support\PaperTypeHelper;
use App\Support\TeachingHomeworkLayout;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TeachingHomeworkGeneratorService
{
    public function __construct(
        private readonly QuestionPaperGeneratorService $generator,
    ) {}

    /**
     * @param  Collection<int, TeachingLog>  $logs
     * @param  array<string, int>  $typeCounts
     * @param  array<string, int>  $marksPerType
     * @return array{
     *     logs: Collection<int, TeachingLog>,
     *     target_marks: int,
     *     type_counts: array<string, int>,
     *     marks_per_type: array<string, int>,
     *     total_marks: int,
     *     total_questions: int,
     *     breakdown: array<int, array{type: string, label: string, count: int, marks: int, subtotal: int}>,
     *     topic_sections: array<int, array<string, mixed>>,
     *     question_ids: array<int, int>,
     *     sources: array<int, array<string, mixed>>
     * }
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
                'teaching_log_id' => $log->id,
                'chapter_name' => $log->chapter?->name,
                'topic_name' => $log->topic?->name,
                'type_counts' => $topicTypeCounts,
                'breakdown' => $generated['breakdown'],
                'total_questions' => PaperTypeHelper::totalQuestions($topicTypeCounts),
                'total_marks' => $generated['total_marks'],
                'grouped' => $generated['grouped'],
            ];

            $sources[] = [
                'teaching_log_id' => $log->id,
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
        ];
    }

    /**
     * @param  array<string, mixed>  $preview
     */
    public function generateFromPreview(array $preview, User $teacher): Homework
    {
        $logs = TeachingLog::query()
            ->where('teacher_id', $teacher->id)
            ->whereIn('id', collect($preview['sources'])->pluck('teaching_log_id'))
            ->with(['subject', 'chapter', 'topic'])
            ->get();

        $this->guardLogs($logs, (int) $preview['target_marks']);

        $first = $logs->first();
        $targetMarks = (int) $preview['target_marks'];
        $teachingDate = $first->teaching_date?->format('d M Y') ?? now()->format('d M Y');
        $subjectName = $first->subject?->name ?? 'Subject';
        $title = "Today's Homework ({$targetMarks} marks) — {$subjectName} — {$teachingDate}";

        $homework = Homework::create([
            'teacher_id' => $teacher->id,
            'standard' => $first->standard,
            'subject_id' => $first->subject_id,
            'chapter_id' => $first->chapter_id,
            'topic_id' => $first->topic_id,
            'title' => $title,
            'description' => 'Generated from Today\'s Teaching for '.count($preview['sources']).' topic(s).',
            'due_at' => now()->addDay()->endOfDay(),
            'status' => 'published',
            'generation_config' => [
                'type_counts' => $preview['type_counts'],
                'marks_per_type' => $preview['marks_per_type'],
                'question_ids' => $preview['question_ids'],
                'target_marks' => $targetMarks,
                'teaching_log_ids' => $logs->pluck('id')->all(),
                'sources' => $preview['sources'],
                'topic_sections' => collect($preview['topic_sections'])->map(fn (array $section) => [
                    'teaching_log_id' => $section['teaching_log_id'],
                    'chapter_name' => $section['chapter_name'],
                    'topic_name' => $section['topic_name'],
                    'type_counts' => $section['type_counts'],
                    'total_questions' => $section['total_questions'],
                    'total_marks' => $section['total_marks'],
                ])->all(),
                'generated_from' => 'todays_teaching',
            ],
        ]);

        $questions = $this->generator->questionsByIds($preview['question_ids']);
        $this->syncHomeworkQuestions($homework, $questions);

        TeachingLog::query()
            ->whereIn('id', $logs->pluck('id'))
            ->update([
                'homework_id' => $homework->id,
                'target_marks' => $targetMarks,
                'status' => TeachingLog::STATUS_COMPLETED,
            ]);

        StudentNotificationService::homeworkAssigned($homework);

        return $homework;
    }

    /**
     * @param  Collection<int, TeachingLog>  $logs
     */
    public function generate(Collection $logs, int $targetMarks, User $teacher): Homework
    {
        $layout = TeachingHomeworkLayout::autoLayout($targetMarks);
        $preview = $this->preview($logs, $targetMarks, $layout['type_counts'], $layout['marks_per_type']);

        return $this->generateFromPreview($preview, $teacher);
    }

    /**
     * @param  Collection<int, TeachingLog>  $logs
     */
    private function guardLogs(Collection $logs, int $targetMarks): void
    {
        if (! in_array($targetMarks, [20, 30, 40, 50], true)) {
            throw ValidationException::withMessages([
                'target_marks' => 'Please choose 20, 30, 40, or 50 marks.',
            ]);
        }

        $logs = $logs->values();

        if ($logs->isEmpty()) {
            throw ValidationException::withMessages([
                'teaching_log_ids' => 'Select at least one completed topic.',
            ]);
        }

        if ($logs->pluck('subject_id')->unique()->count() > 1 || $logs->pluck('standard')->unique()->count() > 1) {
            throw ValidationException::withMessages([
                'teaching_log_ids' => 'Selected items must be from the same standard and subject.',
            ]);
        }

        if ($logs->contains(fn (TeachingLog $log) => $log->hasHomework())) {
            throw ValidationException::withMessages([
                'teaching_log_ids' => 'One or more selected topics already have homework generated.',
            ]);
        }
    }

    private function syncHomeworkQuestions(Homework $homework, Collection $questions): void
    {
        $homework->questions()->delete();

        $sort = 0;
        foreach ($questions as $question) {
            HomeworkQuestion::create([
                'homework_id' => $homework->id,
                'chapter_question_id' => $question->id,
                'question_type' => $question->question_type,
                'question_text' => $question->question_text,
                'options' => $question->options,
                'answer' => $question->answer,
                'sort_order' => $sort++,
            ]);
        }
    }
}
