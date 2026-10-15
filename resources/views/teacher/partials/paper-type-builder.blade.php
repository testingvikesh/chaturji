@php
    $showMarks = $showMarks ?? false;
    $oldCounts = old('type_counts', $selectedTypeCounts ?? []);
    $oldMarks = old('marks_per_type', $selectedMarksPerType ?? []);
@endphp

<div class="space-y-4">
    @if ($showMarks)
        <div>
            <p class="block text-sm font-medium text-slate-700 mb-2">Total marks *</p>
            <input type="hidden" name="target_marks" :value="targetMarks">
            <div class="flex flex-wrap gap-2">
                <template x-for="marks in markOptions" :key="marks">
                    <label class="cursor-pointer">
                        <input type="radio" :value="marks" x-model.number="targetMarks" @change="selectMarks(marks)" class="peer sr-only">
                        <span class="inline-flex h-10 min-w-[3.25rem] items-center justify-center rounded-lg border px-3 text-sm font-semibold leading-none"
                              :class="Number(targetMarks) === Number(marks) ? 'border-brand-green bg-emerald-50 text-brand-green ring-1 ring-brand-green' : 'border-slate-200 bg-white text-slate-700'"
                              x-text="marks"></span>
                    </label>
                </template>
            </div>
            <p class="text-xs text-slate-500 mt-2">Choose the paper total. Question counts fill in from the chapters you selected.</p>
            <p class="mt-3 inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold"
               :class="Number(targetMarks) && paperTotal !== Number(targetMarks) ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800'">
                Paper total: <span class="text-base font-bold" x-text="paperTotal + ' marks'"></span>
            </p>
            <p class="text-xs text-rose-700 mt-1" x-show="planError" x-text="planError"></p>
        </div>
    @endif

    <div>
        <h3 class="font-bold text-slate-900">Question Paper Builder</h3>
        <p class="text-sm text-slate-500 mt-1">Quantities follow the total marks. You can still change a row before preview.</p>
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
                                @if ($showMarks) @input="refreshPaperTotal()" @endif
                                class="w-full rounded-lg border-slate-200 text-center">
                        </td>
                        @if ($showMarks)
                            <td class="py-3 pl-2">
                                <input type="number" name="marks_per_type[{{ $type }}]" min="1" max="100"
                                    value="{{ $oldMarks[$type] ?? (\App\Support\ExamPaperWeightage::MARKS_PER_QUESTION[$type] ?? 1) }}"
                                    @input="refreshPaperTotal()"
                                    class="w-full rounded-lg border-slate-200 text-center">
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($showMarks)
        <p class="text-sm font-semibold text-slate-800">Paper total: <span class="text-brand-green" x-text="paperTotal + ' marks'"></span></p>
        <p class="text-xs text-slate-500">This updates when you change quantity or marks each. It is the sum of quantity × marks each.</p>
    @endif
</div>
