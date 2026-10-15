<x-paper-print-layout :document-title="$exam->title">
    @php
        $standardName = \App\Models\Standard::query()
            ->where('slug', $exam->standard)
            ->value('name') ?? str_replace('_', ' ', ucwords(str_replace('_', ' ', $exam->standard ?? ''), '_'));

        $chapterNumber = $exam->chapter?->sort_order;
        $chapterName = $exam->chapter?->name ?: ($exam->generation_config['chapter_name'] ?? null);
        $topicName = $exam->topic?->name ?: ($exam->generation_config['topic_name'] ?? null);
        $topicName = $topicName ?: 'All topics';
    @endphp
    @if ($grouped->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
            No questions in this exam yet.
        </div>
    @else
        @include('student.partials.paper-view', [
            'breakdown' => $breakdown,
            'grouped' => $grouped,
            'totalMarks' => $exam->total_marks,
            'totalQuestions' => $exam->questions->count(),
            'showMarks' => true,
            'interactiveAttempt' => false,
            'paperHeader' => [
                'title' => $exam->title,
                'className' => $standardName,
                'subjectName' => $exam->subject?->name ?? 'General',
                'teacherName' => auth()->user()->name,
                'durationMinutes' => $exam->duration_minutes,
                'totalMarks' => $exam->total_marks,
                'chapterNumber' => $chapterNumber,
                'chapterName' => $chapterName,
                'topicName' => $topicName,
                'instructions' => $exam->instructions,
                'blankStudentFields' => true,
            ],
        ])
    @endif

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 400);
        });
    </script>
</x-paper-print-layout>
