@php
    $icon = $section->displayIcon();
    $points = $section->contentPoints();
    $accent = $section->sectionAccentClass();
    $column = $column ?? false;
    $blockClass = $blockClass ?? '';
    $storyDisplay = ($section->section_type === 'story' && isset($storyCard) && is_array($storyCard)) ? $storyCard : null;
    $storyReady = is_array($storyDisplay) && (($storyDisplay['paragraphs'] ?? []) !== [] || ($storyDisplay['rows'] ?? []) !== []);
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
        @if ($storyReady)
            <article class="story-card">
                @if ($storyDisplay['headline'] !== '')
                    <h3 class="story-headline">📖 {{ $storyDisplay['headline'] }}</h3>
                @endif
                @if ($storyDisplay['meta_line'] !== '')
                    <p class="story-meta">{{ $storyDisplay['meta_line'] }}</p>
                @endif
                @if (($storyDisplay['topic_theme'] ?? '') !== '')
                    <p class="story-meta"><strong>પાઠનો મુખ્ય વિષય:</strong> {!! \App\Support\MaterialSummaryNote::rich($storyDisplay['topic_theme']) !!}</p>
                @endif
                @if ($storyDisplay['title'] !== '')
                    <p class="story-kicker">🌟 Reverse Story : {{ $storyDisplay['title'] }}</p>
                @endif
                @foreach ($storyDisplay['paragraphs'] as $paragraph)
                    <p class="story-line">{!! \App\Support\MaterialSummaryNote::rich($paragraph) !!}</p>
                @endforeach
                @if (($storyDisplay['memory_chain'] ?? '') !== '')
                    <p class="story-memory">🔗 {!! \App\Support\MaterialSummaryNote::rich($storyDisplay['memory_chain']) !!}</p>
                @endif
                @if (($storyDisplay['important_facts'] ?? []) !== [])
                    <div class="story-memory">
                        <p><strong>📌 Important Facts Revision</strong></p>
                        <ul style="margin:6px 0 0;padding-left:18px;">
                            @foreach ($storyDisplay['important_facts'] as $fact)
                                <li>{!! \App\Support\MaterialSummaryNote::rich($fact) !!}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (($storyDisplay['life_timeline'] ?? '') !== '')
                    <p class="story-memory">⏱ {!! \App\Support\MaterialSummaryNote::rich($storyDisplay['life_timeline']) !!}</p>
                @endif
                @if ($storyDisplay['memory_line'] !== '')
                    <p class="story-memory">🧠 {!! \App\Support\MaterialSummaryNote::rich($storyDisplay['memory_line']) !!}</p>
                @endif
                @if (($storyDisplay['imagination_note'] ?? '') !== '')
                    <p class="story-meta">📝 {!! \App\Support\MaterialSummaryNote::rich($storyDisplay['imagination_note']) !!}</p>
                @endif
                @if ($storyDisplay['moral'] !== '')
                    <p class="story-memory">{!! \App\Support\MaterialSummaryNote::rich($storyDisplay['moral']) !!}</p>
                @endif
                @if ($storyDisplay['rows'] !== [])
                    <h3 class="story-kicker">🧠 W/H Memory Table</h3>
                    <div class="wh-wrap">
                        <table class="wh-table">
                            <thead>
                                <tr>
                                    <th>WH</th>
                                    <th>Question</th>
                                    <th>Answer to remember</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($storyDisplay['rows'] as $row)
                                    <tr>
                                        <td><span class="wh-pill">{!! \App\Support\MaterialSummaryNote::rich($row['wh']) !!}</span></td>
                                        <td>{!! \App\Support\MaterialSummaryNote::rich($row['question']) !!}</td>
                                        <td>{!! \App\Support\MaterialSummaryNote::rich($row['answer']) !!}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </article>
        @elseif ($points !== [])
            <ul class="material-point-list">
                @foreach ($points as $point)
                    <li id="point-{{ $section->id }}-{{ $loop->iteration }}" class="material-point scroll-mt-36 lg:scroll-mt-32">
                        <span class="material-point-no">{{ $loop->iteration }}</span>
                        <p class="material-point-text">{!! \App\Support\MaterialSummaryNote::rich($point) !!}</p>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="material-section-callout">
                <p class="material-section-callout-text">{!! \App\Support\MaterialSummaryNote::rich((string) $section->content) !!}</p>
            </div>
        @endif
    </div>
</article>
@endif
