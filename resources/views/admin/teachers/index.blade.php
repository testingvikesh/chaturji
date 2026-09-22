<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Teachers" subtitle="Manage registered teacher accounts">
            <x-slot name="actions">
                <form method="POST" action="{{ route('admin.teachers.generate-otp') }}" onsubmit="return confirm('Generate a new 4-digit OTP for all approved teachers and send it to Notifications (no email)?')">
                    @csrf
                    <button type="submit" class="admin-btn-secondary">Generate Today OTP</button>
                </form>
                <a href="{{ route('admin.teachers.report') }}" class="admin-btn-secondary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Teacher Report
                </a>
                <a href="{{ route('admin.timetable.index') }}" class="admin-btn-secondary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Timetable
                </a>
                <a href="{{ route('admin.reports.teacher-subjects') }}" class="admin-btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    Subject List
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
                    <div class="lg:col-span-7">
                        <label class="admin-label">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, mobile or email..." class="admin-input">
                    </div>
                    <div class="lg:col-span-3">
                        <label class="admin-label">Status</label>
                        <select name="status" class="admin-select">
                            <option value="">All Status</option>
                            <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending ({{ $pendingCount }})</option>
                            <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2 flex gap-2 items-end">
                        <button type="submit" class="admin-btn-filter flex-1">Filter</button>
                        @if (!empty(array_filter($filters ?? [])))
                            <a href="{{ route('admin.teachers.index') }}" class="admin-btn-ghost">Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">All Teachers</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $teachers->total() }} total registered · Today's OTP date {{ now()->format('d M Y') }}</p>
                    <p class="text-xs text-slate-400 mt-1">Cron auto-sends a new OTP to teacher <strong>Notifications</strong> daily at <strong>1:00 AM</strong> (Asia/Kolkata). Email is off.</p>
                    <p class="text-xs text-slate-400 mt-1 break-all">Manual / cron URL: <a href="{{ \App\Support\TeacherOtpService::cronUrl() }}?force=1" class="text-brand-green hover:underline" target="_blank" rel="noopener">{{ \App\Support\TeacherOtpService::cronUrl() }}?force=1</a></p>
                </div>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>Registered</th>
                            <th>Today OTP</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
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
                                <td>{{ $teacher->email }}</td>
                                <td>{{ $teacher->created_at->format('d M Y') }}</td>
                                <td>
                                    @if ($teacher->todayOtp?->otp)
                                        <span class="font-mono text-base font-bold tracking-widest text-brand-green">{{ $teacher->todayOtp->otp }}</span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                                <td>
                                    @include('admin.partials.status-badge', [
                                        'user' => $teacher,
                                        'approveRoute' => route('admin.teachers.approve', $teacher),
                                        'pendingRoute' => route('admin.teachers.pending', $teacher),
                                    ])
                                </td>
                                <td class="text-right">
                                    <div class="admin-action-group justify-end">
                                        @include('admin.partials.approval-actions', [
                                            'user' => $teacher,
                                            'approveRoute' => route('admin.teachers.approve', $teacher),
                                            'pendingRoute' => route('admin.teachers.pending', $teacher),
                                        ])
                                        <x-admin.action-view :href="route('admin.teachers.show', $teacher)" label="View teacher" />
                                        <x-admin.action-edit :href="route('admin.teachers.edit', $teacher)" label="Edit teacher" />
                                        <x-admin.action-delete :action="route('admin.teachers.destroy', $teacher)" label="Delete teacher" confirm="Delete this teacher?" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            @include('admin.partials.empty-row', ['colspan' => 7, 'message' => 'No teachers found'])
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($teachers->hasPages())
                <div class="admin-pagination">{{ $teachers->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
