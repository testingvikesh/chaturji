<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Teacher Timetable" subtitle="Set each teacher’s weekly periods · login & logout reporting follow this schedule">
            <x-slot name="actions">
                <a href="{{ route('admin.timetable.periods') }}" class="admin-btn-secondary">School Periods</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page">
        @include('admin.partials.alert')

        @if ($periodCount === 0)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 mb-4">
                No active school periods. <a href="{{ route('admin.timetable.periods') }}" class="font-semibold underline">Add periods</a> first (Period 1–8).
            </div>
        @endif

        <div class="admin-card mb-4">
            <div class="admin-card-top"></div>
            <div class="admin-card-body">
                <form method="GET" class="admin-filter-grid">
                    <div class="lg:col-span-10">
                        <label class="admin-label">Search teacher</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, mobile or email..." class="admin-input">
                    </div>
                    <div class="lg:col-span-2 flex gap-2 items-end">
                        <button type="submit" class="admin-btn-filter flex-1">Filter</button>
                        @if (!empty($filters['search']))
                            <a href="{{ route('admin.timetable.index') }}" class="admin-btn-ghost">Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">Approved teachers</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Open a teacher to set Mon–Sat periods. Saving a slot also syncs their subject assignment.</p>
                </div>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Mobile</th>
                            <th>Active slots</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($teachers as $teacher)
                            <tr>
                                <td>
                                    <div class="admin-user-cell">
                                        <span class="admin-avatar">{{ strtoupper(substr($teacher->name, 0, 2)) }}</span>
                                        <p class="font-semibold text-slate-900">{{ $teacher->name }}</p>
                                    </div>
                                </td>
                                <td>{{ $teacher->mobile }}</td>
                                <td>
                                    <span class="{{ $teacher->timetable_slots > 0 ? 'admin-badge-green' : 'admin-badge-slate' }}">
                                        {{ $teacher->timetable_slots }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.timetable.edit', $teacher) }}" class="admin-btn-primary inline-flex text-sm">Set timetable</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-slate-500 py-8">No approved teachers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($teachers->hasPages())
                <div class="admin-card-body border-t border-slate-100">{{ $teachers->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
