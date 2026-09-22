@if ($ticket->hasMissingChapterMeta())
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-amber-800 mb-1">Missing Chapter</p>
        <p class="text-sm font-semibold text-slate-800">{{ $ticket->missingChapterSummary() }}</p>
    </div>
@endif
