<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Mail\StudentLoginCredentialsMail;
use App\Models\Standard;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\MailConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class StudentController extends Controller
{
    public function create(): View
    {
        $standards = $this->allottedStandards(auth()->user());
        abort_if($standards->isEmpty(), 403, 'No standard allotted. Ask admin to allot standards first.');

        return view('principal.students.create', [
            'standards' => $standards,
            'mediums' => Standard::MEDIUMS,
            'defaultPassword' => 'Student@123',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $principal = auth()->user();
        $allottedSlugs = $this->allottedSlugs($principal);
        abort_if($allottedSlugs === [], 403, 'No standard allotted. Ask admin to allot standards first.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', 'unique:users,mobile'],
            'email' => ['nullable', 'string', 'lowercase', 'max:255', Rule::unique('users', 'email')],
            'medium' => ['required', Rule::in(array_keys(Standard::MEDIUMS))],
            'standard' => ['required', 'string', Rule::in($allottedSlugs)],
            'password' => ['required', 'string', 'min:8', 'max:64'],
            'approve' => ['nullable', 'boolean'],
            'send_mail' => ['nullable', 'boolean'],
        ]);

        $sendMail = $request->boolean('send_mail');
        $email = filled($validated['email'] ?? null) ? strtolower(trim((string) $validated['email'])) : null;
        if ($sendMail && ($email === null || ! filter_var($email, FILTER_VALIDATE_EMAIL))) {
            return back()->withInput()->with('error', 'A real email is needed only when Send mail is checked. Students log in with mobile.');
        }

        $student = User::query()->create([
            'name' => $validated['name'],
            'mobile' => preg_replace('/\s+/', '', $validated['mobile']),
            'email' => $email,
            'medium' => $validated['medium'],
            'standard' => $validated['standard'],
            'password' => $validated['password'],
            'role' => 'student',
            'is_approved' => $request->boolean('approve', true),
        ]);

        ActivityLogger::log(
            'principal.students.create',
            'Principal created student '.$student->name,
            $principal,
            [
                'student_id' => $student->id,
                'medium' => $student->medium,
                'standard' => $student->standard,
            ]
        );

        $mailNote = '';
        if ($sendMail) {
            $mailNote = $this->sendLoginCredentialsMail($student, $validated['password'])
                ? ' Login mail sent.'
                : ' Login mail failed — check mail settings.';
        }

        return redirect()
            ->route('principal.reports.allotted-students', [
                'medium' => $student->medium,
                'standard' => $student->standard,
            ])
            ->with('success', $student->name.' created. Login with mobile '.$student->mobile.' and the set password.'.$mailNote);
    }

    public function edit(User $student): View
    {
        $this->assertAllottedStudent(auth()->user(), $student);

        return view('principal.students.edit', [
            'student' => $student,
            'standards' => $this->allottedStandards(auth()->user()),
            'mediums' => Standard::MEDIUMS,
        ]);
    }

    public function update(Request $request, User $student): RedirectResponse
    {
        $principal = auth()->user();
        $this->assertAllottedStudent($principal, $student);
        $allottedSlugs = $this->allottedSlugs($principal);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', 'unique:users,mobile,'.$student->id],
            'email' => ['nullable', 'string', 'lowercase', 'max:255', Rule::unique('users', 'email')->ignore($student->id)],
            'medium' => ['required', Rule::in(array_keys(Standard::MEDIUMS))],
            'standard' => ['required', 'string', Rule::in($allottedSlugs)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $student->fill([
            'name' => $validated['name'],
            'mobile' => preg_replace('/\s+/', '', $validated['mobile']),
            'email' => filled($validated['email'] ?? null) ? strtolower(trim((string) $validated['email'])) : null,
            'medium' => $validated['medium'],
            'standard' => $validated['standard'],
        ]);

        if (! empty($validated['password'])) {
            $student->password = $validated['password'];
        }

        $student->save();

        ActivityLogger::log(
            'principal.students.update',
            'Principal updated student '.$student->name,
            $principal,
            [
                'student_id' => $student->id,
                'medium' => $student->medium,
                'standard' => $student->standard,
            ]
        );

        return redirect()
            ->route('principal.reports.allotted-students', [
                'medium' => $student->medium,
                'standard' => $student->standard,
            ])
            ->with('success', 'Student updated successfully.');
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

    private function allottedSlugs(?User $principal): array
    {
        return $this->allottedStandards($principal)
            ->pluck('slug')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function assertAllottedStudent(User $principal, User $student): void
    {
        abort_unless($student->role === 'student', 404);

        $allottedSlugs = $this->allottedSlugs($principal);
        abort_unless(
            $allottedSlugs !== [] && in_array((string) $student->standard, $allottedSlugs, true),
            404
        );
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
