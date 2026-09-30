<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Mail\StudentLoginCredentialsMail;
use App\Models\Material;
use App\Models\TeacherSubject;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\MailConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class ReportController extends Controller
{
    public function allottedStudents(Request $request): View
    {
        $principal = auth()->user();
        $allotted = $principal->allottedStandards()
            ->where('is_active', true)
            ->orderedByNumber()
            ->get(['standards.id', 'standards.name', 'standards.slug', 'standards.medium']);

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
        $allottedSlugs = $principal->allottedStandards()
            ->where('is_active', true)
            ->pluck('standards.slug')
            ->filter()
            ->unique()
            ->values()
            ->all();

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
        $allottedIds = $principal->allottedStandards()
            ->where('is_active', true)
            ->pluck('standards.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $search = $request->string('search')->trim()->toString();
        $medium = Material::normalizeMedium($request->string('medium')->trim()->toString()) ?: '';
        $standardId = $request->integer('standard_id') ?: null;
        if ($standardId && ! in_array($standardId, $allottedIds, true)) {
            $standardId = null;
        }

        $standards = $principal->allottedStandards()
            ->where('is_active', true)
            ->orderedByNumber()
            ->get(['standards.id', 'standards.name', 'standards.slug', 'standards.medium']);

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
                $q->whereIn('standard_id', $allottedIds);
                if ($medium !== '') {
                    $q->whereRaw('LOWER(TRIM(medium)) = ?', [$medium]);
                }
                if ($standardId) {
                    $q->where('standard_id', $standardId);
                }
            })
            ->with([
                'teacherSubjects' => function ($q) use ($allottedIds, $medium, $standardId) {
                    $q->with(['subject:id,name,standard_id', 'standard:id,name'])
                        ->whereIn('standard_id', $allottedIds)
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
