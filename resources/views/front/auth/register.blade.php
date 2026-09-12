@php
    $roleLabel = $role === 'teacher' ? 'Chaturji' : ucfirst($role);
@endphp

<x-front-layout :title="$roleLabel . ' Register'">
    <x-auth-card
        :badge="$roleLabel . ' Register'"
        title="Create Account"
        :subtitle="'Register as a ' . strtolower($roleLabel) . ' and get started today'"
        :side-title="$role === 'teacher' ? 'Empower Your Teaching' : 'Join Our Platform'"
        :side-text="$role === 'teacher' ? 'Create assignments, review submissions, and guide students effectively.' : 'Access homework tools designed for students and teachers.'"
        :icon="$role">

        <form method="POST" action="{{ route($role . '.register') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="auth-label">Full Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="auth-input" placeholder="Enter your full name">
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="mobile" class="auth-label">Mobile No.</label>
                    <input id="mobile" type="text" name="mobile" value="{{ old('mobile') }}" required autocomplete="tel" class="auth-input" placeholder="9876543210">
                    <x-input-error :messages="$errors->get('mobile')" class="mt-2" />
                </div>
                <div>
                    <label for="email" class="auth-label">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="auth-input" placeholder="email@example.com">
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="auth-label">Password</label>
                    <x-password-input id="password" name="password" required autocomplete="new-password" class="auth-input" placeholder="••••••••" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <div>
                    <label for="password_confirmation" class="auth-label">Confirm Password</label>
                    <x-password-input id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="auth-input" placeholder="••••••••" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>
            </div>

            <button type="submit" class="auth-submit mt-2">Create {{ $roleLabel }} Account</button>
        </form>

        <div class="auth-divider"><span>or</span></div>

        <p class="text-center text-sm text-slate-500">
            Already have an account?
            <a href="{{ route($role . '.login') }}" class="auth-link">Login here</a>
        </p>
        <p class="text-center text-sm text-slate-400 mt-3">
            <a href="{{ route('register') }}" class="hover:text-brand-green transition">&larr; Back to register options</a>
        </p>
    </x-auth-card>
</x-front-layout>
