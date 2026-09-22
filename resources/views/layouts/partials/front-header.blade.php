@php
    $navLinks = [
        ['route' => 'home', 'label' => 'Home'],
        ['route' => 'about', 'label' => 'About Us'],
        ['route' => 'student', 'label' => 'Student'],
        ['route' => 'teacher', 'label' => 'Teacher'],
        ['route' => 'contact', 'label' => 'Contact Us'],
    ];
@endphp

<header class="site-header sticky top-0 z-50 shadow-md" x-data="{ mobileOpen: false }">
    {{-- Top bar --}}
    <div class="bg-brand-green text-white text-sm hidden md:block">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-white/90">{{ $settings['site_tagline'] ?? 'Smart Learning Platform' }}</p>
                <div class="flex flex-wrap items-center gap-6">
                    <a href="mailto:{{ $settings['contact_email'] }}" class="flex items-center gap-2 hover:text-brand-gold-light transition">
                        <svg class="h-4 w-4 text-brand-gold shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        {{ $settings['contact_email'] }}
                    </a>
                    <a href="tel:{{ preg_replace('/\s+/', '', $settings['contact_phone']) }}" class="flex items-center gap-2 hover:text-brand-gold-light transition">
                        <svg class="h-4 w-4 text-brand-gold shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        {{ $settings['contact_phone'] }}
                    </a>
                    <span class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-brand-gold shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $settings['contact_address'] }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Main navigation --}}
    <div class="bg-white border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:grid lg:grid-cols-[1fr_auto_1fr] lg:gap-4">
                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 shrink-0">
                    <img src="{{ asset('images/brand/logo.png') }}" alt="{{ $settings['site_name'] ?? config('app.name') }}" class="h-10 w-10 object-contain drop-shadow-sm">
                    <img src="{{ asset('images/brand/ganpati.png') }}" alt="Shree Ganpati" class="h-10 w-10 object-contain drop-shadow-sm">
                    <div>
                        <span class="block font-bold text-xl text-brand-green leading-tight tracking-tight">{{ $settings['site_name'] ?? config('app.name') }}</span>
                        <span class="block text-[10px] sm:text-xs text-slate-400 uppercase tracking-[0.15em] font-medium">{{ $settings['site_tagline'] ?? 'Gujarat School of Excellence System' }}</span>
                    </div>
                </a>

                {{-- Desktop menu (center) --}}
                <nav class="hidden lg:flex items-center justify-center gap-1 xl:gap-2">
                    @foreach ($navLinks as $link)
                        <a href="{{ route($link['route']) }}"
                           class="relative px-3 xl:px-4 py-4 text-sm font-semibold uppercase tracking-wide transition {{ request()->routeIs($link['route']) ? 'text-brand-green' : 'text-slate-600 hover:text-brand-green' }}">
                            {{ $link['label'] }}
                            @if (request()->routeIs($link['route']))
                                <span class="absolute bottom-0 left-3 right-3 xl:left-4 xl:right-4 h-0.5 bg-brand-gold rounded-full"></span>
                            @endif
                        </a>
                    @endforeach
                </nav>

                {{-- Auth buttons --}}
                <div class="hidden lg:flex items-center justify-end gap-3">
                    @auth
                        @if (Auth::user()->role === 'student' && Auth::user()->isApproved())
                            <a href="{{ route('student.dashboard') }}"
                               class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white bg-brand-green rounded-md shadow-md hover:bg-brand-green-dark transition">
                                Dashboard
                            </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="inline" onsubmit="return confirm('Logout?')">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-brand-green border-2 border-brand-green rounded-md hover:bg-brand-green/5 transition">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-brand-green border-2 border-brand-green rounded-md hover:bg-brand-green/5 transition">
                            Login
                        </a>
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white bg-brand-gold rounded-md shadow-md hover:bg-brand-gold-light transition">
                            Register
                        </a>
                    @endauth
                </div>

                {{-- Mobile: menu + logout (far right) --}}
                <div class="lg:hidden flex items-center gap-1.5 shrink-0">
                    <button @click="mobileOpen = !mobileOpen" class="inline-flex items-center justify-center p-2 rounded-md text-brand-green hover:bg-brand-green/5" aria-label="Toggle menu">
                        <svg x-show="!mobileOpen" class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <svg x-show="mobileOpen" x-cloak class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    @auth
                        <form method="POST" action="{{ route('logout') }}" class="inline" onsubmit="return confirm('Logout?')">
                            @csrf
                            <button type="submit"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-md text-brand-green hover:bg-brand-green/5"
                                    title="Logout"
                                    aria-label="Logout">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div x-show="mobileOpen" x-cloak x-transition class="lg:hidden bg-white border-b border-slate-200 shadow-lg">
        <nav class="max-w-7xl mx-auto px-4 py-4 space-y-1">
            @foreach ($navLinks as $link)
                <a href="{{ route($link['route']) }}"
                   @click="mobileOpen = false"
                   class="block px-4 py-3 text-sm font-semibold uppercase tracking-wide border-l-4 {{ request()->routeIs($link['route']) ? 'border-brand-gold bg-brand-green/5 text-brand-green' : 'border-transparent text-slate-600 hover:bg-slate-50' }}">
                    {{ $link['label'] }}
                </a>
            @endforeach
            <div class="pt-4 mt-4 border-t flex gap-3">
                @auth
                    @if (Auth::user()->role === 'student' && Auth::user()->isApproved())
                        <a href="{{ route('student.dashboard') }}" class="flex-1 text-center px-4 py-3 text-sm font-semibold text-white bg-brand-green rounded-md">Dashboard</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="flex-1" onsubmit="return confirm('Logout?')">
                        @csrf
                        <button type="submit" class="w-full px-4 py-3 text-sm font-semibold text-brand-green border-2 border-brand-green rounded-md">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="flex-1 text-center px-4 py-3 text-sm font-semibold text-brand-green border-2 border-brand-green rounded-md">Login</a>
                    <a href="{{ route('register') }}" class="flex-1 text-center px-4 py-3 text-sm font-semibold text-white bg-brand-gold rounded-md shadow">Register</a>
                @endauth
            </div>
        </nav>
    </div>
</header>

<style>[x-cloak] { display: none !important; }</style>
