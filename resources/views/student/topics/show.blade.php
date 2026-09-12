<x-student-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Student</span>
            <h2 class="admin-page-title">{{ $topic?->name ?? $chapter->name }}</h2>
            <p class="admin-page-subtitle">{{ $standard->name }} / {{ $subject->name }} / {{ $chapter->name }}</p>
        </div>
    </x-slot>

    @include('partials.chapter-material-reader', [
        'subject' => $subject,
        'chapter' => $chapter,
        'topic' => $topic,
        'content' => $content,
        'sections' => $sections,
        'questionGroups' => $questionGroups,
        'questionGroupLabels' => $questionGroupLabels,
        'backUrl' => route('student.subjects.show', $subject),
        'practiceMode' => false,
    ])
</x-student-layout>
