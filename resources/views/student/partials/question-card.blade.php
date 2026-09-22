@props([
    'question',
    'number',
    'showAnswer' => false,
    'toggleAnswer' => false,
    'questionOnly' => false,
    'showMarks' => false,
    'interactiveAttempt' => false,
    'sectionType' => null,
    'questionEditUrl' => null,
])

@php
    use App\Support\ObjectiveQuestionTypes;
    use App\Support\ObjectiveOptionHelper;
    use App\Support\ObjectiveQuestionResolver;

    $rawOptions = $question->options;
    if (is_string($rawOptions)) {
        $rawOptions = json_decode($rawOptions, true);
    }

    $isMatch = is_array($rawOptions) && (isset($rawOptions['column_a']) || isset($rawOptions['column_b']));
    $optionItems = (is_array($rawOptions) && ! $isMatch) ? $rawOptions : [];

    $resolvedType = strtolower((string) ($sectionType ?? $question->question_type ?? ''));
    $optionEntries = ObjectiveOptionHelper::entries($optionItems, $resolvedType);
    $optionsTwoCol = in_array($resolvedType, ['mcq', 'true_false'], true)
        || count($optionEntries) >= 2;

    $questionMarks = (int) ($question->marks ?? 1);
    $resolvedAnswer = ObjectiveQuestionResolver::answer($question);
    $isObjective = ObjectiveQuestionTypes::isObjective($resolvedType) && filled($resolvedAnswer);
    $useInteractive = $interactiveAttempt && $isObjective;
    $useChoiceOptions = $useInteractive && $optionEntries !== [];
    $useTextInput = $useInteractive && ! $useChoiceOptions;

    $correctAnswerLabel = (string) $resolvedAnswer;
    foreach ($optionEntries as $entry) {
        $answer = trim($correctAnswerLabel);
        if (strcasecmp($entry['key'], $answer) === 0 || strcasecmp($entry['text'], $answer) === 0) {
            $correctAnswerLabel = strtoupper($entry['key']).' · '.$entry['text'];
            break;
        }
    }

    $pointwiseAnswer = \App\Models\ChapterQuestion::usesPointwiseAnswer($resolvedType);
    $answerPoints = $pointwiseAnswer
        ? \App\Models\ChapterQuestion::answerPoints($resolvedAnswer)
        : [];
@endphp

<div
    class="material-question p-5 sm:p-6 @if($useInteractive) js-objective-question @endif"
    @if ($useInteractive)
        data-objective-question
        data-question-number="{{ $number }}"
        data-marks="{{ $questionMarks }}"
        data-correct-answer="{{ e($resolvedAnswer) }}"
    @elseif ($toggleAnswer && ! $useInteractive)
        x-data="{ showAnswer: false }"
    @endif
