<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\Standard;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::students()->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($standard = $request->string('standard')->trim()->toString()) {
            $query->where('standard', $standard);
        }

        if ($medium = $request->string('medium')->trim()->toString()) {
            $query->where('medium', $medium);
        }

        if ($status = $request->string('status')->trim()->toString()) {
            if ($status === 'approved') {
                $query->approved();
            } elseif ($status === 'pending') {
                $query->pending();
            }
        }

        return view('admin.students.index', [
            'students' => $query->paginate(15)->withQueryString(),
            'standards' => Standard::orderBy('sort_order')->pluck('name', 'slug'),
            'filters' => $request->only(['search', 'standard', 'medium', 'status']),
            'pendingCount' => User::students()->pending()->count(),
        ]);
    }

    public function show(User $student): View
    {
        $this->ensureStudent($student);

        return view('admin.students.show', [
            'student' => $student,
            'loginLogs' => LoginLog::where('user_id', $student->id)->latest('logged_at')->limit(20)->get(),
            'sessions' => UserSession::where('user_id', $student->id)->latest('logged_in_at')->limit(20)->get(),
        ]);
    }

    public function edit(User $student): View
    {
        $this->ensureStudent($student);

        return view('admin.students.edit', [
            'student' => $student,
            'standards' => Standard::orderBy('sort_order')->pluck('name', 'slug'),
            'mediums' => ['english' => 'English', 'gujarati' => 'Gujarati'],
        ]);
    }

    public function update(Request $request, User $student)
    {
        $this->ensureStudent($student);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', 'unique:users,mobile,'.$student->id],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$student->id],
            'medium' => ['required', 'in:english,gujarati'],
            'standard' => ['required', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $student->fill([
            'name' => $validated['name'],
            'mobile' => $validated['mobile'],
            'email' => $validated['email'] ?? null,
            'medium' => $validated['medium'],
            'standard' => $validated['standard'],
        ]);

        if (! empty($validated['password'])) {
            $student->password = $validated['password'];
        }

        $student->save();

        return redirect()->route('admin.students.index')->with('success', 'Student updated successfully.');
    }

    public function destroy(User $student)
    {
        $this->ensureStudent($student);
        $student->delete();

        return redirect()->route('admin.students.index')->with('success', 'Student deleted successfully.');
    }

    public function approve(User $student)
    {
        $this->ensureStudent($student);
        $student->update(['is_approved' => true]);

        return redirect()
            ->back()
            ->with('success', $student->name.' has been approved and can now login.');
    }

    public function pending(User $student)
    {
        $this->ensureStudent($student);
        $student->update(['is_approved' => false]);

        return redirect()
            ->back()
            ->with('success', $student->name.' has been set to pending. Login is disabled until approved again.');
    }

    public function report(): View
    {
        $byStandard = User::students()
            ->select('standard', DB::raw('count(*) as total'))
            ->groupBy('standard')
            ->orderBy('standard')
            ->get();

        $byMedium = User::students()
            ->select('medium', DB::raw('count(*) as total'))
            ->groupBy('medium')
            ->get();

        return view('admin.students.report', [
            'totalStudents' => User::students()->count(),
            'todayRegistered' => User::students()->whereDate('created_at', today())->count(),
            'todayLogins' => LoginLog::where('role', 'student')->where('status', 'success')->whereDate('logged_at', today())->count(),
            'activeSessions' => UserSession::where('role', 'student')->where('is_active', true)->where('expires_at', '>', now())->count(),
            'byStandard' => $byStandard,
            'byMedium' => $byMedium,
            'recentStudents' => User::students()->latest()->limit(10)->get(),
        ]);
    }

    private function ensureStudent(User $user): void
    {
        if ($user->role !== 'student') {
            abort(404);
        }
    }
}
