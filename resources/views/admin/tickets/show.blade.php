<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="{{ $ticket->ticket_no }}" subtitle="{{ ucfirst($ticket->role) }} ticket · {{ $ticket->categoryLabel() }}">
            <x-slot name="actions">
                <a href="{{ route('admin.tickets.index') }}" class="admin-btn-secondary">Back to Report</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page space-y-4">
        @include('admin.partials.alert')

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">{{ $ticket->subject }}</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ $ticket->user?->name ?? '—' }}
                        · {{ $ticket->user?->mobile }}
                        · {{ ucfirst($ticket->role) }}
                        · {{ $ticket->created_at?->format('d M Y, h:i A') }}
                    </p>
                </div>
                @include('tickets.partials.status-badge', ['status' => $ticket->status])
            </div>
            <div class="p-5 space-y-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Conversation</p>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold text-slate-500 mb-1">
                        {{ ucfirst($ticket->role) }}
                        <span class="font-normal text-slate-400"> · {{ $ticket->created_at?->format('d M Y, h:i A') }}</span>
                    </p>
                    <p class="text-sm text-slate-800 whitespace-pre-line">{{ $ticket->message }}</p>
                    @include('tickets.partials.attachments', [
                        'attachments' => $ticket->attachments,
                        'ticket' => $ticket,
                        'downloadRoute' => 'admin.tickets.attachments.download',
                    ])
                </div>

                @forelse ($ticket->replies as $reply)
                    <div class="rounded-xl border px-4 py-3 {{ $reply->is_admin ? 'border-brand-green-200 bg-brand-green-50' : 'border-slate-200 bg-white' }}">
                        <p class="text-xs font-semibold {{ $reply->is_admin ? 'text-brand-green' : 'text-slate-500' }} mb-1">
                            {{ $reply->is_admin ? 'Admin' : ($ticket->user?->name ?? 'User') }}
                            <span class="font-normal text-slate-400"> · {{ $reply->created_at?->format('d M Y, h:i A') }}</span>
                        </p>
                        <p class="text-sm text-slate-800 whitespace-pre-line">{{ $reply->message }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No replies yet. Send the first reply below.</p>
                @endforelse
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 admin-form-card">
                <div class="admin-card-top"></div>
                <form method="POST"
                      action="{{ route('admin.tickets.reply', $ticket) }}"
                      class="admin-card-body"
                      x-data="{ sending: false }"
                      @submit="if (sending) { $event.preventDefault(); return; } sending = true">
                    @csrf
                    <div>
                        <label class="admin-label">Reply</label>
                        <textarea name="message" rows="5" required maxlength="4000" class="admin-input" placeholder="Write a reply to this ticket">{{ old('message') }}</textarea>
                        @error('message')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="admin-btn-primary" :disabled="sending" :class="{ 'opacity-60 cursor-not-allowed': sending }">
                            <span x-text="sending ? 'Sending…' : 'Send Reply'">Send Reply</span>
                        </button>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="status" value="closed" class="rounded border-slate-300 text-brand-green">
                            Close after reply
                        </label>
                    </div>
                </form>
            </div>

            <div class="admin-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">Update status</p>
                <form method="POST" action="{{ route('admin.tickets.status', $ticket) }}" class="space-y-3">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="admin-select">
                        @foreach ($statuses as $key => $name)
                            <option value="{{ $key }}" @selected($ticket->status === $key)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="admin-btn-secondary w-full">Save Status</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
