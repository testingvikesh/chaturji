<x-paper-print-layout :document-title="$exam->title">
    @php
        $chapterNumber = $exam->chapter?->sort_order;
        $chapterName = $exam->chapter?->name;
        $topicName = $exam->topic?->name;

        if ((! $chapterName || ! $topicName) && filled($exam->description) && str_contains($exam->description, ' · ')) {
            [$descChapter, $descTopic] = array_pad(explode(' · ', $exam->description, 2), 2, null);
            $chapterName = $chapterName ?: trim((string) $descChapter);
            $topicName = $topicName ?: trim((string) $descTopic);
        }

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
                'className' => $user->standardLabel(),
                'subjectName' => $exam->subject?->name ?? 'General',
                'teacherName' => $exam->teacher?->name ?? 'Teacher',
                'durationMinutes' => $exam->duration_minutes,
                'totalMarks' => $exam->total_marks,
                'studentName' => $user->name,
                'chapterNumber' => $chapterNumber,
                'chapterName' => $chapterName,
                'topicName' => $topicName,
                'instructions' => $exam->instructions,
                'blankStudentFields' => true,
            ],
        ])
    @endif
</x-paper-print-layout>
