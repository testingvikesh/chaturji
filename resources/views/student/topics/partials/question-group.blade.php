@if ($type === 'knowledge_ladder')
    @include('partials.knowledge-ladder-grid', [
        'questions' => $questions,
        'label' => $label,
        'toggleAnswer' => $toggleAnswer ?? false,
        'sectionId' => 'questions-'.$type,
    ])
@else
    @include('partials.material-question-group', [
        'type' => $type,
        'questions' => $questions,
        'label' => $label,
        'toggleAnswer' => $toggleAnswer ?? false,
    ])
@endif
