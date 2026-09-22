<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header
            title="{{ $mediumLabel }} Medium"
            subtitle="Select standard">
            <x-slot name="actions">
                <a href="{{ route('admin.materials.index') }}" class="admin-btn-secondary">← All Materials</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page space-y-4">
        <p class="text-sm text-slate-500">
            <a href="{{ route('admin.materials.index') }}" class="text-brand-green hover:underline font-medium">All Materials</a>
            <span class="text-slate-300">/</span> {{ $mediumLabel }}
        </p>

        @if ($standards->isEmpty())
            <div class="admin-card p-8 text-center text-slate-500">No standards with materials for this medium.</div>
        @else
            <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($standards as $standard)
                    <a href="{{ route('admin.materials.standard', [$medium, $standard]) }}"
                       class="group rounded-2xl border border-slate-200 bg-white p-5 hover:border-brand-green-200 hover:shadow-md transition">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Standard</p>
                        <h3 class="mt-1 text-xl font-bold text-slate-900 group-hover:text-brand-green">{{ $standard->name }}</h3>
                        <p class="mt-2 text-sm text-slate-500">
                            {{ $standard->subjects_count }} subject(s) · {{ $standard->chapters_count }} chapter(s)
                        </p>
                        <p class="mt-3 text-sm font-semibold text-brand-green">Open standard →</p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
