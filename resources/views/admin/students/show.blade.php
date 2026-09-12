<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">{{ $student->name }}</h2>
            <p class="text-sm text-slate-500 mt-1">Student profile and activity</p>
        </div>
    </x-slot>

    <div class="grid lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-1 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <h3 class="font-semibold text-slate-900 mb-4">Profile</h3>
            <dl class="space-y-3 text-sm">
                <div><dt class="text-slate-500">Mobile</dt><dd class="font-medium">{{ $student->mobile }}</dd></div>
                <div><dt class="text-slate-500">Email</dt><dd class="font-medium">{{ $student->email ?: '-' }}</dd></div>
                <div><dt class="text-slate-500">Standard</dt><dd class="font-medium">{{ $student->standardLabel() }}</dd></div>
                <div><dt class="text-slate-500">Medium</dt><dd class="font-medium capitalize">{{ $student->medium }}</dd></div>
                <div><dt class="text-slate-500">Status</dt>
                    <dd class="font-medium mt-1">
                        @include('admin.partials.status-badge', [
                            'user' => $student,
                            'approveRoute' => route('admin.students.approve', $student),
                            'pendingRoute' => route('admin.students.pending', $student),
                        ])
                    </dd>
                </div>
                <div><dt class="text-slate-500">Registered</dt><dd class="font-medium">{{ $student->created_at->format('d M Y') }}</dd></div>
            </dl>
            <div class="mt-6 flex flex-wrap gap-2">
                @include('admin.partials.approval-actions', [
                    'user' => $student,
                    'approveRoute' => route('admin.students.approve', $student),
                    'pendingRoute' => route('admin.students.pending', $student),
                    'inline' => false,
                ])
                <x-admin.action-edit :href="route('admin.students.edit', $student)" label="Edit student" :compact="false" />
                <a href="{{ route('admin.students.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm">Back</a>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200"><h3 class="font-semibold">Login History</h3></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500"><tr><th class="px-4 py-3 text-left">Login</th><th class="px-4 py-3 text-left">Method</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-left">Time</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($loginLogs as $log)
                                <tr>
                                    <td class="px-4 py-3">{{ $log->login_identifier }}</td>
                                    <td class="px-4 py-3 capitalize">{{ $log->login_method }}</td>
                                    <td class="px-4 py-3 capitalize">{{ $log->status }}</td>
                                    <td class="px-4 py-3">{{ $log->logged_at->format('d M Y, h:i A') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">No login records</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200"><h3 class="font-semibold">Sessions (1 Day)</h3></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500"><tr><th class="px-4 py-3 text-left">Login At</th><th class="px-4 py-3 text-left">Last Activity</th><th class="px-4 py-3 text-left">Expires</th><th class="px-4 py-3 text-left">Status</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($sessions as $session)
                                <tr>
                                    <td class="px-4 py-3">{{ $session->logged_in_at->format('d M Y, h:i A') }}</td>
                                    <td class="px-4 py-3">{{ $session->last_activity_at->format('d M Y, h:i A') }}</td>
                                    <td class="px-4 py-3">{{ $session->expires_at->format('d M Y, h:i A') }}</td>
                                    <td class="px-4 py-3">{{ $session->is_active ? 'Active' : 'Closed' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">No sessions recorded</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
