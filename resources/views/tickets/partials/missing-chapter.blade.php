@if ($ticket->hasMissingChapterMeta())
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-amber-800 mb-2">Missing Chapter</p>
        <div class="grid sm:grid-cols-2 gap-2 text-sm text-slate-800">
            <p><span class="text-slate-500">Medium:</span> <span class="font-semibold">{{ \App\Models\Standard::MEDIUMS[$ticket->medium] ?? ucfirst((string) $ticket->medium) ?: '—' }}</span></p>
            <p><span class="text-slate-500">Standard:</span> <span class="font-semibold">{{ $ticket->standard?->name ?? '—' }}</span></p>
            <p><span class="text-slate-500">Subject:</span> <span class="font-semibold">{{ $ticket->curriculumSubject?->name ?? '—' }}</span></p>
            <p><span class="text-slate-500">Chapter No:</span> <span class="font-semibold">{{ $ticket->chapter_no ?: '—' }}</span></p>
            <p class="sm:col-span-2"><span class="text-slate-500">Chapter:</span> <span class="font-semibold">{{ $ticket->chapter_name ?: '—' }}</span></p>
        </div>
    </div>
@endif
