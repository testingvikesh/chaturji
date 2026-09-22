<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Homework;
use App\Models\TeacherSubject;
use App\Support\TeacherOtpService;
use App\Support\TeacherTimetableService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TeacherTimetableService $timetables
    ) {}

    public function index(): View
    {
        $teacher = auth()->user();
        $todaySlots = $this->timetables->forTeacherOnDate($teacher->id);

        return view('teacher.dashboard', [
            'teacher' => $teacher,
            'todayOtp' => TeacherOtpService::ensureForTeacher($teacher),
            'examCount' => Exam::where('teacher_id', $teacher->id)->excludeSelfExams()->count(),
            'homeworkCount' => Homework::where('teacher_id', $teacher->id)->count(),
            'publishedExams' => Exam::where('teacher_id', $teacher->id)->excludeSelfExams()->published()->count(),
            'publishedHomework' => Homework::where('teacher_id', $teacher->id)->published()->count(),
            'recentExams' => Exam::where('teacher_id', $teacher->id)->excludeSelfExams()->latest()->limit(5)->get(),
            'recentHomework' => Homework::where('teacher_id', $teacher->id)->latest()->limit(5)->get(),
            'assignedGroups' => TeacherSubject::query()
                ->with(['standard:id,name', 'subject:id,name'])
                ->where('teacher_id', $teacher->id)
                ->get()
                ->groupBy(fn (TeacherSubject $row) => $row->medium.'|'.$row->standard_id),
            'todaySlots' => $todaySlots,
            'weekSlots' => $this->timetables->weekForTeacher($teacher->id),
        ]);
    }
}
