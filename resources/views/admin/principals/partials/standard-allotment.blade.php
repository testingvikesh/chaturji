@props([
    'standards',
    'selected' => [],
])

@php
    $picked = collect(old('standard_ids', $selected))->map(fn ($id) => (int) $id)->all();
    $grouped = collect($standards)->groupBy('medium');
@endphp

<div class="sm:col-span-2">
    <label class="admin-label">Allotted standards *</label>
    <p class="text-xs text-slate-500 mb-2">Principal will see books only for the standards you select here.</p>
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-4 max-h-72 overflow-y-auto">
        @forelse ($grouped as $medium => $rows)
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">{{ \App\Models\Standard::MEDIUMS[$medium] ?? ucfirst((string) $medium) }}</p>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach ($rows as $standard)
                        <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                            <input type="checkbox"
                                   name="standard_ids[]"
                                   value="{{ $standard->id }}"
                                   class="rounded border-slate-300 text-brand-green"
                                   @checked(in_array((int) $standard->id, $picked, true))>
                            <span>{{ $standard->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">No active standards found.</p>
        @endforelse
    </div>
    @error('standard_ids')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    @error('standard_ids.*')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
</div>
