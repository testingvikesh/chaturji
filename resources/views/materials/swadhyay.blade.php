@php
    $component = match ($panel) {
        'student' => 'student-layout',
        'teacher' => 'teacher-layout',
        default => 'app-layout',
    };
@endphp

<x-dynamic-component :component="$component">
    <x-slot name="header">
        <div>
            <a href="{{ $backUrl }}" class="text-sm font-medium text-brand-green hover:underline">&larr; Back</a>
            <h2 class="admin-page-title mt-2">{{ $title }}</h2>
            <p class="admin-page-subtitle">{{ $subject->name }} / {{ $material->displayChapterName() }} · {{ $total }} {{ \Illuminate\Support\Str::plural('question', $total) }} · click the eye to see the answer</p>
        </div>
    </x-slot>

    <div class="admin-page space-y-4">
        @forelse ($sections as $section)
            <section class="material-question-group admin-card mb-6">
                <div class="admin-card-top"></div>
                <header class="material-question-group-header">
                    <h3 class="font-bold text-slate-900 text-lg">{{ $section['title'] }}</h3>
                    <span class="admin-badge-green">{{ count($section['questions']) }}</span>
                </header>
                <div class="divide-y divide-slate-100">
                    @foreach ($section['questions'] as $question)
                        @php
                            $hasAnswer = $question['answer'] !== '' || $question['points'] !== [] || $question['correct'] !== '';
                        @endphp
                        <div class="material-question p-5 sm:p-6" x-data="{ showAnswer: false }">
                            <div class="flex items-start gap-4">
                                <span class="material-question-no">{{ $question['no'] !== '' ? $question['no'] : $loop->iteration }}</span>
                                <div class="min-w-0 flex-1 space-y-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1 space-y-2">
                                            @if ($question['verse'] !== '')
                                                <p class="material-question-text whitespace-pre-line">{{ $question['verse'] }}</p>
                                            @endif
                                            @if ($question['question'] !== '')
                                                <p class="material-question-text">{{ $question['question'] }}</p>
                                            @endif
                                        </div>
                                        @if ($hasAnswer)
                                            <button
                                                type="button"
                                                @click="showAnswer = !showAnswer"
                                                class="shrink-0 inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:border-brand-green hover:text-brand-green transition"
                                                :title="showAnswer ? 'Hide answer' : 'Show answer'"
                                            >
                                                <svg x-show="!showAnswer" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                <svg x-show="showAnswer" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>

                                    @if ($question['options'] !== [])
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                            @foreach ($question['options'] as $option)
                                                <div
                                                    class="flex w-full items-center gap-2 rounded-lg border px-2 py-1.5 text-sm"
                                                    :class="showAnswer && '{{ $option['key'] }}' === '{{ $question['correct'] }}' ? 'border-emerald-300 bg-emerald-50' : 'border-slate-100 bg-slate-50/80'"
                                                >
                                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-brand-green/30 bg-white text-xs font-bold text-brand-green">
                                                        {{ $option['key'] }}
                                                    </span>
                                                    <span class="min-w-0 flex-1 leading-snug text-slate-700">{{ $option['text'] }}</span>
                                                    <span x-show="showAnswer && '{{ $option['key'] }}' === '{{ $question['correct'] }}'" x-cloak class="shrink-0 text-xs font-semibold text-emerald-700">✓</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if ($hasAnswer)
                                        <div x-show="showAnswer" x-cloak x-transition class="material-answer {{ count($question['points']) > 1 ? 'material-answer--points' : '' }}">
                                            <span class="material-answer-label">સાચો ઉત્તર</span>
                                            @if ($question['answer'] !== '')
                                                <span class="material-answer-text">{{ $question['answer'] }}</span>
                                            @endif
                                            @if ($question['points'] !== [])
                                                <ul class="material-answer-points">
                                                    @foreach ($question['points'] as $point)
                                                        <li>{{ $point }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="admin-card p-8 text-center text-sm text-slate-400">No સ્વાધ્યાય questions for this chapter yet.</div>
        @endforelse
    </div>
</x-dynamic-component>
