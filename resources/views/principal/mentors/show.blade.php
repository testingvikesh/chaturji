<x-principal-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Mentor</span>
            <h2 class="admin-page-title">{{ $teacher->name }}</h2>
            <p class="admin-page-subtitle">Select students by standard &amp; medium — this teacher becomes their mentor ({{ $menteeCount }} currently)</p>
        </div>
    </x-slot>

    <div class="admin-page" x-data="{
        selected: @js(collect($assignedIds)->map(fn ($id) => (string) $id)->values()->all()),
        allVisible: @js($students->pluck('id')->map(fn ($id) => (string) $id)->values()->all()),
        toggleAll() {
            const allSelected = this.allVisible.length > 0 && this.allVisible.every(id => this.selected.includes(id));
            if (allSelected) {
                this.selected = this.selected.filter(id => !this.allVisible.includes(id));
            } else {
                this.selected = Array.from(new Set([...this.selected, ...this.allVisible]));
            }
        }
    }">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <div class="mb-4">
            <a href="{{ route('principal.mentors.index') }}" class="text-sm text-brand-green hover:underline font-medium">&larr; Back to Mentors</a>
        </div>

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-body">
                <form method="GET" class="admin-filter-grid">
                    <div class="lg:col-span-3">
                        <label class="admin-label">Search student</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, mobile..." class="admin-input">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Medium</label>
                        <select name="medium" class="admin-select">
                            <option value="">All mediums</option>
                            <option value="english" @selected(($filters['medium'] ?? '') === 'english')>English</option>
                            <option value="gujarati" @selected(($filters['medium'] ?? '') === 'gujarati')>Gujarati</option>
                        </select>
                    </div>
                    <div class="lg:col-span-3">
                        <label class="admin-label">Standard</label>
                        <select name="standard" class="admin-select">
                            <option value="">All allotted standards</option>
                            @foreach ($standards as $standard)
                                <option value="{{ $standard->slug }}" @selected(($filters['standard'] ?? '') === $standard->slug)>{{ $standard->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-2 flex items-end">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 pb-2">
                            <input type="checkbox" name="only_mine" value="1" @checked($filters['only_mine'] ?? false) class="rounded border-slate-300 text-brand-green">
                            Only this mentor’s students
                        </label>
                    </div>
                    <div class="lg:col-span-2 flex gap-2 items-end">
                        <button type="submit" class="admin-btn-filter flex-1">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <form method="POST" action="{{ route('principal.mentors.update', $teacher) }}"
              onsubmit="return confirm('Save mentor students for {{ $teacher->name }}?');">
            @csrf
            @method('PUT')
            <input type="hidden" name="medium" value="{{ $filters['medium'] ?? '' }}">
            <input type="hidden" name="standard" value="{{ $filters['standard'] ?? '' }}">
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="student_ids[]" :value="id">
            </template>

            <div class="admin-card mb-4">
                <div class="admin-card-body flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-slate-600">
                        Checked students are mentored by <strong>{{ $teacher->name }}</strong>.
                        Filter by standard / medium, then select. Uncheck to remove (within current filter only).
                    </p>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-slate-700">Selected: <span x-text="selected.length"></span></span>
                        <button type="submit" class="admin-btn-primary">Save mentor students</button>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <div>
                        <h3 class="font-bold text-slate-900">Students</h3>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $students->count() }} shown · your allotted standards</p>
                    </div>
                </div>
                <div class="admin-table-wrap admin-table-wrap--sticky">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="w-10">
                                    <input type="checkbox" class="rounded border-slate-300 text-brand-green" @click="toggleAll()"
                                           :checked="allVisible.length > 0 && allVisible.every(id => selected.includes(id))">
                                </th>
                                <th>Student</th>
                                <th>Mobile</th>
                                <th>Standard</th>
                                <th>Medium</th>
                                <th>Mentor status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $student)
                                @php
                                    $sid = (string) $student->id;
                                    $isMine = in_array((int) $student->id, $assignedIds, true);
                                    $other = $otherMentors[$student->id] ?? null;
                                @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox"
                                               class="rounded border-slate-300 text-brand-green"
                                               value="{{ $student->id }}"
                                               x-model="selected">
                                    </td>
                                    <td>
                                        <div class="admin-user-cell">
                                            <span class="admin-avatar">{{ strtoupper(substr($student->name, 0, 2)) }}</span>
                                            <p class="font-semibold text-slate-900">{{ $student->name }}</p>
                                        </div>
                                    </td>
                                    <td>{{ $student->mobile ?: '—' }}</td>
                                    <td>{{ $standardNames[$student->standard] ?? $student->standardLabel() }}</td>
                                    <td class="capitalize">{{ $student->medium ?: '—' }}</td>
                                    <td>
                                        @if ($isMine)
                                            <span class="admin-badge-green">This mentor</span>
                                        @elseif ($other)
                                            <span class="admin-badge-gold">{{ $other->teacher?->name ?? 'Other teacher' }}</span>
                                        @else
                                            <span class="text-sm text-slate-400">No mentor</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-row', [
                                    'colspan' => 6,
                                    'message' => 'No students for this filter',
                                    'hint' => 'Choose standard and medium, or clear filters.',
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
</x-principal-layout>
