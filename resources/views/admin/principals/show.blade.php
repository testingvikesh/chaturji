<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Principal" :subtitle="$principal->name">
            <x-slot name="actions">
                <a href="{{ route('admin.principals.edit', $principal) }}" class="admin-btn-secondary">Edit</a>
                <a href="{{ route('admin.principals.index') }}" class="admin-btn-ghost">Back</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page">
        @include('admin.partials.alert')

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-body grid sm:grid-cols-2 gap-4">
                <div><dt class="text-slate-500 text-sm">Name</dt><dd class="font-semibold">{{ $principal->name }}</dd></div>
                <div><dt class="text-slate-500 text-sm">Mobile</dt><dd class="font-semibold">{{ $principal->mobile }}</dd></div>
                <div><dt class="text-slate-500 text-sm">Email</dt><dd class="font-semibold">{{ $principal->email }}</dd></div>
                <div><dt class="text-slate-500 text-sm">Status</dt><dd class="font-semibold">{{ $principal->is_approved ? 'Approved' : 'Pending' }}</dd></div>
                <div><dt class="text-slate-500 text-sm">Login</dt><dd class="font-semibold">/principal/login</dd></div>
                <div class="sm:col-span-2">
                    <dt class="text-slate-500 text-sm">Allotted standards</dt>
                    <dd class="font-semibold">
                        @if ($principal->allottedStandards->isEmpty())
                            None
                        @else
                            {{ $principal->allottedStandards->pluck('name')->join(', ') }}
                        @endif
                    </dd>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            <div class="admin-card">
                <div class="admin-card-header"><h3 class="font-bold text-slate-900">Recent logins</h3></div>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Time</th><th>Status</th><th>IP</th></tr></thead>
                        <tbody>
                            @forelse ($loginLogs as $log)
                                <tr>
                                    <td>{{ $log->logged_at?->format('d M Y, h:i A') }}</td>
                                    <td class="capitalize">{{ $log->status }}</td>
                                    <td>{{ $log->ip_address }}</td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-row', ['colspan' => 3, 'message' => 'No logins yet'])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="admin-card">
                <div class="admin-card-header"><h3 class="font-bold text-slate-900">Sessions</h3></div>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Logged in</th><th>Active</th></tr></thead>
                        <tbody>
                            @forelse ($sessions as $session)
                                <tr>
                                    <td>{{ $session->logged_in_at?->format('d M Y, h:i A') }}</td>
                                    <td>{{ $session->is_active ? 'Yes' : 'No' }}</td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-row', ['colspan' => 2, 'message' => 'No sessions yet'])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
