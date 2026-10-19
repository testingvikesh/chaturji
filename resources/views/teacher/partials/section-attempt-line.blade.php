@php
    $attemptLine = \App\Support\PaperTypeHelper::attemptInstruction((int) ($attempt ?? 0), (int) ($optional ?? 0));
@endphp
@if ($attemptLine)
    <p class="border-b border-amber-100 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-900">{{ $attemptLine }}</p>
@endif
