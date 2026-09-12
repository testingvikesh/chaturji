@php
    $points = $section->contentPoints();
    $body = $points !== [] ? implode("\n\n", $points) : trim((string) $section->content);
@endphp

<article id="section-{{ $section->id }}" class="material-intro-section admin-card mb-6 overflow-hidden scroll-mt-36 lg:scroll-mt-32 {{ $blockClass ?? '' }}">
    <div class="admin-card-top"></div>
    <div class="material-intro-wrap">
        <div class="material-intro-blob" aria-hidden="true"></div>
        <header class="material-intro-header">
            <span class="material-intro-icon" aria-hidden="true">💡</span>
            <div>
                <p class="material-intro-eyebrow">Start here</p>
                <h3 class="material-intro-title">{{ $section->displayTitle() }}</h3>
            </div>
        </header>
        <div class="material-intro-body">
            @if ($points !== [])
                <ul class="space-y-3">
                    @foreach ($points as $point)
                        <li id="point-{{ $section->id }}-{{ $loop->iteration }}" class="material-intro-text scroll-mt-36 lg:scroll-mt-32 list-none">{{ $point }}</li>
                    @endforeach
                </ul>
            @else
                <p id="point-{{ $section->id }}-1" class="material-intro-text scroll-mt-36 lg:scroll-mt-32">{{ $body }}</p>
            @endif
        </div>
    </div>
</article>
