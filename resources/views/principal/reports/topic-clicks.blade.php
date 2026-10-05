<x-principal-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Reports</span>
            <h2 class="admin-page-title">Teacher Click Report</h2>
            <p class="admin-page-subtitle">Teachers on your standards. Subject, chapter and topic, with sections opened and work percent.</p>
        </div>
    </x-slot>

    <div class="admin-page space-y-5">
        @include('principal.partials.reports-nav')
        @include('partials.teacher-click-report', [
            'formAction' => route('principal.reports.topic-clicks'),
            'resetUrl' => route('principal.reports.topic-clicks'),
            'showTeacher' => true,
        ])
    </div>
</x-principal-layout>
