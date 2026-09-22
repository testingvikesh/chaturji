<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Logout Work Reports" subtitle="Teacher logout reports · date wise · employee wise" />
    </x-slot>

    <div class="admin-page">
        <div class="grid sm:grid-cols-2 xl:grid-cols-5 gap-4">
            @include('admin.partials.stat-card', ['label' => 'Today', 'value' => $summary['today']])
            @include('admin.partials.stat-card', ['label' => 'Total Reports', 'value' => $summary['total']])
            @include('admin.partials.stat-card', ['label' => 'Complete', 'value' => $summary['complete']])
            @include('admin.partials.stat-card', ['label' => 'Remain', 'value' => $summary['remain']])
            @include('admin.partials.stat-card', ['label' => 'Employees', 'value' => $summary['teachers']])
        </div>

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-body">
                <form method="GET" class="admin-filter-grid">
                    <div class="lg:col-span-3">
                        <label class="admin-label">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="admin-input" placeholder="Teacher / subject / chapter / EMP code">
                    </div>
                    <div class="lg:col-span-3">
                        <label class="admin-label">Employee</label>
                        <select name="teacher_id" class="admin-select">
                            <option value="">All employees</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected((string) ($filters['teacher_id'] ?? '') === (string) $teacher->id)>{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Status</label>
                        <select name="status" class="admin-select">
                            <option value="">All</option>
                            <option value="complete" @selected(($filters['status'] ?? '') === 'complete')>Complete</option>
                            <option value="remain" @selected(($filters['status'] ?? '') === 'remain')>Remain</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">From date</label>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="admin-input">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">To date</label>
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="admin-input">
                    </div>
                    <div class="lg:col-span-2 flex gap-2 items-end">
                        <button type="submit" class="admin-btn-filter flex-1">Filter</button>
                        @if (! empty(array_filter($filters ?? [])))
                            <a href="{{ route('admin.reports.logout-reports') }}" class="admin-btn-ghost">Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Medium</th>
                            <th>Standard</th>
                            <th>Subject</th>
                            <th>Chapter</th>
                            <th>Topic</th>
                            <th>Status</th>
                            <th>Mail</th>
                            <th class="text-right">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reports as $report)
                            <tr>
                                <td class="text-xs whitespace-nowrap">
                                    <p class="font-semibold text-slate-800">{{ $report->report_date?->format('d M Y') }}</p>
                                    <p class="text-slate-400">{{ $report->submitted_at?->format('h:i A') }}</p>
                                </td>
                                <td>
                                    <p class="font-semibold text-slate-900">{{ $report->teacher?->name ?? '—' }}</p>
                                    <p class="text-xs text-slate-400">{{ $report->employee_code }} · {{ $report->teacher?->mobile }}</p>
                                </td>
                                <td class="capitalize text-sm">{{ $report->medium ?: '—' }}</td>
                                <td class="text-sm">{{ $report->standard ?: '—' }}</td>
                                <td class="text-sm font-medium">{{ $report->subject_name ?: '—' }}</td>
                                <td class="text-sm">{{ $report->chapter_name ?: '—' }}</td>
                                <td class="text-sm text-slate-600">{{ $report->topic_name ?: '—' }}</td>
                                <td>
                                    @if ($report->chk_complete)
                                        <span class="admin-badge-green">Complete</span>
                                    @elseif ($report->chk_remain)
                                        <span class="admin-badge-gold">Remain</span>
                                    @else
                                        <span class="admin-badge-slate">{{ $report->statusLabel() }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($report->mail_sent)
                                        <span class="admin-badge-green text-xs">Sent</span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.reports.logout-reports.show', $report) }}" class="admin-btn-secondary text-xs py-2 px-3 inline-flex">View</a>
                                </td>
                            </tr>
                        @empty
                            @include('admin.partials.empty-row', [
                                'colspan' => 10,
                                'message' => 'No logout reports yet',
                                'hint' => 'Teachers must submit Medium/Standard/Subject/Chapter/Topic report before logout.',
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($reports->hasPages())
                <div class="p-4">{{ $reports->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
