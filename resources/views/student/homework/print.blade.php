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
        @include('student.partials.pdf-exam-sheet', [
            'title' => $homework->title,
            'className' => $user->standardLabel(),
            'subjectName' => $homework->subject?->name ?? 'General',
            'standardSlug' => $homework->standard,
            'config' => $homework->generation_config ?? [],
            'chapterName' => $chapterName,
            'topicName' => $topicName,
            'totalMarks' => $homework->generation_config['total_marks'] ?? null,
            'totalQuestions' => $homework->questions->count(),
            'breakdown' => $breakdown,
            'grouped' => \App\Support\PaperTypeHelper::groupInPaperOrder($homework->questions),
            'attemptCounts' => $homework->generation_config['type_counts'] ?? [],
            'optionalCounts' => $homework->generation_config['optional_counts'] ?? [],
            'instructions' => $homework->description,
        ])
    @endif
</x-paper-print-layout>
