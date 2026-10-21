<x-teacher-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <a href="{{ route('teacher.exams.index') }}" class="text-xs font-medium text-brand-green hover:underline mb-1 inline-block">&larr; All Exams</a>
                <h2 class="admin-page-title">{{ $exam->title }}</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('teacher.exams.print', $exam) }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 rounded-xl bg-brand-green px-4 py-2 text-sm font-semibold text-white hover:bg-brand-green-dark transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V4h12v5M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v6H6v-6z"/>
                    </svg>
                    Print
                </a>
                <a href="{{ route('teacher.exams.edit', $exam) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold hover:bg-slate-50 transition">Edit</a>
            </div>
        </div>
    </x-slot>

    <div class="admin-page space-y-6">
        @php
            $standardName = \App\Models\Standard::query()
                ->where('slug', $exam->standard)
                ->value('name') ?? str_replace('_', ' ', ucwords(str_replace('_', ' ', $exam->standard ?? ''), '_'));
        @endphp

        @include('student.partials.exam-paper-header', [
            'title' => $exam->title,
            'className' => $standardName,
            'subjectName' => $exam->subject?->name ?? 'General',
            'teacherName' => auth()->user()->name,
            'durationMinutes' => $exam->duration_minutes,
            'totalMarks' => $exam->total_marks,
            'blankStudentFields' => true,
            'instructions' => $exam->instructions,
        ])

        <div class="flex flex-wrap gap-2 text-xs">
            <span class="rounded-lg bg-slate-100 px-3 py-1.5 font-semibold">{{ ucfirst($exam->status) }}</span>
            <span class="rounded-lg bg-slate-100 px-3 py-1.5 font-semibold">{{ $exam->questions->count() }} Questions</span>
            <span class="rounded-lg bg-slate-100 px-3 py-1.5 font-semibold">{{ $exam->total_marks }} Marks</span>
        </div>

        @if ($exam->description)
            <div class="admin-card p-5"><p class="text-sm text-slate-600">{{ $exam->description }}</p></div>
        @endif

        @if ($breakdown !== [])
            @include('teacher.partials.paper-breakdown', [
                'breakdown' => $breakdown,
                'totalMarks' => $exam->total_marks,
                'totalQuestions' => $exam->questions->count(),
            ])
        @endif

        @php $questionNumber = 0; @endphp
        @foreach ($grouped as $type => $typeQuestions)
            <div class="admin-card overflow-hidden">
                <div class="material-question-group-header">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">{{ \App\Support\PaperTypeHelper::icon($type) }}</span>
                        <h3 class="font-bold text-slate-900">{{ \App\Support\PaperTypeHelper::label($type) }}</h3>
                    </div>
                    <span class="admin-badge-slate text-xs">({{ $typeQuestions->count() }})</span>
                </div>
                @include('teacher.partials.section-attempt-line', [
                    'attempt' => (int) (($exam->generation_config['type_counts'] ?? [])[$type] ?? 0),
                    'optional' => (int) (($exam->generation_config['optional_counts'] ?? [])[$type] ?? 0),
                ])
                <div class="divide-y divide-slate-100">
                    @foreach ($typeQuestions as $question)
                        @php $questionNumber++; @endphp
                        @include('student.partials.question-card', [
                            'question' => $question,
                            'number' => $questionNumber,
                            'showAnswer' => true,
                            'sectionType' => $type,
                            'questionEditUrl' => route('teacher.exams.questions.edit', [$exam, $question]),
                        ])
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-teacher-layout>
