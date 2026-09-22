@props([
    'attachments',
    'downloadRoute',
    'ticket',
])

@if ($attachments->isNotEmpty())
    <div class="mt-3 space-y-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Attachments</p>
        <div class="flex flex-wrap gap-2">
            @foreach ($attachments as $attachment)
                <a href="{{ route($downloadRoute, [$ticket, $attachment]) }}"
                   class="inline-flex max-w-full items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:border-brand-green-200 hover:text-brand-green transition">
                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $attachment->isImage() ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600' }}">
                        @if ($attachment->isImage())
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        @else
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        @endif
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate" title="{{ $attachment->original_name }}">{{ $attachment->original_name }}</span>
                        <span class="block font-normal text-slate-400">{{ $attachment->humanSize() }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
@endif
