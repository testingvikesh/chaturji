<x-student-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('student.exams.index') }}" class="inline-flex items-center gap-1 text-sm text-brand-green hover:underline mb-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to Self Exam
            </a>
            <span class="admin-section-label">Exam Preview</span>
            <h2 class="admin-page-title">{{ $exam->title }}</h2>
            <p class="admin-page-subtitle">Question paper preview — answers are hidden</p>
        </div>
    </x-slot>

    <div class="admin-page space-y-6">
        @if ($grouped->isEmpty())
            <div class="admin-card p-8 text-center text-slate-500 text-sm">No questions in this exam yet.</div>
        @else
            @include('student.partials.paper-view', [
                'breakdown' => $breakdown,
                'grouped' => $grouped,
                'totalMarks' => $exam->total_marks,
                'totalQuestions' => $exam->questions->count(),
                'showMarks' => true,
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
    </div>
</x-student-layout>
