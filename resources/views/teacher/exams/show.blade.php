<x-teacher-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <a href="{{ route('teacher.exams.index') }}" class="text-xs font-medium text-brand-green hover:underline mb-1 inline-block">&larr; All Exams</a>
                <h2 class="admin-page-title">{{ $exam->title }}</h2>
            </div>
            <a href="{{ route('teacher.exams.edit', $exam) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold hover:bg-slate-50 transition">Edit</a>
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
                <div class="divide-y divide-slate-100">
                    @foreach ($typeQuestions as $question)
                        @php $questionNumber++; @endphp
                        @include('student.partials.question-card', ['question' => $question, 'number' => $questionNumber, 'showAnswer' => true, 'sectionType' => $type])
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-teacher-layout>
