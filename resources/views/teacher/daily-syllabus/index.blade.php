<x-teacher-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <span class="admin-section-label">Daily Syllabus</span>
                <h2 class="admin-page-title">Previous Syllabus Updates</h2>
                <p class="admin-page-subtitle">Your daily teaching / syllabus history</p>
            </div>
            <a href="{{ route('teacher.daily-syllabus.create') }}" class="rounded-xl bg-brand-green px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-green-dark">
                + New Daily Update
            </a>
        </div>
    </x-slot>

    <div class="admin-page space-y-5">
        @include('admin.partials.alert')

        <form method="GET" class="admin-card p-4 flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="rounded-xl border-slate-200 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="rounded-xl border-slate-200 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Status</label>
                <select name="status" class="rounded-xl border-slate-200 text-sm">
                    <option value="">All</option>
                    <option value="completed" @selected(($filters['status'] ?? '') === 'completed')>Completed</option>
                    <option value="partial" @selected(($filters['status'] ?? '') === 'partial')>Partial</option>
                    <option value="remaining" @selected(($filters['status'] ?? '') === 'remaining')>Not Completed</option>
                </select>
            </div>
            <button type="submit" class="rounded-xl bg-brand-green px-4 py-2 text-sm font-semibold text-white">Filter</button>
            <a href="{{ route('teacher.daily-syllabus.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600">Reset</a>
            <a href="{{ route('teacher.daily-syllabus.pending') }}" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800">Pending Topics</a>
        </form>

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
                            <th>HW / Material / Test</th>
                            <th>Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap">{{ optional($log->teaching_date)->format('d-M-Y') }}</td>
                                <td>{{ $log->standard }}</td>
                                <td>{{ $log->subject?->name ?? '—' }}</td>
                                <td>{{ $log->chapter?->name ?? '—' }}</td>
                                <td>{{ $log->topic?->name ?? '—' }}</td>
                                <td>
                                    @if ($log->isCompleted())
                                        <span class="admin-badge-green text-xs">✅ Completed</span>
                                    @elseif ($log->isPartial())
                                        <span class="admin-badge-gold text-xs">🟡 Partial</span>
                                    @else
                                        <span class="admin-badge-slate text-xs">🔴 Not Completed</span>
                                    @endif
                                </td>
                                <td class="text-xs text-slate-600">
                                    HW: {{ $log->homework_given ? 'Yes' : 'No' }} ·
                                    Mat: {{ $log->material_shared ? 'Yes' : 'No' }} ·
                                    Test: {{ $log->self_test_given ? 'Yes' : 'No' }}
                                </td>
                                <td class="max-w-[12rem] truncate" title="{{ $log->notes }}">{{ $log->notes ?: '—' }}</td>
                            </tr>
                        @empty
                            @include('admin.partials.empty-row', ['colspan' => 8, 'message' => 'No syllabus updates yet.', 'hint' => 'Submit your first daily update.'])
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
