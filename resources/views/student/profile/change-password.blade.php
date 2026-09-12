<x-student-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Student</span>
            <h2 class="admin-page-title">Change Password</h2>
            <p class="admin-page-subtitle">Update your account password</p>
        </div>
    </x-slot>

    <div class="admin-page max-w-2xl">
        @if (session('status') === 'password-updated')
            <div class="mb-6 flex items-start gap-3 rounded-2xl bg-brand-green-50 border border-brand-green-200 px-5 py-4 text-sm text-brand-green-dark shadow-sm">
                <span class="mt-0.5 h-5 w-5 shrink-0 rounded-full bg-brand-green text-white flex items-center justify-center text-xs">✓</span>
                <span>Password updated successfully.</span>
            </div>
        @endif

        <div class="admin-form-card">
            <div class="admin-card-top"></div>
            <form method="POST" action="{{ route('student.password.update') }}" class="admin-card-body space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="admin-label">Current Password</label>
                    <x-password-input id="current_password" name="current_password" class="admin-input" autocomplete="current-password" required />
                    <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
                </div>

                <div>
                    <label class="admin-label">New Password</label>
                    <x-password-input id="password" name="password" class="admin-input" autocomplete="new-password" required />
                    <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
                </div>

                <div>
                    <label class="admin-label">Confirm New Password</label>
                    <x-password-input id="password_confirmation" name="password_confirmation" class="admin-input" autocomplete="new-password" required />
                    <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="admin-btn-primary">Update Password</button>
                    <a href="{{ route('student.dashboard') }}" class="admin-btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-student-layout>
