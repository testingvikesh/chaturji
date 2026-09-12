<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Session Report" subtitle="1-day session tracking for students and teachers" />
    </x-slot>

    <div class="admin-page">
        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @include('admin.partials.stat-card', ['label' => 'Active Now', 'value' => $summary['active']])
            @include('admin.partials.stat-card', ['label' => 'Started Today', 'value' => $summary['today']])
            @include('admin.partials.stat-card', ['label' => 'Student Active', 'value' => $summary['student']])
            @include('admin.partials.stat-card', ['label' => 'Teacher Active', 'value' => $summary['teacher']])
        </div>

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-body">
                <form method="GET" class="admin-filter-grid">
                    <div class="lg:col-span-3">
                        <label class="admin-label">Role</label>
                        <select name="role" class="admin-select">
                            <option value="">All Roles</option>
                            <option value="student" @selected(($filters['role'] ?? '') === 'student')>Student</option>
                            <option value="teacher" @selected(($filters['role'] ?? '') === 'teacher')>Teacher</option>
                        </select>
                    </div>
                    <div class="lg:col-span-3">
                        <label class="admin-label">From</label>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="admin-input">
                    </div>
                    <div class="lg:col-span-3">
                        <label class="admin-label">To</label>
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="admin-input">
                    </div>
                    <div class="lg:col-span-3 flex flex-col justify-end gap-2">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" name="active_only" value="1" @checked($filters['active_only'] ?? false) class="rounded border-slate-300 text-brand-green focus:ring-brand-green">
                            Active sessions only
                        </label>
                        <button type="submit" class="admin-btn-filter">Filter</button>
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
                            <th>Login At</th>
                            <th>Last Activity</th>
                            <th>Expires</th>
                            <th>Status</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sessions as $session)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $session->user?->name ?? '—' }}</td>
                                <td><span class="admin-badge-green capitalize">{{ $session->role }}</span></td>
                                <td class="text-slate-500">{{ $session->logged_in_at->format('d M Y, h:i A') }}</td>
                                <td class="text-slate-500">{{ $session->last_activity_at->format('d M Y, h:i A') }}</td>
                                <td class="text-slate-500">{{ $session->expires_at->format('d M Y, h:i A') }}</td>
                                <td>
                                    @if ($session->is_active && $session->expires_at->isFuture())
                                        <span class="admin-badge-green">Active</span>
                                    @else
                                        <span class="admin-badge-slate">Closed</span>
                                    @endif
                                </td>
                                <td class="text-slate-500">{{ $session->ip_address }}</td>
                            </tr>
                        @empty
                            @include('admin.partials.empty-row', ['colspan' => 7, 'message' => 'No sessions found'])
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($sessions->hasPages())<div class="admin-pagination">{{ $sessions->links() }}</div>@endif
        </div>
    </div>
</x-app-layout>
