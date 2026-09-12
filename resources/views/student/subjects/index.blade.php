<x-student-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Student</span>
            <h2 class="admin-page-title">My Subjects</h2>
            <p class="admin-page-subtitle">{{ $standard?->name ?? 'Your standard' }}{{ ! empty($medium) ? ' / '.ucfirst($medium).' medium' : '' }} — subjects for your class</p>
        </div>
    </x-slot>

    <div class="admin-page">
        @include('student.partials.subjects-grid', [
            'subjects' => $subjects,
            'standardName' => $standard?->name ?? $user->standardLabel(),
            'showViewAll' => false,
        ])
    </div>
</x-student-layout>
