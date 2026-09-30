<x-principal-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
            <p class="text-sm text-slate-500 mt-1">Welcome back, {{ Auth::user()->name }}</p>
        </div>
    </x-slot>

    <div class="admin-page">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="grid sm:grid-cols-2 xl:grid-cols-6 gap-4">
            @include('admin.partials.stat-card', ['label' => 'Students', 'value' => $totalStudents])
            @include('admin.partials.stat-card', ['label' => 'Teachers', 'value' => $totalTeachers])
            @include('admin.partials.stat-card', ['label' => 'Pending Students', 'value' => $pendingStudents, 'hint' => 'Awaiting approval'])
            @include('admin.partials.stat-card', ['label' => 'Pending Teachers', 'value' => $pendingTeachers, 'hint' => 'Awaiting approval'])
            @include('admin.partials.stat-card', ['label' => 'Today Logins', 'value' => $todayLogins])
            @include('admin.partials.stat-card', ['label' => 'Active Sessions', 'value' => $activeSessions, 'hint' => '1-day session window'])
        </div>

        <div class="admin-card mt-6">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <h3 class="font-bold text-slate-900">Recent School Logins</h3>
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
    </div>
</x-principal-layout>
