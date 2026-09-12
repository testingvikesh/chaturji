@props(['breakdown', 'totalMarks' => null, 'totalQuestions' => null, 'showMarks' => false])

<div class="admin-card overflow-hidden">
    <div class="admin-card-top"></div>
    <div class="admin-card-header flex flex-wrap items-center justify-between gap-3">
        <h3 class="font-bold text-slate-900">Paper Type Breakdown</h3>
        <div class="flex flex-wrap gap-2 text-xs">
            @if ($totalQuestions)
                <span class="rounded-lg bg-slate-100 px-3 py-1.5 font-semibold">{{ $totalQuestions }} Questions</span>
            @endif
            @if ($totalMarks)
                <span class="rounded-lg bg-brand-gold/20 px-3 py-1.5 font-semibold text-brand-green-darker">{{ $totalMarks }} Marks</span>
            @endif
        </div>
    </div>
    <div class="p-5 grid sm:grid-cols-2 gap-3">
        @foreach ($breakdown as $row)
            <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="shrink-0">{{ \App\Support\PaperTypeHelper::icon($row['type']) }}</span>
                    <span class="font-medium text-slate-800 truncate">{{ $row['label'] }}</span>
                </div>
                <div class="shrink-0 text-right">
                    <span class="font-bold text-brand-green">{{ $row['count'] }} Q</span>
                    @if ($showMarks && ($row['subtotal'] ?? 0) > 0)
                        <span class="block text-xs font-semibold text-amber-700">{{ $row['marks'] }} × {{ $row['count'] }} = {{ $row['subtotal'] }} marks</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
