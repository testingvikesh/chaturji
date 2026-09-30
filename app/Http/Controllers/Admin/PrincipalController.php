<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\Standard;
use App\Models\User;
use App\Models\UserSession;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PrincipalController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::principals()->with('allottedStandards')->latest();

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

        return view('admin.principals.index', [
            'principals' => $query->paginate(100)->withQueryString(),
            'filters' => $request->only(['search', 'status']),
            'pendingCount' => User::principals()->pending()->count(),
            'defaultPassword' => 'Principal@123',
            'standards' => $this->activeStandards(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', 'unique:users,mobile'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:64'],
            'approve' => ['nullable', 'boolean'],
            'standard_ids' => ['nullable', 'array'],
            'standard_ids.*' => ['integer', Rule::exists('standards', 'id')->where('is_active', true)],
        ]);

        $principal = User::query()->create([
            'name' => $validated['name'],
            'mobile' => preg_replace('/\s+/', '', $validated['mobile']),
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'principal',
            'is_approved' => $request->boolean('approve', true),
            'email_verified_at' => now(),
        ]);

        $principal->allottedStandards()->sync($validated['standard_ids'] ?? []);

        ActivityLogger::log(
            'admin.principals.create',
            'Created principal '.$principal->name,
            $principal,
            ['target_role' => 'principal', 'standard_ids' => $validated['standard_ids'] ?? []]
        );

        return redirect()
            ->route('admin.principals.index')
            ->with('success', $principal->name.' created. Login at /principal/login with email and password.');
    }

    public function show(User $principal): View
    {
        $this->ensurePrincipal($principal);
        $principal->load('allottedStandards');

        return view('admin.principals.show', [
            'principal' => $principal,
            'loginLogs' => LoginLog::where('user_id', $principal->id)->latest('logged_at')->limit(20)->get(),
            'sessions' => UserSession::where('user_id', $principal->id)->latest('logged_in_at')->limit(20)->get(),
        ]);
    }

    public function edit(User $principal): View
    {
        $this->ensurePrincipal($principal);
        $principal->load('allottedStandards');

        return view('admin.principals.edit', [
            'principal' => $principal,
            'standards' => $this->activeStandards(),
            'selectedStandardIds' => $principal->allottedStandards->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, User $principal): RedirectResponse
    {
        $this->ensurePrincipal($principal);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', Rule::unique('users', 'mobile')->ignore($principal->id)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($principal->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'standard_ids' => ['nullable', 'array'],
            'standard_ids.*' => ['integer', Rule::exists('standards', 'id')->where('is_active', true)],
        ]);

        $principal->fill([
            'name' => $validated['name'],
            'mobile' => preg_replace('/\s+/', '', $validated['mobile']),
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $principal->password = $validated['password'];
        }

        $principal->save();
        $principal->allottedStandards()->sync($validated['standard_ids'] ?? []);

        return redirect()->route('admin.principals.index')->with('success', 'Principal updated successfully.');
    }

    public function destroy(User $principal): RedirectResponse
    {
        $this->ensurePrincipal($principal);
        $principal->delete();

        return redirect()->route('admin.principals.index')->with('success', 'Principal deleted successfully.');
    }

    public function approve(User $principal): RedirectResponse
    {
        $this->ensurePrincipal($principal);
        $principal->update(['is_approved' => true]);

        ActivityLogger::log(
            'admin.user.approve',
            'Approved principal '.$principal->name,
            $principal,
            ['target_role' => 'principal']
        );

        return back()->with('success', $principal->name.' approved. They can login now.');
    }

    public function pending(User $principal): RedirectResponse
    {
        $this->ensurePrincipal($principal);
        $principal->update(['is_approved' => false]);

        ActivityLogger::log(
            'admin.user.pending',
            'Set principal '.$principal->name.' to pending',
            $principal,
            ['target_role' => 'principal']
        );

        return back()->with('success', $principal->name.' set to pending.');
    }

    private function ensurePrincipal(User $user): void
    {
        if ($user->role !== 'principal') {
            abort(404);
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, Standard>
     */
    private function activeStandards()
    {
        return Standard::query()
            ->where('is_active', true)
            ->orderBy('medium')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'medium']);
    }
}
