<x-teacher-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <a href="{{ route('teacher.homework.index') }}" class="text-xs font-medium text-brand-green hover:underline mb-1 inline-block">&larr; All Homework</a>
                <h2 class="admin-page-title">{{ $homework->title }}</h2>
            </div>
            <a href="{{ route('teacher.homework.edit', $homework) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold hover:bg-slate-50 transition">Edit</a>
        </div>
    </x-slot>

    <div class="admin-page space-y-6">
        <div class="flex flex-wrap gap-2 text-xs">
            <span class="rounded-lg bg-slate-100 px-3 py-1.5 font-semibold">{{ ucfirst($homework->status) }}</span>
            <span class="rounded-lg bg-slate-100 px-3 py-1.5 font-semibold">{{ $homework->questions->count() }} Questions</span>
            @if ($homework->due_at)
                <span class="rounded-lg bg-slate-100 px-3 py-1.5 font-semibold">Due: {{ $homework->due_at->format('d M Y, h:i A') }}</span>
            @endif
        </div>

        @if ($homework->description)
            <div class="admin-card p-5"><p class="text-sm text-slate-600">{{ $homework->description }}</p></div>
        @endif

        @if ($breakdown !== [])
            @include('teacher.partials.paper-breakdown', [
                'breakdown' => $breakdown,
                'totalQuestions' => $homework->questions->count(),
            ])
        @endif

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
                    @foreach ($typeQuestions as $index => $question)
                        @include('student.partials.question-card', ['question' => $question, 'number' => $index + 1, 'showAnswer' => true, 'sectionType' => $type])
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-teacher-layout>
