<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Standard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('student.profile.edit', [
            'user' => auth()->user(),
            'standards' => $this->standardOptions(),
            'mediums' => ['english' => 'English', 'gujarati' => 'Gujarati'],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', 'unique:users,mobile,'.$user->id],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'medium' => ['required', 'in:english,gujarati'],
            'standard' => ['required', 'string', 'max:20'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'mobile' => $validated['mobile'],
            'email' => $validated['email'] ?? null,
            'medium' => $validated['medium'],
            'standard' => $validated['standard'],
        ]);

        return redirect()
            ->route('student.profile.edit')
            ->with('success', 'Profile updated successfully.');
    }

    public function editPassword(): View
    {
        return view('student.profile.change-password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('student.change-password')
            ->with('status', 'password-updated');
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
