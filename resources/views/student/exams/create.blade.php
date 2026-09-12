<x-student-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Self Exam</span>
            <h2 class="admin-page-title">Create Self Exam</h2>
            <p class="admin-page-subtitle">Materials chapter + topic wise generate · 20 / 30 / 50 / 70 / 100 marks</p>
        </div>
    </x-slot>

    @include('student.partials.self-paper-create', [
        'formAction' => url('/student/exams'),
        'countsUrl' => url('/student/exams/question-counts'),
        'previewUrl' => url('/student/exams/paper-preview'),
        'cancelUrl' => url('/student/exams'),
        'generateLabel' => 'Generate & Attempt Exam',
        'markOptions' => $markOptions,
        'subjects' => $subjects,
        'alpineSubjects' => $alpineSubjects,
        'standard' => $standard,
        'standardName' => $standardName,
    ])
</x-student-layout>
