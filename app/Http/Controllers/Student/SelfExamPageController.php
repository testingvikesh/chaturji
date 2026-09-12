<?php

namespace App\Http\Controllers\Student;

use App\Models\Exam;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Isolated list page for Self Exam — keeps /student/exams opening even if
 * create/generate services are missing on the server.
 */
class SelfExamPageController extends BaseStudentController
{
    public function index(): View
    {
        $user = auth()->user();
        $selfExams = collect();
        $teacherExams = collect();
        $selfExamDbReady = false;

        try {
            $selfExamDbReady = $this->selfExamColumnsExist();
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            if ($selfExamDbReady) {
                $selfExams = Exam::query()
                    ->where('is_self_exam', 1)
                    ->where('student_id', $user->id)
                    ->with(['subject:id,name'])
                    ->withCount('questions')
                    ->latest()
                    ->get();

                $teacherExams = Exam::query()
                    ->where('status', 'published')
                    ->where(function ($q) {
                        $q->where('is_self_exam', 0)->orWhereNull('is_self_exam');
                    })
                    ->when($user->standard, fn ($q) => $q->where('standard', $user->standard))
                    ->with(['subject:id,name', 'teacher:id,name'])
                    ->withCount('questions')
                    ->latest()
                    ->get();
            } else {
                $teacherExams = Exam::query()
                    ->where('status', 'published')
                    ->when($user->standard, fn ($q) => $q->where('standard', $user->standard))
                    ->with(['subject:id,name', 'teacher:id,name'])
                    ->withCount('questions')
                    ->latest()
                    ->get();
            }
        } catch (\Throwable $e) {
            report($e);

            try {
                $teacherExams = Exam::query()
                    ->where('status', 'published')
                    ->when($user->standard, fn ($q) => $q->where('standard', $user->standard))
                    ->with(['subject:id,name', 'teacher:id,name'])
                    ->withCount('questions')
                    ->latest()
                    ->get();
            } catch (\Throwable $inner) {
                report($inner);
            }
        }

        return view('student.exams.index', [
            'user' => $user,
            'standardName' => $this->standardName($user->standard),
            'selfExams' => $selfExams,
            'teacherExams' => $teacherExams,
            'markOptions' => [20, 30, 50, 70, 100],
            'selfExamDbReady' => $selfExamDbReady,
            'createUrl' => url('/student/exams/create'),
        ]);
    }

    private function selfExamColumnsExist(): bool
    {
        try {
            $rows = DB::select("SHOW COLUMNS FROM `exams` LIKE 'is_self_exam'");
            $rows2 = DB::select("SHOW COLUMNS FROM `exams` LIKE 'student_id'");

            return count($rows) > 0 && count($rows2) > 0;
        } catch (\Throwable) {
            return false;
        }
    }
}
