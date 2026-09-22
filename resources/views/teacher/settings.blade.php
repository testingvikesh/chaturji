<x-teacher-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Teacher</span>
            <h2 class="admin-page-title">Settings</h2>
            <p class="admin-page-subtitle">Select a medium, then choose multiple subjects for <strong>every standard</strong>. Std 11 &amp; 12 allow up to 2 teachers per subject.</p>
        </div>
    </x-slot>

    <div class="admin-page space-y-6"
         x-data="{
            form: @js($formData),
            medium: @js($initialMedium ?: ''),
            selections: @js($initialSelections ?: new \stdClass()),
            openStandardId: @js(array_key_first($initialSelections ?? []) ? (string) array_key_first($initialSelections) : ''),
            subjectKey(standardId) {
                return this.medium && standardId ? (this.medium + '-' + standardId) : '';
            },
            subjectsFor(standardId) {
                const key = this.subjectKey(standardId);
                return key && this.form.subjects[key] ? this.form.subjects[key] : [];
            },
            selectedFor(standardId) {
                const sid = String(standardId);
                return Array.isArray(this.selections[sid]) ? this.selections[sid].map(String) : [];
            },
            selectedCount(standardId) {
                return this.selectedFor(standardId).length;
            },
            totalSelected() {
                return (this.form.standards || []).reduce((sum, std) => sum + this.selectedCount(std.id), 0);
            },
            isShared(std) {
                return std.grade === 11 || std.grade === 12;
            },
            chooseMedium(value) {
                this.medium = value || '';
                const next = {};
                (this.form.standards || []).forEach(std => {
                    const mine = this.subjectsFor(std.id).filter(s => s.mine).map(s => String(s.id));
                    if (mine.length) next[String(std.id)] = mine;
                });
                this.selections = next;
                this.openStandardId = this.form.standards?.[0] ? String(this.form.standards[0].id) : '';
            },
            ensureSelection(standardId) {
                const sid = String(standardId);
                if (!Array.isArray(this.selections[sid])) {
                    this.selections = { ...this.selections, [sid]: [] };
                }
            },
            toggle(standardId, subject) {
                if (subject.locked) return;
                const sid = String(standardId);
                this.ensureSelection(sid);
                const id = String(subject.id);
                const current = this.selectedFor(sid);
                const next = current.includes(id)
                    ? current.filter(x => x !== id)
                    : [...current, id];
                this.selections = { ...this.selections, [sid]: next };
            },
            isChecked(standardId, id) {
                return this.selectedFor(standardId).includes(String(id));
            },
            clearStandard(standardId) {
                this.selections = { ...this.selections, [String(standardId)]: [] };
            },
            selectAvailable(standardId) {
                const sid = String(standardId);
                const ids = this.subjectsFor(sid).filter(s => !s.locked).map(s => String(s.id));
                this.selections = { ...this.selections, [sid]: ids };
            },
            editGroup(medium, standardId) {
                this.medium = medium || '';
                this.chooseMedium(this.medium);
                this.openStandardId = String(standardId || '');
                this.$nextTick(() => {
                    document.getElementById('assignment-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    document.getElementById('std-block-' + this.openStandardId)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            },
            init() {
                if (this.medium && Object.keys(this.selections || {}).length === 0) {
                    this.chooseMedium(this.medium);
                }
            }
         }">

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">Your assigned medium, standard & subjects</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Use Edit to jump into that standard, or remove a subject with ×</p>
                </div>
            </div>
            <div class="p-5">
                @if ($assignedGroups->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/70 px-4 py-8 text-center">
                        <p class="text-sm font-medium text-slate-600">Nothing saved yet</p>
                        <p class="mt-1 text-xs text-slate-400">Select medium and subjects for each standard below.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($assignedGroups as $rows)
                            @php
                                $first = $rows->first();
                                $mediumKey = \App\Models\Material::normalizeMedium($first?->medium) ?: $first?->medium;
                                $mediumLabel = \App\Models\Standard::MEDIUMS[$mediumKey] ?? ucfirst((string) $first?->medium);
                                $standardName = $first?->standard?->name ?? 'Standard';
                                $subjectCount = $rows->count();
                            @endphp
                            <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-white to-brand-green-50/40 p-4 shadow-sm">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-sm font-bold text-brand-green">
                                                {{ $mediumLabel }} · {{ $standardName }}
                                            </p>
                                            <span class="inline-flex items-center rounded-full bg-white border border-brand-green-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-brand-green">
                                                {{ $subjectCount }} {{ $subjectCount === 1 ? 'subject' : 'subjects' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-brand-green-100 bg-white px-3 py-1.5 text-xs font-semibold text-brand-green hover:bg-brand-green-50 transition"
                                                @click="editGroup(@js($mediumKey), @js((string) $first?->standard_id))">
                                            Edit
                                        </button>
                                        <form method="POST"
                                              action="{{ route('teacher.settings.groups.destroy') }}"
                                              onsubmit="return confirm('Clear all subjects for {{ $mediumLabel }} · {{ $standardName }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="medium" value="{{ $mediumKey }}">
                                            <input type="hidden" name="standard_id" value="{{ $first?->standard_id }}">
                                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-red-100 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 transition">Clear</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($rows as $row)
                                        @php $subjectName = $row->subject?->name ?? 'Subject'; @endphp
                                        <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white py-1.5 pl-3.5 pr-1.5 text-sm font-semibold text-slate-800 shadow-sm">
                                            <span class="leading-none">{{ $subjectName }}</span>
                                            <form method="POST" action="{{ route('teacher.settings.subjects.destroy', $row) }}" class="inline-flex" onsubmit="return confirm('Remove {{ $subjectName }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-red-500 hover:text-white" aria-label="Remove {{ $subjectName }}">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div id="assignment-form" class="admin-form-card scroll-mt-4">
            <div class="admin-card-top"></div>
            <form method="POST" action="{{ route('teacher.settings.update') }}" class="admin-card-body">
                @csrf
                @method('PUT')
                <input type="hidden" name="medium" :value="medium">

                <template x-for="std in form.standards" :key="'hidden-'+std.id">
                    <template x-for="id in selectedFor(std.id)" :key="'hid-'+std.id+'-'+id">
                        <input type="hidden" :name="'assignments['+std.id+'][]'" :value="id">
                    </template>
                </template>

                <div>
                    <h3 class="font-bold text-slate-900">Select assignment</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Pick medium once, then multi-select subjects under each standard.</p>
                </div>

                <div>
                    <label class="admin-label">1. Select medium</label>
                    <select class="admin-select" x-model="medium" @change="chooseMedium(medium)" required>
                        <option value="">Choose medium</option>
                        <template x-for="(label, value) in form.mediums" :key="value">
                            <option :value="value" x-text="label"></option>
                        </template>
                    </select>
                    @error('medium')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <label class="admin-label mb-0">2. All standards — multiple subjects</label>
                        <p class="text-xs font-semibold text-brand-green" x-show="medium" x-cloak>
                            Selected total: <span x-text="totalSelected()"></span>
                        </p>
                    </div>
                    <p class="text-xs text-slate-500 mb-3">Locked subjects already belong to another teacher for this medium. Std 11 &amp; 12 can be shared by 2 teachers.</p>

                    <div x-show="!medium" class="rounded-xl border border-dashed border-slate-200 p-6 text-sm text-slate-400 text-center">
                        Choose a medium first to load subjects for all standards.
                    </div>

                    <div x-show="medium" x-cloak class="space-y-3">
                        <template x-for="std in form.standards" :key="'std-'+std.id">
                            <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden" :id="'std-block-'+std.id">
                                <button type="button"
                                        class="w-full flex items-center justify-between gap-3 px-4 py-3 text-left hover:bg-slate-50"
                                        @click="openStandardId = openStandardId === String(std.id) ? '' : String(std.id)">
                                    <span>
                                        <span class="block text-sm font-bold text-slate-900" x-text="std.name"></span>
                                        <span class="block text-xs text-slate-500 mt-0.5">
                                            <span x-text="selectedCount(std.id)"></span> selected
                                            <span x-show="isShared(std)" x-cloak class="text-brand-green"> · shared up to 2 teachers</span>
                                        </span>
                                    </span>
                                    <svg class="h-4 w-4 text-slate-400 transition" :class="openStandardId === String(std.id) ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>

                                <div x-show="openStandardId === String(std.id)" x-cloak class="border-t border-slate-100 px-4 py-4 space-y-3">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" class="text-xs font-semibold text-brand-green hover:underline" @click="selectAvailable(std.id)">Select all available</button>
                                        <button type="button" class="text-xs font-semibold text-slate-500 hover:text-red-600" @click="clearStandard(std.id)" x-show="selectedCount(std.id) > 0">Clear</button>
                                    </div>

                                    <div x-show="subjectsFor(std.id).length === 0" class="rounded-xl border border-dashed border-slate-200 p-4 text-sm text-slate-400 text-center">
                                        No subjects found for this standard and medium.
                                    </div>

                                    <div class="grid sm:grid-cols-2 gap-3" x-show="subjectsFor(std.id).length">
                                        <template x-for="subject in subjectsFor(std.id)" :key="std.id+'-'+subject.id">
                                            <button type="button"
                                                    @click="toggle(std.id, subject)"
                                                    :disabled="!!subject.locked"
                                                    class="text-left rounded-xl border px-4 py-3 transition"
                                                    :class="subject.locked
                                                        ? 'border-slate-200 bg-slate-50 opacity-70 cursor-not-allowed'
                                                        : (isChecked(std.id, subject.id)
                                                            ? 'border-brand-green bg-brand-green-50 ring-1 ring-brand-green'
                                                            : 'border-slate-200 bg-white hover:border-brand-green-200')">
                                                <span class="flex items-start justify-between gap-3">
                                                    <span>
                                                        <span class="block text-sm font-semibold text-slate-900" x-text="subject.name"></span>
                                                        <span class="block text-xs mt-1 text-slate-500" x-show="subject.slots_max > 1" x-cloak>
                                                            Teachers: <span x-text="subject.slots_used"></span>/<span x-text="subject.slots_max"></span>
                                                        </span>
                                                        <span class="block text-xs mt-1" x-show="subject.locked" x-cloak>
                                                            Full — <span class="font-semibold text-amber-800" x-text="subject.taken_by"></span>
                                                        </span>
                                                        <span class="block text-xs mt-1 text-brand-green" x-show="!subject.locked && isChecked(std.id, subject.id)">Selected</span>
                                                    </span>
                                                    <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded border"
                                                          :class="isChecked(std.id, subject.id) && !subject.locked ? 'bg-brand-green border-brand-green text-white' : 'border-slate-300 bg-white text-transparent'">✓</span>
                                                </span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    @error('assignments')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="admin-btn-primary" :disabled="!medium">Save all assignments</button>
                    <a href="{{ route('teacher.dashboard') }}" class="admin-btn-ghost">Back to dashboard</a>
                </div>
            </form>
        </div>
    </div>
</x-teacher-layout>
