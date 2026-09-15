<x-teacher-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Teacher</span>
            <h2 class="admin-page-title">Settings</h2>
            <p class="admin-page-subtitle">Select medium, then standard, then subjects. Standard 11 &amp; 12 support multi-select subjects. A subject taken by another teacher for the same medium stays locked.</p>
        </div>
    </x-slot>

    <div class="admin-page space-y-6"
         x-data="{
            form: @js($formData),
            medium: @js($initialMedium ?: ''),
            standardId: @js((string) $initialStandardId),
            selected: @js($initialSubjectIds),
            subjectKey() {
                return this.medium && this.standardId ? (this.medium + '-' + this.standardId) : '';
            },
            currentSubjects() {
                const key = this.subjectKey();
                return key && this.form.subjects[key] ? this.form.subjects[key] : [];
            },
            availableSubjects() {
                return this.currentSubjects().filter(s => !s.taken_by);
            },
            standardNumber() {
                const std = (this.form.standards || []).find(s => String(s.id) === String(this.standardId));
                if (!std) return 0;
                const m = String(std.name || '').match(/(\d+)/);
                return m ? parseInt(m[1], 10) : 0;
            },
            isMultiSelectStandard() {
                const n = this.standardNumber();
                return n === 11 || n === 12;
            },
            chooseMedium(value) {
                this.medium = value || '';
                this.standardId = '';
                this.selected = [];
            },
            chooseStandard(id) {
                this.standardId = String(id || '');
                this.selected = this.currentSubjects().filter(s => s.mine).map(s => String(s.id));
            },
            toggle(subject) {
                if (subject.taken_by) return;
                const id = String(subject.id);
                if (this.selected.includes(id)) {
                    this.selected = this.selected.filter(x => x !== id);
                    return;
                }
                // Std 11 & 12: always multi-select. Other standards: also allow multi.
                this.selected = [...this.selected, id];
            },
            isChecked(id) {
                return this.selected.includes(String(id));
            },
            selectAll() {
                this.selected = this.availableSubjects().map(s => String(s.id));
            },
            clearAll() {
                this.selected = [];
            }
         }">

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">Your assigned medium, standard & subjects</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Saved after you submit below</p>
                </div>
            </div>
            <div class="p-5">
                @if ($assignedGroups->isEmpty())
                    <p class="text-sm text-slate-500">Nothing saved yet. Select medium, standard and subjects below.</p>
                @else
                    <div class="space-y-4">
                        @foreach ($assignedGroups as $rows)
                            @php
                                $first = $rows->first();
                                $mediumKey = \App\Models\Material::normalizeMedium($first?->medium) ?: $first?->medium;
                                $mediumLabel = \App\Models\Standard::MEDIUMS[$mediumKey] ?? ucfirst((string) $first?->medium);
                            @endphp
                            <div class="rounded-xl border border-brand-green-100 bg-brand-green-50/50 p-4">
                                <p class="text-sm font-bold text-brand-green">
                                    {{ $mediumLabel }} · {{ $first?->standard?->name ?? 'Standard' }}
                                </p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($rows as $row)
                                        <span class="inline-flex items-center rounded-full bg-white border border-brand-green-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                            {{ $row->subject?->name ?? 'Subject' }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="admin-form-card">
            <div class="admin-card-top"></div>
            <form method="POST" action="{{ route('teacher.settings.update') }}" class="admin-card-body">
                @csrf
                @method('PUT')
                <input type="hidden" name="medium" :value="medium">
                <input type="hidden" name="standard_id" :value="standardId">
                <template x-for="id in selected" :key="'sid-'+id">
                    <input type="hidden" name="subject_ids[]" :value="id">
                </template>

                <div>
                    <label class="admin-label">1. Select medium</label>
                    <select class="admin-select" x-model="medium" @change="chooseMedium(medium)" required>
                        <option value="">Choose medium</option>
                        <template x-for="(label, value) in form.mediums" :key="value">
                            <option :value="value" x-text="label" :selected="medium === value"></option>
                        </template>
                    </select>
                    @error('medium')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="admin-label">2. Select standard</label>
                    <select class="admin-select" x-model="standardId" @change="chooseStandard(standardId)" :disabled="!medium" required>
                        <option value="" x-text="medium ? 'Choose standard' : 'Select medium first'"></option>
                        <template x-for="std in form.standards" :key="std.id">
                            <option :value="String(std.id)" x-text="std.name" :selected="String(standardId) === String(std.id)"></option>
                        </template>
                    </select>
                    @error('standard_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <div>
                            <label class="admin-label mb-0">3. Select subjects</label>
                            <p class="text-xs text-slate-500 mt-1"
                               x-text="isMultiSelectStandard()
                                   ? ('Standard ' + standardNumber() + ' — multi-select: choose one or more subjects (Ctrl/Cmd + click in the list, or tap cards).')
                                   : 'Tap subjects to select. You can choose more than one.'"></p>
                        </div>
                        <div class="flex items-center gap-2" x-show="medium && standardId && availableSubjects().length" x-cloak>
                            <button type="button" class="admin-btn-ghost text-xs py-1.5 px-3" @click="selectAll()">Select all</button>
                            <button type="button" class="admin-btn-ghost text-xs py-1.5 px-3" @click="clearAll()">Clear</button>
                        </div>
                    </div>

                    <p class="text-xs font-semibold text-brand-green mb-3" x-show="selected.length" x-cloak>
                        <span x-text="selected.length"></span> subject(s) selected
                    </p>

                    <div x-show="!medium" class="rounded-xl border border-dashed border-slate-200 p-6 text-sm text-slate-400 text-center">
                        Choose a medium first.
                    </div>
                    <div x-show="medium && !standardId" x-cloak class="rounded-xl border border-dashed border-slate-200 p-6 text-sm text-slate-400 text-center">
                        Choose a standard to see subjects.
                    </div>
                    <div x-show="medium && standardId && currentSubjects().length === 0" x-cloak class="rounded-xl border border-dashed border-slate-200 p-6 text-sm text-slate-400 text-center">
                        No material subjects found for this medium and standard.
                    </div>

                    {{-- Native multi-select for Std 11 & 12 --}}
                    <div x-show="medium && standardId && isMultiSelectStandard() && availableSubjects().length" x-cloak class="mb-4">
                        <label class="admin-label">Multi-select list (Std <span x-text="standardNumber()"></span>)</label>
                        <select multiple
                                size="8"
                                class="admin-select min-h-[12rem]"
                                x-model="selected">
                            <template x-for="subject in availableSubjects()" :key="'ms-'+subject.id">
                                <option :value="String(subject.id)" x-text="subject.name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-slate-400 mt-1">Hold Ctrl (Windows) or Cmd (Mac) to select multiple subjects. On mobile, use the subject cards below.</p>
                    </div>

                    <div x-show="medium && standardId && currentSubjects().length" class="grid sm:grid-cols-2 gap-3">
                        <template x-for="subject in currentSubjects()" :key="subject.id">
                            <button type="button"
                                    @click="toggle(subject)"
                                    :disabled="!!subject.taken_by"
                                    class="text-left rounded-xl border px-4 py-3 transition"
                                    :class="subject.taken_by
                                        ? 'border-slate-200 bg-slate-50 opacity-70 cursor-not-allowed'
                                        : (isChecked(subject.id)
                                            ? 'border-brand-green bg-brand-green-50 ring-1 ring-brand-green'
                                            : 'border-slate-200 bg-white hover:border-brand-green-200')">
                                <span class="flex items-start justify-between gap-3">
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-900" x-text="subject.name"></span>
                                        <span class="block text-xs mt-1" x-show="subject.taken_by" x-cloak>
                                            Assigned to <span class="font-semibold text-amber-800" x-text="subject.taken_by"></span>
                                        </span>
                                        <span class="block text-xs mt-1 text-brand-green" x-show="!subject.taken_by && isChecked(subject.id)">
                                            Selected for you
                                        </span>
                                    </span>
                                    <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded border"
                                          :class="isChecked(subject.id) && !subject.taken_by ? 'bg-brand-green border-brand-green text-white' : 'border-slate-300 bg-white text-transparent'">
                                        ✓
                                    </span>
                                </span>
                            </button>
                        </template>
                    </div>
                    @error('subject_ids')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="admin-btn-primary"
                            :disabled="!medium || !standardId || (isMultiSelectStandard() && selected.length === 0)">
                        Save assignment
                    </button>
                    <a href="{{ route('teacher.dashboard') }}" class="admin-btn-ghost">Back to dashboard</a>
                </div>
            </form>
        </div>
    </div>
</x-teacher-layout>
