<footer class="site-footer" data-animate="fade-up">
    <div class="relative section-wrap pt-10 lg:pt-12 pb-6">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-10 mb-8">
            {{-- Brand --}}
            <div class="sm:col-span-2 lg:col-span-1">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 mb-4 group">
                    <img src="{{ asset('images/brand/logo.png') }}" alt="{{ $settings['site_name'] ?? config('app.name') }}" class="h-9 w-9 object-contain drop-shadow-lg group-hover:scale-105 transition-transform">
                    <img src="{{ asset('images/brand/ganpati.png') }}" alt="Shree Ganpati" class="h-9 w-9 object-contain drop-shadow-lg group-hover:scale-105 transition-transform">
                    <div>
                        <span class="block font-bold text-lg text-white leading-tight">{{ $settings['site_name'] ?? config('app.name') }}</span>
                        <span class="block text-[10px] uppercase tracking-[0.15em] text-white/40 font-medium">{{ $settings['site_tagline'] ?? 'Gujarat School of Excellence System' }}</span>
                    </div>
                </a>
                <p class="text-sm text-white/60 leading-relaxed max-w-xs">
                    {{ $settings['footer_description'] ?? 'A modern homework and learning platform connecting students, teachers, and administrators in one place.' }}
                </p>
            </div>

            {{-- Quick Links --}}
            <div>
                <h4 class="footer-heading">Quick Links</h4>
                <ul class="space-y-2">
                    @foreach ([
                        ['route' => 'home', 'label' => 'Home'],
                        ['route' => 'about', 'label' => 'About Us'],
                        ['route' => 'student', 'label' => 'Student'],
                        ['route' => 'teacher', 'label' => 'Teacher'],
                    ] as $link)
                        <li>
                            <a href="{{ route($link['route']) }}" class="footer-link">
                                <svg class="h-3 w-3 text-brand-gold/60" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Support --}}
            <div>
                <h4 class="footer-heading">Support</h4>
                <ul class="space-y-2">
                    @foreach ([
                        ['route' => 'contact', 'label' => 'Contact Us'],
                    ] as $link)
                        <li>
                            <a href="{{ route($link['route']) }}" class="footer-link">
                                <svg class="h-3 w-3 text-brand-gold/60" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Contact Info --}}
            <div>
                <h4 class="footer-heading">Contact Info</h4>
                <ul class="space-y-3">
                    <li class="footer-contact-item">
                        <span class="footer-contact-icon">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </span>
                        <span class="pt-1.5 leading-relaxed">{{ $settings['contact_address'] ?? '' }}</span>
                    </li>
                    <li class="footer-contact-item">
                        <span class="footer-contact-icon">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </span>
                        <a href="mailto:{{ $settings['contact_email'] ?? '' }}" class="pt-1.5 hover:text-brand-gold-light transition">{{ $settings['contact_email'] ?? '' }}</a>
                    </li>
                    <li class="footer-contact-item">
                        <span class="footer-contact-icon">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </span>
                        <a href="tel:{{ preg_replace('/\s+/', '', $settings['contact_phone'] ?? '') }}" class="pt-1.5 hover:text-brand-gold-light transition">{{ $settings['contact_phone'] ?? '' }}</a>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Bottom bar --}}
        <div class="border-t border-white/10 pt-5 flex flex-col sm:flex-row justify-between items-center gap-3">
            <p class="text-sm text-white/40">
                &copy; {{ date('Y') }} {{ $settings['site_name'] ?? config('app.name') }}. All rights reserved.
            </p>
            <div class="flex flex-wrap justify-center gap-6 text-sm">
                <a href="{{ route('home') }}" class="text-white/40 hover:text-brand-gold-light transition">Home</a>
                <a href="{{ route('contact') }}" class="text-white/40 hover:text-brand-gold-light transition">Contact</a>
            </div>
        </div>
    </div>
</footer>
