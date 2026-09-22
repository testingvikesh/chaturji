<?php

namespace App\Services;

use App\Models\LoginLog;
use App\Models\User;
use App\Models\UserSession;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthActivityService
{
    public static function sessionLifetimeMinutes(): int
    {
        return (int) config('session.lifetime', 1440);
    }

    public static function recordLogin(
        ?User $user,
        string $role,
        string $loginIdentifier,
        Request $request,
        string $status = 'success'
    ): LoginLog {
        $method = filter_var($loginIdentifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';

        $log = LoginLog::create([
            'user_id' => $user?->id,
            'role' => $role,
            'login_identifier' => $loginIdentifier,
            'login_method' => $method,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500),
            'status' => $status,
            'logged_at' => now(),
        ]);

        ActivityLogger::log(
            $status === 'success' ? 'auth.login' : 'auth.login_failed',
            ($status === 'success' ? 'Logged in' : 'Login failed').' as '.$role.' ('.$loginIdentifier.')',
            $user,
            [
                'role' => $role,
                'login_identifier' => $loginIdentifier,
                'login_method' => $method,
                'status' => $status,
            ],
            $user
        );

        return $log;
    }

    public static function startSession(User $user, Request $request): UserSession
    {
        $now = now();
        $lifetime = self::sessionLifetimeMinutes();

        return UserSession::updateOrCreate(
            ['session_id' => $request->session()->getId()],
            [
                'user_id' => $user->id,
                'role' => $user->role,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500),
                'logged_in_at' => $now,
                'last_activity_at' => $now,
                'logged_out_at' => null,
                'expires_at' => $now->copy()->addMinutes($lifetime),
                'is_active' => true,
            ]
        );
    }

    public static function touchSession(Request $request): void
    {
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();

        if (! in_array($user->role, ['student', 'teacher'], true)) {
            return;
        }

        $session = UserSession::query()
            ->where('session_id', $request->session()->getId())
            ->where('is_active', true)
            ->first();

        if (! $session) {
            return;
        }

        if ($session->expires_at->isPast()) {
            $session->update([
                'is_active' => false,
                'logged_out_at' => now(),
            ]);

            return;
        }

        $session->update([
            'last_activity_at' => now(),
            'expires_at' => now()->addMinutes(self::sessionLifetimeMinutes()),
        ]);
    }

    public static function endSession(Request $request): void
    {
        $user = auth()->user();

        UserSession::query()
            ->where('session_id', $request->session()->getId())
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'logged_out_at' => now(),
            ]);

        if ($user instanceof User) {
            ActivityLogger::log(
                'auth.logout',
                'Logged out',
                $user,
                ['role' => $user->role],
                $user
            );
        }
    }
}
