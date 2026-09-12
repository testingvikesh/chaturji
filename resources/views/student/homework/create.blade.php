<x-student-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Self Homework</span>
            <h2 class="admin-page-title">Create Self Homework</h2>
            <p class="admin-page-subtitle">Materials chapter + topic wise generate · 20 / 30 / 50 / 70 / 100 marks</p>
        </div>
    </x-slot>

    @include('student.partials.self-paper-create', [
        'formAction' => url('/student/homework'),
        'countsUrl' => url('/student/homework/question-counts'),
        'previewUrl' => url('/student/homework/paper-preview'),
        'cancelUrl' => url('/student/homework'),
        'generateLabel' => 'Generate & Attempt Homework',
        'markOptions' => $markOptions,
        'subjects' => $subjects,
        'alpineSubjects' => $alpineSubjects ?? null,
        'standard' => $standard,
        'standardName' => $standardName,
    ])
</x-student-layout>
