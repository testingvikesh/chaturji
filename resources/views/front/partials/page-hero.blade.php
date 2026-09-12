@props(['title', 'subtitle', 'badge' => null])

<section class="section-hero py-10 lg:py-14">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-0 right-0 w-96 h-96 bg-brand-gold/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
        <div class="absolute bottom-0 left-0 w-72 h-72 bg-brand-green-light/20 rounded-full blur-3xl translate-y-1/2 -translate-x-1/3"></div>
    </div>
    <div class="relative section-wrap text-center">
        @if ($badge)
            <span class="hero-badge mb-4" data-animate="fade-down" data-animate-immediate data-delay="100">
                <span class="h-2 w-2 rounded-full bg-brand-gold hero-badge-dot"></span>
                {{ $badge }}
            </span>
        @endif
        <h1 class="text-3xl sm:text-4xl lg:text-[2.5rem] font-extrabold leading-tight mb-3 tracking-tight" data-animate="fade-up" data-animate-immediate data-delay="200">{{ $title }}</h1>
        <p class="text-base text-white/75 max-w-2xl mx-auto leading-relaxed" data-animate="fade-up" data-animate-immediate data-delay="350">{{ $subtitle }}</p>
    </div>
</section>
