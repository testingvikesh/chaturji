<x-teacher-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('teacher.homework.create') }}" class="text-xs font-medium text-brand-green hover:underline mb-1 inline-block">&larr; Back to setup</a>
            <h2 class="admin-page-title">Preview Homework</h2>
            <p class="admin-page-subtitle">{{ $meta['title'] ?? 'Untitled homework' }}</p>
        </div>
    </x-slot>

    <div class="admin-page max-w-4xl space-y-6">
        @include('teacher.partials.paper-breakdown', [
            'breakdown' => $breakdown,
            'totalQuestions' => $totalQuestions,
        ])

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
                        @include('student.partials.question-card', [
                            'question' => $question,
                            'number' => $index + 1,
                            'questionOnly' => true,
                            'sectionType' => $type,
                        ])
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ route('teacher.homework.store') }}">
                @csrf
                <button type="submit" class="rounded-xl bg-brand-green text-white px-6 py-2.5 text-sm font-semibold hover:bg-brand-green-dark transition">Confirm &amp; Save Homework</button>
            </form>

            <form method="POST" action="{{ route('teacher.homework.preview') }}">
                @csrf
                @foreach ($meta as $key => $value)
                    @if (is_scalar($value) || $value === null)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                @foreach ($paperTypes as $type => $label)
                    <input type="hidden" name="type_counts[{{ $type }}]" value="{{ $typeCounts[$type] ?? 0 }}">
                @endforeach
                <button type="submit" class="rounded-xl border border-slate-200 px-6 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Regenerate Random Paper</button>
            </form>
        </div>
    </div>
</x-teacher-layout>
