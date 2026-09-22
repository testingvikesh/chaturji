@if ($type === 'knowledge_ladder')
    @include('partials.knowledge-ladder-grid', [
        'questions' => $questions,
        'label' => $label,
        'toggleAnswer' => $toggleAnswer ?? false,
        'sectionId' => 'questions-'.$type,
        'blockClass' => $blockClass ?? '',
        'questionEditUrlBuilder' => $questionEditUrlBuilder ?? null,
    ])
@else
    <section id="questions-{{ $type }}" class="material-question-group admin-card mb-6 {{ $blockClass ?? '' }}">
        <div class="admin-card-top"></div>
        <header class="material-question-group-header">
            <div class="flex items-center gap-2">
                <span class="text-lg">{{ \App\Models\ChapterQuestion::iconForType($type) }}</span>
                <h3 class="font-bold text-slate-900 text-lg">{{ $label }}</h3>
            </div>
            <span class="admin-badge-green">{{ $questions->count() }}</span>
        </header>
        <div class="divide-y divide-slate-100">
            @foreach ($questions as $question)
                @include('student.partials.question-card', [
                    'question' => $question,
                    'number' => $loop->iteration,
                    'showAnswer' => ! ($toggleAnswer ?? false),
                    'toggleAnswer' => $toggleAnswer ?? false,
                    'questionOnly' => true,
                    'sectionType' => $type,
                    'questionEditUrl' => is_callable($questionEditUrlBuilder ?? null)
                        ? ($questionEditUrlBuilder)($question)
                        : ($questionEditUrl ?? null),
                ])
            @endforeach
        </div>
    </section>
@endif
