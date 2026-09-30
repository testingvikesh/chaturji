<x-principal-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Principal</span>
            <h2 class="admin-page-title">Reports</h2>
            <p class="admin-page-subtitle">All reports for your allotted standards — open any report and print</p>
        </div>
    </x-slot>

    <div class="admin-page space-y-8">
        @include('principal.partials.reports-nav')

        @if (! ($hasAllotments ?? false))
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                No standard allotted yet. Ask admin to allot standards — reports show only your standards.
            </div>
        @else
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                Showing data for: <strong class="text-slate-800">{{ $allottedNames }}</strong>
            </div>
        @endif

        @foreach ($groups as $group)
            <section>
                <div class="mb-3">
                    <h3 class="text-lg font-bold text-slate-900">{{ $group['title'] }}</h3>
                    <p class="text-sm text-slate-500">{{ $group['description'] }}</p>
                </div>

                <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($group['reports'] as $report)
                        @php $active = request()->routeIs(...$report['match']); @endphp
                        <a href="{{ route($report['route']) }}"
                           class="group rounded-2xl border bg-white p-5 transition hover:border-brand-green-200 hover:shadow-md
                                  {{ $active ? 'border-brand-green ring-1 ring-brand-green/30' : 'border-slate-200' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-900 group-hover:text-brand-green">{{ $report['title'] }}</p>
                                    <p class="mt-1 text-sm text-slate-500 leading-snug">{{ $report['description'] }}</p>
                                </div>
                                @if (! empty($report['badge']))
                                    <span class="shrink-0 rounded-full bg-brand-green-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-brand-green">
                                        {{ $report['badge'] }}
                                    </span>
                                @endif
                            </div>
                            <p class="mt-4 text-xs font-semibold text-brand-green">Open report →</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-principal-layout>
