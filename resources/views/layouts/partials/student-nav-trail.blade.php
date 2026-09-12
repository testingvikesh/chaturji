@if ($studentNavTrail->isNotEmpty())
    @php $currentPage = $studentNavTrail->last(); @endphp
    <p class="text-[11px] font-semibold uppercase tracking-wide text-white/70 mb-2">Current Page</p>
    <p class="text-base sm:text-lg font-bold text-brand-gold leading-snug break-words" title="{{ $currentPage['label'] }}">
        {{ $currentPage['label'] }}
    </p>
@endif
