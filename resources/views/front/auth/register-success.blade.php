@php
    $roleLabel = $role === 'teacher' ? 'Teacher' : ucfirst($role);
@endphp

<x-front-layout :title="$roleLabel . ' Registration Successful'">
    <section class="auth-section">
        <div class="section-wrap">
            <div class="max-w-lg mx-auto" data-animate="fade-up">
                <div class="auth-shell overflow-hidden">
                    <div class="auth-brand-panel text-center lg:text-left">
                        <div class="relative flex flex-col items-center lg:items-start">
                            <div class="auth-icon-wrap mb-4">
                                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-extrabold leading-tight mb-2">Registration Successful!</h2>
                            <p class="text-white/75 text-sm leading-relaxed">Your {{ $roleLabel }} account has been submitted successfully.</p>
                        </div>
                    </div>

                    <div class="auth-form-panel text-center lg:text-left">
                        <div class="auth-form-header">
                            <span class="section-badge">Pending Approval</span>
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Thank you, {{ session('registered_name') }}!</h1>
                        </div>

                        <div class="rounded-2xl bg-brand-green-50 border border-brand-green-100 px-5 py-4 text-sm text-brand-green-dark leading-relaxed mb-6">
                            <p class="font-semibold mb-2">Your account is waiting for admin approval.</p>
                            <p>Once the admin reviews and approves your registration, you can log in using your mobile number or email and password.</p>
                        </div>

                        <ul class="space-y-2 text-sm text-slate-600 mb-8 text-left">
                            <li class="flex items-start gap-2"><span class="text-brand-gold mt-0.5">✓</span> Registration submitted successfully</li>
                            <li class="flex items-start gap-2"><span class="text-brand-gold mt-0.5">⏳</span> Waiting for admin approval</li>
                            <li class="flex items-start gap-2"><span class="text-brand-gold mt-0.5">→</span> Login after approval</li>
                        </ul>

                        <a href="{{ route($role . '.login') }}" class="auth-submit block text-center">Go to Login</a>
                        <p class="text-center text-sm text-slate-400 mt-4">
                            <a href="{{ route('login') }}" class="hover:text-brand-green transition">&larr; Back to Login</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-front-layout>
