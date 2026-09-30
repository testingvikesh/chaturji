<x-principal-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Reports</span>
            <h2 class="admin-page-title">{{ $roleLabel }} Login Report</h2>
            <p class="admin-page-subtitle">Your allotted standards only · default date range is today</p>
        </div>
    </x-slot>

    <div class="admin-page space-y-5">
        @include('principal.partials.reports-nav')

        <div class="flex flex-wrap gap-2 report-print-hide">
            <a href="{{ route('principal.reports.student-logins', request()->only(['from', 'to', 'status', 'search'])) }}"
               class="rounded-full px-4 py-1.5 text-sm font-semibold border {{ $role === 'student' ? 'bg-brand-green text-white border-brand-green' : 'bg-white text-slate-600 border-slate-200' }}">
                Student Login
            </a>
            <a href="{{ route('principal.reports.teacher-logins', request()->only(['from', 'to', 'status', 'search'])) }}"
               class="rounded-full px-4 py-1.5 text-sm font-semibold border {{ $role === 'teacher' ? 'bg-brand-green text-white border-brand-green' : 'bg-white text-slate-600 border-slate-200' }}">
                Teacher Login
            </a>
        </div>

        <div class="grid sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6 gap-4">
            @include('admin.partials.stat-card', ['label' => 'In range (all)', 'value' => $summary['total']])
            @include('admin.partials.stat-card', ['label' => 'Success', 'value' => $summary['success']])
            @include('admin.partials.stat-card', ['label' => 'Failed', 'value' => $summary['failed']])
            @include('admin.partials.stat-card', ['label' => 'Unique users', 'value' => $summary['unique_users']])
            @include('admin.partials.stat-card', ['label' => 'Today success', 'value' => $summary['today_success']])
            @include('admin.partials.stat-card', ['label' => 'Today failed', 'value' => $summary['today_failed']])
        </div>

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-body">
                <form method="GET" class="admin-filter-grid">
                    <div class="lg:col-span-3">
                        <label class="admin-label">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, mobile, email, IP..." class="admin-input">
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
                    <div class="lg:col-span-3 flex gap-2 items-end">
                        <button type="submit" class="admin-btn-filter flex-1">Filter</button>
                        <a href="{{ route($role === 'teacher' ? 'principal.reports.teacher-logins' : 'principal.reports.student-logins') }}" class="admin-btn-ghost">Today</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">{{ $roleLabel }} login records</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d M Y') }}
                        → {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d M Y') }}
                        · {{ $logs->total() }} row(s)
                    </p>
                </div>
            </div>
            <div class="admin-table-wrap admin-table-wrap--sticky">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Date / Time</th>
                            <th>{{ $roleLabel }}</th>
                            @if ($role === 'student')
                                <th>Medium · Standard</th>
                            @else
                                <th>Employee</th>
                            @endif
                            <th>Login used</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>IP</th>
                            <th>Device</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            @php
                                $user = $log->user;
                                $ua = (string) ($log->user_agent ?? '');
                                $device = '—';
                                if ($ua !== '') {
                                    if (stripos($ua, 'Mobile') !== false || stripos($ua, 'Android') !== false || stripos($ua, 'iPhone') !== false) {
                                        $device = 'Mobile';
                                    } elseif (stripos($ua, 'Windows') !== false || stripos($ua, 'Macintosh') !== false || stripos($ua, 'Linux') !== false) {
                                        $device = 'Desktop';
                                    } else {
                                        $device = \Illuminate\Support\Str::limit($ua, 28);
                                    }
                                }
                            @endphp
                            <tr>
                                <td class="whitespace-nowrap text-sm">
                                    <p class="font-semibold text-slate-800">{{ $log->logged_at?->format('d M Y') }}</p>
                                    <p class="text-xs text-slate-400">{{ $log->logged_at?->format('h:i:s A') }}</p>
                                </td>
                                <td>
                                    <p class="font-semibold text-slate-900">{{ $user?->name ?? '—' }}</p>
                                    <p class="text-xs text-slate-500">{{ $user?->mobile ?: '—' }}@if($user?->email) · {{ $user->email }}@endif</p>
                                </td>
                                @if ($role === 'student')
                                    <td class="text-sm">
                                        <span class="capitalize">{{ $user?->medium ?: '—' }}</span>
                                        · {{ $user?->standardLabel() ?: '—' }}
                                    </td>
                                @else
                                    <td class="text-sm font-medium">
                                        {{ $user ? 'EMP-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT) : '—' }}
                                    </td>
                                @endif
                                <td class="text-sm">{{ $log->login_identifier }}</td>
                                <td class="capitalize text-sm">{{ $log->login_method }}</td>
                                <td>
                                    @if ($log->status === 'failed')
                                        <span class="rounded-full bg-red-50 text-red-700 border border-red-200 px-2.5 py-1 text-xs font-semibold">Failed</span>
                                    @else
                                        <span class="admin-badge-green">Success</span>
                                    @endif
                                </td>
                                <td class="text-sm text-slate-500">{{ $log->ip_address ?: '—' }}</td>
                                <td class="text-xs text-slate-500" title="{{ $ua }}">{{ $device }}</td>
                            </tr>
                        @empty
                            @include('admin.partials.empty-row', [
                                'colspan' => 8,
                                'message' => 'No '.$roleLabel.' login records for this date range',
                                'hint' => 'Only users on your allotted standards are shown.',
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="admin-pagination">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>
</x-principal-layout>
