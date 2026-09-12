@props([
    'questions',
    'label' => 'Knowledge Ladder',
    'subtitle' => 'Step-by-step questions linked like a ladder',
    'icon' => '🪜',
    'badgeClass' => 'admin-badge-green',
    'toggleAnswer' => false,
    'showAnswer' => true,
    'sectionId' => 'questions-knowledge_ladder',
    'blockClass' => '',
    'variant' => 'ladder',
])

@php
    $isLine = $variant === 'line';
    $variants = $isLine
        ? [
            'line-to-line-card--amber',
            'line-to-line-card--sky',
            'line-to-line-card--mint',
            'line-to-line-card--rose',
            'line-to-line-card--violet',
            'line-to-line-card--cyan',
        ]
        : [
            'knowledge-ladder-card--mint',
            'knowledge-ladder-card--peach',
            'knowledge-ladder-card--sky',
        ];
@endphp

@if ($isLine)
@once
<style>
    .line-to-line-card--amber {
        border-color: #fcd34d !important;
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 45%, #fff7ed 100%) !important;
    }
    .line-to-line-card--amber .knowledge-ladder-card-dot { background: #f59e0b !important; }
    .line-to-line-card--sky {
        border-color: #7dd3fc !important;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 45%, #f8fafc 100%) !important;
    }
    .line-to-line-card--sky .knowledge-ladder-card-dot { background: #0ea5e9 !important; }
    .line-to-line-card--mint {
        border-color: #6ee7b7 !important;
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 45%, #f0fdfa 100%) !important;
    }
    .line-to-line-card--mint .knowledge-ladder-card-dot { background: #10b981 !important; }
    .line-to-line-card--rose {
        border-color: #fda4af !important;
        background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 45%, #fff7ed 100%) !important;
    }
    .line-to-line-card--rose .knowledge-ladder-card-dot { background: #f43f5e !important; }
    .line-to-line-card--violet {
        border-color: #c4b5fd !important;
        background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 45%, #faf5ff 100%) !important;
    }
    .line-to-line-card--violet .knowledge-ladder-card-dot { background: #8b5cf6 !important; }
    .line-to-line-card--cyan {
        border-color: #67e8f9 !important;
        background: linear-gradient(135deg, #ecfeff 0%, #cffafe 45%, #f0fdfa 100%) !important;
    }
    .line-to-line-card--cyan .knowledge-ladder-card-dot { background: #06b6d4 !important; }
</style>
@endonce
@endif

<section id="{{ $sectionId }}" class="material-question-group knowledge-ladder-section admin-card mb-6 overflow-hidden {{ $blockClass }}">
    <div class="admin-card-top"></div>
    <header class="material-question-group-header knowledge-ladder-section-header {{ $isLine ? 'line-to-line-section-header' : '' }}">
        <div class="flex items-center gap-2">
            <span class="knowledge-ladder-section-icon" aria-hidden="true">{{ $icon }}</span>
            <div>
                <h3 class="font-bold text-slate-900 text-lg">{{ $label }}</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ $subtitle }}</p>
            </div>
        </div>
        <span class="{{ $badgeClass }}">{{ $questions->count() }}</span>
    </header>

    <div class="knowledge-ladder-wrap">
        <div class="knowledge-ladder-blob knowledge-ladder-blob--one" aria-hidden="true"></div>
        <div class="knowledge-ladder-blob knowledge-ladder-blob--two" aria-hidden="true"></div>

        <div class="knowledge-ladder-grid">
            @foreach ($questions as $question)
                @php $cardVariant = $variants[$loop->index % count($variants)]; @endphp
                <article
                    class="knowledge-ladder-card {{ $cardVariant }}"
                    @if ($toggleAnswer) x-data="{ showAnswer: false }" @endif
                >
                    <span class="knowledge-ladder-card-dot" aria-hidden="true"></span>
                    <span class="knowledge-ladder-card-step">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>

                    <p class="knowledge-ladder-card-question">{{ $question->question_text }}</p>

                    @if ($question->answer)
                        @if ($toggleAnswer)
                            <button
                                type="button"
                                class="knowledge-ladder-card-answer knowledge-ladder-card-answer--toggle"
                                @click="showAnswer = !showAnswer"
                            >
                                <span x-show="!showAnswer" class="knowledge-ladder-card-answer-hint">Tap to reveal answer</span>
                                <span x-show="showAnswer" x-cloak class="knowledge-ladder-card-answer-text">{{ $question->answer }}</span>
                            </button>
                        @else
                            <div class="knowledge-ladder-card-answer">
                                <span class="knowledge-ladder-card-answer-text">{{ $question->answer }}</span>
                            </div>
                        @endif
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
