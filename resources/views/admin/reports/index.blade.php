<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header
            title="Reports"
            subtitle="One module for all admin reports — open any report below" />
    </x-slot>

    <div class="admin-page space-y-8">
        @include('admin.partials.alert')
        @include('admin.partials.reports-nav')

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
</x-app-layout>
