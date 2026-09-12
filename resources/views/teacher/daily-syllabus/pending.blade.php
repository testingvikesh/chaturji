<x-teacher-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <span class="admin-section-label">Daily Syllabus</span>
                <h2 class="admin-page-title">Pending Topics</h2>
                <p class="admin-page-subtitle">Topics marked partial or not completed</p>
            </div>
            <a href="{{ route('teacher.daily-syllabus.create') }}" class="rounded-xl bg-brand-green px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-green-dark">
                + New Daily Update
            </a>
        </div>
    </x-slot>

    <div class="admin-page">
        @include('admin.partials.alert')

        <div class="admin-card overflow-hidden">
            <div class="admin-card-top"></div>
            <div class="admin-table-wrap">
                <table class="admin-table w-full text-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Chapter</th>
                            <th>Topic</th>
                            <th>Status</th>
                            <th>Remark</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>{{ optional($log->teaching_date)->format('d-M-Y') }}</td>
                                <td>{{ $log->standard }}</td>
                                <td>{{ $log->subject?->name ?? '—' }}</td>
                                <td>{{ $log->chapter?->name ?? '—' }}</td>
                                <td>{{ $log->topic?->name ?? '—' }}</td>
                                <td>
                                    @if ($log->isPartial())
                                        <span class="admin-badge-gold text-xs">Partial</span>
                                    @else
                                        <span class="admin-badge-slate text-xs">Not Completed</span>
                                    @endif
                                </td>
                                <td class="max-w-[10rem] truncate">{{ $log->notes ?: '—' }}</td>
                                <td>
                                    <a href="{{ route('teacher.daily-syllabus.create', [
                                        'standard' => $log->standard,
                                        'subject_id' => $log->subject_id,
                                        'chapter_id' => $log->chapter_id,
                                        'topic_id' => $log->topic_id,
                                    ]) }}" class="text-xs font-semibold text-brand-green hover:underline">Update</a>
                                </td>
                            </tr>
                        @empty
                            @include('admin.partials.empty-row', ['colspan' => 8, 'message' => 'No pending topics.'])
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="p-4 border-t border-slate-100">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>
</x-teacher-layout>
