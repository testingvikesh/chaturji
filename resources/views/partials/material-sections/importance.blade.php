@php
    $points = $section->contentPoints();
    if ($points === [] && trim((string) $section->content) !== '') {
        $points = [trim((string) $section->content)];
    }
@endphp

@once
<style>
    .material-importance-section .material-importance-header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        border-bottom: 1px solid rgb(204 251 241);
        background: linear-gradient(to right, rgb(240 253 250 / 0.8), #fff, rgb(236 254 255 / 0.5));
        padding: 1rem 1.25rem;
    }
    .material-importance-section .material-importance-header-main {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        min-width: 0;
        flex: 1;
    }
    .material-importance-section .material-importance-icon {
        display: flex;
        height: 3rem;
        width: 3rem;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        border-radius: 1rem;
        border: 1px solid rgb(153 246 228);
        background: #fff;
        font-size: 1.5rem;
        box-shadow: 0 1px 2px rgb(15 23 42 / 0.06);
    }
    .material-importance-section .material-importance-eyebrow {
        margin: 0;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: rgb(15 118 110);
    }
    .material-importance-section .material-importance-title {
        margin: 0.15rem 0 0;
        font-size: 1.125rem;
        font-weight: 700;
        color: rgb(15 23 42);
    }
    .material-importance-section .material-importance-subtitle {
        margin: 0.15rem 0 0;
        font-size: 0.75rem;
        color: rgb(100 116 139);
    }
    .material-importance-section .material-importance-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
        padding: 1.25rem;
    }
    @media (min-width: 640px) {
        .material-importance-section .material-importance-header { padding: 1rem 1.5rem; }
        .material-importance-section .material-importance-title { font-size: 1.25rem; }
        .material-importance-section .material-importance-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding: 1.5rem;
        }
    }
    .material-importance-section .material-importance-card {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        border-radius: 1rem;
        border: 1px solid rgb(204 251 241);
        background: linear-gradient(to bottom right, rgb(240 253 250 / 0.7), #fff);
        padding: 1rem;
        box-shadow: 0 1px 2px rgb(15 23 42 / 0.05);
    }
    .material-importance-section .material-importance-card-no {
        display: flex;
        height: 2rem;
        width: 2rem;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        border-radius: 0.75rem;
        background: rgb(13 148 136);
        font-size: 0.75rem;
        font-weight: 700;
        color: #fff;
        box-shadow: 0 1px 2px rgb(15 23 42 / 0.08);
    }
    .material-importance-section .material-importance-card-text {
        margin: 0;
        font-size: 0.875rem;
        font-weight: 500;
        line-height: 1.5;
        color: rgb(30 41 59);
    }
    .material-importance-section .material-importance-empty {
        padding: 2rem;
        text-align: center;
        font-size: 0.875rem;
        color: rgb(148 163 184);
    }
</style>
@endonce

<article id="section-{{ $section->id }}" class="material-importance-section admin-card mb-6 overflow-hidden scroll-mt-36 lg:scroll-mt-32 {{ $blockClass ?? '' }}">
    <div class="admin-card-top"></div>
    <header class="material-importance-header">
        <div class="material-importance-header-main">
            <span class="material-importance-icon" aria-hidden="true">⭐</span>
            <div>
                <p class="material-importance-eyebrow">Why it matters</p>
                <h3 class="material-importance-title">{{ $section->displayTitle() }}</h3>
                <p class="material-importance-subtitle">Key reasons to study this topic</p>
            </div>
        </div>
        @if ($points !== [])
            <span class="admin-badge-gold shrink-0">{{ count($points) }} points</span>
        @endif
    </header>

    @if ($points !== [])
        <div class="material-importance-grid">
            @foreach ($points as $point)
                <div id="point-{{ $section->id }}-{{ $loop->iteration }}" class="material-importance-card scroll-mt-36 lg:scroll-mt-32">
                    <span class="material-importance-card-no">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <p class="material-importance-card-text">{{ $point }}</p>
                </div>
            @endforeach
        </div>
    @else
        <div class="material-importance-empty">No importance points available yet.</div>
    @endif
</article>
