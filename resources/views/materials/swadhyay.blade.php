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
            <h2 class="admin-page-title mt-2">સ્વાધ્યાય</h2>
            <p class="admin-page-subtitle">{{ $subject->name }} / {{ $material->displayChapterName() }} · {{ $total }} {{ \Illuminate\Support\Str::plural('question', $total) }} · click the eye to see the answer</p>
        </div>
    </x-slot>

    <div class="admin-page space-y-4">
        @forelse ($groups as $type => $questions)
            @include('partials.material-question-group', [
                'type' => $type,
                'questions' => $questions,
                'label' => $labels[$type] ?? \App\Models\ChapterQuestion::labelForType($type),
                'toggleAnswer' => true,
            ])
        @empty
            <div class="admin-card p-8 text-center text-sm text-slate-400">No સ્વાધ્યાય questions for this chapter yet.</div>
        @endforelse
    </div>
</x-dynamic-component>
