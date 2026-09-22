<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Homework;
use App\Models\StudentWorkAttempt;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkAttemptController extends Controller
{
    public function storeObjective(Request $request): JsonResponse
    {
        $student = auth()->user();
        abort_unless($student && $student->role === 'student', 403);

        $validated = $request->validate([
            'paper_type' => ['required', 'in:exam,homework'],
            'paper_id' => ['required', 'integer'],
            'answered_count' => ['required', 'integer', 'min:0'],
            'correct_count' => ['required', 'integer', 'min:0'],
            'total_objective' => ['required', 'integer', 'min:0'],
            'earned_marks' => ['required', 'numeric', 'min:0'],
            'max_marks' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validated['paper_type'] === 'exam') {
            $paper = Exam::query()->findOrFail((int) $validated['paper_id']);
            $this->assertCanAttemptExam($student->id, $paper);
            $workType = StudentWorkAttempt::TYPE_EXAM_OBJECTIVE;
        } else {
            $paper = Homework::query()->findOrFail((int) $validated['paper_id']);
            $this->assertCanAttemptHomework($student->id, $paper);
            $workType = StudentWorkAttempt::TYPE_HOMEWORK_OBJECTIVE;
        }

        $attempt = StudentWorkAttempt::query()->create([
            'user_id' => $student->id,
            'work_type' => $workType,
            'paper_type' => $validated['paper_type'],
            'paper_id' => (int) $validated['paper_id'],
            'answered_count' => (int) $validated['answered_count'],
            'correct_count' => (int) $validated['correct_count'],
            'total_objective' => (int) $validated['total_objective'],
            'earned_marks' => (float) $validated['earned_marks'],
            'max_marks' => (float) $validated['max_marks'],
            'status' => 'submitted',
            'attempted_at' => now(),
        ]);

        ActivityLogger::log(
            'student.objective_attempt',
            'Objective attempt on '.$validated['paper_type'].' #'.$validated['paper_id'].' ('.$attempt->correct_count.'/'.$attempt->total_objective.')',
            $attempt,
            [
                'paper_type' => $validated['paper_type'],
                'paper_id' => (int) $validated['paper_id'],
                'earned_marks' => $attempt->earned_marks,
                'correct_count' => $attempt->correct_count,
            ],
            $student
        );

        return response()->json([
            'ok' => true,
            'id' => $attempt->id,
        ]);
    }

    private function assertCanAttemptExam(int $studentId, Exam $exam): void
    {
        $ok = (int) $exam->student_id === $studentId
            || ($exam->status === 'published' && (int) ($exam->student_id ?? 0) === 0);

        // Self exams belong to the student; published class exams are open to students.
        if ((bool) $exam->is_self_exam) {
            $ok = (int) $exam->student_id === $studentId;
        }

        abort_unless($ok, 403, 'You cannot attempt this exam.');
    }

    private function assertCanAttemptHomework(int $studentId, Homework $homework): void
    {
        $ok = (int) ($homework->student_id ?? 0) === $studentId
            || ($homework->status === 'published' && (int) ($homework->student_id ?? 0) === 0);

        if ((bool) ($homework->is_self_homework ?? false)) {
            $ok = (int) ($homework->student_id ?? 0) === $studentId;
        }

        abort_unless($ok, 403, 'You cannot attempt this homework.');
    }
}
