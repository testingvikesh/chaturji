<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\Subject;
use App\Models\User;
use App\Support\MaterialPaperBank;
use App\Support\PaperTypeHelper;
use App\Support\TeachingHomeworkLayout;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Student Self Exam — generate from materials chapter + optional material topic.
 */
class SelfExamGeneratorService
{
    public const MARK_OPTIONS = [20, 30, 50, 70, 100];

    /**
     * @param  Collection<int, Material>  $materials
     * @param  Collection<int, MaterialTopic>|null  $topics  Empty = all topics
     */
    public function buildPlan(Collection $materials, ?Collection $topics, int $targetMarks): array
    {
        $this->guardMarks($targetMarks);
        $topics = $topics ?? collect();

        $layout = TeachingHomeworkLayout::autoLayout($targetMarks);
        $available = MaterialPaperBank::availableCounts($materials, $topics);
        $typeCounts = $this->fitCountsToAvailable($layout['type_counts'], $available);

        if ($typeCounts === []) {
            throw ValidationException::withMessages([
                'chapter_ids' => 'Not enough questions in these chapters/topics. Choose another or lower marks.',
            ]);
        }

        $marksPerType = PaperTypeHelper::normalizeMarksPerType($layout['marks_per_type'], $typeCounts);

        return [
            'target_marks' => $targetMarks,
            'type_counts' => $typeCounts,
            'marks_per_type' => $marksPerType,
            'available' => $available,
            'breakdown' => PaperTypeHelper::breakdown($typeCounts, $marksPerType),
            'total_marks' => PaperTypeHelper::totalMarks($typeCounts, $marksPerType),
            'total_questions' => PaperTypeHelper::totalQuestions($typeCounts),
            'layout_total_marks' => $layout['total_marks'],
        ];
    }

    /**
     * @param  Collection<int, Material>  $materials
     * @param  Collection<int, MaterialTopic>|null  $topics
     * @param  array<string, int>  $requestedCounts
     */
    public function buildCustomPlan(
        Collection $materials,
        ?Collection $topics,
        int $targetMarks,
        array $requestedCounts
    ): array {
        $this->guardMarks($targetMarks);
        $topics = $topics ?? collect();

        $available = MaterialPaperBank::availableCounts($materials, $topics);
        $typeCounts = PaperTypeHelper::normalizeCounts($requestedCounts);

        if ($typeCounts === []) {
            throw ValidationException::withMessages([
                'type_counts' => 'Enter at least one question in "In this paper".',
            ]);
        }

        foreach ($typeCounts as $type => $count) {
            if ($count > (int) ($available[$type] ?? 0)) {
                throw ValidationException::withMessages([
                    "type_counts.{$type}" => PaperTypeHelper::label($type).' has only '.($available[$type] ?? 0).' question(s) available.',
                ]);
            }
        }

        $marksPerType = PaperTypeHelper::normalizeMarksPerType(TeachingHomeworkLayout::marksPerType(), $typeCounts);

        return [
            'target_marks' => $targetMarks,
            'type_counts' => $typeCounts,
            'marks_per_type' => $marksPerType,
            'available' => $available,
            'breakdown' => PaperTypeHelper::breakdown($typeCounts, $marksPerType),
            'total_marks' => PaperTypeHelper::totalMarks($typeCounts, $marksPerType),
            'total_questions' => PaperTypeHelper::totalQuestions($typeCounts),
            'layout_total_marks' => $targetMarks,
        ];
    }

    /**
     * @param  Collection<int, Material>  $materials
     * @param  Collection<int, MaterialTopic>|null  $topics
     */
    public function generate(
        User $student,
        Subject $subject,
        Collection $materials,
        ?Collection $topics,
        int $targetMarks,
        array $requestedCounts = [],
    ): Exam {
        if (! Exam::hasSelfExamColumns()) {
            throw ValidationException::withMessages([
                'subject_id' => 'Self Exam DB columns are missing. Run MySQL ALTER for student_id / is_self_exam.',
            ]);
        }

        if ($materials->isEmpty()) {
            throw ValidationException::withMessages([
                'chapter_ids' => 'Select at least one chapter.',
            ]);
        }

        $topics = $topics ?? collect();
        $primary = $materials->first();
        $chapterId = filled($primary->chapter_id) ? (int) $primary->chapter_id : null;

        $plan = $requestedCounts !== []
            ? $this->buildCustomPlan($materials, $topics, $targetMarks, $requestedCounts)
            : $this->buildPlan($materials, $topics, $targetMarks);

        $generated = MaterialPaperBank::generate(
            $materials,
            $topics,
            $plan['type_counts'],
            $plan['marks_per_type']
        );

        $chapterName = MaterialPaperBank::chapterLabel($materials);
        $topicLabel = MaterialPaperBank::topicLabel($topics);
        $finalMarks = (int) $generated['total_marks'];
        $duration = max(30, (int) round($finalMarks * 1.5));

        $exam = Exam::create([
            'teacher_id' => $student->id,
            'student_id' => $student->id,
            'is_self_exam' => true,
            'standard' => $student->standard,
            'subject_id' => $subject->id,
            'chapter_id' => $chapterId,
            'topic_id' => null,
            'title' => "Self Exam ({$finalMarks} marks) — {$subject->name}",
            'description' => "{$chapterName} · {$topicLabel}",
            'instructions' => "Self practice exam.\nAnswer all questions.\nUpload your answer sheet when finished.",
            'duration_minutes' => $duration,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30)->endOfDay(),
            'status' => 'published',
            'total_marks' => $generated['total_marks'],
            'generation_config' => [
                'type_counts' => $plan['type_counts'],
                'marks_per_type' => $generated['marks_per_type'],
                'target_marks' => $targetMarks,
                'selected_marks' => $finalMarks,
                'chapter_id' => $chapterId,
                'material_ids' => $materials->pluck('id')->values()->all(),
                'material_topic_ids' => $topics->pluck('id')->values()->all(),
                'generated_from' => 'self_exam_materials',
            ],
        ]);

        $this->syncExamQuestions($exam, $generated['questions'], $generated['marks_per_type']);

        return $exam;
    }

    private function guardMarks(int $targetMarks): void
    {
        if (! in_array($targetMarks, self::MARK_OPTIONS, true)) {
            throw ValidationException::withMessages([
                'target_marks' => 'Please choose 20, 30, 50, 70, or 100 marks.',
            ]);
        }
    }

    /**
     * @param  array<string, int>  $desired
     * @param  array<string, int>  $available
     * @return array<string, int>
     */
    private function fitCountsToAvailable(array $desired, array $available): array
    {
        $fitted = [];
        foreach ($desired as $type => $count) {
            $use = min(max(0, (int) $count), (int) ($available[$type] ?? 0));
            if ($use > 0) {
                $fitted[$type] = $use;
            }
        }

        return PaperTypeHelper::normalizeCounts($fitted);
    }

    private function syncExamQuestions(Exam $exam, Collection $questions, array $marksPerType): void
    {
        $exam->questions()->delete();
        $sort = 0;
        foreach ($questions as $question) {
            ExamQuestion::create([
                'exam_id' => $exam->id,
                'chapter_question_id' => $question->id ?: null,
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
