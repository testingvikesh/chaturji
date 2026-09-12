<x-front-layout title="Home">
    {{-- Hero --}}
    <section class="section-hero">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-16 -right-20 w-80 h-80 bg-brand-gold/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-20 -left-20 w-96 h-96 bg-brand-green-light/20 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] border border-white/5 rounded-full"></div>
        </div>

        <div class="relative section-wrap py-10 sm:py-12 lg:py-16">
            <div class="grid lg:grid-cols-2 gap-8 lg:gap-10 items-center">
                {{-- Left: headline + CTAs --}}
                <div class="max-w-xl">
                    <span class="hero-badge mb-4" data-animate="fade-down" data-animate-immediate data-delay="100">
                        <span class="h-2 w-2 rounded-full bg-brand-gold hero-badge-dot shadow-[0_0_8px_rgba(212,160,23,0.8)]"></span>
                        {{ $settings['home_hero_badge'] }}
                    </span>

                    <h1 class="text-4xl sm:text-5xl lg:text-[3rem] font-extrabold leading-[1.1] tracking-tight mb-4">
                        <span class="block text-white" data-animate="fade-up" data-animate-immediate data-delay="200">{{ $settings['home_hero_title'] }}</span>
                        <span class="block text-brand-gold-light mt-1" data-animate="fade-up" data-animate-immediate data-delay="320">{{ $settings['home_hero_title_highlight'] }}</span>
                    </h1>

                    <p class="text-base sm:text-lg text-white/75 mb-6 leading-relaxed" data-animate="fade-up" data-animate-immediate data-delay="440">
                        {{ $settings['home_hero_description'] }}
                    </p>

                    <div class="flex flex-wrap gap-4" data-animate="fade-up" data-animate-immediate data-delay="560">
                        <a href="{{ route('student') }}"
                           class="inline-flex items-center rounded-xl bg-white px-7 py-3.5 text-sm font-bold text-brand-green shadow-lg shadow-black/10 hover:bg-brand-green-50 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200">
                            For Students
                        </a>
                        <a href="{{ route('teacher') }}"
                           class="inline-flex items-center rounded-xl border-2 border-white/50 px-7 py-3.5 text-sm font-bold text-white hover:bg-white/10 hover:border-white transition-all duration-200">
                            For Teachers
                        </a>
                    </div>
                </div>

                {{-- Right: stats grid --}}
                <div class="hero-stat-panel hero-float" data-animate="fade-right" data-animate-immediate data-delay="400">
                    <div class="grid grid-cols-2 gap-4 sm:gap-5">
                        <div class="hero-stat-card" data-animate="zoom-in" data-animate-immediate data-delay="500">
                            <p class="text-3xl sm:text-4xl font-extrabold text-brand-green tracking-tight" data-counter>{{ $settings['home_stat_students'] }}</p>
                            <p class="text-sm text-slate-500 mt-2 font-medium">Active Students</p>
                        </div>
                        <div class="hero-stat-card" data-animate="zoom-in" data-animate-immediate data-delay="620">
                            <p class="text-3xl sm:text-4xl font-extrabold text-brand-green tracking-tight" data-counter>{{ $settings['home_stat_teachers'] }}</p>
                            <p class="text-sm text-slate-500 mt-2 font-medium">Expert Teachers</p>
                        </div>
                        <div class="hero-stat-card" data-animate="zoom-in" data-animate-immediate data-delay="740">
                            <p class="text-3xl sm:text-4xl font-extrabold text-brand-green tracking-tight" data-counter>{{ $settings['home_stat_assignments'] }}</p>
                            <p class="text-sm text-slate-500 mt-2 font-medium">Assignments Done</p>
                        </div>
                        <div class="hero-stat-card ring-2 ring-brand-gold/20" data-animate="zoom-in" data-animate-immediate data-delay="860">
                            <p class="text-3xl sm:text-4xl font-extrabold text-brand-gold tracking-tight" data-counter>{{ $settings['home_stat_satisfaction'] }}</p>
                            <p class="text-sm text-slate-500 mt-2 font-medium">Satisfaction Rate</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="features-section section-py">
        <div class="relative section-wrap">
            <div class="section-header" data-animate="fade-up">
                <span class="section-badge">Why Choose Us</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    Everything You Need to <span class="text-brand-green">Succeed</span>
                </h2>
                <p class="mt-3 text-slate-600 max-w-2xl mx-auto leading-relaxed text-base">
                    Everything students, teachers, and schools need for effective homework management.
                </p>
                <div class="mt-4 flex justify-center">
                    <span class="h-1 w-16 rounded-full bg-gradient-to-r from-brand-green via-brand-gold to-brand-green"></span>
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-5 lg:gap-6 items-stretch">
                @foreach ([
                    [
                        'num' => '01',
                        'title' => 'Homework Tracking',
                        'desc' => 'Submit, review, and track homework assignments with real-time updates and deadlines.',
                        'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                        'featured' => false,
                    ],
                    [
                        'num' => '02',
                        'title' => 'Student & Teacher Connect',
                        'desc' => 'Bridge the gap between students and teachers with seamless communication tools.',
                        'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
                        'featured' => true,
                    ],
                    [
                        'num' => '03',
                        'title' => 'Progress Reports',
                        'desc' => 'Detailed analytics and reports to monitor academic performance and improvement.',
                        'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                        'featured' => false,
                    ],
                ] as $feature)
                    <div class="feature-card group {{ $feature['featured'] ? 'feature-card-featured' : '' }}" data-animate="fade-up" data-delay="{{ $loop->index * 120 }}">
                        <span class="feature-number">{{ $feature['num'] }}</span>

                        <div class="feature-icon-wrap mb-5">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $feature['icon'] }}"/>
                            </svg>
                        </div>

                        <h3 class="relative z-10 text-lg font-bold text-slate-900 mb-2 group-hover:text-brand-green transition-colors duration-300">
                            {{ $feature['title'] }}
                        </h3>
                        <p class="relative z-10 text-slate-600 text-sm leading-relaxed">
                            {{ $feature['desc'] }}
                        </p>

                        <div class="relative z-10 mt-3 hidden items-center gap-2 text-sm font-semibold text-brand-green group-hover:flex">
                            <span>Explore feature</span>
                            <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works / Process --}}
    <section class="process-section">
        <div class="relative section-wrap">
            <div class="section-header" data-animate="fade-up">
                <span class="section-badge">Simple Process</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    How It <span class="text-brand-green">Works</span>
                </h2>
                <p class="mt-3 text-slate-600 text-base">Get started in three simple steps</p>
                <div class="mt-4 flex justify-center">
                    <span class="h-1 w-16 rounded-full bg-gradient-to-r from-brand-green via-brand-gold to-brand-green"></span>
                </div>
            </div>

            <div class="process-track">
                @foreach ([
                    [
                        'step' => '01',
                        'title' => 'Register Account',
                        'desc' => 'Create your student or teacher account in minutes.',
                        'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
                    ],
                    [
                        'step' => '02',
                        'title' => 'Access Dashboard',
                        'desc' => 'Login to your personalized dashboard and manage tasks.',
                        'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z',
                    ],
                    [
                        'step' => '03',
                        'title' => 'Start Learning',
                        'desc' => 'Submit homework, get feedback, and track your progress.',
                        'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                    ],
                ] as $index => $step)
                    <div class="process-item">
                        <div class="process-step-card group {{ $index === 1 ? 'process-step-card-active' : '' }}" data-animate="fade-up" data-delay="{{ $index * 120 }}">
                            <div class="process-step-icon">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $step['icon'] }}"/>
                                </svg>
                            </div>

                            <span class="process-step-label">Step {{ $step['step'] }}</span>
                            <h3 class="process-step-title">{{ $step['title'] }}</h3>
                            <p class="process-step-desc">{{ $step['desc'] }}</p>
                        </div>

                        @if ($index < 2)
                            <div class="process-connector" aria-hidden="true">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                            <div class="process-connector-mobile" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="section-wrap section-py">
        <div class="cta-panel" data-animate="zoom-in">
            <div class="absolute inset-0 pointer-events-none overflow-hidden">
                <div class="absolute -top-20 -right-20 w-72 h-72 bg-brand-gold/15 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-16 w-80 h-80 bg-white/5 rounded-full blur-3xl"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] border border-white/5 rounded-full"></div>
            </div>

            <div class="relative max-w-3xl mx-auto">
                <span class="cta-badge">Get Started Today</span>

                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold mb-3 tracking-tight leading-tight">
                    {{ $settings['home_cta_title'] }}
                </h2>

                <p class="text-base text-white/80 mb-6 max-w-2xl mx-auto leading-relaxed">
                    {{ $settings['home_cta_description'] }}
                </p>

                <div class="flex flex-col sm:flex-row flex-wrap justify-center gap-4">
                    <a href="{{ route('contact') }}" class="cta-btn-white">
                        Contact Us
                    </a>
                    <a href="{{ route('register') }}" class="cta-btn-gold">
                        Create Account
                    </a>
                </div>

                <div class="mt-6 pt-5 border-t border-white/10 flex flex-wrap justify-center gap-4 sm:gap-8 text-sm text-white/60">
                    <span class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 text-brand-gold" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Free to register
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 text-brand-gold" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Easy setup
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 text-brand-gold" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        24/7 support
                    </span>
                </div>
            </div>
        </div>
    </section>
</x-front-layout>
