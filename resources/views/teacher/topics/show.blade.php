<x-teacher-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('teacher.subjects.show', $subject) }}" class="inline-flex items-center gap-1 text-sm text-brand-green hover:underline mb-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to {{ $subject->name }}
            </a>
            <span class="admin-section-label">Topic Material</span>
            <h2 class="admin-page-title">{{ $topic?->name ?? $content->title }}</h2>
            <p class="admin-page-subtitle">
                {{ $standard->name }} / {{ $subject->name }} / {{ $chapter->name }}
            </p>
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
        'backUrl' => route('teacher.subjects.show', $subject),
        'practiceMode' => false,
    ])
</x-teacher-layout>
