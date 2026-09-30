<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Services\AuthActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('principal.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            AuthActivityService::recordLogin(null, 'principal', $credentials['email'], $request, 'failed');

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = Auth::user();
        if ($user->role !== 'principal') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'This account cannot access the principal panel.',
            ]);
        }

        if (! $user->isApproved()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Your principal account is pending admin approval.',
            ]);
        }

        $request->session()->regenerate();

        AuthActivityService::recordLogin($user, 'principal', $credentials['email'], $request, 'success');
        AuthActivityService::startSession($user, $request);

        return redirect()->intended(route('principal.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        AuthActivityService::endSession($request);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('principal.login');
    }
}
