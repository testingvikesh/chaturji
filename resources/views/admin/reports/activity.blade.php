<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Activity Log" subtitle="All user and admin activity in one report" />
    </x-slot>

    <div class="admin-page">
        <div class="grid sm:grid-cols-2 xl:grid-cols-5 gap-4">
            @include('admin.partials.stat-card', ['label' => 'Today', 'value' => $summary['today']])
            @include('admin.partials.stat-card', ['label' => 'Total Events', 'value' => $summary['total']])
            @include('admin.partials.stat-card', ['label' => 'Admin', 'value' => $summary['admin']])
            @include('admin.partials.stat-card', ['label' => 'Teacher', 'value' => $summary['teacher']])
            @include('admin.partials.stat-card', ['label' => 'Student', 'value' => $summary['student']])
        </div>

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-body">
                <form method="GET" class="admin-filter-grid">
                    <div class="lg:col-span-3">
                        <label class="admin-label">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="User, description, IP..." class="admin-input">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Role</label>
                        <select name="role" class="admin-select">
                            <option value="">All roles</option>
                            <option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Admin</option>
                            <option value="teacher" @selected(($filters['role'] ?? '') === 'teacher')>Teacher</option>
                            <option value="student" @selected(($filters['role'] ?? '') === 'student')>Student</option>
                        </select>
                    </div>
                    <div class="lg:col-span-3">
                        <label class="admin-label">Action</label>
                        <select name="action" class="admin-select">
                            <option value="">All actions</option>
                            @foreach ($actionOptions as $action)
                                <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>
                                    {{ $actionLabels[$action] ?? $action }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">From</label>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="admin-input">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">To</label>
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="admin-input">
                    </div>
                    <div class="lg:col-span-2 flex gap-2 items-end">
                        <button type="submit" class="admin-btn-filter flex-1">Filter</button>
                        @if (! empty(array_filter($filters ?? [])))
                            <a href="{{ route('admin.reports.activity') }}" class="admin-btn-ghost">Clear</a>
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
                            <th>When</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP</th>
                            <th class="text-right">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="text-xs text-slate-500 whitespace-nowrap">{{ $log->created_at?->format('d M Y, h:i A') }}</td>
                                <td>
                                    <p class="font-semibold text-slate-900">{{ $log->user?->name ?? '—' }}</p>
                                    <p class="text-xs text-slate-400">{{ $log->user?->mobile ?: ($log->user?->email ?? '') }}</p>
                                </td>
                                <td><span class="admin-badge-slate capitalize">{{ $log->role ?: '—' }}</span></td>
                                <td><span class="admin-badge-green">{{ $actionLabels[$log->action] ?? $log->actionLabel() }}</span></td>
                                <td class="text-sm text-slate-700 max-w-sm">
                                    <p class="truncate" title="{{ $log->description }}">{{ \Illuminate\Support\Str::limit($log->description, 70) }}</p>
                                </td>
                                <td class="text-xs text-slate-500">{{ $log->ip_address ?: '—' }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.reports.activity.show', $log) }}" class="admin-btn-secondary text-xs py-2 px-3 inline-flex">View</a>
                                </td>
                            </tr>
                        @empty
                            @include('admin.partials.empty-row', [
                                'colspan' => 7,
                                'message' => 'No activity yet',
                                'hint' => 'Login, settings, tickets, exams and approvals will appear here.',
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="p-4">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
