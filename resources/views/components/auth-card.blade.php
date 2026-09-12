@props([
    'title',
    'subtitle',
    'badge',
    'sideTitle' => 'Welcome',
    'sideText' => 'Join our smart learning platform and manage homework with ease.',
    'icon' => 'student',
])

<section class="auth-section">
    <div class="section-wrap">
        <div class="auth-shell" data-animate="fade-up">
            <div class="auth-brand-panel">
                <div class="relative">
                    <div class="auth-icon-wrap">
                        @if ($icon === 'teacher')
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        @elseif ($icon === 'login')
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        @else
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                        @endif
                    </div>
                    <h2 class="text-2xl font-extrabold leading-tight mb-3">{{ $sideTitle }}</h2>
                    <p class="text-white/75 text-sm leading-relaxed max-w-xs">{{ $sideText }}</p>
                </div>
                <ul class="relative space-y-3 text-sm text-white/70">
                    <li class="flex items-center gap-2"><span class="text-brand-gold">✓</span> Secure account access</li>
                    <li class="flex items-center gap-2"><span class="text-brand-gold">✓</span> Easy homework management</li>
                    <li class="flex items-center gap-2"><span class="text-brand-gold">✓</span> Track progress anytime</li>
                </ul>
            </div>

            <div class="auth-form-panel">
                <div class="auth-form-header text-center lg:text-left">
                    <span class="section-badge">{{ $badge }}</span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ $title }}</h1>
                    <p class="text-sm text-slate-500 mt-2">{{ $subtitle }}</p>
                </div>

                {{ $slot }}
            </div>
        </div>
    </div>
</section>
