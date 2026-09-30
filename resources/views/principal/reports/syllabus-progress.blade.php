<x-principal-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Reports</span>
            <h2 class="admin-page-title">Syllabus Progress Report</h2>
            <p class="admin-page-subtitle">Teacher → subjects → topics complete / remain with % (your allotted standards)</p>
        </div>
    </x-slot>

    <div class="admin-page space-y-5">
        @include('principal.partials.reports-nav')

        @if (! ($hasAllotments ?? false))
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                No standard allotted yet. Ask admin to allot standards first.
            </div>
        @else
            <div class="grid sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7 gap-4">
                @include('admin.partials.stat-card', ['label' => 'Teachers', 'value' => $summary['teachers']])
                @include('admin.partials.stat-card', ['label' => 'Subjects', 'value' => $summary['subjects']])
                @include('admin.partials.stat-card', ['label' => 'Topics total', 'value' => $summary['topics_total']])
                @include('admin.partials.stat-card', ['label' => 'Complete', 'value' => $summary['topics_complete']])
                @include('admin.partials.stat-card', ['label' => 'Remain', 'value' => $summary['topics_remain']])
                @include('admin.partials.stat-card', ['label' => '% Complete', 'value' => $summary['percent_complete'].'%'])
                @include('admin.partials.stat-card', ['label' => '% Remain', 'value' => $summary['percent_remain'].'%'])
            </div>

            <div class="admin-card">
                <div class="admin-card-top"></div>
                <div class="admin-card-body">
                    <form method="GET" class="admin-filter-grid">
                        <div class="lg:col-span-3">
                            <label class="admin-label">Search teacher</label>
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
                        <div class="lg:col-span-2">
                            <label class="admin-label">Standard</label>
                            <select name="standard_id" class="admin-select">
                                <option value="">All allotted</option>
                                @foreach ($standards as $standard)
                                    <option value="{{ $standard->id }}" @selected((string) ($filters['standard_id'] ?? '') === (string) $standard->id)>{{ $standard->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lg:col-span-3">
                            <label class="admin-label">Teacher</label>
                            <select name="teacher_id" class="admin-select">
                                <option value="">All teachers</option>
                                @foreach ($teacherOptions as $teacher)
                                    <option value="{{ $teacher->id }}" @selected((string) ($filters['teacher_id'] ?? '') === (string) $teacher->id)>{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lg:col-span-2 flex gap-2 items-end">
                            <button type="submit" class="admin-btn-filter flex-1">Filter</button>
                            @if (! empty(array_filter($filters ?? [])))
                                <a href="{{ route('principal.reports.syllabus-progress') }}" class="admin-btn-ghost">Clear</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <div>
                        <h3 class="font-bold text-slate-900">Teacher syllabus completion</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Complete = topics marked completed in daily syllabus · Remain = syllabus topics not yet completed
                        </p>
                    </div>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Teacher</th>
                                <th>Subjects</th>
                                <th>Subject</th>
                                <th>Standard</th>
                                <th>Medium</th>
                                <th class="text-right">Topics</th>
                                <th class="text-right">Complete</th>
                                <th class="text-right">Remain</th>
                                <th class="text-right">% Complete</th>
                                <th class="text-right">% Remain</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($teachers as $teacherGroup)
                                @foreach ($teacherGroup['rows'] as $row)
                                    <tr>
                                        @if ($loop->first)
                                            <td rowspan="{{ $teacherGroup['rows']->count() }}" class="align-top">
                                                <p class="font-semibold text-slate-900">{{ $teacherGroup['teacher'] }}</p>
                                                <p class="text-xs text-slate-400">{{ $teacherGroup['mobile'] ?: '' }}</p>
                                                <p class="text-xs text-brand-green font-semibold mt-1">
                                                    {{ $teacherGroup['subjects'] }} subject(s) · {{ $teacherGroup['percent_complete'] }}% done
                                                </p>
                                            </td>
                                            <td rowspan="{{ $teacherGroup['rows']->count() }}" class="align-top text-center font-semibold">
                                                {{ $teacherGroup['subjects'] }}
                                            </td>
                                        @endif
                                        <td class="font-medium text-slate-800">{{ $row['subject'] }}</td>
                                        <td class="text-sm">{{ $row['standard'] }}</td>
                                        <td class="text-sm capitalize">{{ $row['medium'] }}</td>
                                        <td class="text-right font-semibold">{{ $row['topics_total'] }}</td>
                                        <td class="text-right text-emerald-700 font-semibold">{{ $row['topics_complete'] }}</td>
                                        <td class="text-right text-amber-700 font-semibold">{{ $row['topics_remain'] }}</td>
                                        <td class="text-right">
                                            <div class="inline-flex flex-col items-end gap-1 min-w-[4.5rem]">
                                                <span class="font-bold text-slate-900">{{ $row['percent_complete'] }}%</span>
                                                <span class="block h-1.5 w-16 rounded-full bg-slate-100 overflow-hidden">
                                                    <span class="block h-full rounded-full {{ $row['status'] === 'good' ? 'bg-emerald-500' : ($row['status'] === 'warn' ? 'bg-amber-400' : ($row['status'] === 'empty' ? 'bg-slate-300' : 'bg-rose-400')) }}"
                                                          style="width: {{ min(100, $row['percent_complete']) }}%"></span>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="text-right font-semibold text-slate-600">{{ $row['percent_remain'] }}%</td>
                                        <td>
                                            @if ($row['status'] === 'good')
                                                <span class="admin-badge-green">On track</span>
                                            @elseif ($row['status'] === 'warn')
                                                <span class="admin-badge-gold">In progress</span>
                                            @elseif ($row['status'] === 'empty')
                                                <span class="admin-badge-slate">No topics</span>
                                            @else
                                                <span class="rounded-full bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-1 text-xs font-semibold">Behind</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                @include('admin.partials.empty-row', [
                                    'colspan' => 11,
                                    'message' => 'No teacher subject allotments found',
                                    'hint' => 'Teachers need subjects allotted; progress comes from daily syllabus complete marks.',
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-principal-layout>
