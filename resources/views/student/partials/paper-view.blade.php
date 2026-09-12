@props([
    'breakdown' => [],
    'grouped',
    'totalMarks' => null,
    'totalQuestions' => null,
    'showMarks' => false,
    'toggleAnswer' => false,
    'interactiveAttempt' => false,
    'objectiveStats' => null,
    'subjectiveStats' => null,
    'paperHeader' => null,
])

@php
    $breakdownByType = collect($breakdown)->keyBy('type');
    $questionNumber = 0;
@endphp

@if ($paperHeader)
    @include('student.partials.exam-paper-header', $paperHeader)
@endif

@if ($interactiveAttempt && ($objectiveStats['count'] ?? 0) > 0)
    <div
        id="objective-paper-score"
        class="admin-card overflow-hidden mb-6 sticky top-14 lg:top-0 z-30"
    >
        <div class="admin-card-top"></div>
        <div class="bg-gradient-to-r from-brand-green-50 via-white to-amber-50 px-5 py-4 sm:px-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Objective Practice Score</p>
                <p class="mt-1 text-lg font-bold text-slate-900">
                    <span data-score-earned>0</span> / {{ (int) ($objectiveStats['max_marks'] ?? 0) }} marks
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="rounded-full bg-brand-green px-3 py-1.5 text-xs font-bold text-white">
                    Correct: <span data-score-correct>0</span> / {{ (int) ($objectiveStats['count'] ?? 0) }}
                </span>
                <span class="rounded-full bg-white border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600">
                    Answered: <span data-score-answered>0</span> / {{ (int) ($objectiveStats['count'] ?? 0) }}
                </span>
                <span id="objective-status-hint" class="rounded-full bg-white border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600">
                    Answer all, then Submit
                </span>
            </div>
        </div>
    </div>
@endif

@if ($interactiveAttempt && ($subjectiveStats['count'] ?? 0) > 0)
    <div class="admin-card overflow-hidden mb-6 border border-amber-200 bg-amber-50/50">
        <div class="px-5 py-4 sm:px-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Written Answers</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">
                    Short &amp; Long Answer — upload photo/PDF for teacher check
                </p>
                <p class="mt-1 text-xs text-slate-600">
                    Questions:
                    @foreach ($subjectiveStats['numbers'] ?? [] as $number)
                        Q{{ $number }}@if (! $loop->last), @endif
                    @endforeach
                </p>
            </div>
            <button
                type="button"
                x-on:click="$dispatch('open-modal', 'upload-answer-pdf')"
                class="rounded-xl bg-brand-green px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-green-dark"
            >
                Upload Answer Sheet
            </button>
        </div>
    </div>
@endif

@if ($breakdown !== [])
    @include('teacher.partials.paper-breakdown', [
        'breakdown' => $breakdown,
        'totalMarks' => $totalMarks,
        'totalQuestions' => $totalQuestions,
        'showMarks' => $showMarks,
    ])
@endif

@foreach ($grouped as $type => $typeQuestions)
    @php
        $typeRow = $breakdownByType->get($type);
    @endphp
    <div class="admin-card overflow-hidden">
        <div class="admin-card-top"></div>
        <div class="material-question-group-header">
            <div class="flex items-center gap-2 min-w-0">
                <span class="text-lg shrink-0">{{ \App\Support\PaperTypeHelper::sectionIcon($type) }}</span>
                <h3 class="font-bold text-slate-900 truncate">{{ \App\Support\PaperTypeHelper::sectionLabel($type) }}</h3>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <span class="admin-badge-slate text-xs">{{ $typeQuestions->count() }} Q</span>
                @if ($showMarks && $typeRow && ($typeRow['subtotal'] ?? 0) > 0)
                    <span class="admin-badge-gold text-xs">{{ $typeRow['subtotal'] }} marks</span>
                @endif
            </div>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach ($typeQuestions as $question)
                @php $questionNumber++; @endphp
                @include('student.partials.question-card', [
                    'question' => $question,
                    'number' => $questionNumber,
                    'showAnswer' => false,
                    'toggleAnswer' => $toggleAnswer && ! $interactiveAttempt,
                    'interactiveAttempt' => $interactiveAttempt,
                    'questionOnly' => true,
                    'showMarks' => $showMarks || $interactiveAttempt,
                    'sectionType' => $question->question_type ?? $type,
                ])
            @endforeach
        </div>
    </div>
@endforeach

@if ($interactiveAttempt && ($objectiveStats['count'] ?? 0) > 0)
    <div id="objective-submit-bar" class="admin-card overflow-hidden">
        <div class="admin-card-top"></div>
        <div class="px-5 py-5 sm:px-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-sm font-bold text-slate-900">Submit objective answers</p>
                <p class="mt-1 text-xs text-slate-500">Select all answers first. Results and marks appear only after you submit.</p>
            </div>
            <button
                type="button"
                id="objective-submit-btn"
                disabled
                class="inline-flex h-11 items-center justify-center rounded-xl bg-brand-green px-6 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-green-dark opacity-60 cursor-not-allowed"
            >
                Submit Answers
            </button>
        </div>
    </div>

    <div id="objective-success-modal"
         class="fixed inset-0 z-[90] hidden items-center justify-center p-4"
         aria-hidden="true">
        <div class="absolute inset-0 bg-slate-900/60" data-objective-success-close></div>
        <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl border border-slate-100"
             role="dialog"
             aria-modal="true"
             aria-labelledby="objective-success-title">
            <div class="bg-gradient-to-br from-brand-green-50 via-white to-amber-50 px-6 pt-8 pb-6 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-brand-green text-white shadow-lg shadow-brand-green/30">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 id="objective-success-title" class="text-xl font-bold text-slate-900">Submitted Successfully</h3>
                <p class="mt-2 text-sm text-slate-600">Your objective answers have been checked.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 px-6 py-5 border-t border-slate-100 bg-white">
                <div class="rounded-xl border border-brand-green-100 bg-brand-green-50/60 px-3 py-3 text-center">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Score</p>
                    <p class="mt-1 text-lg font-bold text-brand-green">
                        <span data-success-earned>0</span> / <span data-success-max>{{ (int) ($objectiveStats['max_marks'] ?? 0) }}</span>
                    </p>
                </div>
                <div class="rounded-xl border border-amber-100 bg-amber-50/60 px-3 py-3 text-center">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Correct</p>
                    <p class="mt-1 text-lg font-bold text-amber-800">
                        <span data-success-correct>0</span> / <span data-success-total>{{ (int) ($objectiveStats['count'] ?? 0) }}</span>
                    </p>
                </div>
            </div>
            <div class="px-6 pb-6">
                <button type="button"
                        data-objective-success-close
                        class="w-full inline-flex h-11 items-center justify-center rounded-xl bg-brand-green px-5 text-sm font-semibold text-white hover:bg-brand-green-dark transition">
                    View Results
                </button>
            </div>
        </div>
    </div>
@endif

@if ($interactiveAttempt)
    @include('student.partials.objective-attempt-script')
@endif
