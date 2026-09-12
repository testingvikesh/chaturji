<?php

namespace App\Support;

use App\Models\Exam;
use App\Models\Homework;
use App\Models\Standard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeacherSubjectStats
{
    /**
     * @return Collection<int, Standard>
     */
    public static function standardsWithSubjects(int $teacherId): Collection
    {
        $examStats = self::examStatsBySubject($teacherId);
        $homeworkStats = self::homeworkStatsBySubject($teacherId);
        $questionCounts = self::questionCountsBySubject();
        $topicCounts = self::topicCountsBySubject();

        return Standard::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['activeSubjects' => function ($query) {
                $query->withCount(['chapters' => fn ($q) => $q->where('is_active', true)])
                    ->orderBy('sort_order');
            }])
            ->get()
            ->map(function (Standard $standard) use ($examStats, $homeworkStats, $questionCounts, $topicCounts) {
                $subjects = $standard->activeSubjects->map(function ($subject) use ($examStats, $homeworkStats, $questionCounts, $topicCounts) {
                    self::applyStats($subject, $examStats, $homeworkStats, $questionCounts, $topicCounts);

                    return $subject;
                });

                $standard->setRelation('activeSubjects', $subjects);

                return $standard;
            });
    }

    public static function attachStatsToSubject($subject, int $teacherId): void
    {
        self::applyStats(
            $subject,
            self::examStatsBySubject($teacherId),
            self::homeworkStatsBySubject($teacherId),
            self::questionCountsBySubject(),
            self::topicCountsBySubject(),
        );
    }

    private static function applyStats(
        $subject,
        Collection $examStats,
        Collection $homeworkStats,
        array $questionCounts,
        array $topicCounts,
    ): void {
        $examRow = $examStats->get($subject->id);
        $homeworkRow = $homeworkStats->get($subject->id);

        $subject->teacher_stats = [
            'chapters' => (int) ($subject->chapters_count ?? 0),
            'topics' => (int) ($topicCounts[$subject->id] ?? 0),
            'questions' => (int) ($questionCounts[$subject->id] ?? 0),
            'exams' => (int) ($examRow->total ?? 0),
            'exams_published' => (int) ($examRow->published ?? 0),
            'homework' => (int) ($homeworkRow->total ?? 0),
            'homework_published' => (int) ($homeworkRow->published ?? 0),
        ];
    }

    private static function examStatsBySubject(int $teacherId): Collection
    {
        return Exam::query()
            ->where('teacher_id', $teacherId)
            ->excludeSelfExams()
            ->whereNotNull('subject_id')
            ->selectRaw("subject_id, COUNT(*) as total, SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published")
            ->groupBy('subject_id')
            ->get()
            ->keyBy('subject_id');
    }

    private static function homeworkStatsBySubject(int $teacherId): Collection
    {
        return Homework::query()
            ->where('teacher_id', $teacherId)
            ->whereNotNull('subject_id')
            ->selectRaw("subject_id, COUNT(*) as total, SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published")
            ->groupBy('subject_id')
            ->get()
            ->keyBy('subject_id');
    }

    /**
     * @return array<int, int>
     */
    private static function questionCountsBySubject(): array
    {
        return DB::table('chapter_questions')
            ->join('chapter_contents', 'chapter_contents.id', '=', 'chapter_questions.chapter_content_id')
            ->join('chapters', 'chapters.id', '=', 'chapter_contents.chapter_id')
            ->where('chapters.is_active', true)
            ->groupBy('chapters.subject_id')
            ->selectRaw('chapters.subject_id, COUNT(chapter_questions.id) as questions_count')
            ->pluck('questions_count', 'subject_id')
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private static function topicCountsBySubject(): array
    {
        return DB::table('topics')
            ->join('chapters', 'chapters.id', '=', 'topics.chapter_id')
            ->where('topics.is_active', true)
            ->where('chapters.is_active', true)
            ->groupBy('chapters.subject_id')
            ->selectRaw('chapters.subject_id, COUNT(topics.id) as topics_count')
            ->pluck('topics_count', 'subject_id')
            ->all();
    }
}
