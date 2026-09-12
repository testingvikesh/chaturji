<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Students" subtitle="Manage registered student accounts">
            <x-slot name="actions">
                <a href="{{ route('admin.students.report') }}" class="admin-btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Student Report
                </a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page">
        @include('admin.partials.alert')

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-body">
                <form method="GET" class="admin-filter-grid">
                    <div class="lg:col-span-4">
                        <label class="admin-label">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, mobile or email..." class="admin-input">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Standard</label>
                        <select name="standard" class="admin-select">
                            <option value="">All Standards</option>
                            @foreach ($standards as $slug => $name)
                                <option value="{{ $slug }}" @selected(($filters['standard'] ?? '') === $slug)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Medium</label>
                        <select name="medium" class="admin-select">
                            <option value="">All Mediums</option>
                            <option value="english" @selected(($filters['medium'] ?? '') === 'english')>English</option>
                            <option value="gujarati" @selected(($filters['medium'] ?? '') === 'gujarati')>Gujarati</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Status</label>
                        <select name="status" class="admin-select">
                            <option value="">All Status</option>
                            <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending ({{ $pendingCount }})</option>
                            <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2 flex gap-2">
                        <button type="submit" class="admin-btn-filter flex-1">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            Filter
                        </button>
                        @if (!empty(array_filter($filters ?? [])))
                            <a href="{{ route('admin.students.index') }}" class="admin-btn-ghost" title="Clear filters">Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">All Students</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $students->total() }} total registered</p>
                </div>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>Standard</th>
                            <th>Medium</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $student)
                            <tr>
                                <td>
                                    <div class="admin-user-cell">
                                        <span class="admin-avatar">{{ strtoupper(substr($student->name, 0, 2)) }}</span>
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $student->name }}</p>
                                            <p class="text-xs text-slate-400">Joined {{ $student->created_at->format('d M Y') }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $student->mobile }}</td>
                                <td>{{ $student->email ?: '—' }}</td>
                                <td><span class="admin-badge-green">{{ $standards[$student->standard] ?? $student->standardLabel() }}</span></td>
                                <td><span class="admin-badge-gold capitalize">{{ $student->medium }}</span></td>
                                <td>
                                    @include('admin.partials.status-badge', [
                                        'user' => $student,
                                        'approveRoute' => route('admin.students.approve', $student),
                                        'pendingRoute' => route('admin.students.pending', $student),
                                    ])
                                </td>
                                <td class="text-right">
                                    <div class="admin-action-group justify-end">
                                        @include('admin.partials.approval-actions', [
                                            'user' => $student,
                                            'approveRoute' => route('admin.students.approve', $student),
                                            'pendingRoute' => route('admin.students.pending', $student),
                                        ])
                                        <a href="{{ route('admin.students.show', $student) }}" class="admin-action-icon-btn admin-action-icon-btn--view" title="View student" aria-label="View student">
                                            <x-admin.icon name="view" />
                                        </a>
                                        <x-admin.action-edit :href="route('admin.students.edit', $student)" label="Edit student" />
                                        <x-admin.action-delete :action="route('admin.students.destroy', $student)" label="Delete student" confirm="Delete this student?" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            @include('admin.partials.empty-row', ['colspan' => 7, 'message' => 'No students found', 'hint' => 'Students will appear here after they register from the website.'])
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($students->hasPages())
                <div class="admin-pagination">{{ $students->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
