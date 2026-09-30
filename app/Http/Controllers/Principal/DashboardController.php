<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\Standard;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('principal.dashboard', [
            'totalStudents' => User::students()->count(),
            'totalTeachers' => User::teachers()->count(),
            'pendingStudents' => User::students()->pending()->count(),
            'pendingTeachers' => User::teachers()->pending()->count(),
            'totalStandards' => Standard::count(),
            'todayLogins' => LoginLog::whereDate('logged_at', today())
                ->where('status', 'success')
                ->whereIn('role', ['student', 'teacher'])
                ->count(),
            'activeSessions' => UserSession::where('is_active', true)
                ->whereIn('role', ['student', 'teacher'])
                ->where('expires_at', '>', now())
                ->count(),
            'recentLogins' => LoginLog::with('user')
                ->whereIn('role', ['student', 'teacher'])
                ->latest('logged_at')
                ->limit(10)
                ->get(),
        ]);
    }
}
