<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\StudentLoginCredentialsMail;
use App\Models\LoginLog;
use App\Models\Standard;
use App\Models\User;
use App\Models\UserSession;
use App\Support\ActivityLogger;
use App\Support\MailConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::students()->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($standard = $request->string('standard')->trim()->toString()) {
            $query->where('standard', $standard);
        }

        if ($medium = $request->string('medium')->trim()->toString()) {
            $query->where('medium', $medium);
        }

        if ($status = $request->string('status')->trim()->toString()) {
            if ($status === 'approved') {
                $query->approved();
            } elseif ($status === 'pending') {
                $query->pending();
            }
        }

        return view('admin.students.index', [
            'students' => $query->paginate(500)->withQueryString(),
            'standards' => Standard::orderBy('sort_order')->pluck('name', 'slug'),
            'filters' => $request->only(['search', 'standard', 'medium', 'status']),
            'pendingCount' => User::students()->pending()->count(),
        ]);
    }

    public function show(User $student): View
    {
        $this->ensureStudent($student);

        return view('admin.students.show', [
            'student' => $student,
            'loginLogs' => LoginLog::where('user_id', $student->id)->latest('logged_at')->limit(20)->get(),
            'sessions' => UserSession::where('user_id', $student->id)->latest('logged_in_at')->limit(20)->get(),
        ]);
    }

    public function edit(User $student): View
    {
        $this->ensureStudent($student);

        return view('admin.students.edit', [
            'student' => $student,
            'standards' => Standard::orderBy('sort_order')->pluck('name', 'slug'),
            'mediums' => ['english' => 'English', 'gujarati' => 'Gujarati'],
        ]);
    }

    public function update(Request $request, User $student)
    {
        $this->ensureStudent($student);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', 'unique:users,mobile,'.$student->id],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$student->id],
            'medium' => ['required', 'in:english,gujarati'],
            'standard' => ['required', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $student->fill([
            'name' => $validated['name'],
            'mobile' => $validated['mobile'],
            'email' => $validated['email'] ?? null,
            'medium' => $validated['medium'],
            'standard' => $validated['standard'],
        ]);

        if (! empty($validated['password'])) {
            $student->password = $validated['password'];
        }

        $student->save();

        return redirect()->route('admin.students.index')->with('success', 'Student updated successfully.');
    }

    public function destroy(User $student)
    {
        $this->ensureStudent($student);
        $student->delete();

        return redirect()->route('admin.students.index')->with('success', 'Student deleted successfully.');
    }

    public function approve(User $student)
    {
        $this->ensureStudent($student);
        $student->update(['is_approved' => true]);

        ActivityLogger::log(
            'admin.user.approve',
            'Approved student '.$student->name,
            $student,
            ['target_role' => 'student']
        );

        return redirect()
            ->back()
            ->with('success', $student->name.' has been approved and can now login.');
    }

    public function pending(User $student)
    {
        $this->ensureStudent($student);
        $student->update(['is_approved' => false]);

        ActivityLogger::log(
            'admin.user.pending',
            'Set student '.$student->name.' to pending',
            $student,
            ['target_role' => 'student']
        );

        return redirect()
            ->back()
            ->with('success', $student->name.' has been set to pending. Login is disabled until approved again.');
    }

    public function report(): View
    {
        $byStandard = User::students()
            ->select('standard', DB::raw('count(*) as total'))
            ->groupBy('standard')
            ->orderBy('standard')
            ->get();

        $byMedium = User::students()
            ->select('medium', DB::raw('count(*) as total'))
            ->groupBy('medium')
            ->get();

        return view('admin.students.report', [
            'totalStudents' => User::students()->count(),
            'todayRegistered' => User::students()->whereDate('created_at', today())->count(),
            'todayLogins' => LoginLog::where('role', 'student')->where('status', 'success')->whereDate('logged_at', today())->count(),
            'activeSessions' => UserSession::where('role', 'student')->where('is_active', true)->where('expires_at', '>', now())->count(),
            'byStandard' => $byStandard,
            'byMedium' => $byMedium,
            'recentStudents' => User::students()->latest()->limit(10)->get(),
        ]);
    }

    public function uploadForm(): View
    {
        return view('admin.students.upload', [
            'standards' => Standard::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'slug', 'medium']),
            'mediums' => Standard::MEDIUMS,
            'defaultPassword' => 'Student@123',
        ]);
    }

    public function uploadTemplate(): StreamedResponse
    {
        $filename = 'student-upload-template.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
            fputcsv($out, ['name', 'mobile', 'email', 'password']);
            fputcsv($out, ['Rahul Patel', '9876543210', 'rahul@example.com', '']);
            fputcsv($out, ['Priya Shah', '9876543211', '', 'MyPass@123']);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function uploadStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'medium' => ['required', Rule::in(array_keys(Standard::MEDIUMS))],
            'standard' => ['required', 'string', Rule::exists('standards', 'slug')->where(fn ($q) => $q->where('is_active', true))],
            'default_password' => ['required', 'string', 'min:8', 'max:64'],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'approve' => ['nullable', 'boolean'],
            'send_mail' => ['nullable', 'boolean'],
        ]);

        $approve = $request->boolean('approve', true);
        $sendMail = $request->boolean('send_mail');
        $medium = $validated['medium'];
        $standardSlug = $validated['standard'];
        $defaultPassword = $validated['default_password'];

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return back()->with('error', 'Could not read uploaded file.')->withInput();
        }

        $header = null;
        $created = 0;
        $skipped = 0;
        $mailed = 0;
        $mailFailed = 0;
        $errors = [];
        $rowNum = 0;
        $seenMobiles = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if ($row === [null] || (count($row) === 1 && trim((string) $row[0]) === '')) {
                continue;
            }

            // Strip BOM from first cell
            if ($header === null) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) ($row[0] ?? ''));
                $normalized = array_map(fn ($h) => strtolower(trim((string) $h)), $row);
                if (in_array('name', $normalized, true) && in_array('mobile', $normalized, true)) {
                    $header = $normalized;
                    continue;
                }
                // No header — treat first row as data with fixed order
                $header = ['name', 'mobile', 'email', 'password'];
            }

            $data = [];
            foreach ($header as $i => $key) {
                $data[$key] = trim((string) ($row[$i] ?? ''));
            }

            $name = $data['name'] ?? '';
            $mobile = preg_replace('/\s+/', '', $data['mobile'] ?? '') ?: '';
            $email = ($data['email'] ?? '') !== '' ? strtolower($data['email']) : null;
            $password = ($data['password'] ?? '') !== '' ? $data['password'] : $defaultPassword;

            if ($name === '' && $mobile === '') {
                continue;
            }

            if ($name === '' || $mobile === '') {
                $skipped++;
                $errors[] = "Row {$rowNum}: name and mobile are required.";
                continue;
            }

            if (strlen($password) < 8) {
                $skipped++;
                $errors[] = "Row {$rowNum} ({$mobile}): password must be at least 8 characters.";
                continue;
            }

            if (isset($seenMobiles[$mobile])) {
                $skipped++;
                $errors[] = "Row {$rowNum}: duplicate mobile {$mobile} in file.";
                continue;
            }
            $seenMobiles[$mobile] = true;

            if (User::query()->where('mobile', $mobile)->exists()) {
                $skipped++;
                $errors[] = "Row {$rowNum}: mobile {$mobile} already registered.";
                continue;
            }

            if ($email && User::query()->where('email', $email)->exists()) {
                $skipped++;
                $errors[] = "Row {$rowNum}: email {$email} already registered.";
                continue;
            }

            if ($sendMail && ! $email) {
                $skipped++;
                $errors[] = "Row {$rowNum} ({$mobile}): email required when Send mail is checked.";
                continue;
            }

            try {
                $student = User::query()->create([
                    'name' => $name,
                    'mobile' => $mobile,
                    'email' => $email,
                    'medium' => $medium,
                    'standard' => $standardSlug,
                    'password' => $password,
                    'role' => 'student',
                    'is_approved' => $approve,
                ]);
                $created++;

                if ($sendMail && $email) {
                    if ($this->sendLoginCredentialsMail($student, $password)) {
                        $mailed++;
                    } else {
                        $mailFailed++;
                        $errors[] = "Row {$rowNum} ({$mobile}): account created but mail failed.";
                    }
                }
            } catch (Throwable $e) {
                report($e);
                $skipped++;
                $errors[] = "Row {$rowNum} ({$mobile}): ".$e->getMessage();
            }
        }

        fclose($handle);

        ActivityLogger::log(
            'admin.students.upload',
            "Uploaded student list ({$medium} / {$standardSlug}): {$created} created, {$skipped} skipped, {$mailed} mailed",
            null,
            [
                'medium' => $medium,
                'standard' => $standardSlug,
                'created' => $created,
                'skipped' => $skipped,
                'mailed' => $mailed,
                'mail_failed' => $mailFailed,
                'approved' => $approve,
                'send_mail' => $sendMail,
            ]
        );

        $message = "Student upload finished: {$created} created";
        if ($skipped > 0) {
            $message .= ", {$skipped} skipped";
        }
        if ($sendMail) {
            $message .= ", {$mailed} mail sent";
            if ($mailFailed > 0) {
                $message .= ", {$mailFailed} mail failed";
            }
        }
        $message .= '. Login with mobile + password'.($approve ? ' (approved).' : ' after approval.');

        return redirect()
            ->route('admin.students.upload')
            ->with('success', $message)
            ->with('upload_errors', array_slice($errors, 0, 40))
            ->with('upload_summary', ['created' => $created, 'skipped' => $skipped, 'mailed' => $mailed]);
    }

    public function storeOne(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', 'unique:users,mobile'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'medium' => ['required', Rule::in(array_keys(Standard::MEDIUMS))],
            'standard' => ['required', 'string', Rule::exists('standards', 'slug')->where(fn ($q) => $q->where('is_active', true))],
            'password' => ['required', 'string', 'min:8', 'max:64'],
            'approve' => ['nullable', 'boolean'],
            'send_mail' => ['nullable', 'boolean'],
        ]);

        $sendMail = $request->boolean('send_mail');
        if ($sendMail && empty($validated['email'])) {
            return back()->withInput()->with('error', 'Email is required to send login mail.');
        }

        $student = User::query()->create([
            'name' => $validated['name'],
            'mobile' => preg_replace('/\s+/', '', $validated['mobile']),
            'email' => $validated['email'] ?? null,
            'medium' => $validated['medium'],
            'standard' => $validated['standard'],
            'password' => $validated['password'],
            'role' => 'student',
            'is_approved' => $request->boolean('approve', true),
        ]);

        ActivityLogger::log(
            'admin.students.create',
            'Created student '.$student->name,
            $student,
            ['medium' => $student->medium, 'standard' => $student->standard]
        );

        $mailNote = '';
        if ($sendMail) {
            $mailNote = $this->sendLoginCredentialsMail($student, $validated['password'])
                ? ' Login mail sent.'
                : ' Login mail failed — check mail settings.';
        }

        return redirect()
            ->route('admin.students.index', ['medium' => $student->medium, 'standard' => $student->standard])
            ->with('success', $student->name.' created. Login with mobile '.$student->mobile.' and the set password.'.$mailNote);
    }

    public function sendCredentials(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:users,id'],
            'password' => ['required', 'string', 'min:8', 'max:64'],
            'reset_password' => ['nullable', 'boolean'],
        ]);

        $reset = $request->boolean('reset_password', true);
        $password = $validated['password'];
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        $students = User::students()
            ->whereIn('id', $validated['student_ids'])
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
            'admin.students.send_credentials',
            "Sent student login mail: {$sent} sent, {$failed} failed, {$skipped} skipped",
            null,
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
        if ($reset) {
            $msg .= '. Password was reset to the password you entered for mailed students.';
        }

        return back()->with($sent > 0 ? 'success' : 'error', $msg);
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

    private function ensureStudent(User $user): void
    {
        if ($user->role !== 'student') {
            abort(404);
        }
    }
}
