<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Principals" subtitle="Add principals and manage principal login accounts" />
    </x-slot>

    <div class="admin-page">
        @include('admin.partials.alert')

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-header"><h3 class="font-bold text-slate-900">Add principal</h3></div>
            <div class="admin-card-body">
                <form method="POST" action="{{ route('admin.principals.store') }}" class="grid sm:grid-cols-2 gap-4">
                    @csrf
                    <div>
                        <label class="admin-label">Name *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="admin-input">
                        @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Mobile *</label>
                        <input type="text" name="mobile" value="{{ old('mobile') }}" required class="admin-input">
                        @error('mobile')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Email *</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="admin-input">
                        <p class="text-xs text-slate-500 mt-1">Used for principal login at /principal/login</p>
                        @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Password *</label>
                        <input type="text" name="password" value="{{ old('password', $defaultPassword) }}" required minlength="8" class="admin-input">
                        @error('password')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    @include('admin.principals.partials.standard-allotment', [
                        'standards' => $standards,
                        'selected' => old('standard_ids', []),
                    ])
                    <div class="sm:col-span-2">
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="hidden" name="approve" value="0">
                            <input type="checkbox" name="approve" value="1" class="rounded border-slate-300 text-brand-green" @checked(old('approve', true))>
                            Approve now (can login immediately)
                        </label>
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="admin-btn-primary">Create principal</button>
                    </div>
                </form>
            </div>
        </div>

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
                            <a href="{{ route('admin.principals.index') }}" class="admin-btn-ghost">Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">All Principals</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $principals->total() }} total · Login URL: /principal/login</p>
                </div>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Principal</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>Allotted standards</th>
                            <th>Registered</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($principals as $principal)
                            <tr>
                                <td>
                                    <div class="admin-user-cell">
                                        <span class="admin-avatar">{{ strtoupper(substr($principal->name, 0, 2)) }}</span>
                                        <p class="font-semibold text-slate-900">{{ $principal->name }}</p>
                                    </div>
                                </td>
                                <td>{{ $principal->mobile }}</td>
                                <td>{{ $principal->email }}</td>
                                <td>
                                    @if ($principal->allottedStandards->isEmpty())
                                        <span class="text-xs text-slate-400">None</span>
                                    @else
                                        <span class="text-xs text-slate-700">{{ $principal->allottedStandards->pluck('name')->join(', ') }}</span>
                                    @endif
                                </td>
                                <td>{{ $principal->created_at->format('d M Y') }}</td>
                                <td>
                                    @include('admin.partials.status-badge', [
                                        'user' => $principal,
                                        'approveRoute' => route('admin.principals.approve', $principal),
                                        'pendingRoute' => route('admin.principals.pending', $principal),
                                    ])
                                </td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.principals.show', $principal) }}" class="admin-btn-ghost text-xs">View</a>
                                        <a href="{{ route('admin.principals.edit', $principal) }}" class="admin-btn-secondary text-xs">Edit</a>
                                        <form method="POST" action="{{ route('admin.principals.destroy', $principal) }}" onsubmit="return confirm('Delete this principal?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-btn-ghost text-xs text-red-600">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            @include('admin.partials.empty-row', ['colspan' => 7, 'message' => 'No principals yet. Add one above.'])
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($principals->hasPages())
                <div class="px-4 py-3 border-t border-slate-100">{{ $principals->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
