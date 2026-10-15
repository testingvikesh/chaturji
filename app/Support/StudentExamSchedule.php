<?php

namespace App\Support;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Support\Collection;

class StudentExamSchedule
{
    private static bool $liveResolved = false;

    private static ?Exam $liveExam = null;

    /**
     * Published teacher exams for this class that are still ahead, running, or happened today.
     *
     * @return Collection<int, Exam>
     */
    public static function timetable(User $user): Collection
    {
        return self::scheduledFor($user)
            ->filter(function (Exam $exam) {
                if (in_array($exam->schedulePhase(), ['upcoming', 'live'], true)) {
                    return true;
                }

                return $exam->starts_at && $exam->starts_at->greaterThanOrEqualTo(now()->startOfDay());
            })
            ->sortBy(fn (Exam $exam) => $exam->starts_at?->timestamp ?? 0)
            ->values();
    }

    public static function liveFor(User $user): ?Exam
    {
        if (self::$liveResolved) {
            return self::$liveExam;
        }

        self::$liveResolved = true;
        self::$liveExam = self::scheduledFor($user)
            ->filter(fn (Exam $exam) => $exam->schedulePhase() === 'live')
            ->sortByDesc(fn (Exam $exam) => $exam->starts_at?->timestamp ?? 0)
            ->first();

        return self::$liveExam;
    }

    /**
     * @return Collection<int, Exam>
     */
    private static function scheduledFor(User $user): Collection
    {
        $query = Exam::query()
            ->where('status', 'published')
            ->where('standard', $user->standard)
            ->whereNotNull('starts_at')
            ->with('subject:id,name');

        if (Exam::hasSelfExamColumns()) {
            $query->where(function ($inner) {
                $inner->where('is_self_exam', false)->orWhereNull('is_self_exam');
            });
        }

        return $query->get();
    }
}
