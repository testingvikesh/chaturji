<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Generated Paper" subtitle="Only important questions">
            <x-slot name="actions">
                <button type="button" onclick="window.print()" class="rounded-xl bg-brand-green px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-green-dark">Print</button>
                <a href="{{ route('admin.paper-generate.create') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">New paper</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page space-y-6">
        @include('student.partials.exam-paper-header', [
            'title' => $title,
            'className' => $standardName,
            'subjectName' => $subjectName,
            'teacherName' => auth()->user()->name,
            'durationMinutes' => $durationMinutes,
            'totalMarks' => $totalMarks,
            'blankStudentFields' => true,
            'instructions' => $instructions,
        ])

        <div class="flex flex-wrap gap-2 text-xs">
            <span class="rounded-lg bg-slate-100 px-3 py-1.5 font-semibold">{{ $totalQuestions }} important questions</span>
            <span class="rounded-lg bg-slate-100 px-3 py-1.5 font-semibold">{{ $totalMarks }} marks</span>
        </div>

        @if ($breakdown !== [])
            @include('teacher.partials.paper-breakdown', [
                'breakdown' => $breakdown,
                'totalMarks' => $totalMarks,
                'totalQuestions' => $totalQuestions,
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
