<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Teacher Subject Report" subtitle="Teacher-wise subject selection list from Settings" />
    </x-slot>

    <div class="admin-page">
        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @include('admin.partials.stat-card', ['label' => 'Subject Selections', 'value' => $summary['assignments']])
            @include('admin.partials.stat-card', ['label' => 'Teachers With Subjects', 'value' => $summary['with_subjects']])
            @include('admin.partials.stat-card', ['label' => 'Teachers Without Subjects', 'value' => $summary['without_subjects']])
            @include('admin.partials.stat-card', ['label' => 'Unique Subjects', 'value' => $summary['unique_subjects']])
        </div>

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-body">
                <form method="GET" class="admin-filter-grid">
                    <div class="lg:col-span-4">
                        <label class="admin-label">Search teacher</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, mobile or email..." class="admin-input">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Medium</label>
                        <select name="medium" class="admin-select">
                            <option value="">All mediums</option>
                            <option value="english" @selected(($filters['medium'] ?? '') === 'english')>English</option>
                            <option value="gujarati" @selected(($filters['medium'] ?? '') === 'gujarati')>Gujarati</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Standard</label>
                        <select name="standard_id" class="admin-select">
                            <option value="">All standards</option>
                            @foreach ($standards as $standard)
                                <option value="{{ $standard->id }}" @selected((string) ($filters['standard_id'] ?? '') === (string) $standard->id)>{{ $standard->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Assignment</label>
                        <select name="assignment" class="admin-select">
                            <option value="">All teachers</option>
                            <option value="assigned" @selected(($filters['assignment'] ?? '') === 'assigned')>With subjects</option>
                            <option value="unassigned" @selected(($filters['assignment'] ?? '') === 'unassigned')>Without subjects</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2 flex gap-2 items-end">
                        <button type="submit" class="admin-btn-filter flex-1">Filter</button>
                        @if (! empty(array_filter($filters ?? [])))
                            <a href="{{ route('admin.reports.teacher-subjects') }}" class="admin-btn-ghost">Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Contact</th>
                            <th>Subjects selected</th>
                            <th class="text-right">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($teachers as $teacher)
                            @php
                                $rows = $teacher->teacherSubjects;
                            @endphp
                            <tr>
                                <td>
                                    <div class="admin-user-cell">
                                        <span class="admin-avatar">{{ strtoupper(substr($teacher->name, 0, 2)) }}</span>
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $teacher->name }}</p>
                                            <a href="{{ route('admin.teachers.show', $teacher) }}" class="text-xs text-brand-green hover:underline">View profile</a>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-sm">
                                    <p>{{ $teacher->mobile ?: '—' }}</p>
                                    <p class="text-xs text-slate-400">{{ $teacher->email ?: '' }}</p>
                                </td>
                                <td>
                                    @if ($rows->isEmpty())
                                        <span class="text-sm text-slate-400">No subjects selected</span>
                                    @else
                                        <div class="flex flex-wrap gap-1.5 max-w-xl">
                                            @foreach ($rows as $row)
                                                <span class="inline-flex items-center gap-1 rounded-full border border-brand-green-100 bg-brand-green-50 px-2.5 py-1 text-xs font-medium text-slate-800">
                                                    <span class="capitalize text-brand-green">{{ $row->medium ?: '—' }}</span>
                                                    <span class="text-slate-300">·</span>
                                                    <span>{{ $row->standard?->name ?? 'Std' }}</span>
                                                    <span class="text-slate-300">·</span>
                                                    <span class="font-semibold">{{ $row->subject?->name ?? 'Subject' }}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <span class="admin-badge-green">{{ $rows->count() }}</span>
                                </td>
                            </tr>
                        @empty
                            @include('admin.partials.empty-row', [
                                'colspan' => 4,
                                'message' => 'No teachers found',
                                'hint' => 'Try clearing filters or check teacher subject selections in Settings.',
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($teachers->hasPages())
                <div class="p-4">{{ $teachers->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