>
    <div class="flex items-start gap-4">
        <span class="material-question-no">{{ $number }}</span>
        <div class="min-w-0 flex-1 space-y-3">
            <div class="flex items-start justify-between gap-3">
                <p class="material-question-text flex-1">{{ $question->question_text }}</p>
                <div class="flex shrink-0 items-center gap-2">
                    @if (($questionOnly && $showMarks) || $useInteractive)
                        @if ($questionMarks > 0)
                            <span class="admin-badge-gold text-xs whitespace-nowrap">{{ $questionMarks }} mark{{ $questionMarks > 1 ? 's' : '' }}</span>
                        @endif
                    @endif
                    @if ($toggleAnswer && $resolvedAnswer && ! $useInteractive)
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
                    @if (! empty($questionEditUrl))
                        <a href="{{ $questionEditUrl }}"
                           class="shrink-0 inline-flex h-9 items-center gap-1 rounded-lg border border-brand-green-100 bg-brand-green-50 px-2.5 text-xs font-semibold text-brand-green hover:bg-brand-green hover:text-white transition"
                           title="Edit question">
                            Edit
                        </a>
                    @endif
                </div>
            </div>

            @if (! $questionOnly && ! $useInteractive && (isset($question->question_type) || isset($question->marks)))
                <div class="flex flex-wrap gap-2">
                    @if (isset($question->question_type))
                        <span class="admin-badge-slate text-xs">{{ $question->typeLabel() }}</span>
                    @endif
                    @if (isset($question->marks) && $question->marks)
                        <span class="admin-badge-gold text-xs">{{ $question->marks }} mark{{ $question->marks > 1 ? 's' : '' }}</span>
                    @endif
                </div>
            @endif

            @if ($useInteractive && $useTextInput)
                <div class="flex flex-wrap items-center gap-2">
                    <input
                        type="text"
                        data-objective-text
                        class="js-objective-text flex-1 min-w-[12rem] rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-brand-green focus:ring-brand-green"
                        placeholder="Type your answer (submit at the end)"
                    >
                </div>
            @elseif ($useChoiceOptions)
                <div class="{{ $optionsTwoCol ? 'grid grid-cols-1 sm:grid-cols-2 gap-1.5' : 'space-y-1.5' }}">
                    @foreach ($optionEntries as $entry)
                        <button
                            type="button"
                            data-objective-option
                            data-option-key="{{ $entry['key'] }}"
                            data-option-text="{{ e($entry['text']) }}"
                            class="js-objective-option group flex w-full items-center gap-2 rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-left text-sm transition hover:border-amber-400 hover:bg-amber-50 cursor-pointer"
                        >
                            <span class="js-objective-letter flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-amber-300 bg-white text-xs font-bold text-amber-700 group-hover:bg-amber-500 group-hover:text-white group-hover:border-amber-500">
                                {{ strtoupper($entry['key']) }}
                            </span>
                            <span class="min-w-0 flex-1 leading-snug text-slate-700">{{ $entry['text'] }}</span>
                        </button>
                    @endforeach
                </div>
            @elseif ($optionEntries !== [])
                <div class="{{ $optionsTwoCol ? 'grid grid-cols-1 sm:grid-cols-2 gap-1.5' : 'space-y-1.5' }}">
                    @foreach ($optionEntries as $entry)
                        <div class="flex w-full items-center gap-2 rounded-lg border border-slate-100 bg-slate-50/80 px-2 py-1.5 text-sm">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-brand-green/30 bg-white text-xs font-bold text-brand-green">
                                {{ strtoupper($entry['key']) }}
                            </span>
                            <span class="min-w-0 flex-1 leading-snug text-slate-700">{{ $entry['text'] }}</span>
                        </div>
                    @endforeach
                </div>
            @elseif ($isMatch)
                <div class="material-match-grid">
                    <div>
                        <p class="material-options-label">Column A</p>
                        <ul class="material-options-list">
                            @foreach ($rawOptions['column_a'] ?? [] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div>
                        <p class="material-options-label">Column B</p>
                        <ul class="material-options-list">
                            @foreach ($rawOptions['column_b'] ?? [] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if ($useInteractive)
                <div
                    data-objective-result
                    class="hidden rounded-xl px-4 py-3 text-sm"
                >
                    <p class="font-bold js-objective-result-title"></p>
                    <p class="mt-1 js-objective-result-marks"></p>
                    <p class="mt-2 hidden text-xs font-normal text-slate-600 js-objective-result-answer">
                        Correct answer: <span class="font-semibold">{{ $correctAnswerLabel }}</span>
                    </p>
                </div>
            @elseif ($resolvedAnswer)
                @if ($toggleAnswer)
                    <div x-show="showAnswer" x-cloak x-transition class="material-answer {{ $pointwiseAnswer && count($answerPoints) > 1 ? 'material-answer--points' : '' }}">
                        <span class="material-answer-label">Answer</span>
                        @if ($pointwiseAnswer && count($answerPoints) > 1)
                            <ul class="material-answer-points">
                                @foreach ($answerPoints as $point)
                                    <li>{{ $point }}</li>
                                @endforeach
                            </ul>
                        @else
                            <span class="material-answer-text">{{ $resolvedAnswer }}</span>
                        @endif
                    </div>
                @elseif ($showAnswer)
                    <div class="material-answer {{ $pointwiseAnswer && count($answerPoints) > 1 ? 'material-answer--points' : '' }}">
                        <span class="material-answer-label">Answer</span>
                        @if ($pointwiseAnswer && count($answerPoints) > 1)
                            <ul class="material-answer-points">
                                @foreach ($answerPoints as $point)
                                    <li>{{ $point }}</li>
                                @endforeach
                            </ul>
                        @else
                            <span class="material-answer-text">{{ $resolvedAnswer }}</span>
                        @endif
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
