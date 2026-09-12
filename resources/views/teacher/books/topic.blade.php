<x-teacher-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Books</span>
            <h2 class="admin-page-title">{{ $materialTopic->displayName() }}</h2>
            <p class="admin-page-subtitle">{{ $standard->name ?? '' }} / {{ $subject->name }} / {{ is_object($chapter) ? ($chapter->name ?? '') : '' }}</p>
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
        'backUrl' => route('teacher.books.show', ['subject' => $subject, 'medium' => $medium]),
        'practiceMode' => false,
        'material' => $material,
        'textbookPoints' => $textbookPoints ?? collect(),
        'readerNav' => $readerNav ?? [],
        'readerMaterialId' => $readerMaterialId ?? $material->id,
        'materialTopic' => $materialTopic,
    ])
</x-teacher-layout>
