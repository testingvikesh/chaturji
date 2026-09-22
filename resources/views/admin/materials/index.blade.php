<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header
            title="All Materials"
            subtitle="Medium → Standard → Subject → Chapter → open material view">
            <x-slot name="actions">
                <a href="{{ route('admin.dashboard.materials') }}" class="admin-btn-secondary">Material Report</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page space-y-6">
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            Total materials in library: <strong class="text-slate-900">{{ $totalMaterials }}</strong>.
            Click a medium to continue.
        </div>

        @if ($mediums->isEmpty())
            <div class="admin-card p-8 text-center text-slate-500">No materials uploaded yet.</div>
        @else
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach ($mediums as $row)
                    <a href="{{ route('admin.materials.medium', $row['key']) }}"
                       class="group rounded-2xl border border-slate-200 bg-white p-6 hover:border-brand-green-200 hover:shadow-md transition">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Medium</p>
                        <h3 class="mt-1 text-2xl font-bold text-slate-900 group-hover:text-brand-green">{{ $row['label'] }}</h3>
                        <div class="mt-4 flex gap-4 text-sm text-slate-500">
                            <span>{{ $row['standards_count'] }} standard(s)</span>
                            <span>{{ $row['materials_count'] }} material(s)</span>
                        </div>
                        <p class="mt-4 text-sm font-semibold text-brand-green">Open medium →</p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
