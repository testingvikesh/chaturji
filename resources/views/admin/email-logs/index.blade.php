<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Email Log" subtitle="Every email sent from this project — tickets, password reset, test mail" />
    </x-slot>

    <div class="admin-page">
        @include('admin.partials.alert')

        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @include('admin.partials.stat-card', ['label' => 'Total', 'value' => $summary['all']])
            @include('admin.partials.stat-card', ['label' => 'Sent', 'value' => $summary['sent']])
            @include('admin.partials.stat-card', ['label' => 'Failed', 'value' => $summary['failed']])
            @include('admin.partials.stat-card', ['label' => 'Today', 'value' => $summary['today']])
        </div>

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-body">
                <form method="GET" class="admin-filter-grid">
                    <div class="lg:col-span-4">
                        <label class="admin-label">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="To, from, subject..." class="admin-input">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Status</label>
                        <select name="status" class="admin-select">
                            <option value="">All Status</option>
                            <option value="sent" @selected(($filters['status'] ?? '') === 'sent')>Sent</option>
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
                    <div class="lg:col-span-2 flex gap-2 items-end">
                        <button type="submit" class="admin-btn-filter flex-1">Filter</button>
                        @if (! empty(array_filter($filters ?? [])))
                            <a href="{{ route('admin.email-logs.index') }}" class="admin-btn-ghost">Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>To</th>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="text-xs text-slate-500 whitespace-nowrap">{{ ($log->sent_at ?? $log->created_at)?->format('d M Y, h:i A') }}</td>
                                <td>
                                    <p class="font-medium text-slate-900 break-all">{{ $log->to_email ?: '—' }}</p>
                                    @if ($log->from_email)
                                        <p class="text-xs text-slate-400">From {{ $log->from_email }}</p>
                                    @endif
                                </td>
                                <td><span class="admin-badge-slate text-xs">{{ $log->typeLabel() }}</span></td>
                                <td class="text-slate-700">{{ $log->subject ?: '—' }}</td>
                                <td>
                                    @if ($log->status === 'sent')
                                        <span class="admin-badge-green text-xs">Sent</span>
                                    @elseif ($log->status === 'failed')
                                        <span class="admin-badge-gold text-xs">Failed</span>
                                    @else
                                        <span class="admin-badge-slate text-xs">{{ $log->statusLabel() }}</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.email-logs.show', $log) }}" class="admin-btn-secondary text-xs py-2 px-3">View</a>
                                </td>
                            </tr>
                        @empty
                            @include('admin.partials.empty-row', ['colspan' => 6, 'message' => 'No emails logged yet', 'hint' => 'Send a test mail from Settings or reply to a ticket.'])
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="p-4">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
