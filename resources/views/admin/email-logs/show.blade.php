<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="{{ $log->subject ?: 'Email' }}" subtitle="{{ $log->typeLabel() }} · {{ ($log->sent_at ?? $log->created_at)?->format('d M Y, h:i A') }}">
            <x-slot name="actions">
                <a href="{{ route('admin.email-logs.index') }}" class="admin-btn-secondary">Back</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page space-y-4">
        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-body space-y-4">
                <div class="grid sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs font-semibold text-slate-500">Status</p>
                        <p class="mt-1">
                            @if ($log->status === 'sent')
                                <span class="admin-badge-green text-xs">Sent</span>
                            @elseif ($log->status === 'failed')
                                <span class="admin-badge-gold text-xs">Failed</span>
                            @else
                                <span class="admin-badge-slate text-xs">{{ $log->statusLabel() }}</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500">Type</p>
                        <p class="mt-1 font-medium text-slate-900">{{ $log->typeLabel() }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500">To</p>
                        <p class="mt-1 font-medium text-slate-900 break-all">{{ $log->to_email ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500">From</p>
                        <p class="mt-1 font-medium text-slate-900 break-all">{{ $log->from_email ?: '—' }}</p>
                    </div>
                    @if ($log->cc_email)
                        <div>
                            <p class="text-xs font-semibold text-slate-500">CC</p>
                            <p class="mt-1 text-slate-800 break-all">{{ $log->cc_email }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-xs font-semibold text-slate-500">Mailer</p>
                        <p class="mt-1 text-slate-800">{{ $log->mailer ?: '—' }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs font-semibold text-slate-500">Subject</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $log->subject ?: '—' }}</p>
                    </div>
                </div>

                @if ($log->error)
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $log->error }}
                    </div>
                @endif
            </div>
        </div>

        @if ($log->body_html)
            <div class="admin-card overflow-hidden">
                <div class="admin-card-header">
                    <h3 class="font-bold text-slate-900">Email preview</h3>
                </div>
                <iframe title="Email preview" sandbox class="w-full bg-white min-h-[420px] border-0" srcdoc="{{ $log->body_html }}"></iframe>
            </div>
        @elseif ($log->body_text)
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="font-bold text-slate-900">Email text</h3>
                </div>
                <div class="admin-card-body">
                    <pre class="whitespace-pre-wrap text-sm text-slate-700 font-sans">{{ $log->body_text }}</pre>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
