<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\Standard;
use App\Models\User;
use App\Models\UserSession;
use App\Support\AdminMaterialUploadReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
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
                ->limit(8)
                ->get(),
        ]);
    }

    public function materials(Request $request, AdminMaterialUploadReport $report): View
    {
        try {
            $payload = $report->build(
                (string) $request->input('standard', ''),
                (string) $request->input('subject', ''),
                (string) $request->input('sort', 'standard') ?: 'standard',
                (string) $request->input('dir', 'asc') ?: 'asc',
                (string) $request->input('medium', '')
            );
        } catch (Throwable $e) {
            Log::error('Material report failed: '.$e->getMessage(), ['exception' => $e]);
            $payload = [
                'standards' => [],
                'standard_filter' => 'all',
                'subject_filter' => 'all',
                'medium_filter' => 'all',
                'subjects' => [],
                'totals' => [],
                'tree' => [],
                'rows' => [],
            ];
            session()->now('error', 'Material report could not load. Please try again.');
        }

        $payload['standards'] = collect($payload['standards'] ?? []);
        $payload['subjects'] = $payload['subjects'] ?? [];
        $payload['tree'] = $payload['tree'] ?? [];
        $payload['rows'] = collect($payload['rows'] ?? []);
        $payload['totals'] = array_merge([
            'all' => 0,
            'english' => 0,
            'gujarati' => 0,
            'complete' => 0,
            'partial' => 0,
            'planned' => 0,
            'with_pdf' => 0,
            'topics' => 0,
            'topics_ready' => 0,
            'subjects' => 0,
            'standards' => 0,
        ], $payload['totals'] ?? []);

        return view('admin.dashboard-materials', $payload);
    }
}
