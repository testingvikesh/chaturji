<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Activity Detail" subtitle="{{ $log->actionLabel() }}">
            <x-slot name="actions">
                <a href="{{ route('admin.reports.activity') }}" class="admin-btn-secondary">Back to Activity Log</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page space-y-4 max-w-3xl">
        <div class="admin-card p-5 space-y-3">
            <div class="admin-card-top"></div>
            <div class="grid sm:grid-cols-2 gap-3 text-sm">
                <p><span class="text-slate-500">When:</span> <span class="font-semibold">{{ $log->created_at?->format('d M Y, h:i A') }}</span></p>
                <p><span class="text-slate-500">Action:</span> <span class="font-semibold">{{ $actionLabels[$log->action] ?? $log->actionLabel() }}</span></p>
                <p><span class="text-slate-500">User:</span> <span class="font-semibold">{{ $log->user?->name ?? '—' }}</span></p>
                <p><span class="text-slate-500">Role:</span> <span class="font-semibold capitalize">{{ $log->role ?: '—' }}</span></p>
                <p><span class="text-slate-500">IP:</span> <span class="font-semibold">{{ $log->ip_address ?: '—' }}</span></p>
                <p><span class="text-slate-500">Code:</span> <span class="font-mono text-xs">{{ $log->action }}</span></p>
                <p class="sm:col-span-2"><span class="text-slate-500">Description:</span> <span class="font-semibold">{{ $log->description ?: '—' }}</span></p>
                @if ($log->subject_type)
                    <p class="sm:col-span-2"><span class="text-slate-500">Subject:</span> <span class="font-semibold">{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</span></p>
                @endif
            </div>
        </div>

        @if (is_array($log->properties) && $log->properties !== [])
            <div class="admin-card p-5 space-y-3">
                <h3 class="font-bold text-slate-900">Properties</h3>
                <pre class="text-xs bg-slate-50 border border-slate-200 rounded-xl p-4 overflow-x-auto whitespace-pre-wrap">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif

        @if ($log->user_agent)
            <div class="admin-card p-5 space-y-2">
                <h3 class="font-bold text-slate-900">User agent</h3>
                <p class="text-xs text-slate-600 break-all">{{ $log->user_agent }}</p>
            </div>
        @endif
    </div>
</x-app-layout>
