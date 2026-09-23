@php
    $reportNav = \App\Support\AdminReportCatalog::navItems();
    $showReportPrint = ! request()->routeIs('admin.reports.index', 'admin.reports.logins');
@endphp

@include('admin.partials.report-print')

<div class="mb-5 -mx-1 report-print-hide">
    <div class="flex items-center justify-between gap-3 mb-2 px-1">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Reports module</p>
            <a href="{{ route('admin.reports.index') }}" class="text-sm font-semibold text-brand-green hover:underline">All reports</a>
        </div>
        @if ($showReportPrint)
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print records
            </button>
        @endif
    </div>
    <div class="flex gap-1.5 overflow-x-auto pb-1 px-1">
        @foreach ($reportNav as $item)
            @php $active = request()->routeIs(...$item['match']); @endphp
            <a href="{{ route($item['route']) }}"
               class="shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold border transition
                      {{ $active
                          ? 'bg-brand-green text-white border-brand-green shadow-sm'
                          : 'bg-white text-slate-600 border-slate-200 hover:border-brand-green-200 hover:text-brand-green' }}">
                {{ $item['title'] }}
            </a>
        @endforeach
    </div>
</div>
