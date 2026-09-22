<x-student-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <a href="{{ route('student.exams.index') }}" class="inline-flex items-center gap-1 text-sm text-brand-green hover:underline mb-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to Self Exam
                </a>
                <span class="admin-section-label">Exam</span>
                <h2 class="admin-page-title">{{ $exam->title }}</h2>
                <p class="admin-page-subtitle">
                    {{ $exam->subject?->name ?? 'General' }}
                    · By {{ $exam->teacher?->name ?? 'Teacher' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2 shrink-0">
                <a href="{{ route('student.exams.print', $exam) }}" target="_blank" rel="noopener"
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

        @include('student.partials.exam-submission-results', ['submission' => $submission ?? null])

        @if ($grouped->isEmpty())
            <div class="admin-card p-8 text-center text-slate-500 text-sm">No questions added to this exam yet.</div>
        @else
            @include('student.partials.paper-view', [
                'breakdown' => $breakdown,
                'grouped' => $grouped,
                'totalMarks' => $exam->total_marks,
                'totalQuestions' => $exam->questions->count(),
                'showMarks' => true,
                'interactiveAttempt' => true,
                'objectiveStats' => $objectiveStats,
                'subjectiveStats' => $subjectiveStats,
                'workAttemptMeta' => [
                    'paper_type' => 'exam',
                    'paper_id' => $exam->id,
                    'max_marks' => (float) ($objectiveStats['max_marks'] ?? 0),
                    'total_objective' => (int) ($objectiveStats['count'] ?? 0),
                    'store_url' => route('student.work-attempts.objective'),
                ],
                'paperHeader' => [
                    'title' => $exam->title,
                    'className' => $user->standardLabel(),
                    'subjectName' => $exam->subject?->name ?? 'General',
                    'teacherName' => $exam->teacher?->name ?? 'Teacher',
                    'durationMinutes' => $exam->duration_minutes,
                    'totalMarks' => $exam->total_marks,
                    'studentName' => $user->name,
                    'instructions' => $exam->instructions,
                ],
            ])
        @endif

        @include('student.partials.answer-sheet-upload-modal', [
            'action' => route('student.exams.submit-answer-pdf', $exam),
            'subjectiveStats' => $subjectiveStats ?? null,
        ])
    </div>
</x-student-layout>
