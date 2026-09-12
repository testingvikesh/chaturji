@php
    $points = $section->contentPoints();
    if ($points === [] && trim((string) $section->content) !== '') {
        $points = [trim((string) $section->content)];
    }
@endphp

<article id="section-{{ $section->id }}" class="material-trailer-section admin-card mb-6 overflow-hidden scroll-mt-36 lg:scroll-mt-32 {{ $blockClass ?? '' }}">
    <div class="admin-card-top"></div>
    <header class="material-trailer-header">
        <span class="material-trailer-icon" aria-hidden="true">🎬</span>
        <div>
            <h3 class="material-trailer-title">{{ $section->displayTitle() }}</h3>
            <p class="material-trailer-subtitle">{{ $section->typeLabel() }} — quick preview points</p>
        </div>
        @if ($points !== [])
            <span class="admin-badge-gold shrink-0">{{ count($points) }} points</span>
        @endif
    </header>

    @if ($points !== [])
        <div class="material-trailer-grid">
            @foreach ($points as $point)
                <div id="point-{{ $section->id }}-{{ $loop->iteration }}" class="material-trailer-card scroll-mt-36 lg:scroll-mt-32">
                    <span class="material-trailer-card-no">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <p class="material-trailer-card-text">{{ $point }}</p>
                </div>
            @endforeach
        </div>
    @else
        <div class="material-trailer-empty">No trailer points available yet.</div>
    @endif
</article>
