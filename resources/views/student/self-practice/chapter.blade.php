<x-student-layout>

    <x-slot name="header">

        <div>

            <span class="admin-section-label">Self Practice</span>

            <h2 class="admin-page-title">{{ $chapter->name }}</h2>

            <p class="admin-page-subtitle">{{ $subject->name }} — tap to reveal answers while practicing</p>

        </div>

    </x-slot>



    @if ($content)

        @include('partials.chapter-material-reader', [

            'subject' => $subject,

            'chapter' => $chapter,

            'content' => $content,

            'sections' => $sections,

            'questionGroups' => $questionGroups,

            'questionGroupLabels' => $questionGroupLabels,

            'backUrl' => route('student.self-practice.subject', $subject),

            'practiceMode' => true,

        ])

    @else

        <div class="admin-page">

            <div class="admin-card p-10 text-center text-slate-400">

                No practice material available for this chapter yet.

            </div>

        </div>

    @endif

</x-student-layout>

