@php
    $icon = $section->displayIcon();
    $points = $section->contentPoints();
    $accent = $section->sectionAccentClass();
    $column = $column ?? false;
    $blockClass = $blockClass ?? '';
@endphp

@if ($section->section_type === 'introduction')
    @include('partials.material-sections.introduction', ['section' => $section, 'blockClass' => $blockClass])
@elseif ($section->section_type === 'trailer')
    @include('partials.material-sections.trailer', ['section' => $section, 'blockClass' => $blockClass])
@elseif ($section->section_type === 'importance_of_this_topic')
    @include('partials.material-sections.importance', ['section' => $section, 'blockClass' => $blockClass])
@else
<article id="section-{{ $section->id }}" class="material-section material-section-card admin-card mb-6 {{ $accent }} {{ $column ? 'material-section--column mb-0' : '' }} {{ $blockClass }}">
    <div class="admin-card-top"></div>
    <header class="material-section-header {{ $column ? 'material-section-header--column' : '' }}">
        <span class="material-section-icon" aria-hidden="true">{{ $icon }}</span>
        <div class="min-w-0 flex-1 {{ $column ? 'text-center w-full' : '' }}">
            <h3 class="material-section-title {{ $column ? 'text-base' : '' }}">{{ $section->displayTitle() }}</h3>
            @if ($section->showSubtitle())
                <p class="material-section-type">{{ $section->typeLabel() }}</p>
            @endif
            @if ($points !== [])
                <p class="material-section-count">{{ count($points) }} {{ Str::plural('point', count($points)) }}</p>
            @endif
        </div>
    </header>

    <div class="material-section-body {{ $column ? 'material-section-body--column' : '' }}">
        @if ($points !== [])
            <ul class="material-point-list">
                @foreach ($points as $point)
                    <li id="point-{{ $section->id }}-{{ $loop->iteration }}" class="material-point scroll-mt-36 lg:scroll-mt-32">
                        <span class="material-point-no">{{ $loop->iteration }}</span>
                        <p class="material-point-text">{{ $point }}</p>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="material-section-callout">
                <p class="material-section-callout-text">{{ $section->content }}</p>
            </div>
        @endif
    </div>
</article>
@endif
