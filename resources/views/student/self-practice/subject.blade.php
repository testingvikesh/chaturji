<x-student-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('student.self-practice.index') }}" class="inline-flex items-center gap-1 text-sm text-brand-green hover:underline mb-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to Self Practice
            </a>
            <span class="admin-section-label">Self Practice</span>
            <h2 class="admin-page-title">{{ $subject->name }}</h2>
            <p class="admin-page-subtitle">{{ ! empty($medium) ? ucfirst($medium).' medium — ' : '' }}chapters from materials — click a topic to practice</p>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">Index — {{ $subject->name }}</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $materials->count() }} chapter(s) from materials — {{ \App\Support\MaterialWorkedExamples::isExampleSubject($subject) ? 'open a chapter to view examples' : 'open a chapter to view topics' }}</p>
                </div>
            </div>

            @include('partials.book-index-materials', [
                'materials' => $materials,
                'subject' => $subject,
                'materialTopicRoute' => 'student.self-practice.material-topics.show',
                'materialChapterRoute' => 'student.self-practice.materials.show',
                'emptyText' => 'No material chapters available for practice yet.',
            ])
        </div>
    </div>
</x-student-layout>
