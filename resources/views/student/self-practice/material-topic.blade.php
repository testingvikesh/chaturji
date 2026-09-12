<x-student-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Self Practice</span>
            <h2 class="admin-page-title">{{ $materialTopic->displayName() }}</h2>
            <p class="admin-page-subtitle">{{ $subject->name }} / {{ $material->displayChapterName() }} — tap to reveal answers while practicing</p>
        </div>
    </x-slot>

    @include('partials.chapter-material-reader', [
        'subject' => $subject,
        'chapter' => $chapter,
        'topic' => $topic,
        'content' => $content,
        'sections' => $sections,
        'questionGroups' => $questionGroups,
        'questionGroupLabels' => $questionGroupLabels,
        'backUrl' => route('student.self-practice.subject', $subject),
        'practiceMode' => true,
        'material' => $material,
        'textbookPoints' => $textbookPoints ?? collect(),
        'readerNav' => $readerNav ?? [],
        'readerMaterialId' => $readerMaterialId ?? $material->id,
        'materialTopic' => $materialTopic,
    ])
</x-student-layout>
