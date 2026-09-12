<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacher
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('teacher.login');
        }

        $user = auth()->user();

        if ($user->role !== 'teacher') {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('teacher.login')
                ->with('error', 'Please login with a teacher account.');
        }

        if (! $user->isApproved()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('teacher.login')
                ->with('error', 'Your account is pending admin approval.');
        }

        return $next($request);
    }
}
