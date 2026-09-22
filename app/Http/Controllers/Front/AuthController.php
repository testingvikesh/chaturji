<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Standard;
use App\Models\User;
use App\Services\AuthActivityService;
use App\Support\TeacherOtpService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function loginHub(): View
    {
        return view('front.auth.login-hub');
    }

    public function registerHub(): View
    {
        return view('front.auth.register-hub');
    }

    public function studentLoginForm(): View
    {
        return $this->loginForm('student');
    }

    public function teacherLoginForm(): View
    {
        return $this->loginForm('teacher');
    }

    public function studentRegisterForm(): View
    {
        return view('front.auth.register-student', [
            'standards' => $this->standardOptions(),
            'mediums' => ['english' => 'English', 'gujarati' => 'Gujarati'],
        ]);
    }

    public function teacherRegisterForm(): View
    {
        return $this->registerForm('teacher');
    }

    public function studentLoginStore(Request $request): RedirectResponse
    {
        return $this->loginStore($request, 'student');
    }

    public function teacherLoginStore(Request $request): RedirectResponse
    {
        return $this->loginStore($request, 'teacher');
    }

    public function studentRegisterStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', 'unique:'.User::class.',mobile'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class.',email'],
            'medium' => ['required', 'in:english,gujarati'],
            'standard' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'mobile' => $validated['mobile'],
            'email' => $validated['email'] ?? null,
            'medium' => $validated['medium'],
            'standard' => $validated['standard'],
            'password' => Hash::make($validated['password']),
            'role' => 'student',
            'is_approved' => false,
        ]);

        event(new Registered($user));

        return redirect()
            ->route('student.register.success')
            ->with('registered_name', $user->name);
    }

    public function teacherRegisterStore(Request $request): RedirectResponse
    {
        return $this->registerStore($request, 'teacher');
    }

    public function registerSuccess(string $role): View|RedirectResponse
    {
        $this->ensureRole($role);

        if (! session('registered_name')) {
            return redirect()->route($role.'.register');
        }

        return view('front.auth.register-success', compact('role'));
    }

    public function loginForm(string $role): View
    {
        $this->ensureRole($role);

        return view('front.auth.login', compact('role'));
    }

    public function registerForm(string $role): View
    {
        $this->ensureRole($role);

        return view('front.auth.register', compact('role'));
    }

    public function loginStore(Request $request, string $role): RedirectResponse
    {
        $this->ensureRole($role);

        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $login = $validated['login'];
        $user = User::where('role', $role)
            ->where(function ($query) use ($login) {
                $query->where('mobile', $login)->orWhere('email', $login);
            })
            ->first();

        $authenticated = $user && Hash::check($validated['password'], $user->password);

        if (! $authenticated && $role === 'teacher' && $user) {
            $authenticated = TeacherOtpService::matches($user, $validated['password']);
        }

        if ($authenticated && ! $user->isApproved()) {
            AuthActivityService::recordLogin($user, $role, $login, $request, 'failed');

            throw ValidationException::withMessages([
                'login' => 'Your account is pending admin approval. Please wait until the admin approves your registration.',
            ]);
        }

        if ($authenticated) {
            Auth::login($user, $request->boolean('remember'));
        }

        if (! $authenticated) {
            AuthActivityService::recordLogin($user, $role, $login, $request, 'failed');

            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        if (Auth::user()->role !== $role) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'login' => 'This account is not registered as a '.ucfirst($role).'.',
            ]);
        }

        $request->session()->regenerate();

        AuthActivityService::recordLogin($user, $role, $login, $request);
        AuthActivityService::startSession($user, $request);

        return redirect()->intended($this->redirectForRole($role));
    }

    public function registerStore(Request $request, string $role): RedirectResponse
    {
        $this->ensureRole($role);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', 'unique:'.User::class.',mobile'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'mobile' => $validated['mobile'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $role,
            'is_approved' => false,
        ]);

        event(new Registered($user));

        return redirect()
            ->route($role.'.register.success')
            ->with('registered_name', $user->name);
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user && ($user->role ?? null) === 'teacher') {
            return redirect()->route('teacher.logout-report.create');
        }

        AuthActivityService::endSession($request);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function ensureRole(string $role): void
    {
        if (! in_array($role, ['student', 'teacher'], true)) {
            abort(404);
        }
    }

    private function redirectForRole(string $role): string
    {
        return match ($role) {
            'student' => route('student.dashboard'),
            'teacher' => route('teacher.dashboard'),
            default => route('home'),
        };
    }

    private function standardOptions(): array
    {
        $standards = Standard::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'slug')
            ->all();

        if ($standards !== []) {
            return $standards;
        }

        $fallback = [];
        for ($i = 1; $i <= 12; $i++) {
            $fallback["standard_{$i}"] = "Standard {$i}";
        }

        return $fallback;
    }
}
