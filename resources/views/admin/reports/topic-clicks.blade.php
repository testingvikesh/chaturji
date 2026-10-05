<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header
            title="Teacher Click Report"
            subtitle="Teacher-wise and date-wise topic opens, with total points and total clicks">
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page space-y-5">
        @include('partials.teacher-click-report', [
            'formAction' => route('admin.reports.topic-clicks'),
            'resetUrl' => route('admin.reports.topic-clicks'),
            'showTeacher' => true,
        ])
    </div>
</x-app-layout>
