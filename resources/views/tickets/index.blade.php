<x-dynamic-component :component="$layout">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="admin-section-label">{{ $label }}</span>
                <h2 class="admin-page-title">My Tickets</h2>
                <p class="admin-page-subtitle">Generate a ticket for help. Admin will reply here.</p>
            </div>
            <a href="{{ route($routePrefix.'.create') }}" class="admin-btn-primary">Generate Ticket</a>
        </div>
    </x-slot>

    <div class="admin-page space-y-4">
        @include('admin.partials.alert')

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">Ticket list</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $tickets->total() }} ticket(s)</p>
                </div>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Category</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Replies</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            <tr>
                                <td class="font-semibold text-slate-900">
                                    <a href="{{ route($routePrefix.'.show', $ticket) }}" class="hover:text-brand-green">{{ $ticket->ticket_no }}</a>
                                </td>
                                <td>{{ $ticket->categoryLabel() }}</td>
                                <td class="text-slate-700">{{ $ticket->subject }}</td>
                                <td>@include('tickets.partials.status-badge', ['status' => $ticket->status])</td>
                                <td>{{ $ticket->replies_count }}</td>
                                <td class="text-xs text-slate-500 whitespace-nowrap">{{ $ticket->created_at?->format('d M Y, h:i A') }}</td>
                            </tr>
                        @empty
                            @include('admin.partials.empty-row', ['colspan' => 6, 'message' => 'No tickets yet', 'hint' => 'Click Generate Ticket to create one.'])
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($tickets->hasPages())
                <div class="p-4">{{ $tickets->links() }}</div>
            @endif
        </div>
    </div>
</x-dynamic-component>
