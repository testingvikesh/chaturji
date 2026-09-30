<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PrincipalLoginCredentialsMail;
use App\Models\LoginLog;
use App\Models\Standard;
use App\Models\User;
use App\Models\UserSession;
use App\Support\ActivityLogger;
use App\Support\MailConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class PrincipalController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::principals()->with('allottedStandards')->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->trim()->toString()) {
            if ($status === 'approved') {
                $query->approved();
            } elseif ($status === 'pending') {
                $query->pending();
            }
        }

        return view('admin.principals.index', [
            'principals' => $query->paginate(100)->withQueryString(),
            'filters' => $request->only(['search', 'status']),
            'pendingCount' => User::principals()->pending()->count(),
            'defaultPassword' => 'Principal@123',
            'standards' => $this->activeStandards(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', 'unique:users,mobile'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:64'],
            'approve' => ['nullable', 'boolean'],
            'send_mail' => ['nullable', 'boolean'],
            'standard_ids' => ['nullable', 'array'],
            'standard_ids.*' => ['integer', Rule::exists('standards', 'id')->where('is_active', true)],
        ]);

        $sendMail = $request->boolean('send_mail');

        $principal = User::query()->create([
            'name' => $validated['name'],
            'mobile' => preg_replace('/\s+/', '', $validated['mobile']),
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'principal',
            'is_approved' => $request->boolean('approve', true),
            'email_verified_at' => now(),
        ]);

        $principal->allottedStandards()->sync($validated['standard_ids'] ?? []);

        ActivityLogger::log(
            'admin.principals.create',
            'Created principal '.$principal->name,
            $principal,
            ['target_role' => 'principal', 'standard_ids' => $validated['standard_ids'] ?? [], 'send_mail' => $sendMail]
        );

        $mailNote = '';
        if ($sendMail) {
            $mailNote = $this->sendLoginCredentialsMail($principal, $validated['password'])
                ? ' Login mail sent.'
                : ' Login mail failed — check mail settings.';
        }

        return redirect()
            ->route('admin.principals.index')
            ->with('success', $principal->name.' created. Login at /principal/login with email and password.'.$mailNote);
    }

    public function sendCredentials(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'principal_ids' => ['required', 'array', 'min:1'],
            'principal_ids.*' => ['integer', 'exists:users,id'],
            'password' => ['required', 'string', 'min:8', 'max:64'],
            'reset_password' => ['nullable', 'boolean'],
        ]);

        $reset = $request->boolean('reset_password', true);
        $password = $validated['password'];
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        $principals = User::principals()
            ->whereIn('id', $validated['principal_ids'])
            ->get();

        foreach ($principals as $principal) {
            if (! filled($principal->email) || ! filter_var($principal->email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }

            if ($reset) {
                $principal->password = $password;
                $principal->save();
            }

            if ($this->sendLoginCredentialsMail($principal, $password)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        ActivityLogger::log(
            'admin.principals.send_credentials',
            "Sent principal login mail: {$sent} sent, {$failed} failed, {$skipped} skipped",
            null,
            [
                'sent' => $sent,
                'failed' => $failed,
                'skipped' => $skipped,
                'reset_password' => $reset,
                'count' => count($validated['principal_ids']),
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
            $msg .= '. Password was reset to the password you entered for mailed principals.';
        }

        return back()->with($sent > 0 ? 'success' : 'error', $msg);
    }

    public function show(User $principal): View
    {
        $this->ensurePrincipal($principal);
        $principal->load('allottedStandards');

        return view('admin.principals.show', [
            'principal' => $principal,
            'defaultPassword' => 'Principal@123',
            'loginLogs' => LoginLog::where('user_id', $principal->id)->latest('logged_at')->limit(20)->get(),
            'sessions' => UserSession::where('user_id', $principal->id)->latest('logged_in_at')->limit(20)->get(),
        ]);
    }

    public function edit(User $principal): View
    {
        $this->ensurePrincipal($principal);
        $principal->load('allottedStandards');

        return view('admin.principals.edit', [
            'principal' => $principal,
            'standards' => $this->activeStandards(),
            'selectedStandardIds' => $principal->allottedStandards->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, User $principal): RedirectResponse
    {
        $this->ensurePrincipal($principal);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', Rule::unique('users', 'mobile')->ignore($principal->id)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($principal->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'send_mail' => ['nullable', 'boolean'],
            'standard_ids' => ['nullable', 'array'],
            'standard_ids.*' => ['integer', Rule::exists('standards', 'id')->where('is_active', true)],
        ]);

        $sendMail = $request->boolean('send_mail');
        $plainPassword = $validated['password'] ?? null;

        if ($sendMail && empty($plainPassword)) {
            return back()->withInput()->with('error', 'Enter a new password when Send login mail is checked.');
        }

        $principal->fill([
            'name' => $validated['name'],
            'mobile' => preg_replace('/\s+/', '', $validated['mobile']),
            'email' => $validated['email'],
        ]);

        if (! empty($plainPassword)) {
            $principal->password = $plainPassword;
        }

        $principal->save();
        $principal->allottedStandards()->sync($validated['standard_ids'] ?? []);

        $mailNote = '';
        if ($sendMail && $plainPassword) {
            $mailNote = $this->sendLoginCredentialsMail($principal, $plainPassword)
                ? ' Login mail sent.'
                : ' Login mail failed — check mail settings.';
        }

        return redirect()->route('admin.principals.index')->with('success', 'Principal updated successfully.'.$mailNote);
    }

    public function destroy(User $principal): RedirectResponse
    {
        $this->ensurePrincipal($principal);
        $principal->delete();

        return redirect()->route('admin.principals.index')->with('success', 'Principal deleted successfully.');
    }

    public function approve(User $principal): RedirectResponse
    {
        $this->ensurePrincipal($principal);
        $principal->update(['is_approved' => true]);

        ActivityLogger::log(
            'admin.user.approve',
            'Approved principal '.$principal->name,
            $principal,
            ['target_role' => 'principal']
        );

        return back()->with('success', $principal->name.' approved. They can login now.');
    }

    public function pending(User $principal): RedirectResponse
    {
        $this->ensurePrincipal($principal);
        $principal->update(['is_approved' => false]);

        ActivityLogger::log(
            'admin.user.pending',
            'Set principal '.$principal->name.' to pending',
            $principal,
            ['target_role' => 'principal']
        );

        return back()->with('success', $principal->name.' set to pending.');
    }

    private function sendLoginCredentialsMail(User $principal, string $plainPassword): bool
    {
        $email = trim((string) $principal->email);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            MailConfig::apply();
            Mail::to($email)->send(new PrincipalLoginCredentialsMail(
                $principal,
                $plainPassword,
                route('principal.login'),
                url('/')
            ));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    private function ensurePrincipal(User $user): void
    {
        if ($user->role !== 'principal') {
            abort(404);
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, Standard>
     */
    private function activeStandards()
    {
        return Standard::query()
            ->where('is_active', true)
            ->orderBy('medium')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'medium']);
    }
}
