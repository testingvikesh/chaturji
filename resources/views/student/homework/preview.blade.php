<x-student-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('student.homework.index') }}" class="inline-flex items-center gap-1 text-sm text-brand-green hover:underline mb-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to My Homework
            </a>
            <span class="admin-section-label">Homework Preview</span>
            <h2 class="admin-page-title">{{ $homework->title }}</h2>
            <p class="admin-page-subtitle">Question paper preview — answers are hidden</p>
        </div>
    </x-slot>

    <div class="admin-page space-y-6">
        @include('partials.subject-chapter-topic-bar', [
            'subjectName' => $homework->subject?->name ?? 'General',
            'chapterName' => $homework->chapter?->name,
            'topicName' => $homework->topic?->name ?? $homework->title,
        ])

        @if ($homework->description)
            <div class="admin-card p-5">
                <p class="text-slate-500 text-sm mb-1">Description</p>
                <p class="text-slate-800">{{ $homework->description }}</p>
            </div>
        @endif

        @if ($grouped->isEmpty())
            <div class="admin-card p-8 text-center text-slate-500 text-sm">No questions in this homework yet.</div>
        @else
            @include('student.partials.paper-view', [
                'breakdown' => $breakdown,
                'grouped' => $grouped,
                'totalQuestions' => $homework->questions->count(),
            ])
        @endif
    </div>
</x-student-layout>
