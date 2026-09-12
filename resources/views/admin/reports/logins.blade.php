<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Login Report" subtitle="Student and teacher login activity" />
    </x-slot>

    <div class="admin-page">
        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @include('admin.partials.stat-card', ['label' => 'Today', 'value' => $summary['today']])
            @include('admin.partials.stat-card', ['label' => 'Student Logins', 'value' => $summary['student']])
            @include('admin.partials.stat-card', ['label' => 'Teacher Logins', 'value' => $summary['teacher']])
            @include('admin.partials.stat-card', ['label' => 'Failed Attempts', 'value' => $summary['failed']])
        </div>

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-body">
                <form method="GET" class="admin-filter-grid">
                    <div class="lg:col-span-3">
                        <label class="admin-label">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name or login..." class="admin-input">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Role</label>
                        <select name="role" class="admin-select">
                            <option value="">All Roles</option>
                            <option value="student" @selected(($filters['role'] ?? '') === 'student')>Student</option>
                            <option value="teacher" @selected(($filters['role'] ?? '') === 'teacher')>Teacher</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Status</label>
                        <select name="status" class="admin-select">
                            <option value="">All Status</option>
                            <option value="success" @selected(($filters['status'] ?? '') === 'success')>Success</option>
                            <option value="failed" @selected(($filters['status'] ?? '') === 'failed')>Failed</option>
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
                    <div class="lg:col-span-1 flex items-end">
                        <button type="submit" class="admin-btn-filter w-full">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Login</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>IP</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $log->user?->name ?? '—' }}</td>
                                <td><span class="admin-badge-green capitalize">{{ $log->role }}</span></td>
                                <td>{{ $log->login_identifier }}</td>
                                <td class="capitalize">{{ $log->login_method }}</td>
                                <td>
                                    @if ($log->status === 'failed')
                                        <span class="admin-badge bg-red-50 text-red-700 border border-red-200 rounded-full px-2.5 py-1 text-xs font-semibold">Failed</span>
                                    @else
                                        <span class="admin-badge-green">Success</span>
                                    @endif
                                </td>
                                <td class="text-slate-500">{{ $log->ip_address }}</td>
                                <td class="text-slate-500">{{ $log->logged_at->format('d M Y, h:i A') }}</td>
                            </tr>
                        @empty
                            @include('admin.partials.empty-row', ['colspan' => 7, 'message' => 'No login records found'])
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())<div class="admin-pagination">{{ $logs->links() }}</div>@endif
        </div>
    </div>
</x-app-layout>
