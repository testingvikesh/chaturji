@php
    $showMarks = $showMarks ?? false;
    $oldCounts = old('type_counts', $selectedTypeCounts ?? []);
    $oldMarks = old('marks_per_type', $selectedMarksPerType ?? []);
@endphp

<div class="space-y-4">
    <div>
        <h3 class="font-bold text-slate-900">Question Paper Builder</h3>
        <p class="text-sm text-slate-500 mt-1">Enter how many questions you want from each type. Questions are picked randomly from the uploaded question bank.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-slate-600">
                    <th class="py-2 pr-4 font-semibold">Paper Type</th>
                    <th class="py-2 px-2 font-semibold w-28">Available</th>
                    <th class="py-2 px-2 font-semibold w-28">Quantity *</th>
                    @if ($showMarks)
                        <th class="py-2 pl-2 font-semibold w-28">Marks each</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($paperTypes as $type => $label)
                    <tr>
                        <td class="py-3 pr-4">
                            <span class="mr-2">{{ \App\Support\PaperTypeHelper::icon($type) }}</span>
                            <span class="font-medium text-slate-800">{{ $label }}</span>
                        </td>
                        <td class="py-3 px-2">
                            <span class="inline-flex min-w-[2rem] justify-center rounded-lg bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600"
                                x-text="available['{{ $type }}'] ?? '—'">—</span>
                        </td>
                        <td class="py-3 px-2">
                            <input type="number" name="type_counts[{{ $type }}]" min="0" max="200"
                                value="{{ $oldCounts[$type] ?? 0 }}"
                                class="w-full rounded-lg border-slate-200 text-center">
                        </td>
                        @if ($showMarks)
                            <td class="py-3 pl-2">
                                <input type="number" name="marks_per_type[{{ $type }}]" min="1" max="100"
                                    value="{{ $oldMarks[$type] ?? 1 }}"
                                    class="w-full rounded-lg border-slate-200 text-center">
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($showMarks)
        <p class="text-xs text-slate-500">Total marks = sum of (quantity × marks each) for each row with quantity &gt; 0.</p>
    @endif
</div>
