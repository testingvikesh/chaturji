<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function logins(Request $request): View
    {
        $query = LoginLog::with('user')->whereIn('role', ['student', 'teacher'])->latest('logged_at');

        if ($role = $request->string('role')->trim()->toString()) {
            $query->where('role', $role);
        }

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }

        if ($from = $request->date('from')) {
            $query->whereDate('logged_at', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->whereDate('logged_at', '<=', $to);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('login_identifier', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        return view('admin.reports.logins', [
            'logs' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['role', 'status', 'from', 'to', 'search']),
            'summary' => [
                'today' => LoginLog::whereIn('role', ['student', 'teacher'])->whereDate('logged_at', today())->count(),
                'student' => LoginLog::where('role', 'student')->where('status', 'success')->count(),
                'teacher' => LoginLog::where('role', 'teacher')->where('status', 'success')->count(),
                'failed' => LoginLog::whereIn('role', ['student', 'teacher'])->where('status', 'failed')->count(),
            ],
        ]);
    }

    public function sessions(Request $request): View
    {
        $query = UserSession::with('user')->whereIn('role', ['student', 'teacher'])->latest('logged_in_at');

        if ($role = $request->string('role')->trim()->toString()) {
            $query->where('role', $role);
        }

        if ($request->boolean('active_only')) {
            $query->where('is_active', true)->where('expires_at', '>', now());
        }

        if ($from = $request->date('from')) {
            $query->whereDate('logged_in_at', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->whereDate('logged_in_at', '<=', $to);
        }

        return view('admin.reports.sessions', [
            'sessions' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['role', 'active_only', 'from', 'to']),
            'summary' => [
                'active' => UserSession::whereIn('role', ['student', 'teacher'])->where('is_active', true)->where('expires_at', '>', now())->count(),
                'today' => UserSession::whereIn('role', ['student', 'teacher'])->whereDate('logged_in_at', today())->count(),
                'student' => UserSession::where('role', 'student')->where('is_active', true)->where('expires_at', '>', now())->count(),
                'teacher' => UserSession::where('role', 'teacher')->where('is_active', true)->where('expires_at', '>', now())->count(),
            ],
        ]);
    }
}
