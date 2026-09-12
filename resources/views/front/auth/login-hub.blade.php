@php
    $siteName = config('app.name', 'Gses Chaturji');
    $tagline = 'Gujarat School of Excellence System';
    $role = old('role', 'student');
@endphp

<x-auth-direct-layout title="Login">
    <div class="login-direct"
         x-data="{
            role: '{{ $role === 'teacher' ? 'teacher' : 'student' }}',
            slide: 0,
            fading: false,
            timer: null,
            slides: [
                {
                    image: '{{ asset('images/brand/slide1.png') }}',
                    title: 'Blessings for bright learners',
                    text: 'Start every study day with focus, joy, and wisdom.'
                },
                {
                    image: '{{ asset('images/brand/slide2.png') }}',
                    title: 'Learn smart with Chaturji',
                    text: 'Homework, exams, and progress — all in one place.'
                }
            ],
            get current() {
                return this.slides[this.slide] || this.slides[0];
            },
            init() {
                this.start();
            },
            start() {
                clearInterval(this.timer);
                this.timer = setInterval(() => this.next(), 5000);
            },
            next() {
                this.go((this.slide + 1) % this.slides.length);
            },
            go(index) {
                if (index === this.slide || this.fading) return;
                this.fading = true;
                clearInterval(this.timer);
                setTimeout(() => {
                    this.slide = index;
                    this.fading = false;
                    this.start();
                }, 350);
            },
            action() {
                return this.role === 'teacher'
                    ? '{{ route('teacher.login') }}'
                    : '{{ route('student.login') }}';
            }
         }">

        {{-- Left: brand + slider --}}
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
                    <div class="login-direct-slide" :class="fading && 'is-fading'">
                        <div class="login-direct-slide-art">
                            <img :src="current.image" :alt="current.title" class="login-direct-slide-img">
                        </div>
                        <h2 class="login-direct-slide-title" x-text="current.title"></h2>
                        <p class="login-direct-slide-text" x-text="current.text"></p>
                    </div>
                </div>

                <div class="login-direct-dots">
                    <template x-for="(item, index) in slides" :key="'dot-' + index">
                        <button type="button"
                                class="login-direct-dot"
                                :class="slide === index && 'is-active'"
                                @click="go(index)"
                                :aria-label="'Go to slide ' + (index + 1)"></button>
                    </template>
                </div>
            </div>

            <p class="login-direct-motto">विद्या ददाति विनयम्</p>
        </aside>

        {{-- Right: login form --}}
        <section class="login-direct-form-wrap">
            <div class="login-direct-form-card">
                <div class="lg:hidden flex items-center gap-3 mb-6">
                    <img src="{{ asset('images/brand/logo.png') }}" alt="{{ $siteName }}" class="h-14 w-14 object-contain">
                    <div>
                        <p class="font-bold text-brand-green text-lg leading-tight">{{ $siteName }}</p>
                        <p class="text-xs text-slate-500">{{ $tagline }}</p>
                    </div>
                </div>

                <span class="section-badge">Welcome back</span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">Sign in to continue</h1>
                <p class="text-sm text-slate-500 mt-2 mb-6">Use your mobile number or email to access your account.</p>

                <x-auth-session-status class="mb-4 rounded-xl bg-brand-green-50 border border-brand-green-100 px-4 py-3 text-sm text-brand-green-dark" :status="session('status')" />

                <div class="login-role-tabs mb-5" role="tablist" aria-label="Login as">
                    <button type="button"
                            role="tab"
                            class="login-role-tab"
                            :class="role === 'student' && 'is-active'"
                            :aria-selected="role === 'student'"
                            @click="role = 'student'">Student</button>
                    <button type="button"
                            role="tab"
                            class="login-role-tab"
                            :class="role === 'teacher' && 'is-active'"
                            :aria-selected="role === 'teacher'"
                            @click="role = 'teacher'">Teacher</button>
                </div>

                <form method="POST" :action="action()" class="space-y-4">
                    @csrf
                    <input type="hidden" name="role" :value="role">

                    <div>
                        <label for="login" class="auth-label">Mobile No. or Email</label>
                        <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus autocomplete="username" class="auth-input" placeholder="Mobile number or email address">
                        <x-input-error :messages="$errors->get('login')" class="mt-2" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <label for="password" class="auth-label">Password</label>
                            <a href="{{ route('password.request') }}" class="text-xs font-semibold text-brand-green hover:underline">Forgot password?</a>
                        </div>
                        <x-password-input id="password" name="password" required autocomplete="current-password" class="auth-input" placeholder="Enter your password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                        <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-brand-green focus:ring-brand-gold" name="remember">
                        <span class="text-sm text-slate-600">Remember me</span>
                    </label>

                    <button type="submit" class="auth-submit login-direct-submit">
                        Sign In
                    </button>
                </form>

                <div class="auth-divider"><span>or</span></div>

                <p class="text-center text-sm text-slate-500">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="auth-link">Register here</a>
                </p>

                <a href="{{ route('pwa.install') }}" class="pwa-home-install">
                    <span class="pwa-home-install-icon" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block font-semibold text-slate-900">Install App</span>
                        <span class="block text-xs text-slate-500">Add to your home screen</span>
                    </span>
                    <svg class="h-4 w-4 text-brand-green shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </section>
    </div>
</x-auth-direct-layout>
