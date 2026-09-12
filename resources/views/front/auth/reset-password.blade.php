@php
    $siteName = config('app.name', 'Gses Chaturji');
    $tagline = 'Gujarat School of Excellence System';
@endphp

<x-auth-direct-layout title="Reset Password">
    <div class="login-direct">
        <aside class="login-direct-side">
            <div class="login-direct-side-glow" aria-hidden="true"></div>
            <div class="login-direct-side-pattern" aria-hidden="true"></div>
            <div class="login-direct-side-top">
                <img src="{{ asset('images/brand/logo.png') }}" alt="{{ $siteName }}" class="login-direct-logo">
                <div>
                    <p class="login-direct-brand">{{ $siteName }}</p>
                    <p class="login-direct-tagline">{{ $tagline }}</p>
                </div>
            </div>
            <div class="login-direct-slider">
                <div class="login-direct-slides">
                    <div class="login-direct-slide">
                        <h2 class="login-direct-slide-title">Choose a new password</h2>
                        <p class="login-direct-slide-text">After reset, sign in with your mobile or email and the new password.</p>
                    </div>
                </div>
            </div>
            <p class="login-direct-motto">विद्या ददाति विनयम्</p>
        </aside>

        <section class="login-direct-form-wrap">
            <div class="login-direct-form-card">
                <span class="section-badge">Reset password</span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">Set a new password</h1>
                <p class="text-sm text-slate-500 mt-2 mb-6">Enter the email from the reset link and your new password.</p>

                <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <div>
                        <label for="email" class="auth-label">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus class="auth-input">
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password" class="auth-label">New password</label>
                        <x-password-input id="password" name="password" required autocomplete="new-password" class="auth-input" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password_confirmation" class="auth-label">Confirm password</label>
                        <x-password-input id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="auth-input" />
                    </div>

                    <button type="submit" class="auth-submit login-direct-submit">Reset Password</button>
                </form>
            </div>
        </section>
    </div>
</x-auth-direct-layout>
