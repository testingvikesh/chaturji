<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\User;
use App\Models\UserSession;
use App\Support\ActivityLogger;
use App\Support\TeacherOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::teachers()->latest();

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

        TeacherOtpService::generateForApprovedTeachers();

        return view('admin.teachers.index', [
            'teachers' => $query->with('todayOtp')->paginate(500)->withQueryString(),
            'filters' => $request->only(['search', 'status']),
            'pendingCount' => User::teachers()->pending()->count(),
        ]);
    }

    public function show(User $teacher): View
    {
        $this->ensureTeacher($teacher);

        $todayOtp = $teacher->isApproved()
            ? TeacherOtpService::ensureForTeacher($teacher)
            : TeacherOtpService::todayFor($teacher);

        return view('admin.teachers.show', [
            'teacher' => $teacher,
            'todayOtp' => $todayOtp,
            'loginLogs' => LoginLog::where('user_id', $teacher->id)->latest('logged_at')->limit(20)->get(),
            'sessions' => UserSession::where('user_id', $teacher->id)->latest('logged_in_at')->limit(20)->get(),
        ]);
    }

    public function edit(User $teacher): View
    {
        $this->ensureTeacher($teacher);

        return view('admin.teachers.edit', [
            'teacher' => $teacher,
        ]);
    }

    public function update(Request $request, User $teacher)
    {
        $this->ensureTeacher($teacher);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', 'unique:users,mobile,'.$teacher->id],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$teacher->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $teacher->fill([
            'name' => $validated['name'],
            'mobile' => $validated['mobile'],
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $teacher->password = $validated['password'];
        }

        $teacher->save();

        return redirect()->route('admin.teachers.index')->with('success', 'Teacher updated successfully.');
    }

    public function destroy(User $teacher)
    {
        $this->ensureTeacher($teacher);
        $teacher->delete();

        return redirect()->route('admin.teachers.index')->with('success', 'Teacher deleted successfully.');
    }

    public function approve(User $teacher)
    {
        $this->ensureTeacher($teacher);
        $teacher->update(['is_approved' => true]);
        TeacherOtpService::ensureForTeacher($teacher);
        TeacherOtpService::notifyTodayOtpToTeacher($teacher);

        ActivityLogger::log(
            'admin.user.approve',
            'Approved teacher '.$teacher->name,
            $teacher,
            ['target_role' => 'teacher']
        );

        return redirect()
            ->back()
            ->with('success', $teacher->name.' has been approved and can now login. Today\'s OTP is in Notifications.');
    }

    public function pending(User $teacher)
    {
        $this->ensureTeacher($teacher);
        $teacher->update(['is_approved' => false]);

        ActivityLogger::log(
            'admin.user.pending',
            'Set teacher '.$teacher->name.' to pending',
            $teacher,
            ['target_role' => 'teacher']
        );

        return redirect()
            ->back()
            ->with('success', $teacher->name.' has been set to pending. Login is disabled until approved again.');
    }

    public function generateOtp(): RedirectResponse
    {
        $count = TeacherOtpService::generateForApprovedTeachers(null, true);
        $notify = TeacherOtpService::notifyTodayOtpsToApprovedTeachers();

        return redirect()
            ->route('admin.teachers.index')
            ->with('success', 'Today\'s 4-digit OTP generated for '.$count.' approved teacher'.($count === 1 ? '' : 's').'. Sent to Notifications ('.$notify['notified'].'). No email sent.');
    }

    public function report(): View
    {
        return view('admin.teachers.report', [
            'totalTeachers' => User::teachers()->count(),
            'todayRegistered' => User::teachers()->whereDate('created_at', today())->count(),
            'todayLogins' => LoginLog::where('role', 'teacher')->where('status', 'success')->whereDate('logged_at', today())->count(),
            'activeSessions' => UserSession::where('role', 'teacher')->where('is_active', true)->where('expires_at', '>', now())->count(),
            'recentTeachers' => User::teachers()->latest()->limit(10)->get(),
            'weeklyLogins' => LoginLog::where('role', 'teacher')
                ->where('status', 'success')
                ->where('logged_at', '>=', now()->subDays(7))
                ->selectRaw('DATE(logged_at) as date, count(*) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ]);
    }

    private function ensureTeacher(User $user): void
    {
        if ($user->role !== 'teacher') {
            abort(404);
        }
    }
}
