<x-front-layout :title="$title">
    <section class="auth-section">
        <div class="section-wrap">
            <div class="text-center mb-8 max-w-2xl mx-auto" data-animate="fade-up">
                <span class="section-badge">{{ $type === 'login' ? 'Sign In' : 'Get Started' }}</span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ $title }}</h1>
                <p class="mt-2 text-slate-600">{{ $subtitle }}</p>
                <div class="mt-4 flex justify-center">
                    <span class="h-1 w-16 rounded-full bg-gradient-to-r from-brand-green via-brand-gold to-brand-green"></span>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-5 max-w-3xl mx-auto">
                <a href="{{ route('student.' . $type) }}" class="auth-hub-card group" data-animate="fade-up" data-delay="100">
                    <div class="h-14 w-14 rounded-2xl bg-brand-green-50 text-brand-green flex items-center justify-center mb-5 group-hover:bg-brand-green group-hover:text-white transition-colors duration-300">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                    </div>
                    <h2 class="text-xl font-bold text-brand-green mb-2">Student {{ ucfirst($type) }}</h2>
                    <p class="text-sm text-slate-500 mb-4">{{ $type === 'login' ? 'Sign in with mobile or email' : 'Register with name, mobile & standard' }}</p>
                    <span class="inline-flex items-center gap-1 text-sm font-semibold text-brand-green group-hover:gap-2 transition-all">
                        Continue
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </span>
                </a>

                <a href="{{ route('teacher.' . $type) }}" class="auth-hub-card group" data-animate="fade-up" data-delay="200">
                    <div class="h-14 w-14 rounded-2xl bg-brand-green-50 text-brand-green flex items-center justify-center mb-5 group-hover:bg-brand-green group-hover:text-white transition-colors duration-300">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h2 class="text-xl font-bold text-brand-green mb-2">Teacher {{ ucfirst($type) }}</h2>
                    <p class="text-sm text-slate-500 mb-4">{{ $type === 'login' ? 'Sign in with mobile or email' : 'Register with name, mobile & email' }}</p>
                    <span class="inline-flex items-center gap-1 text-sm font-semibold text-brand-green group-hover:gap-2 transition-all">
                        Continue
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </span>
                </a>
            </div>
        </div>
    </section>
</x-front-layout>
