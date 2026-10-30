<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="{{ $paper->title }}" subtitle="Only important questions">
            <x-slot name="actions">
                <a href="{{ route('admin.paper-generate.print', ['generatedPaper' => $paper, 'answers' => 0]) }}" target="_blank" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Print without answers</a>
                <a href="{{ route('admin.paper-generate.print', ['generatedPaper' => $paper, 'answers' => 1]) }}" target="_blank" class="rounded-xl bg-brand-green px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-green-dark">Print with answers</a>
                <a href="{{ route('admin.paper-generate.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">All papers</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page space-y-6">
        @include('admin.partials.alert')

        @include('student.partials.exam-paper-header', [
            'title' => $paper->title,
            'className' => trim(($paper->standard->name ?? '').' · '.$paper->mediumLabel()),
            'subjectName' => $paper->subject->name ?? '',
            'teacherName' => auth()->user()->name,
            'durationMinutes' => $paper->duration_minutes,
            'totalMarks' => $paper->total_marks,
            'blankStudentFields' => true,
            'instructions' => $paper->instructions,
        ])

        <div class="flex flex-wrap gap-2 text-xs">
            <span class="rounded-lg bg-slate-100 px-3 py-1.5 font-semibold">{{ $paper->total_questions }} important questions</span>
            <span class="rounded-lg bg-slate-100 px-3 py-1.5 font-semibold">{{ $paper->total_marks }} marks</span>
        </div>

        @if ($breakdown !== [])
            @include('teacher.partials.paper-breakdown', [
                'breakdown' => $breakdown,
                'totalMarks' => $paper->total_marks,
                'totalQuestions' => $paper->total_questions,
            ])
        @endif

        @php $questionNumber = 0; @endphp
        @foreach ($grouped as $type => $typeQuestions)
            <div class="admin-card overflow-hidden">
                <div class="material-question-group-header">
                    <h3 class="font-bold text-slate-900">{{ \App\Support\PaperTypeHelper::label($type) }}</h3>
                    <span class="admin-badge-slate text-xs">{{ $typeQuestions->count() }}</span>
                </div>
                @foreach ($typeQuestions as $question)
                    @php $questionNumber++; @endphp
                    @include('student.partials.question-card', [
                        'question' => $question,
                        'number' => $questionNumber,
                        'showAnswer' => true,
                        'sectionType' => $type,
                    ])
                @endforeach
            </div>
        @endforeach
    </div>
</x-app-layout>
