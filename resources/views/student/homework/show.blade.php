<x-student-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <a href="{{ route('student.homework.index') }}" class="inline-flex items-center gap-1 text-sm text-brand-green hover:underline mb-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to My Homework
                </a>
                <span class="admin-section-label">Homework</span>
                <h2 class="admin-page-title">{{ $homework->title }}</h2>
                <p class="admin-page-subtitle">
                    {{ $homework->subject?->name ?? 'General' }}
                    @if ($homework->chapter) · {{ $homework->chapter->name }} @endif
                    · By {{ $homework->teacher?->name ?? 'Teacher' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2 shrink-0">
                <a href="{{ route('student.homework.print', $homework) }}" target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-800 shadow-sm transition hover:bg-amber-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                    </svg>
                    PDF
                </a>

                <button
                type="button"
                x-data=""
                x-on:click="$dispatch('open-modal', 'upload-answer-pdf')"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-brand-green px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-green-dark"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                Upload Answer Sheet
            </button>
            </div>
        </div>
    </x-slot>

    <div class="admin-page space-y-6" x-data="{ fileName: '' }">
        @if (session('success'))
            <div class="rounded-xl border border-brand-green-200 bg-brand-green-50 px-4 py-3 text-sm text-brand-green-dark">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @include('partials.subject-chapter-topic-bar', [
            'subjectName' => $homework->subject?->name ?? 'General',
            'chapterName' => $homework->chapter?->name,
            'topicName' => $homework->topic?->name ?? $homework->title,
        ])

        @include('student.partials.exam-submission-results', [
            'submission' => $submission ?? null,
            'paperLabel' => 'homework',
        ])

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <h3 class="font-bold text-slate-900">Homework Details</h3>
            </div>
            <div class="p-5 sm:p-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-slate-500 mb-1">Status</p>
                    @if ($homework->isOverdue())
                        <span class="admin-badge text-xs bg-red-50 text-red-700 border border-red-200">Overdue</span>
                    @else
                        <span class="admin-badge-green">Assigned</span>
                    @endif
                </div>
                <div>
                    <p class="text-slate-500 mb-1">Due Date</p>
                    <p class="font-semibold text-slate-900">
                        {{ $homework->due_at ? $homework->due_at->format('d M Y, h:i A') : 'No due date' }}
                    </p>
                </div>
                <div>
                    <p class="text-slate-500 mb-1">Questions</p>
                    <p class="font-semibold text-slate-900">{{ $homework->questions->count() }}</p>
                </div>
            </div>
            @if ($homework->description)
                <div class="px-5 sm:px-6 pb-5 border-t border-slate-100 pt-4">
                    <p class="text-slate-500 text-sm mb-1">Description</p>
                    <p class="text-slate-800">{{ $homework->description }}</p>
                </div>
            @endif
        </div>

        @if ($grouped->isEmpty())
            <div class="admin-card p-8 text-center text-slate-500 text-sm">No questions added to this homework yet.</div>
        @else
            @include('student.partials.paper-view', [
                'breakdown' => $breakdown,
                'grouped' => $grouped,
                'totalQuestions' => $homework->questions->count(),
                'showMarks' => true,
                'interactiveAttempt' => true,
                'objectiveStats' => $objectiveStats,
                'subjectiveStats' => $subjectiveStats,
            ])
        @endif

        @include('student.partials.answer-sheet-upload-modal', [
            'action' => route('student.homework.submit-answer-pdf', $homework),
            'subjectiveStats' => $subjectiveStats ?? null,
        ])
    </div>
</x-student-layout>
