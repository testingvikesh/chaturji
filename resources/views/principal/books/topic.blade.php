<x-principal-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Books</span>
            <h2 class="admin-page-title">{{ $materialTopic->displayName() }}</h2>
            <p class="admin-page-subtitle">{{ $standard->name ?? '' }} / {{ $subject->name }} / {{ is_object($chapter) ? ($chapter->name ?? '') : '' }}</p>
        </div>
    </x-slot>

    @if (collect($sections ?? [])->isEmpty() && collect($questionGroups ?? [])->flatten()->isEmpty())
        <div class="admin-page mb-4">
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Topic opened, but material content is not ready for this chapter yet.
            </div>
        </div>
    @endif

    @include('partials.chapter-material-reader', [
        'subject' => $subject,
        'chapter' => $chapter,
        'topic' => $topic,
        'content' => $content,
        'sections' => $sections,
        'questionGroups' => $questionGroups,
        'questionGroupLabels' => $questionGroupLabels,
        'backUrl' => route('principal.books.show', ['subject' => $subject, 'medium' => $medium]),
        'practiceMode' => false,
        'material' => $material,
        'textbookPoints' => $textbookPoints ?? collect(),
        'readerNav' => $readerNav ?? [],
        'readerMaterialId' => $readerMaterialId ?? $material->id,
        'materialTopic' => $materialTopic,
        'canEditQuestions' => false,
        'questionEditMedium' => $medium,
    ])
</x-principal-layout>
