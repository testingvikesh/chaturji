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
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white border border-brand-green-100 pl-3 pr-1 py-1 text-xs font-semibold text-slate-700">
                                            {{ $row->subject?->name ?? 'Subject' }}
                                            <form method="POST" action="{{ route('teacher.settings.subjects.destroy', $row) }}" class="inline" onsubmit="return confirm('Remove {{ $row->subject?->name ?? 'this subject' }} from your assignment?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex h-5 w-5 items-center justify-center rounded-full text-slate-400 hover:bg-red-50 hover:text-red-600"
                                                        title="Remove {{ $row->subject?->name ?? 'subject' }}"
                                                        aria-label="Remove {{ $row->subject?->name ?? 'subject' }}">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                                                    </svg>
                                                </button>
                                            </form>
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
                           ? 'Tap to select. For Std ' + standardNumber() + ', up to 2 teachers may share one subject.'
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

                    <div x-show="medium && standardId && selected.length" x-cloak class="mb-3">
                        <p class="text-xs font-semibold text-slate-600 mb-2">Selected (click × to remove)</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="id in selected" :key="'chip-'+id">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-green-50 border border-brand-green-100 pl-3 pr-1 py-1 text-xs font-semibold text-brand-green">
                                    <span x-text="(currentSubjects().find(s => String(s.id) === String(id)) || {}).name || ('#'+id)"></span>
                                    <button type="button"
                                            class="inline-flex h-5 w-5 items-center justify-center rounded-full text-brand-green/70 hover:bg-white hover:text-red-600"
                                            @click="selected = selected.filter(x => x !== id)"
                                            title="Remove"
                                            aria-label="Remove selected subject">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                                        </svg>
                                    </button>
                                </span>
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
