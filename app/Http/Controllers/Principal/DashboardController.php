<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\TeacherSubject;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $principal = auth()->user();
        $allotted = $principal->allottedStandards()
            ->where('is_active', true)
            ->orderedByNumber()
            ->get(['standards.id', 'standards.slug']);

        $allottedIds = $allotted->pluck('id')->map(fn ($id) => (int) $id)->all();
        $allottedSlugs = $allotted->pluck('slug')->filter()->unique()->values()->all();
        $hasAllotments = $allottedIds !== [];

        $studentQuery = $this->allottedStudentsQuery($allottedSlugs);
        $teacherQuery = $this->allottedTeachersQuery($allottedIds);

        return view('principal.dashboard', [
            'totalStudents' => $hasAllotments ? (clone $studentQuery)->count() : 0,
            'totalTeachers' => $hasAllotments ? (clone $teacherQuery)->count() : 0,
            'pendingStudents' => $hasAllotments ? (clone $studentQuery)->pending()->count() : 0,
            'pendingTeachers' => $hasAllotments ? (clone $teacherQuery)->pending()->count() : 0,
            'allottedStandardsCount' => count($allottedIds),
            'todayLogins' => $hasAllotments ? $this->scopedLoginCount($allottedIds, $allottedSlugs) : 0,
            'activeSessions' => $hasAllotments ? $this->scopedActiveSessionCount($allottedIds, $allottedSlugs) : 0,
            'recentLogins' => $hasAllotments ? $this->scopedRecentLogins($allottedIds, $allottedSlugs) : collect(),
            'hasAllotments' => $hasAllotments,
        ]);
    }

    private function allottedStudentsQuery(array $allottedSlugs): Builder
    {
        return User::students()->whereIn('standard', $allottedSlugs);
    }

    private function allottedTeachersQuery(array $allottedIds): Builder
    {
        return User::teachers()->whereHas('teacherSubjects', function ($q) use ($allottedIds) {
            $q->whereIn('teacher_subjects.standard_id', $allottedIds);
        });
    }

    private function applyScopedUsers(Builder $query, array $allottedIds, array $allottedSlugs): Builder
    {
        return $query->where(function ($outer) use ($allottedIds, $allottedSlugs) {
            $outer->where(function ($q) use ($allottedSlugs) {
                $q->where('role', 'student')
                    ->whereIn('user_id', User::students()->whereIn('standard', $allottedSlugs)->select('id'));
            })->orWhere(function ($q) use ($allottedIds) {
                $q->where('role', 'teacher')
                    ->whereIn('user_id', TeacherSubject::query()
                        ->whereIn('standard_id', $allottedIds)
                        ->select('teacher_id'));
            });
        });
    }

    private function scopedLoginCount(array $allottedIds, array $allottedSlugs): int
    {
        return $this->applyScopedUsers(
            LoginLog::query()
                ->whereDate('logged_at', today())
                ->where('status', 'success')
                ->whereIn('role', ['student', 'teacher']),
            $allottedIds,
            $allottedSlugs
        )->count();
    }

    private function scopedActiveSessionCount(array $allottedIds, array $allottedSlugs): int
    {
        return $this->applyScopedUsers(
            UserSession::query()
                ->where('is_active', true)
                ->whereIn('role', ['student', 'teacher'])
                ->where('expires_at', '>', now()),
            $allottedIds,
            $allottedSlugs
        )->count();
    }

    private function scopedRecentLogins(array $allottedIds, array $allottedSlugs): Collection
    {
        return $this->applyScopedUsers(
            LoginLog::query()
                ->with('user')
                ->whereIn('role', ['student', 'teacher'])
                ->latest('logged_at')
                ->limit(10),
            $allottedIds,
            $allottedSlugs
        )->get();
    }
}
