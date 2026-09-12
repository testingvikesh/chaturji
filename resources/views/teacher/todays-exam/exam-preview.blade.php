<x-teacher-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('teacher.todays-exam.create', ['date' => $preview['exam_date'] ?? '']) }}" class="text-xs font-medium text-brand-green hover:underline mb-1 inline-block">&larr; Back to Add Topics</a>
            <h2 class="admin-page-title">Preview Exam</h2>
            <p class="admin-page-subtitle">Review topic-wise questions before publishing</p>
        </div>
    </x-slot>

    <div class="admin-page max-w-4xl space-y-6">
        <div class="admin-card p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Target</p>
                    <p class="text-lg font-bold text-slate-900">{{ $preview['target_marks'] }} marks exam</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-slate-500">{{ $preview['total_questions'] }} questions · {{ $preview['total_marks'] }} marks total</p>
                </div>
            </div>
            @include('teacher.partials.paper-breakdown', [
                'breakdown' => $preview['breakdown'],
                'totalQuestions' => $preview['total_questions'],
            ])
        </div>

        @foreach ($preview['topic_sections'] as $section)
            <div class="admin-card overflow-hidden">
                <div class="bg-brand-green-50/60 border-b border-slate-100 px-5 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-green-dark">Topic</p>
                    <h3 class="font-bold text-slate-900">{{ $section['topic_name'] ?? 'Topic' }}</h3>
                    <p class="text-xs text-slate-500 mt-1">{{ $section['chapter_name'] ?? 'Chapter' }} · {{ $section['total_questions'] }} questions · {{ $section['total_marks'] }} marks</p>
                </div>

                @foreach ($section['grouped'] as $type => $typeQuestions)
                    <div class="border-b border-slate-100 last:border-b-0">
                        <div class="material-question-group-header">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">{{ \App\Support\PaperTypeHelper::icon($type) }}</span>
                                <h4 class="font-bold text-slate-900">{{ \App\Support\TeachingHomeworkLayout::buckets()[$type]['label'] ?? \App\Support\PaperTypeHelper::label($type) }}</h4>
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
            </div>
        @endforeach

        <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ route('teacher.todays-exam.exam-confirm') }}">
                @csrf
                <button type="submit" class="rounded-xl bg-brand-green text-white px-6 py-2.5 text-sm font-semibold hover:bg-brand-green-dark transition">
                    Confirm &amp; Publish Exam
                </button>
            </form>

            <form method="POST" action="{{ route('teacher.todays-exam.exam-preview.build') }}">
                @csrf
                <input type="hidden" name="exam_log_ids" value="{{ implode(',', $preview['exam_log_ids']) }}">
                <input type="hidden" name="target_marks" value="{{ $preview['target_marks'] }}">
                @foreach ($preview['type_counts'] as $type => $count)
                    <input type="hidden" name="type_counts[{{ $type }}]" value="{{ $count }}">
                @endforeach
                <button type="submit" class="rounded-xl border border-slate-200 px-6 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Regenerate Random Paper
                </button>
            </form>
        </div>
    </div>
</x-teacher-layout>
