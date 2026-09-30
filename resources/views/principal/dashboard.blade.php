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

        @if (! ($hasAllotments ?? false))
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                No standard allotted yet. Ask admin to allot standards — reports show only your standards.
            </div>
        @endif

        <div class="grid sm:grid-cols-2 xl:grid-cols-6 gap-4">
            @include('admin.partials.stat-card', ['label' => 'Students', 'value' => $totalStudents, 'hint' => 'Your allotted standards'])
            @include('admin.partials.stat-card', ['label' => 'Teachers', 'value' => $totalTeachers, 'hint' => 'On your allotted standards'])
            @include('admin.partials.stat-card', ['label' => 'Pending Students', 'value' => $pendingStudents, 'hint' => 'Your standards'])
            @include('admin.partials.stat-card', ['label' => 'Pending Teachers', 'value' => $pendingTeachers, 'hint' => 'Your standards'])
            @include('admin.partials.stat-card', ['label' => 'Today Logins', 'value' => $todayLogins, 'hint' => 'Your standards only'])
            @include('admin.partials.stat-card', ['label' => 'Active Sessions', 'value' => $activeSessions, 'hint' => 'Your standards only'])
        </div>

        <div class="admin-card mt-6">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <h3 class="font-bold text-slate-900">Quick Actions</h3>
            </div>
            <div class="p-5 grid sm:grid-cols-2 gap-3">
                <a href="{{ route('principal.books.index') }}" class="rounded-xl border border-slate-200 p-4 hover:border-brand-green-200 hover:bg-brand-green-50/50 transition group">
                    <p class="font-semibold text-slate-900 group-hover:text-brand-green">Books</p>
                    <p class="text-xs text-slate-500 mt-1">Open books for your allotted standards</p>
                </a>
                <a href="{{ route('principal.mentors.index') }}" class="rounded-xl border border-slate-200 p-4 hover:border-brand-green-200 hover:bg-brand-green-50/50 transition group">
                    <p class="font-semibold text-slate-900 group-hover:text-brand-green">Mentor</p>
                    <p class="text-xs text-slate-500 mt-1">Assign students to a teacher mentor</p>
                </a>
                <a href="{{ route('principal.reports.index') }}" class="rounded-xl border border-slate-200 p-4 hover:border-brand-green-200 hover:bg-brand-green-50/50 transition group">
                    <p class="font-semibold text-slate-900 group-hover:text-brand-green">Reports</p>
                    <p class="text-xs text-slate-500 mt-1">All related reports with print</p>
                </a>
                <a href="{{ route('principal.reports.allotted-teachers') }}" class="rounded-xl border border-slate-200 p-4 hover:border-brand-green-200 hover:bg-brand-green-50/50 transition group">
                    <p class="font-semibold text-slate-900 group-hover:text-brand-green">Allotted Teachers</p>
                    <p class="text-xs text-slate-500 mt-1">Teachers on your allotted standards</p>
                </a>
                <a href="{{ route('principal.reports.allotted-students') }}" class="rounded-xl border border-slate-200 p-4 hover:border-brand-green-200 hover:bg-brand-green-50/50 transition group">
                    <p class="font-semibold text-slate-900 group-hover:text-brand-green">Allotted Students</p>
                    <p class="text-xs text-slate-500 mt-1">Add, edit, and mail students in your standards</p>
                </a>
            </div>
        </div>

        <div class="admin-card mt-6">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <h3 class="font-bold text-slate-900">Recent Logins (your standards)</h3>
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
