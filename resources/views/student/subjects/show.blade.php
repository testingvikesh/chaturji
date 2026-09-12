<x-student-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Student</span>
            <h2 class="admin-page-title">{{ $subject->name }}</h2>
            <p class="admin-page-subtitle">{{ $standard->name }}{{ ! empty($medium) ? ' / '.ucfirst($medium).' medium' : '' }} — chapters from materials</p>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="mb-4">
            <a href="{{ route('student.subjects.index') }}" class="text-sm text-brand-green hover:underline font-medium">&larr; Back to Subjects</a>
        </div>

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">Index — {{ $subject->name }}</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $materials->count() }} chapter(s){{ ! empty($medium) ? ' · '.ucfirst($medium).' medium' : '' }} — {{ \App\Support\MaterialWorkedExamples::isExampleSubject($subject) ? 'open a chapter to view examples' : 'open a chapter to view topics' }}</p>
                </div>
            </div>

            @include('partials.book-index-materials', [
                'materials' => $materials,
                'subject' => $subject,
                'materialTopicRoute' => 'student.material-topics.show',
                'materialChapterRoute' => 'student.materials.show',
            ])
        </div>
    </div>
</x-student-layout>
