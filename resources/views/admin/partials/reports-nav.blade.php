@php
    $reportNav = \App\Support\AdminReportCatalog::navItems();
@endphp

<div class="mb-5 -mx-1">
    <div class="flex items-center justify-between gap-3 mb-2 px-1">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Reports module</p>
            <a href="{{ route('admin.reports.index') }}" class="text-sm font-semibold text-brand-green hover:underline">All reports</a>
        </div>
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
