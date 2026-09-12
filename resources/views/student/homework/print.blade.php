<x-paper-print-layout :document-title="$homework->title">
    @php
        $chapterNumber = $homework->chapter?->sort_order;
        $chapterName = $homework->chapter?->name;
        $topicName = $homework->topic?->name;

        if ((! $chapterName || ! $topicName) && filled($homework->description) && str_contains($homework->description, ' · ')) {
            [$descChapter, $descTopic] = array_pad(explode(' · ', $homework->description, 2), 2, null);
            $chapterName = $chapterName ?: trim((string) $descChapter);
            $topicName = $topicName ?: trim((string) $descTopic);
        }

        $topicName = $topicName ?: 'All topics';
    @endphp
    @if ($grouped->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
            No questions in this homework yet.
        </div>
    @else
        @include('student.partials.paper-view', [
            'breakdown' => $breakdown,
            'grouped' => $grouped,
            'totalMarks' => $homework->generation_config['total_marks'] ?? null,
            'totalQuestions' => $homework->questions->count(),
            'showMarks' => true,
            'interactiveAttempt' => false,
            'paperHeader' => [
                'title' => $homework->title,
                'className' => $user->standardLabel(),
                'subjectName' => $homework->subject?->name ?? 'General',
                'teacherName' => $homework->teacher?->name ?? 'Teacher',
                'durationMinutes' => null,
                'totalMarks' => $homework->generation_config['total_marks'] ?? null,
                'studentName' => $user->name,
                'chapterNumber' => $chapterNumber,
                'chapterName' => $chapterName,
                'topicName' => $topicName,
                'instructions' => $homework->description,
                'blankStudentFields' => true,
            ],
        ])
    @endif
</x-paper-print-layout>
