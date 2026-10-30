<x-paper-print-layout :document-title="$paper->title.($withAnswers ? ' (With answers)' : ' (Without answers)')">
    @include('student.partials.pdf-exam-sheet', [
        'title' => $paper->title,
        'className' => $paper->standard->name ?? '',
        'subjectName' => $paper->subject->name ?? 'General',
        'standardSlug' => $paper->standard->slug ?? null,
        'mediumLabel' => $paper->mediumLabel(),
        'config' => ['material_ids' => $paper->chapter_ids ?? []],
        'chapterName' => $paper->chapter_label,
        'topicName' => null,
        'totalMarks' => $paper->total_marks,
        'totalQuestions' => $paper->total_questions,
        'breakdown' => $breakdown,
        'grouped' => $grouped,
        'attemptCounts' => $paper->type_counts ?? [],
        'optionalCounts' => [],
        'instructions' => $paper->instructions,
        'showAnswers' => $withAnswers,
    ])

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 400);
        });
    </script>
</x-paper-print-layout>
