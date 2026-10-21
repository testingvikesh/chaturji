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
        @include('student.partials.pdf-exam-sheet', [
            'title' => $exam->title,
            'className' => $user->standardLabel(),
            'subjectName' => $exam->subject?->name ?? 'General',
            'standardSlug' => $exam->standard,
            'config' => $exam->generation_config ?? [],
            'chapterName' => $chapterName,
            'topicName' => $topicName,
            'totalMarks' => $exam->total_marks,
            'totalQuestions' => $exam->questions->count(),
            'breakdown' => $breakdown,
            'grouped' => \App\Support\PaperTypeHelper::groupInPaperOrder($exam->questions),
            'attemptCounts' => $exam->generation_config['type_counts'] ?? [],
            'optionalCounts' => $exam->generation_config['optional_counts'] ?? [],
            'instructions' => $exam->instructions,
        ])
    @endif
</x-paper-print-layout>
