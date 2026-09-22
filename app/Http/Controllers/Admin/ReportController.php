<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ExamSubmission;
use App\Models\HomeworkSubmission;
use App\Models\LoginLog;
use App\Models\Material;
use App\Models\Standard;
use App\Models\StudentWorkAttempt;
use App\Models\TeacherLogoutReport;
use App\Models\TeacherSubject;
use App\Models\User;
use App\Models\UserSession;
use App\Support\AdminMaterialUploadReport;
use App\Support\AdminReportCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('admin.reports.index', [
            'groups' => AdminReportCatalog::groups(),
        ]);
    }

    public function logins(Request $request): View
    {
        return view('admin.reports.logins-hub', [
            'studentToday' => LoginLog::query()->where('role', 'student')->where('status', 'success')->whereDate('logged_at', today())->count(),
            'teacherToday' => LoginLog::query()->where('role', 'teacher')->where('status', 'success')->whereDate('logged_at', today())->count(),
            'studentFailedToday' => LoginLog::query()->where('role', 'student')->where('status', 'failed')->whereDate('logged_at', today())->count(),
            'teacherFailedToday' => LoginLog::query()->where('role', 'teacher')->where('status', 'failed')->whereDate('logged_at', today())->count(),
            'studentUniqueToday' => (int) LoginLog::query()->where('role', 'student')->where('status', 'success')->whereDate('logged_at', today())->whereNotNull('user_id')->selectRaw('COUNT(DISTINCT user_id) as c')->value('c'),
            'teacherUniqueToday' => (int) LoginLog::query()->where('role', 'teacher')->where('status', 'success')->whereDate('logged_at', today())->whereNotNull('user_id')->selectRaw('COUNT(DISTINCT user_id) as c')->value('c'),
        ]);
    }

    public function studentLogins(Request $request): View
    {
        return $this->roleLoginReport($request, 'student');
    }

    public function teacherLogins(Request $request): View
    {
        return $this->roleLoginReport($request, 'teacher');
    }

    private function roleLoginReport(Request $request, string $role): View
    {
        $from = $request->filled('from') ? $request->date('from') : today();
        $to = $request->filled('to') ? $request->date('to') : today();
        $status = $request->string('status')->trim()->toString();
        $search = $request->string('search')->trim()->toString();

        $query = LoginLog::query()
            ->with(['user:id,name,mobile,email,medium,standard,role'])
            ->where('role', $role)
            ->whereDate('logged_at', '>=', $from)
            ->whereDate('logged_at', '<=', $to)
            ->latest('logged_at');

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('login_identifier', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $base = LoginLog::query()
            ->where('role', $role)
            ->whereDate('logged_at', '>=', $from)
            ->whereDate('logged_at', '<=', $to);

        $summary = [
            'total' => (clone $base)->count(),
            'success' => (clone $base)->where('status', 'success')->count(),
            'failed' => (clone $base)->where('status', 'failed')->count(),
            'unique_users' => (int) (clone $base)->where('status', 'success')->whereNotNull('user_id')->selectRaw('COUNT(DISTINCT user_id) as c')->value('c'),
            'today_success' => LoginLog::query()->where('role', $role)->where('status', 'success')->whereDate('logged_at', today())->count(),
            'today_failed' => LoginLog::query()->where('role', $role)->where('status', 'failed')->whereDate('logged_at', today())->count(),
        ];

        return view('admin.reports.role-logins', [
            'role' => $role,
            'roleLabel' => $role === 'teacher' ? 'Teacher' : 'Student',
            'logs' => $query->paginate(500)->withQueryString(),
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'status' => $status,
                'search' => $search,
            ],
            'summary' => $summary,
        ]);
    }

    public function studentWork(Request $request): View
    {
        $from = $request->filled('from') ? $request->date('from') : today();
        $to = $request->filled('to') ? $request->date('to') : today();
        $search = $request->string('search')->trim()->toString();
        $workFilter = $request->string('work')->trim()->toString(); // worked|login_only|inactive|all
        $medium = Material::normalizeMedium($request->string('medium')->trim()->toString()) ?: '';
        $standard = $request->string('standard')->trim()->toString();

        $studentsQuery = User::students()
            ->where('is_approved', true)
            ->orderBy('name');

        if ($search !== '') {
            $studentsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if ($medium !== '') {
            $studentsQuery->whereRaw('LOWER(TRIM(medium)) = ?', [$medium]);
        }
        if ($standard !== '') {
            $studentsQuery->where('standard', $standard);
        }

        $students = $studentsQuery->get(['id', 'name', 'mobile', 'email', 'medium', 'standard']);
        $studentIds = $students->pluck('id');

        $loginCounts = LoginLog::query()
            ->where('role', 'student')
            ->where('status', 'success')
            ->whereDate('logged_at', '>=', $from)
            ->whereDate('logged_at', '<=', $to)
            ->whereIn('user_id', $studentIds)
            ->selectRaw('user_id, COUNT(*) as login_count, MAX(logged_at) as last_login_at')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $examSheetCounts = ExamSubmission::query()
            ->whereDate('submitted_at', '>=', $from)
            ->whereDate('submitted_at', '<=', $to)
            ->whereIn('user_id', $studentIds)
            ->selectRaw('user_id, COUNT(*) as cnt')
            ->groupBy('user_id')
            ->pluck('cnt', 'user_id');

        $homeworkSheetCounts = HomeworkSubmission::query()
            ->whereDate('submitted_at', '>=', $from)
            ->whereDate('submitted_at', '<=', $to)
            ->whereIn('user_id', $studentIds)
            ->selectRaw('user_id, COUNT(*) as cnt')
            ->groupBy('user_id')
            ->pluck('cnt', 'user_id');

        $objectiveExam = collect();
        $objectiveHomework = collect();
        $workAttemptsTable = Schema::hasTable('student_work_attempts');

        if ($workAttemptsTable) {
            $objectiveExam = StudentWorkAttempt::query()
                ->where('work_type', StudentWorkAttempt::TYPE_EXAM_OBJECTIVE)
                ->whereDate('attempted_at', '>=', $from)
                ->whereDate('attempted_at', '<=', $to)
                ->whereIn('user_id', $studentIds)
                ->selectRaw('user_id, COUNT(*) as cnt')
                ->groupBy('user_id')
                ->pluck('cnt', 'user_id');

            $objectiveHomework = StudentWorkAttempt::query()
                ->where('work_type', StudentWorkAttempt::TYPE_HOMEWORK_OBJECTIVE)
                ->whereDate('attempted_at', '>=', $from)
                ->whereDate('attempted_at', '<=', $to)
                ->whereIn('user_id', $studentIds)
                ->selectRaw('user_id, COUNT(*) as cnt')
                ->groupBy('user_id')
                ->pluck('cnt', 'user_id');

            // Prefer logged sheet attempts when present; still count old submissions above.
            $loggedExamSheets = StudentWorkAttempt::query()
                ->where('work_type', StudentWorkAttempt::TYPE_EXAM_SHEET)
                ->whereDate('attempted_at', '>=', $from)
                ->whereDate('attempted_at', '<=', $to)
                ->whereIn('user_id', $studentIds)
                ->selectRaw('user_id, COUNT(*) as cnt')
                ->groupBy('user_id')
                ->pluck('cnt', 'user_id');

            $loggedHomeworkSheets = StudentWorkAttempt::query()
                ->where('work_type', StudentWorkAttempt::TYPE_HOMEWORK_SHEET)
                ->whereDate('attempted_at', '>=', $from)
                ->whereDate('attempted_at', '<=', $to)
                ->whereIn('user_id', $studentIds)
                ->selectRaw('user_id, COUNT(*) as cnt')
                ->groupBy('user_id')
                ->pluck('cnt', 'user_id');

            foreach ($loggedExamSheets as $uid => $cnt) {
                $examSheetCounts[$uid] = max((int) ($examSheetCounts[$uid] ?? 0), (int) $cnt);
            }
            foreach ($loggedHomeworkSheets as $uid => $cnt) {
                $homeworkSheetCounts[$uid] = max((int) ($homeworkSheetCounts[$uid] ?? 0), (int) $cnt);
            }
        }

        $rows = $students->map(function (User $student) use (
            $loginCounts,
            $examSheetCounts,
            $homeworkSheetCounts,
            $objectiveExam,
            $objectiveHomework
        ) {
            $logins = (int) ($loginCounts[$student->id]->login_count ?? 0);
            $lastLogin = $loginCounts[$student->id]->last_login_at ?? null;
            $examAttempts = (int) ($examSheetCounts[$student->id] ?? 0);
            $homeworkAttempts = (int) ($homeworkSheetCounts[$student->id] ?? 0);
            $objExam = (int) ($objectiveExam[$student->id] ?? 0);
            $objHw = (int) ($objectiveHomework[$student->id] ?? 0);
            $objectiveTotal = $objExam + $objHw;
            $didWork = ($examAttempts + $homeworkAttempts + $objectiveTotal) > 0;

            if ($didWork) {
                $status = 'worked';
                $statusLabel = 'Work done';
            } elseif ($logins > 0) {
                $status = 'login_only';
                $statusLabel = 'Login only';
            } else {
                $status = 'inactive';
                $statusLabel = 'No activity';
            }

            return [
                'id' => $student->id,
                'name' => $student->name,
                'mobile' => $student->mobile,
                'email' => $student->email,
                'medium' => $student->medium,
                'standard' => $student->standardLabel(),
                'logins' => $logins,
                'last_login' => $lastLogin,
                'exam_attempts' => $examAttempts,
                'homework_attempts' => $homeworkAttempts,
                'objective_attempts' => $objectiveTotal,
                'objective_exam' => $objExam,
                'objective_homework' => $objHw,
                'did_work' => $didWork,
                'status' => $status,
                'status_label' => $statusLabel,
            ];
        });

        $summary = [
            'students' => $rows->count(),
            'login_students' => $rows->where('logins', '>', 0)->count(),
            'login_attempts' => (int) $rows->sum('logins'),
            'exam_students' => $rows->where('exam_attempts', '>', 0)->count(),
            'homework_students' => $rows->where('homework_attempts', '>', 0)->count(),
            'objective_students' => $rows->where('objective_attempts', '>', 0)->count(),
            'worked' => $rows->where('status', 'worked')->count(),
            'login_only' => $rows->where('status', 'login_only')->count(),
            'inactive' => $rows->where('status', 'inactive')->count(),
        ];

        if (in_array($workFilter, ['worked', 'login_only', 'inactive'], true)) {
            $rows = $rows->where('status', $workFilter)->values();
        }

        return view('admin.reports.student-work', [
            'rows' => $rows,
            'summary' => $summary,
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'search' => $search,
                'work' => $workFilter,
                'medium' => $medium,
                'standard' => $standard,
            ],
            'mediums' => Standard::MEDIUMS,
            'workAttemptsEnabled' => $workAttemptsTable,
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
            'sessions' => $query->paginate(500)->withQueryString(),
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
            'logs' => $query->paginate(500)->withQueryString(),
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

    public function chapterList(Request $request, AdminMaterialUploadReport $report): View
    {
        $payload = $report->build(
            (string) $request->input('standard', ''),
            (string) $request->input('subject', ''),
            'standard',
            'asc',
            (string) $request->input('medium', '')
        );

        $rows = collect($payload['rows'] ?? [])->sortBy([
            ['medium_key', 'asc'],
            ['standard_sort', 'asc'],
            ['subject', 'asc'],
            ['chapter_no_sort', 'asc'],
            ['chapter_name', 'asc'],
        ])->values();

        $groups = $rows
            ->groupBy(fn (array $row) => ($row['medium_key'] ?? 'other').'|'.($row['standard_key'] ?? '').'|'.mb_strtolower((string) ($row['subject'] ?? '')))
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'medium' => $first['medium'] ?? '—',
                    'medium_key' => $first['medium_key'] ?? 'other',
                    'standard' => $first['standard'] ?? '—',
                    'subject' => $first['subject'] ?? '—',
                    'chapters' => $items->values()->all(),
                    'count' => $items->count(),
                    'complete' => $items->where('status', 'complete')->count(),
                ];
            })
            ->values();

        return view('admin.reports.chapter-list', [
            'groups' => $groups,
            'rows' => $rows,
            'standards' => collect($payload['standards'] ?? []),
            'subjects' => $payload['subjects'] ?? [],
            'filters' => [
                'medium' => $payload['medium_filter'] ?? 'all',
                'standard' => $payload['standard_filter'] ?? 'all',
                'subject' => $payload['subject_filter'] ?? 'all',
            ],
            'summary' => [
                'chapters' => $rows->count(),
                'subjects' => $groups->count(),
                'complete' => $rows->where('status', 'complete')->count(),
                'partial' => $rows->where('status', 'partial')->count(),
                'english' => $rows->where('medium_key', 'english')->count(),
                'gujarati' => $rows->where('medium_key', 'gujarati')->count(),
            ],
        ]);
    }

    public function logoutReports(Request $request): View
    {
        $query = TeacherLogoutReport::query()
            ->with(['teacher:id,name,mobile,email'])
            ->latest('submitted_at')
            ->latest('id');

        if ($from = $request->date('from')) {
            $query->whereDate('report_date', '>=', $from);
        }
        if ($to = $request->date('to')) {
            $query->whereDate('report_date', '<=', $to);
        }
        if ($teacherId = $request->integer('teacher_id')) {
            $query->where('teacher_id', $teacherId);
        }
        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }
        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('subject_name', 'like', "%{$search}%")
                    ->orWhere('chapter_name', 'like', "%{$search}%")
                    ->orWhere('topic_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhereHas('teacher', fn ($t) => $t->where('name', 'like', "%{$search}%"));
            });
        }

        return view('admin.reports.logout-reports', [
            'reports' => $query->paginate(500)->withQueryString(),
            'teachers' => User::teachers()->orderBy('name')->get(['id', 'name', 'mobile']),
            'filters' => [
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
                'teacher_id' => $request->string('teacher_id')->toString(),
                'status' => $request->string('status')->toString(),
                'search' => $search ?? '',
            ],
            'summary' => [
                'today' => TeacherLogoutReport::query()->whereDate('report_date', today())->count(),
                'total' => TeacherLogoutReport::query()->count(),
                'complete' => TeacherLogoutReport::query()->where('chk_complete', true)->count(),
                'remain' => TeacherLogoutReport::query()->where('chk_remain', true)->count(),
                'teachers' => (int) TeacherLogoutReport::query()->selectRaw('COUNT(DISTINCT teacher_id) as aggregate')->value('aggregate'),
            ],
        ]);
    }

    public function logoutReportShow(TeacherLogoutReport $teacherLogoutReport): View
    {
        $teacherLogoutReport->load(['teacher:id,name,mobile,email']);

        return view('admin.reports.logout-report-show', [
            'report' => $teacherLogoutReport,
        ]);
    }
}
