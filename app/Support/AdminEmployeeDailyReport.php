<?php

namespace App\Support;

use App\Models\Exam;
use App\Models\ExamLog;
use App\Models\Homework;
use App\Models\LoginLog;
use App\Models\TeachingLog;
use App\Models\User;
use App\Models\UserSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AdminEmployeeDailyReport
{
    /**
     * @return array{
     *     date: string,
     *     date_label: string,
     *     total_employees: int,
     *     present_count: int,
     *     absent_count: int,
     *     present_homework_yes: int,
     *     present_homework_no: int,
     *     present_exam_yes: int,
     *     present_exam_no: int,
     *     rows: Collection<int, array<string, mixed>>
     * }
     */
    public function build(?string $date = null): array
    {
        $date = $date ?? now()->toDateString();
        $day = Carbon::parse($date);

        $teachers = User::query()
            ->teachers()
            ->approved()
            ->orderBy('name')
            ->get(['id', 'name', 'mobile', 'email']);

        $presentIds = $this->presentTeacherIds($day);

        $homeworkTeacherIds = $this->homeworkSubmittedTeacherIds($day);
        $examTeacherIds = $this->examCreatedTeacherIds($day);

        $rows = $teachers->map(function (User $teacher) use ($presentIds, $homeworkTeacherIds, $examTeacherIds) {
            $present = $presentIds->contains($teacher->id);

            return [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'mobile' => $teacher->mobile,
                'email' => $teacher->email,
                'attendance' => $present ? 'present' : 'absent',
                'homework_submitted' => $present && $homeworkTeacherIds->contains($teacher->id),
                'exam_created' => $present && $examTeacherIds->contains($teacher->id),
            ];
        });

        $presentRows = $rows->where('attendance', 'present');

        return [
            'date' => $day->toDateString(),
            'date_label' => $day->format('d M Y'),
            'total_employees' => $teachers->count(),
            'present_count' => $presentRows->count(),
            'absent_count' => $rows->where('attendance', 'absent')->count(),
            'present_homework_yes' => $presentRows->where('homework_submitted', true)->count(),
            'present_homework_no' => $presentRows->where('homework_submitted', false)->count(),
            'present_exam_yes' => $presentRows->where('exam_created', true)->count(),
            'present_exam_no' => $presentRows->where('exam_created', false)->count(),
            'rows' => $rows,
        ];
    }

    private function presentTeacherIds(Carbon $day): Collection
    {
        $fromLogins = LoginLog::query()
            ->where('role', 'teacher')
            ->where('status', 'success')
            ->whereDate('logged_at', $day)
            ->pluck('user_id');

        $fromSessions = UserSession::query()
            ->where('role', 'teacher')
            ->whereDate('logged_in_at', $day)
            ->pluck('user_id');

        return $fromLogins->merge($fromSessions)->filter()->unique()->values();
    }

    private function homeworkSubmittedTeacherIds(Carbon $day): Collection
    {
        $fromHomework = Homework::query()
            ->whereDate('created_at', $day)
            ->pluck('teacher_id');

        $fromTeaching = TeachingLog::query()
            ->whereDate('teaching_date', $day)
            ->whereNotNull('homework_id')
            ->pluck('teacher_id');

        return $fromHomework->merge($fromTeaching)->filter()->unique()->values();
    }

    private function examCreatedTeacherIds(Carbon $day): Collection
    {
        $fromExams = Exam::query()
            ->whereDate('created_at', $day)
            ->pluck('teacher_id');

        $fromLogs = ExamLog::query()
            ->whereDate('exam_date', $day)
            ->whereNotNull('exam_id')
            ->pluck('teacher_id');

        return $fromExams->merge($fromLogs)->filter()->unique()->values();
    }
}
