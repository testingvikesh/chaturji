<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\MailConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Throwable;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('front.auth.forgot-password', [
            'role' => old('role', request('role', '')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['nullable', 'in:student,teacher'],
        ]);

        $email = trim($validated['email']);
        $role = $validated['role'] ?? null;

        $user = User::query()
            ->when($role, fn ($query) => $query->where('role', $role), fn ($query) => $query->whereIn('role', ['student', 'teacher']))
            ->where('email', $email)
            ->first();

        if ($user) {
            try {
                MailConfig::apply();
                Password::sendResetLink(['email' => $user->email]);
            } catch (Throwable $e) {
                report($e);

                return back()
                    ->withInput($request->only('email', 'role'))
                    ->withErrors([
                        'email' => 'Could not send the reset email. Please try again after mail setup is complete.',
                    ]);
            }
        }

        return back()->with(
            'status',
            'If this email is registered, a reset link has been sent. Check your inbox.'
        );
    }
}
