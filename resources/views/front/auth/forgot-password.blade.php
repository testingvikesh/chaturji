@php
    $siteName = config('app.name', 'Gses Chaturji');
    $tagline = 'Gujarat School of Excellence System';
    $role = in_array($role ?? '', ['student', 'teacher'], true) ? $role : '';
@endphp

<x-auth-direct-layout title="Forgot Password">
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
                        <h2 class="login-direct-slide-title">Reset your password</h2>
                        <p class="login-direct-slide-text">Enter your registered email. We will send a password reset link.</p>
                    </div>
                </div>
            </div>
            <p class="login-direct-motto">विद्या ददाति विनयम्</p>
        </aside>

        <section class="login-direct-form-wrap">
            <div class="login-direct-form-card">
                <span class="section-badge">Forgot password</span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">Get a reset link</h1>
                <p class="text-sm text-slate-500 mt-2 mb-6">Enter the email registered on your account.</p>

                <x-auth-session-status class="mb-4 rounded-xl bg-brand-green-50 border border-brand-green-100 px-4 py-3 text-sm text-brand-green-dark" :status="session('status')" />

                <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                    @csrf
                    @if ($role)
                        <input type="hidden" name="role" value="{{ $role }}">
                    @endif

                    <div>
                        <label for="email" class="auth-label">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="auth-input" placeholder="Enter your email">
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <button type="submit" class="auth-submit login-direct-submit">Send Reset Link</button>
                </form>

                <p class="text-center text-sm text-slate-400 mt-6">
                    <a href="{{ route('login') }}" class="auth-link">&larr; Back to login</a>
                </p>
            </div>
        </section>
    </div>
</x-auth-direct-layout>
