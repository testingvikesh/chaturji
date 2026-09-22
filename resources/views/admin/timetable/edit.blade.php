<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header
            title="Timetable · {{ $teacher->name }}"
            subtitle="Assign Medium · Standard · Subject to each weekday period. Teacher login shows today’s slots; logout report uses them.">
            <x-slot name="actions">
                <a href="{{ route('admin.timetable.index') }}" class="admin-btn-secondary">← All teachers</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page"
         x-data="{
            medium: '{{ old('medium', 'gujarati') }}',
            standardId: '{{ old('standard_id', '') }}',
            subjectId: '{{ old('subject_id', '') }}',
            standards: @js($standards->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'medium' => \App\Models\Material::normalizeMedium($s->medium) ?: $s->medium])),
            allSubjects: @js($subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'standard_id' => $s->standard_id])),
            syncStandards() {
                const sel = this.$refs.standardSelect;
                if (!sel) return;
                const keep = this.standardId;
                while (sel.options.length > 1) sel.remove(1);
                this.standards.filter(s => s.medium === this.medium).forEach(s => {
                    const o = document.createElement('option');
                    o.value = String(s.id);
                    o.textContent = s.name;
                    sel.appendChild(o);
                });
                if (keep && [...sel.options].some(o => o.value === String(keep))) {
                    sel.value = String(keep);
                    this.standardId = String(keep);
                } else {
                    sel.value = '';
                    this.standardId = '';
                }
                this.syncSubjects();
            },
            syncSubjects() {
                const sel = this.$refs.subjectSelect;
                if (!sel) return;
                const keep = this.subjectId;
                const sid = parseInt(this.standardId || '0', 10);
                while (sel.options.length > 1) sel.remove(1);
                this.allSubjects.filter(s => s.standard_id === sid).forEach(s => {
                    const o = document.createElement('option');
                    o.value = String(s.id);
                    o.textContent = s.name;
                    sel.appendChild(o);
                });
                if (keep && [...sel.options].some(o => o.value === String(keep))) {
                    sel.value = String(keep);
                    this.subjectId = String(keep);
                } else {
                    sel.value = '';
                    this.subjectId = '';
                }
            },
            onMediumChange() {
                this.standardId = '';
                this.subjectId = '';
                this.syncStandards();
            },
            onStandardChange() {
                this.subjectId = '';
                this.syncSubjects();
            }
         }"
         x-init="syncStandards()">
        @include('admin.partials.alert')

        <div class="admin-card mb-6">
            <div class="admin-card-top"></div>
            <div class="admin-card-header"><h3 class="font-bold text-slate-900">Add / update slot</h3></div>
            <div class="admin-card-body">
                <form method="POST" action="{{ route('admin.timetable.store', $teacher) }}" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @csrf
                    <div>
                        <label class="admin-label">Weekday *</label>
                        <select name="weekday" required class="admin-select">
                            @foreach ($weekdays as $num => $label)
                                <option value="{{ $num }}" @selected((int) old('weekday', 1) === $num)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label">Period *</label>
                        <select name="period_id" required class="admin-select">
                            <option value="">Select period</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}" @selected((int) old('period_id') === $period->id)>{{ $period->displayLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label">Section</label>
                        <input type="text" name="section" value="{{ old('section') }}" placeholder="A / B (optional)" class="admin-input">
                    </div>
                    <div>
                        <label class="admin-label">Medium *</label>
                        <select name="medium" x-model="medium" @change="onMediumChange()" required class="admin-select">
                            @foreach ($mediums as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label">Standard *</label>
                        <select name="standard_id" x-ref="standardSelect" x-model="standardId" @change="onStandardChange()" required class="admin-select">
                            <option value="">Select standard</option>
                        </select>
                    </div>
                    <div>
                        <label class="admin-label">Subject *</label>
                        <select name="subject_id" x-ref="subjectSelect" x-model="subjectId" required class="admin-select">
                            <option value="">Select subject</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <button type="submit" class="admin-btn-primary">Save slot</button>
                        <p class="text-xs text-slate-500 mt-2">Same weekday + period overwrites the previous subject. Also syncs teacher subject assignment.</p>
                    </div>
                </form>
            </div>
        </div>

        <div class="admin-card overflow-x-auto">
            <div class="admin-card-header">
                <h3 class="font-bold text-slate-900">Weekly grid</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ $slots->count() }} active slot(s)</p>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table text-sm">
                    <thead>
                        <tr>
                            <th>Period</th>
                            @foreach ($weekdays as $num => $label)
                                @if ($num <= 6)
                                    <th>{{ substr($label, 0, 3) }}</th>
                                @endif
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($periods as $period)
                            <tr>
                                <td class="font-semibold whitespace-nowrap">{{ $period->displayLabel() }}</td>
                                @foreach ($weekdays as $num => $label)
                                    @if ($num <= 6)
                                        @php $cell = $grid[$num][$period->id] ?? null; @endphp
                                        <td class="align-top min-w-[140px]">
                                            @if ($cell)
                                                <div class="rounded-lg border border-brand-green-100 bg-brand-green-50/60 p-2 space-y-1">
                                                    <p class="font-semibold text-slate-900 leading-tight">{{ $cell->subject?->name }}</p>
                                                    <p class="text-[11px] text-slate-600">{{ $cell->mediumLabel() }} · {{ $cell->standard?->name }}@if($cell->section) · Sec {{ $cell->section }}@endif</p>
                                                    <form method="POST" action="{{ route('admin.timetable.destroy', [$teacher, $cell]) }}" onsubmit="return confirm('Remove this slot?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-[11px] text-red-600 hover:underline">Remove</button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-slate-500 py-8">Add school periods first.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
