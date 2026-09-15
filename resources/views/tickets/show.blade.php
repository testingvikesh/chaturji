<x-dynamic-component :component="$layout">
    <x-slot name="header">
        <div>
            <span class="admin-section-label">{{ $label }}</span>
            <h2 class="admin-page-title">{{ $ticket->ticket_no }}</h2>
            <p class="admin-page-subtitle">{{ $ticket->categoryLabel() }} · {{ $ticket->subject }}</p>
        </div>
    </x-slot>

    <div class="admin-page space-y-4">
        @include('admin.partials.alert')

        <div class="mb-1">
            <a href="{{ route($routePrefix.'.index') }}" class="text-sm text-brand-green hover:underline font-medium">&larr; Back to Tickets</a>
        </div>

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">{{ $ticket->subject }}</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Opened {{ $ticket->created_at?->format('d M Y, h:i A') }}</p>
                </div>
                @include('tickets.partials.status-badge', ['status' => $ticket->status])
            </div>
            <div class="p-5 space-y-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Conversation</p>

                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold text-slate-500 mb-1">
                        You
                        <span class="font-normal text-slate-400"> · {{ $ticket->created_at?->format('d M Y, h:i A') }}</span>
                    </p>
                    <p class="text-sm text-slate-800 whitespace-pre-line">{{ $ticket->message }}</p>
                </div>

                @forelse ($ticket->replies as $reply)
                    <div class="rounded-xl border px-4 py-3 {{ $reply->is_admin ? 'border-brand-green-200 bg-brand-green-50' : 'border-slate-200 bg-white' }}">
                        <p class="text-xs font-semibold {{ $reply->is_admin ? 'text-brand-green' : 'text-slate-500' }} mb-1">
                            {{ $reply->is_admin ? 'Admin' : 'You' }}
                            <span class="font-normal text-slate-400"> · {{ $reply->created_at?->format('d M Y, h:i A') }}</span>
                        </p>
                        <p class="text-sm text-slate-800 whitespace-pre-line">{{ $reply->message }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No replies yet. Admin will respond here.</p>
                @endforelse
            </div>
        </div>

        @if ($ticket->isClosed())
            <div class="admin-card p-5 text-sm text-slate-500">This ticket is closed. Generate a new ticket if you need more help.</div>
        @else
            <div class="admin-form-card">
                <div class="admin-card-top"></div>
                <form method="POST"
                      action="{{ route($routePrefix.'.reply', $ticket) }}"
                      class="admin-card-body"
                      x-data="{ sending: false }"
                      @submit="if (sending) { $event.preventDefault(); return; } sending = true">
                    @csrf
                    <div>
                        <label class="admin-label">Add a reply</label>
                        <textarea name="message" rows="4" required maxlength="4000" class="admin-input" placeholder="Write your reply">{{ old('message') }}</textarea>
                        @error('message')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit"
                            class="admin-btn-primary"
                            :disabled="sending"
                            :class="{ 'opacity-60 cursor-not-allowed': sending }">
                        <span x-text="sending ? 'Sending…' : 'Send Reply'">Send Reply</span>
                    </button>
                </form>
            </div>
        @endif
    </div>
</x-dynamic-component>
