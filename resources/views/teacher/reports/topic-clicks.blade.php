<x-teacher-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Report</span>
            <h2 class="admin-page-title">My click report</h2>
            <p class="admin-page-subtitle">Subject, chapter and topic. Sections opened out of the topic total, shown as work percent.</p>
        </div>
    </x-slot>

    <div class="admin-page space-y-5">
        @include('partials.teacher-click-report', [
            'formAction' => route('teacher.click-report'),
            'resetUrl' => route('teacher.click-report'),
            'showTeacher' => false,
        ])
    </div>
</x-teacher-layout>
