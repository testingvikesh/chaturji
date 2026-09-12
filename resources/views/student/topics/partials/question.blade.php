@php
    $resolvedType = strtolower((string) ($sectionType ?? $question->question_type ?? ''));
    $pointwiseAnswer = \App\Models\ChapterQuestion::usesPointwiseAnswer($resolvedType);
    $answerPoints = $pointwiseAnswer
        ? \App\Models\ChapterQuestion::answerPoints($question->answer ?? null)
        : [];
@endphp

<div class="material-question p-5 sm:p-6">
    <div class="flex items-start gap-4">
        <span class="material-question-no">{{ $number }}</span>
        <div class="min-w-0 flex-1 space-y-3">
            <p class="material-question-text">{{ $question->question_text }}</p>

            @if ($question->options)
                @if (isset($question->options['column_a']) || isset($question->options['column_b']))
                    <div class="material-match-grid">
                        <div>
                            <p class="material-options-label">Column A</p>
                            <ul class="material-options-list">
                                @foreach ($question->options['column_a'] ?? [] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <div>
                            <p class="material-options-label">Column B</p>
                            <ul class="material-options-list">
                                @foreach ($question->options['column_b'] ?? [] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @else
                    <ul class="material-options-list material-options-list--mcq">
                        @foreach ($question->options as $key => $option)
                            <li>
                                @if (is_array($option))
                                    {{ json_encode($option, JSON_UNESCAPED_UNICODE) }}
                                @else
                                    <span class="material-option-key">{{ is_string($key) && strlen($key) === 1 ? $key.'.' : '' }}</span>
                                    {{ $option }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif

            @if ($question->answer)
                <div class="material-answer {{ $pointwiseAnswer && count($answerPoints) > 1 ? 'material-answer--points' : '' }}">
                    <span class="material-answer-label">Answer</span>
                    @if ($pointwiseAnswer && count($answerPoints) > 1)
                        <ul class="material-answer-points">
                            @foreach ($answerPoints as $point)
                                <li>{{ $point }}</li>
                            @endforeach
                        </ul>
                    @else
                        <span class="material-answer-text">{{ $question->answer }}</span>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
