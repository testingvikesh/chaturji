<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Ticket Report" subtitle="Student and teacher support tickets" />
    </x-slot>

    <div class="admin-page">
        @include('admin.partials.alert')

        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @include('admin.partials.stat-card', ['label' => 'Open', 'value' => $summary['open']])
            @include('admin.partials.stat-card', ['label' => 'Answered', 'value' => $summary['answered']])
            @include('admin.partials.stat-card', ['label' => 'Closed', 'value' => $summary['closed']])
            @include('admin.partials.stat-card', ['label' => 'Student Tickets', 'value' => $summary['student']])
            @include('admin.partials.stat-card', ['label' => 'Teacher Tickets', 'value' => $summary['teacher']])
            @include('admin.partials.stat-card', ['label' => 'Total', 'value' => $summary['all']])
        </div>

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-body">
                <form method="GET" class="admin-filter-grid">
                    <div class="lg:col-span-3">
                        <label class="admin-label">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Ticket no, subject or name" class="admin-input">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Role</label>
                        <select name="role" class="admin-select">
                            <option value="">All Roles</option>
                            <option value="student" @selected(($filters['role'] ?? '') === 'student')>Student</option>
                            <option value="teacher" @selected(($filters['role'] ?? '') === 'teacher')>Teacher</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Status</label>
                        <select name="status" class="admin-select">
                            <option value="">All Status</option>
                            <option value="open" @selected(($filters['status'] ?? '') === 'open')>Open</option>
                            <option value="answered" @selected(($filters['status'] ?? '') === 'answered')>Answered</option>
                            <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Closed</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Category</label>
                        <select name="category" class="admin-select">
                            <option value="">All Categories</option>
                            @foreach ($categories as $key => $name)
                                <option value="{{ $key }}" @selected(($filters['category'] ?? '') === $key)>{{ $name }}</option>
                            @endforeach
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
                    <div class="lg:col-span-1 flex items-end">
                        <button type="submit" class="admin-btn-filter w-full">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>User</th>
                            <th>Role</th>
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
                                    <a href="{{ route('admin.tickets.show', $ticket) }}" class="hover:text-brand-green">{{ $ticket->ticket_no }}</a>
                                </td>
                                <td>
                                    <p class="font-medium text-slate-900">{{ $ticket->user?->name ?? '—' }}</p>
                                    <p class="text-xs text-slate-400">{{ $ticket->user?->mobile }}</p>
                                </td>
                                <td><span class="admin-badge-green capitalize">{{ $ticket->role }}</span></td>
                                <td>{{ $ticket->categoryLabel() }}</td>
                                <td class="text-slate-700">{{ $ticket->subject }}</td>
                                <td>@include('tickets.partials.status-badge', ['status' => $ticket->status])</td>
                                <td>{{ $ticket->replies_count }}</td>
                                <td class="text-xs text-slate-500 whitespace-nowrap">{{ $ticket->created_at?->format('d M Y, h:i A') }}</td>
                            </tr>
                        @empty
                            @include('admin.partials.empty-row', ['colspan' => 8, 'message' => 'No tickets yet'])
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($tickets->hasPages())
                <div class="p-4">{{ $tickets->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
