<x-student-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Student</span>
            <h2 class="admin-page-title">{{ $material->displayChapterName() }}</h2>
            <p class="admin-page-subtitle">{{ $standard->name }} / {{ $subject->name }} — solved examples</p>
        </div>
    </x-slot>

    @include('partials.chapter-examples-reader', [
        'subject' => $subject,
        'chapter' => $chapter,
        'material' => $material,
        'examples' => $examples,
        'backUrl' => $backUrl,
        'practiceMode' => $practiceMode,
        'readerNav' => $readerNav ?? [],
    ])
</x-student-layout>
