<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Student Report" subtitle="Registration and login statistics">
            <x-slot name="actions">
                <a href="{{ route('admin.students.index') }}" class="admin-btn-secondary">&larr; Back to Students</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page">
        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @include('admin.partials.stat-card', ['label' => 'Total Students', 'value' => $totalStudents])
            @include('admin.partials.stat-card', ['label' => 'Registered Today', 'value' => $todayRegistered])
            @include('admin.partials.stat-card', ['label' => 'Logins Today', 'value' => $todayLogins])
            @include('admin.partials.stat-card', ['label' => 'Active Sessions', 'value' => $activeSessions])
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            <div class="admin-card">
                <div class="admin-card-top"></div>
                <div class="admin-card-header"><h3 class="font-bold text-slate-900">By Standard</h3></div>
                <div class="p-5 space-y-2">
                    @forelse ($byStandard as $row)
                        <div class="flex items-center justify-between text-sm border-b border-slate-100 pb-2">
                            <span>{{ str_replace('_', ' ', ucwords(str_replace('_', ' ', $row->standard ?? 'Unknown'), '_')) }}</span>
                            <span class="admin-badge-green">{{ $row->total }}</span>
                        </div>
                    @empty
                        <p class="text-slate-400 text-sm text-center py-6">No data</p>
                    @endforelse
                </div>
            </div>
            <div class="admin-card">
                <div class="admin-card-top"></div>
                <div class="admin-card-header"><h3 class="font-bold text-slate-900">By Medium</h3></div>
                <div class="p-5 space-y-2">
                    @forelse ($byMedium as $row)
                        <div class="flex items-center justify-between text-sm border-b border-slate-100 pb-2">
                            <span class="capitalize">{{ $row->medium ?? 'Unknown' }}</span>
                            <span class="admin-badge-gold">{{ $row->total }}</span>
                        </div>
                    @empty
                        <p class="text-slate-400 text-sm text-center py-6">No data</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header"><h3 class="font-bold text-slate-900">Recent Registrations</h3></div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Name</th><th>Mobile</th><th>Standard</th><th>Registered</th></tr></thead>
                    <tbody>
                        @foreach ($recentStudents as $student)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $student->name }}</td>
                                <td>{{ $student->mobile }}</td>
                                <td><span class="admin-badge-green">{{ $student->standardLabel() }}</span></td>
                                <td>{{ $student->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
