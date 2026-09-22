<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\LoginLog;
use App\Models\Material;
use App\Models\Standard;
use App\Models\TeacherSubject;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function logins(Request $request): View
    {
        $query = LoginLog::with('user')->whereIn('role', ['student', 'teacher'])->latest('logged_at');

        if ($role = $request->string('role')->trim()->toString()) {
            $query->where('role', $role);
        }

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }

        if ($from = $request->date('from')) {
            $query->whereDate('logged_at', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->whereDate('logged_at', '<=', $to);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('login_identifier', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        return view('admin.reports.logins', [
            'logs' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['role', 'status', 'from', 'to', 'search']),
            'summary' => [
                'today' => LoginLog::whereIn('role', ['student', 'teacher'])->whereDate('logged_at', today())->count(),
                'student' => LoginLog::where('role', 'student')->where('status', 'success')->count(),
                'teacher' => LoginLog::where('role', 'teacher')->where('status', 'success')->count(),
                'failed' => LoginLog::whereIn('role', ['student', 'teacher'])->where('status', 'failed')->count(),
            ],
        ]);
    }

    public function sessions(Request $request): View
    {
        $query = UserSession::with('user')->whereIn('role', ['student', 'teacher'])->latest('logged_in_at');

        if ($role = $request->string('role')->trim()->toString()) {
            $query->where('role', $role);
        }

        if ($request->boolean('active_only')) {
            $query->where('is_active', true)->where('expires_at', '>', now());
        }

        if ($from = $request->date('from')) {
            $query->whereDate('logged_in_at', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->whereDate('logged_in_at', '<=', $to);
        }

        return view('admin.reports.sessions', [
            'sessions' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['role', 'active_only', 'from', 'to']),
            'summary' => [
                'active' => UserSession::whereIn('role', ['student', 'teacher'])->where('is_active', true)->where('expires_at', '>', now())->count(),
                'today' => UserSession::whereIn('role', ['student', 'teacher'])->whereDate('logged_in_at', today())->count(),
                'student' => UserSession::where('role', 'student')->where('is_active', true)->where('expires_at', '>', now())->count(),
                'teacher' => UserSession::where('role', 'teacher')->where('is_active', true)->where('expires_at', '>', now())->count(),
            ],
        ]);
    }

    public function teacherSubjects(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $medium = Material::normalizeMedium($request->string('medium')->trim()->toString()) ?: '';
        $standardId = $request->integer('standard_id') ?: null;
        $assignment = $request->string('assignment')->trim()->toString();

        $query = User::teachers()
            ->with([
                'teacherSubjects' => function ($q) use ($medium, $standardId) {
                    $q->with(['subject:id,name,standard_id', 'standard:id,name'])
                        ->when($medium !== '', fn ($inner) => $inner->whereRaw('LOWER(TRIM(medium)) = ?', [$medium]))
                        ->when($standardId, fn ($inner) => $inner->where('standard_id', $standardId))
                        ->orderBy('medium')
                        ->orderBy('standard_id')
                        ->orderBy('subject_id');
                },
            ])
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($medium !== '' || $standardId) {
            $query->whereHas('teacherSubjects', function ($q) use ($medium, $standardId) {
                if ($medium !== '') {
                    $q->whereRaw('LOWER(TRIM(medium)) = ?', [$medium]);
                }
                if ($standardId) {
                    $q->where('standard_id', $standardId);
                }
            });
        }

        if ($assignment === 'assigned') {
            $query->has('teacherSubjects');
        } elseif ($assignment === 'unassigned') {
            $query->doesntHave('teacherSubjects');
        }

        $teachers = $query->paginate(500)->withQueryString();

        $totalAssignments = TeacherSubject::query()->count();
        $teachersWithSubjects = User::teachers()->has('teacherSubjects')->count();
        $teachersWithoutSubjects = User::teachers()->doesntHave('teacherSubjects')->count();
        $uniqueSubjects = (int) TeacherSubject::query()->selectRaw('COUNT(DISTINCT subject_id) as aggregate')->value('aggregate');

        return view('admin.reports.teacher-subjects', [
            'teachers' => $teachers,
            'standards' => Standard::query()->orderedByNumber()->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'medium' => $medium,
                'standard_id' => $standardId ? (string) $standardId : '',
                'assignment' => $assignment,
            ],
            'summary' => [
                'assignments' => $totalAssignments,
                'with_subjects' => $teachersWithSubjects,
                'without_subjects' => $teachersWithoutSubjects,
                'unique_subjects' => $uniqueSubjects,
            ],
        ]);
    }

    public function activity(Request $request): View
    {
        $query = ActivityLog::query()->with('user:id,name,mobile,email,role')->latest('created_at');

        if ($role = $request->string('role')->trim()->toString()) {
            $query->where('role', $role);
        }

        if ($action = $request->string('action')->trim()->toString()) {
            $query->where('action', $action);
        }

        if ($from = $request->date('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $actions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('admin.reports.activity', [
            'logs' => $query->paginate(30)->withQueryString(),
            'filters' => $request->only(['role', 'action', 'from', 'to', 'search']),
            'actionOptions' => $actions,
            'actionLabels' => ActivityLog::ACTION_LABELS,
            'summary' => [
                'today' => ActivityLog::query()->whereDate('created_at', today())->count(),
                'total' => ActivityLog::query()->count(),
                'admin' => ActivityLog::query()->where('role', 'admin')->count(),
                'teacher' => ActivityLog::query()->where('role', 'teacher')->count(),
                'student' => ActivityLog::query()->where('role', 'student')->count(),
            ],
        ]);
    }

    public function activityShow(ActivityLog $activityLog): View
    {
        $activityLog->load('user:id,name,mobile,email,role');

        return view('admin.reports.activity-show', [
            'log' => $activityLog,
            'actionLabels' => ActivityLog::ACTION_LABELS,
        ]);
    }
}
