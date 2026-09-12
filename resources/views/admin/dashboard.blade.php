<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Dashboard 1" :subtitle="'Welcome back, ' . Auth::user()->name" />
    </x-slot>

    <div class="admin-page">
        @include('admin.partials.dashboard-tabs')
        @include('admin.partials.alert')

        <div class="grid sm:grid-cols-2 xl:grid-cols-6 gap-4">
            @include('admin.partials.stat-card', ['label' => 'Students', 'value' => $totalStudents])
            @include('admin.partials.stat-card', ['label' => 'Teachers', 'value' => $totalTeachers])
            @include('admin.partials.stat-card', ['label' => 'Pending Students', 'value' => $pendingStudents, 'hint' => 'Awaiting approval'])
            @include('admin.partials.stat-card', ['label' => 'Pending Teachers', 'value' => $pendingTeachers, 'hint' => 'Awaiting approval'])
            @include('admin.partials.stat-card', ['label' => 'Today Logins', 'value' => $todayLogins])
            @include('admin.partials.stat-card', ['label' => 'Active Sessions', 'value' => $activeSessions, 'hint' => '1-day session window'])
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            <div class="admin-card">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <h3 class="font-bold text-slate-900">Recent Logins</h3>
                    <a href="{{ route('admin.reports.logins') }}" class="admin-btn-secondary text-xs py-2 px-3">View all</a>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentLogins as $log)
                                <tr>
                                    <td class="font-medium text-slate-900">{{ $log->user?->name ?? 'Unknown' }}</td>
                                    <td><span class="admin-badge-green capitalize">{{ $log->role }}</span></td>
                                    <td class="text-slate-500">{{ $log->logged_at->format('d M, h:i A') }}</td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-row', ['colspan' => 3, 'message' => 'No login activity yet'])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <h3 class="font-bold text-slate-900">Quick Actions</h3>
                </div>
                <div class="p-5 grid sm:grid-cols-2 gap-3">
                    <a href="{{ route('admin.students.index') }}" class="rounded-xl border border-slate-200 p-4 hover:border-brand-green-200 hover:bg-brand-green-50/50 transition group">
                        <p class="font-semibold text-slate-900 group-hover:text-brand-green">Manage Students</p>
                        <p class="text-xs text-slate-500 mt-1">View and edit accounts</p>
                    </a>
                    <a href="{{ route('admin.teachers.index') }}" class="rounded-xl border border-slate-200 p-4 hover:border-brand-green-200 hover:bg-brand-green-50/50 transition group">
                        <p class="font-semibold text-slate-900 group-hover:text-brand-green">Manage Teachers</p>
                        <p class="text-xs text-slate-500 mt-1">View and edit accounts</p>
                    </a>
                    <a href="{{ route('admin.standards.index') }}" class="rounded-xl border border-slate-200 p-4 hover:border-brand-green-200 hover:bg-brand-green-50/50 transition group">
                        <p class="font-semibold text-slate-900 group-hover:text-brand-green">Curriculum</p>
                        <p class="text-xs text-slate-500 mt-1">Standard → Topic hierarchy</p>
                    </a>
                    <a href="{{ route('admin.reports.sessions') }}" class="rounded-xl border border-slate-200 p-4 hover:border-brand-green-200 hover:bg-brand-green-50/50 transition group">
                        <p class="font-semibold text-slate-900 group-hover:text-brand-green">Session Report</p>
                        <p class="text-xs text-slate-500 mt-1">Track active sessions</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
