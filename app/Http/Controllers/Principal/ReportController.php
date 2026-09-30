<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Mail\StudentLoginCredentialsMail;
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
use App\Support\ActivityLogger;
use App\Support\MailConfig;
use App\Support\PrincipalReportCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class ReportController extends Controller
{
    public function index(): View
    {
        $allotted = $this->allottedStandards(auth()->user());

        return view('principal.reports.index', [
            'groups' => PrincipalReportCatalog::groups(),
            'hasAllotments' => $allotted->isNotEmpty(),
            'allottedNames' => $allotted->pluck('name')->join(', '),
        ]);
    }

    public function allottedStudents(Request $request): View
    {
        $principal = auth()->user();
        $allotted = $this->allottedStandards($principal);
        $allottedSlugs = $allotted->pluck('slug')->filter()->unique()->values()->all();
        $search = $request->string('search')->trim()->toString();
        $medium = Material::normalizeMedium($request->string('medium')->trim()->toString()) ?: '';
        $standardSlug = $request->string('standard')->trim()->toString();
        if ($standardSlug !== '' && ! in_array($standardSlug, $allottedSlugs, true)) {
            $standardSlug = '';
        }
        $status = $request->string('status')->trim()->toString();

        if ($allottedSlugs === []) {
            return view('principal.reports.allotted-students', [
                'students' => User::students()->whereRaw('1 = 0')->paginate(1),
                'standards' => $allotted,
                'filters' => [
                    'search' => $search,
                    'medium' => $medium,
                    'standard' => '',
                    'status' => $status,
                ],
                'summary' => [
                    'students' => 0,
                    'approved' => 0,
                    'pending' => 0,
                    'standards' => 0,
                ],
                'hasAllotments' => false,
            ]);
        }

        $query = User::students()
            ->whereIn('standard', $allottedSlugs)
            ->orderBy('name');

        if ($standardSlug !== '') {
            $query->where('standard', $standardSlug);
        }
        if ($medium !== '') {
            $query->where('medium', $medium);
        }
        if ($status === 'approved') {
            $query->approved();
        } elseif ($status === 'pending') {
            $query->pending();
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $query->paginate(200)->withQueryString();

        $base = User::students()->whereIn('standard', $allottedSlugs);
        if ($standardSlug !== '') {
            $base->where('standard', $standardSlug);
        }
        if ($medium !== '') {
            $base->where('medium', $medium);
        }

        return view('principal.reports.allotted-students', [
            'students' => $students,
            'standards' => $allotted,
            'standardNames' => $allotted->pluck('name', 'slug'),
            'filters' => [
                'search' => $search,
                'medium' => $medium,
                'standard' => $standardSlug,
                'status' => $status,
            ],
            'summary' => [
                'students' => (clone $base)->count(),
                'approved' => (clone $base)->approved()->count(),
                'pending' => (clone $base)->pending()->count(),
                'standards' => count($allottedSlugs),
            ],
            'hasAllotments' => true,
        ]);
    }

    public function sendStudentCredentials(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:users,id'],
            'password' => ['required', 'string', 'min:8', 'max:64'],
            'reset_password' => ['nullable', 'boolean'],
        ]);

        $principal = auth()->user();
        $allottedSlugs = $this->allottedSlugs($principal);

        if ($allottedSlugs === []) {
            return back()->with('error', 'No standard allotted. Ask admin to allot standards first.');
        }

        $reset = $request->boolean('reset_password', true);
        $password = $validated['password'];
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        $students = User::students()
            ->whereIn('id', $validated['student_ids'])
            ->whereIn('standard', $allottedSlugs)
            ->get();

        foreach ($students as $student) {
            if (! filled($student->email) || ! filter_var($student->email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }

            if ($reset) {
                $student->password = $password;
                $student->save();
            }

            if ($this->sendLoginCredentialsMail($student, $password)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        ActivityLogger::log(
            'principal.students.send_credentials',
            "Principal sent student login mail: {$sent} sent, {$failed} failed, {$skipped} skipped",
            $principal,
            [
                'sent' => $sent,
                'failed' => $failed,
                'skipped' => $skipped,
                'reset_password' => $reset,
                'count' => count($validated['student_ids']),
            ]
        );

        $msg = "Login mail: {$sent} sent";
        if ($failed > 0) {
            $msg .= ", {$failed} failed";
        }
        if ($skipped > 0) {
            $msg .= ", {$skipped} skipped (no email)";
        }
        if ($reset && $sent > 0) {
            $msg .= '. Password was reset to the password you entered for mailed students.';
        }

        return back()->with($sent > 0 ? 'success' : 'error', $msg);
    }

    public function allottedTeachers(Request $request): View
    {
        $principal = auth()->user();
        $allottedIds = $this->allottedIds($principal);
        $search = $request->string('search')->trim()->toString();
        $medium = Material::normalizeMedium($request->string('medium')->trim()->toString()) ?: '';
        $standardId = $request->integer('standard_id') ?: null;
        if ($standardId && ! in_array($standardId, $allottedIds, true)) {
            $standardId = null;
        }

        $standards = $this->allottedStandards($principal);

        if ($allottedIds === []) {
            return view('principal.reports.allotted-teachers', [
                'teachers' => User::teachers()->whereRaw('1 = 0')->paginate(1),
                'standards' => $standards,
                'filters' => [
                    'search' => $search,
                    'medium' => $medium,
                    'standard_id' => '',
                ],
                'summary' => [
                    'teachers' => 0,
                    'assignments' => 0,
                    'unique_subjects' => 0,
                    'standards' => 0,
                ],
                'hasAllotments' => false,
            ]);
        }

        $query = User::teachers()
            ->whereHas('teacherSubjects', function ($q) use ($allottedIds, $medium, $standardId) {
                $q->whereIn('teacher_subjects.standard_id', $allottedIds);
                if ($medium !== '') {
                    $q->whereRaw('LOWER(TRIM(teacher_subjects.medium)) = ?', [$medium]);
                }
                if ($standardId) {
                    $q->where('teacher_subjects.standard_id', $standardId);
                }
            })
            ->with([
                'teacherSubjects' => function ($q) use ($allottedIds, $medium, $standardId) {
                    $q->with(['subject:id,name,standard_id', 'standard:id,name'])
                        ->whereIn('teacher_subjects.standard_id', $allottedIds)
                        ->when($medium !== '', fn ($inner) => $inner->whereRaw('LOWER(TRIM(teacher_subjects.medium)) = ?', [$medium]))
                        ->when($standardId, fn ($inner) => $inner->where('teacher_subjects.standard_id', $standardId))
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

        $teachers = $query->paginate(200)->withQueryString();

        $assignmentQuery = TeacherSubject::query()->whereIn('standard_id', $allottedIds);
        if ($medium !== '') {
            $assignmentQuery->whereRaw('LOWER(TRIM(medium)) = ?', [$medium]);
        }
        if ($standardId) {
            $assignmentQuery->where('standard_id', $standardId);
        }

        return view('principal.reports.allotted-teachers', [
            'teachers' => $teachers,
            'standards' => $standards,
            'filters' => [
                'search' => $search,
                'medium' => $medium,
                'standard_id' => $standardId ? (string) $standardId : '',
            ],
            'summary' => [
                'teachers' => $teachers->total(),
                'assignments' => (clone $assignmentQuery)->count(),
                'unique_subjects' => (int) (clone $assignmentQuery)->selectRaw('COUNT(DISTINCT subject_id) as aggregate')->value('aggregate'),
                'standards' => count($allottedIds),
            ],
            'hasAllotments' => true,
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

    public function sessions(Request $request): View
    {
        $principal = auth()->user();
        $allottedIds = $this->allottedIds($principal);
        $allottedSlugs = $this->allottedSlugs($principal);
        $teacherIds = $this->teacherIdsOnAllotment($allottedIds);

        $query = UserSession::query()
            ->with('user')
            ->whereIn('role', ['student', 'teacher'])
            ->latest('logged_in_at');

        $this->scopeSessionOrLoginUsers($query, $allottedSlugs, $teacherIds);

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

        $activeBase = UserSession::query()
            ->whereIn('role', ['student', 'teacher'])
            ->where('is_active', true)
            ->where('expires_at', '>', now());
        $this->scopeSessionOrLoginUsers($activeBase, $allottedSlugs, $teacherIds);

        $todayBase = UserSession::query()
            ->whereIn('role', ['student', 'teacher'])
            ->whereDate('logged_in_at', today());
        $this->scopeSessionOrLoginUsers($todayBase, $allottedSlugs, $teacherIds);

        return view('principal.reports.sessions', [
            'sessions' => $allottedSlugs === [] && $teacherIds === []
                ? UserSession::query()->whereRaw('1 = 0')->paginate(1)
                : $query->paginate(500)->withQueryString(),
            'filters' => $request->only(['role', 'active_only', 'from', 'to']),
            'summary' => [
                'active' => (clone $activeBase)->count(),
                'today' => (clone $todayBase)->count(),
                'student' => (clone $activeBase)->where('role', 'student')->count(),
                'teacher' => (clone $activeBase)->where('role', 'teacher')->count(),
            ],
            'hasAllotments' => $allottedIds !== [],
        ]);
    }

    public function logoutReports(Request $request): View
    {
        $principal = auth()->user();
        $allotted = $this->allottedStandards($principal);
        $allottedIds = $allotted->pluck('id')->map(fn ($id) => (int) $id)->all();
        $standardKeys = $allotted->flatMap(fn (Standard $s) => array_filter([(string) $s->name, (string) $s->slug]))->unique()->values()->all();
        $teacherIds = $this->teacherIdsOnAllotment($allottedIds);

        $query = TeacherLogoutReport::query()
            ->with(['teacher:id,name,mobile,email'])
            ->latest('submitted_at')
            ->latest('id');

        if ($allottedIds === []) {
            $query->whereRaw('1 = 0');
        } else {
            $query->where(function ($q) use ($teacherIds, $standardKeys) {
                if ($teacherIds !== []) {
                    $q->whereIn('teacher_id', $teacherIds);
                }
                if ($standardKeys !== []) {
                    $q->orWhereIn('standard', $standardKeys);
                }
                if ($teacherIds === [] && $standardKeys === []) {
                    $q->whereRaw('1 = 0');
                }
            });
        }

        if ($from = $request->date('from')) {
            $query->whereDate('report_date', '>=', $from);
        }
        if ($to = $request->date('to')) {
            $query->whereDate('report_date', '<=', $to);
        }
        if ($teacherId = $request->integer('teacher_id')) {
            if (in_array($teacherId, $teacherIds, true) || $teacherIds === []) {
                $query->where('teacher_id', $teacherId);
            } else {
                $query->whereRaw('1 = 0');
            }
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

        $summaryQuery = TeacherLogoutReport::query();
        if ($allottedIds === []) {
            $summaryQuery->whereRaw('1 = 0');
        } else {
            $summaryQuery->where(function ($q) use ($teacherIds, $standardKeys) {
                if ($teacherIds !== []) {
                    $q->whereIn('teacher_id', $teacherIds);
                }
                if ($standardKeys !== []) {
                    $q->orWhereIn('standard', $standardKeys);
                }
            });
        }

        return view('principal.reports.logout-reports', [
            'reports' => $query->paginate(500)->withQueryString(),
            'teachers' => User::teachers()->whereIn('id', $teacherIds ?: [0])->orderBy('name')->get(['id', 'name', 'mobile']),
            'filters' => [
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
                'teacher_id' => $request->string('teacher_id')->toString(),
                'status' => $request->string('status')->toString(),
                'search' => $search ?? '',
            ],
            'summary' => [
                'today' => (clone $summaryQuery)->whereDate('report_date', today())->count(),
                'total' => (clone $summaryQuery)->count(),
                'complete' => (clone $summaryQuery)->where('chk_complete', true)->count(),
                'remain' => (clone $summaryQuery)->where('chk_remain', true)->count(),
                'teachers' => (int) (clone $summaryQuery)->selectRaw('COUNT(DISTINCT teacher_id) as aggregate')->value('aggregate'),
            ],
            'hasAllotments' => $allottedIds !== [],
        ]);
    }

    public function logoutReportShow(TeacherLogoutReport $teacherLogoutReport): View
    {
        $principal = auth()->user();
        $allottedIds = $this->allottedIds($principal);
        $allotted = $this->allottedStandards($principal);
        $standardKeys = $allotted->flatMap(fn (Standard $s) => array_filter([(string) $s->name, (string) $s->slug]))->unique()->values()->all();
        $teacherIds = $this->teacherIdsOnAllotment($allottedIds);

        $allowed = in_array((int) $teacherLogoutReport->teacher_id, $teacherIds, true)
            || in_array((string) $teacherLogoutReport->standard, $standardKeys, true);
        abort_unless($allowed, 404);

        $teacherLogoutReport->load(['teacher:id,name,mobile,email']);

        return view('principal.reports.logout-report-show', [
            'report' => $teacherLogoutReport,
        ]);
    }

    public function studentWork(Request $request): View
    {
        $principal = auth()->user();
        $allotted = $this->allottedStandards($principal);
        $allottedSlugs = $allotted->pluck('slug')->filter()->unique()->values()->all();

        $from = $request->filled('from') ? $request->date('from') : today();
        $to = $request->filled('to') ? $request->date('to') : today();
        $search = $request->string('search')->trim()->toString();
        $workFilter = $request->string('work')->trim()->toString();
        $medium = Material::normalizeMedium($request->string('medium')->trim()->toString()) ?: '';
        $standard = $request->string('standard')->trim()->toString();
        if ($standard !== '' && ! in_array($standard, $allottedSlugs, true)) {
            $standard = '';
        }

        $studentsQuery = User::students()
            ->where('is_approved', true)
            ->whereIn('standard', $allottedSlugs ?: ['__none__'])
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

        $students = $allottedSlugs === []
            ? collect()
            : $studentsQuery->get(['id', 'name', 'mobile', 'email', 'medium', 'standard']);
        $studentIds = $students->pluck('id');

        $loginCounts = LoginLog::query()
            ->where('role', 'student')
            ->where('status', 'success')
            ->whereDate('logged_at', '>=', $from)
            ->whereDate('logged_at', '<=', $to)
            ->whereIn('user_id', $studentIds->isEmpty() ? [0] : $studentIds)
            ->selectRaw('user_id, COUNT(*) as login_count, MAX(logged_at) as last_login_at')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $examSheetCounts = ExamSubmission::query()
            ->whereDate('submitted_at', '>=', $from)
            ->whereDate('submitted_at', '<=', $to)
            ->whereIn('user_id', $studentIds->isEmpty() ? [0] : $studentIds)
            ->selectRaw('user_id, COUNT(*) as cnt')
            ->groupBy('user_id')
            ->pluck('cnt', 'user_id');

        $homeworkSheetCounts = HomeworkSubmission::query()
            ->whereDate('submitted_at', '>=', $from)
            ->whereDate('submitted_at', '<=', $to)
            ->whereIn('user_id', $studentIds->isEmpty() ? [0] : $studentIds)
            ->selectRaw('user_id, COUNT(*) as cnt')
            ->groupBy('user_id')
            ->pluck('cnt', 'user_id');

        $objectiveExam = collect();
        $objectiveHomework = collect();
        $workAttemptsTable = Schema::hasTable('student_work_attempts');

        if ($workAttemptsTable && $studentIds->isNotEmpty()) {
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

        return view('principal.reports.student-work', [
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
            'standards' => $allotted,
            'workAttemptsEnabled' => $workAttemptsTable,
            'hasAllotments' => $allottedSlugs !== [],
        ]);
    }

    private function roleLoginReport(Request $request, string $role): View
    {
        $principal = auth()->user();
        $allottedIds = $this->allottedIds($principal);
        $allottedSlugs = $this->allottedSlugs($principal);
        $teacherIds = $this->teacherIdsOnAllotment($allottedIds);

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

        $this->scopeLoginLogs($query, $role, $allottedSlugs, $teacherIds);

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
        $this->scopeLoginLogs($base, $role, $allottedSlugs, $teacherIds);

        $todayBase = LoginLog::query()->where('role', $role)->whereDate('logged_at', today());
        $this->scopeLoginLogs($todayBase, $role, $allottedSlugs, $teacherIds);

        $empty = ($role === 'student' && $allottedSlugs === []) || ($role === 'teacher' && $teacherIds === []);

        return view('principal.reports.role-logins', [
            'role' => $role,
            'roleLabel' => $role === 'teacher' ? 'Teacher' : 'Student',
            'logs' => $empty
                ? LoginLog::query()->whereRaw('1 = 0')->paginate(1)
                : $query->paginate(500)->withQueryString(),
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'status' => $status,
                'search' => $search,
            ],
            'summary' => [
                'total' => (clone $base)->count(),
                'success' => (clone $base)->where('status', 'success')->count(),
                'failed' => (clone $base)->where('status', 'failed')->count(),
                'unique_users' => (int) (clone $base)->where('status', 'success')->whereNotNull('user_id')->selectRaw('COUNT(DISTINCT user_id) as c')->value('c'),
                'today_success' => (clone $todayBase)->where('status', 'success')->count(),
                'today_failed' => (clone $todayBase)->where('status', 'failed')->count(),
            ],
            'hasAllotments' => $allottedIds !== [],
        ]);
    }

    private function scopeLoginLogs($query, string $role, array $allottedSlugs, array $teacherIds): void
    {
        if ($role === 'student') {
            $query->whereIn('user_id', User::students()->whereIn('standard', $allottedSlugs ?: ['__none__'])->select('id'));

            return;
        }

        $query->whereIn('user_id', $teacherIds ?: [0]);
    }

    private function scopeSessionOrLoginUsers($query, array $allottedSlugs, array $teacherIds): void
    {
        $query->where(function ($outer) use ($allottedSlugs, $teacherIds) {
            $outer->where(function ($q) use ($allottedSlugs) {
                $q->where('role', 'student')
                    ->whereIn('user_id', User::students()->whereIn('standard', $allottedSlugs ?: ['__none__'])->select('id'));
            })->orWhere(function ($q) use ($teacherIds) {
                $q->where('role', 'teacher')
                    ->whereIn('user_id', $teacherIds ?: [0]);
            });
        });
    }

    private function allottedStandards(?User $principal): Collection
    {
        if (! $principal) {
            return collect();
        }

        return $principal->allottedStandards()
            ->where('is_active', true)
            ->orderedByNumber()
            ->get(['standards.id', 'standards.name', 'standards.slug', 'standards.medium']);
    }

    private function allottedIds(?User $principal): array
    {
        return $this->allottedStandards($principal)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function allottedSlugs(?User $principal): array
    {
        return $this->allottedStandards($principal)
            ->pluck('slug')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function teacherIdsOnAllotment(array $allottedIds): array
    {
        if ($allottedIds === []) {
            return [];
        }

        return TeacherSubject::query()
            ->whereIn('standard_id', $allottedIds)
            ->distinct()
            ->pluck('teacher_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function sendLoginCredentialsMail(User $student, string $plainPassword): bool
    {
        $email = trim((string) $student->email);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            MailConfig::apply();
            Mail::to($email)->send(new StudentLoginCredentialsMail(
                $student,
                $plainPassword,
                route('student.login'),
                url('/')
            ));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
