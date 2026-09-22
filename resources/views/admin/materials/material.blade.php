<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">All Materials</span>
            <h2 class="admin-page-title">{{ $material->displayChapterName() }}</h2>
            <p class="admin-page-subtitle">{{ $mediumLabel }} / {{ $standard->name ?? '' }} / {{ $subject->name }} — solved examples</p>
        </div>
    </x-slot>

    @include('partials.chapter-examples-reader', [
        'subject' => $subject,
        'chapter' => $chapter,
        'material' => $material,
        'examples' => $examples,
        'backUrl' => route('admin.materials.subject', ['medium' => $medium, 'subject' => $subject]),
        'practiceMode' => false,
        'readerNav' => $readerNav ?? [],
    ])
</x-app-layout>
