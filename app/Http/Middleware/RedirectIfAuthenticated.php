<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::user();

                if ($user->role === 'admin') {
                    return redirect(RouteServiceProvider::HOME);
                }

                if ($user->role === 'student' && $user->isApproved()) {
                    return redirect()->route('student.dashboard');
                }

                if ($user->role === 'teacher' && $user->isApproved()) {
                    return redirect()->route('teacher.dashboard');
                }

                return redirect()->route('login');
            }
        }

        return $next($request);
    }
}
