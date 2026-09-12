@php
    $buckets = \App\Support\TeachingHomeworkLayout::buckets();
    $oldCounts = old('type_counts', $selectedTypeCounts ?? []);
    $layouts = collect([20, 30, 40, 50])->mapWithKeys(fn (int $marks) => [
        $marks => \App\Support\TeachingHomeworkLayout::autoLayout($marks),
    ]);
@endphp

<div class="space-y-5" x-data="{
    targetMarks: '{{ old('target_marks', 20) }}',
    counts: @js($oldCounts !== [] ? $oldCounts : $layouts[20]['type_counts']),
    layouts: @js($layouts),
    applyLayout() {
        const layout = this.layouts[this.targetMarks];
        if (layout) this.counts = { ...layout.type_counts };
    },
    totalMarks() {
        const marks = @js(\App\Support\TeachingHomeworkLayout::marksPerType());
        return Object.entries(this.counts).reduce((sum, [type, count]) => {
            return sum + (Number(count) || 0) * (marks[type] || 1);
        }, 0);
    },
    totalQuestions() {
        return Object.values(this.counts).reduce((sum, count) => sum + (Number(count) || 0), 0);
    }
}" x-init="applyLayout()">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-2">Homework Marks *</label>
        <div class="flex flex-wrap gap-2">
            @foreach ($markOptions as $marks)
                <label class="cursor-pointer">
                    <input type="radio" name="target_marks" value="{{ $marks }}" class="sr-only" x-model="targetMarks" @change="applyLayout()">
                    <span
                        class="inline-flex rounded-xl border px-4 py-2 text-sm font-bold transition"
                        :class="String(targetMarks) === '{{ $marks }}'
                            ? 'border-brand-green bg-brand-green text-white shadow-sm'
                            : 'border-slate-200 bg-white text-slate-700 hover:border-brand-green/50'"
                    >
                        {{ $marks }} Marks
                    </span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-5">
        <div class="rounded-xl border border-slate-200 p-4">
            <h4 class="font-bold text-slate-900 mb-1">Objective</h4>
            <p class="text-xs text-slate-500 mb-3">One Word · MCQ · True/False · Match (1 mark each)</p>
            <div class="space-y-2">
                @foreach ($buckets as $type => $bucket)
                    @if ($bucket['group'] === 'objective')
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-medium text-slate-700">{{ $bucket['label'] }}</span>
                            <input
                                type="number"
                                name="type_counts[{{ $type }}]"
                                min="0"
                                max="200"
                                class="w-20 rounded-lg border-slate-200 text-center text-sm"
                                x-model.number="counts['{{ $type }}']"
                            >
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 p-4">
            <h4 class="font-bold text-slate-900 mb-1">Subjective</h4>
            <p class="text-xs text-slate-500 mb-3">Short answers by marks</p>
            <div class="space-y-2">
                @foreach ($buckets as $type => $bucket)
                    @if ($bucket['group'] === 'subjective')
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-medium text-slate-700">{{ $bucket['label'] }}</span>
                            <input
                                type="number"
                                name="type_counts[{{ $type }}]"
                                min="0"
                                max="200"
                                class="w-20 rounded-lg border-slate-200 text-center text-sm"
                                x-model.number="counts['{{ $type }}']"
                            >
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 flex flex-wrap items-center justify-between gap-2 text-sm">
        <span class="text-slate-600">Quantities auto-set from marks — you can adjust before preview</span>
        <span class="font-bold text-brand-green-dark">
            Total: <span x-text="totalQuestions()"></span> questions · <span x-text="totalMarks()"></span> marks
        </span>
    </div>
</div>
