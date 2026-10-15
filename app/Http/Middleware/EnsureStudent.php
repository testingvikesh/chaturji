<?php

namespace App\Http\Middleware;

use App\Models\Exam;
use App\Support\StudentExamSchedule;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('student.login');
        }

        $user = auth()->user();

        if ($user->role !== 'student') {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('student.login')
                ->with('error', 'Please login with a student account.');
        }

        if (! $user->isApproved()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('student.login')
                ->with('error', 'Your account is pending admin approval.');
        }

        try {
            $liveExam = StudentExamSchedule::liveFor($user);
        } catch (\Throwable $e) {
            report($e);
            $liveExam = null;
        }

        view()->share('liveTeacherExam', $liveExam);

        if ($liveExam && ! $this->requestStaysOnLiveExam($request, $liveExam)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'An exam is in progress.'], 423);
            }

            return redirect()->route('student.exams.show', $liveExam);
        }

        return $next($request);
    }

    private function requestStaysOnLiveExam(Request $request, Exam $liveExam): bool
    {
        $route = $request->route()?->getName();

        if ($route === 'student.work-attempts.objective') {
            return true;
        }

        if (! in_array($route, ['student.exams.show', 'student.exams.print', 'student.exams.submit-answer-pdf'], true)) {
            return false;
        }

        $exam = $request->route('exam');
        $examId = $exam instanceof Exam ? $exam->id : $exam;

        return (int) $examId === (int) $liveExam->id;
    }
}
