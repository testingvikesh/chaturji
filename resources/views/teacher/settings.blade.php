<x-teacher-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Teacher</span>
            <h2 class="admin-page-title">Settings</h2>
            <p class="admin-page-subtitle">Select medium, then standard, then subjects. For Standard 11 &amp; 12, up to <strong>2 teachers</strong> can share the same subject. Other standards stay 1 teacher per subject.</p>
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
            selectedSubjects() {
                return this.selected
                    .map(id => this.currentSubjects().find(s => String(s.id) === String(id)))
                    .filter(Boolean);
            },
            standardMeta() {
                return (this.form.standards || []).find(s => String(s.id) === String(this.standardId)) || null;
            },
            standardNumber() {
                return this.standardMeta()?.grade || 0;
            },
            isSharedStandard() {
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
            editGroup(medium, standardId) {
                this.medium = medium || '';
                this.standardId = String(standardId || '');
                this.selected = this.currentSubjects().filter(s => s.mine).map(s => String(s.id));
                this.$nextTick(() => {
                    document.getElementById('assignment-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            },
            removeSelected(id) {
                this.selected = this.selected.filter(x => String(x) !== String(id));
            },
            toggle(subject) {
                if (subject.locked) return;
                const id = String(subject.id);
                if (this.selected.includes(id)) {
                    this.selected = this.selected.filter(x => x !== id);
                } else {
                    this.selected = [...this.selected, id];
                }
            },
            isChecked(id) {
                return this.selected.includes(String(id));
            }
         }">

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">Your assigned medium, standard & subjects</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Use Edit to change subjects, or remove a subject with the × on each chip</p>
                </div>
            </div>
            <div class="p-5">
                @if ($assignedGroups->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/70 px-4 py-8 text-center">
                        <p class="text-sm font-medium text-slate-600">Nothing saved yet</p>
                        <p class="mt-1 text-xs text-slate-400">Select medium, standard and subjects in the form below.</p>
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
                                        <p class="mt-1 text-xs text-slate-500">Saved assignment</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-brand-green-100 bg-white px-3 py-1.5 text-xs font-semibold text-brand-green hover:bg-brand-green-50 transition"
                                                @click="editGroup(@js($mediumKey), @js((string) $first?->standard_id))">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z"/>
                                            </svg>
                                            Edit
                                        </button>
                                        <form method="POST"
                                              action="{{ route('teacher.settings.groups.destroy') }}"
                                              onsubmit="return confirm('Clear all subjects for {{ $mediumLabel }} · {{ $standardName }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="medium" value="{{ $mediumKey }}">
                                            <input type="hidden" name="standard_id" value="{{ $first?->standard_id }}">
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-100 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 transition">
                                                Clear
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($rows as $row)
                                        @php $subjectName = $row->subject?->name ?? 'Subject'; @endphp
                                        <div class="group inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white py-1.5 pl-3.5 pr-1.5 text-sm font-semibold text-slate-800 shadow-sm">
                                            <span class="leading-none">{{ $subjectName }}</span>
                                            <form method="POST"
                                                  action="{{ route('teacher.settings.subjects.destroy', $row) }}"
                                                  class="inline-flex"
                                                  onsubmit="return confirm('Remove {{ $subjectName }} from {{ $mediumLabel }} · {{ $standardName }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-red-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-red-200"
                                                        title="Remove {{ $subjectName }}"
                                                        aria-label="Remove {{ $subjectName }}">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                                                    </svg>
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
                <input type="hidden" name="standard_id" :value="standardId">
                <template x-for="id in selected" :key="'sid-'+id">
                    <input type="hidden" name="subject_ids[]" :value="id">
                </template>

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
                    <label class="admin-label">2. Select standard</label>
                    <select class="admin-select" x-model="standardId" @change="chooseStandard(standardId)" :disabled="!medium" required>
                        <option value="" x-text="medium ? 'Choose standard' : 'Select medium first'"></option>
                        <template x-for="std in form.standards" :key="std.id">
                            <option :value="String(std.id)" x-text="std.name"></option>
                        </template>
                    </select>
                    @error('standard_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    <p class="text-xs text-brand-green mt-2" x-show="isSharedStandard()" x-cloak>
                        Standard <span x-text="standardNumber()"></span>: same subject can be selected by up to 2 teachers.
                    </p>
                </div>

                <div>
                    <label class="admin-label">3. Select subjects</label>
                    <p class="text-xs text-slate-500 mb-3"
                       x-text="isSharedStandard()
                           ? 'Tap cards to select. For Std ' + standardNumber() + ', up to 2 teachers may share one subject.'
                           : 'Locked subjects already belong to another teacher for this medium.'"></p>

                    <div x-show="!medium" class="rounded-xl border border-dashed border-slate-200 p-6 text-sm text-slate-400 text-center">
                        Choose a medium first.
                    </div>
                    <div x-show="medium && !standardId" x-cloak class="rounded-xl border border-dashed border-slate-200 p-6 text-sm text-slate-400 text-center">
                        Choose a standard to see subjects.
                    </div>
                    <div x-show="medium && standardId && currentSubjects().length === 0" x-cloak class="rounded-xl border border-dashed border-slate-200 p-6 text-sm text-slate-400 text-center">
                        No material subjects found for this medium and standard.
                    </div>

                    <div x-show="medium && standardId && selected.length" x-cloak class="mb-4 rounded-2xl border border-brand-green-100 bg-brand-green-50/50 p-3">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <p class="text-xs font-bold uppercase tracking-wide text-brand-green">
                                Selected · <span x-text="selected.length"></span>
                            </p>
                            <button type="button"
                                    class="text-xs font-semibold text-slate-500 hover:text-red-600 transition"
                                    @click="selected = []"
                                    x-show="selected.length">
                                Clear selected
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="subject in selectedSubjects()" :key="'chip-'+subject.id">
                                <div class="inline-flex items-center gap-2 rounded-full border border-brand-green-200 bg-white py-1.5 pl-3.5 pr-1.5 text-sm font-semibold text-slate-800 shadow-sm">
                                    <span class="leading-none" x-text="subject.name"></span>
                                    <button type="button"
                                            class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-red-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-red-200"
                                            @click="removeSelected(subject.id)"
                                            title="Remove"
                                            aria-label="Remove selected subject">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div x-show="medium && standardId && currentSubjects().length" class="grid sm:grid-cols-2 gap-3">
                        <template x-for="subject in currentSubjects()" :key="subject.id">
                            <button type="button"
                                    @click="toggle(subject)"
                                    :disabled="!!subject.locked"
                                    class="text-left rounded-xl border px-4 py-3 transition"
                                    :class="subject.locked
                                        ? 'border-slate-200 bg-slate-50 opacity-70 cursor-not-allowed'
                                        : (isChecked(subject.id)
                                            ? 'border-brand-green bg-brand-green-50 ring-1 ring-brand-green'
                                            : 'border-slate-200 bg-white hover:border-brand-green-200')">
                                <span class="flex items-start justify-between gap-3">
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-900" x-text="subject.name"></span>
                                        <span class="block text-xs mt-1 text-slate-500" x-show="subject.slots_max > 1" x-cloak>
                                            Teachers: <span x-text="subject.slots_used"></span>/<span x-text="subject.slots_max"></span>
                                            <span x-show="subject.teacher_names && subject.teacher_names.length" x-cloak>
                                                · <span x-text="subject.teacher_names.join(', ')"></span>
                                            </span>
                                        </span>
                                        <span class="block text-xs mt-1" x-show="subject.locked" x-cloak>
                                            Full — <span class="font-semibold text-amber-800" x-text="subject.taken_by"></span>
                                        </span>
                                        <span class="block text-xs mt-1 text-brand-green" x-show="!subject.locked && isChecked(subject.id)">
                                            Selected for you
                                        </span>
                                        <span class="block text-xs mt-1 text-amber-700" x-show="!subject.locked && !isChecked(subject.id) && subject.teacher_names && subject.teacher_names.length" x-cloak>
                                            Also taken by <span x-text="subject.teacher_names.join(', ')"></span> (you can still join)
                                        </span>
                                    </span>
                                    <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded border"
                                          :class="isChecked(subject.id) && !subject.locked ? 'bg-brand-green border-brand-green text-white' : 'border-slate-300 bg-white text-transparent'">
                                        ✓
                                    </span>
                                </span>
                            </button>
                        </template>
                    </div>
                    @error('subject_ids')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="admin-btn-primary" :disabled="!medium || !standardId">Save assignment</button>
                    <a href="{{ route('teacher.dashboard') }}" class="admin-btn-ghost">Back to dashboard</a>
                </div>
            </form>
        </div>
    </div>
</x-teacher-layout>
