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

            <div class="admin-card overflow-hidden">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <div>
                        <h3 class="font-bold text-slate-900">Teacher syllabus completion</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Complete / Remain from teacher <strong>logout report</strong> topics · Total = book material topics
                        </p>
                    </div>
                    <div class="hidden sm:flex items-center gap-2 text-[11px] text-slate-500 report-print-hide">
                        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-slate-50 border border-slate-200"></span> Teacher A</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-brand-green-50 border border-brand-green-100"></span> Teacher B</span>
                    </div>
                </div>
                <div class="admin-table-wrap admin-table-wrap--sticky">
                    <table class="admin-table syllabus-progress-table">
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
                                @php
                                    $band = $loop->even ? 'syllabus-teacher-band--a' : 'syllabus-teacher-band--b';
                                    $rowCount = $teacherGroup['rows']->count();
                                @endphp
                                @foreach ($teacherGroup['rows'] as $row)
                                    <tr class="{{ $band }}">
                                        @if ($loop->first)
                                            <td rowspan="{{ $rowCount }}" class="align-top syllabus-teacher-cell">
                                                <div class="flex items-start gap-3">
                                                    <span class="admin-avatar shrink-0">{{ strtoupper(substr($teacherGroup['teacher'], 0, 2)) }}</span>
                                                    <div class="min-w-0">
                                                        <p class="font-semibold text-slate-900 leading-snug">{{ $teacherGroup['teacher'] }}</p>
                                                        <p class="text-xs text-slate-400 mt-0.5">{{ $teacherGroup['mobile'] ?: '—' }}</p>
                                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                                            <span class="inline-flex items-center rounded-full bg-white/80 border border-slate-200 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
                                                                {{ $teacherGroup['subjects'] }} subject{{ $teacherGroup['subjects'] === 1 ? '' : 's' }}
                                                            </span>
                                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-bold
                                                                {{ $teacherGroup['percent_complete'] >= 80 ? 'bg-emerald-100 text-emerald-800' : ($teacherGroup['percent_complete'] >= 40 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                                                {{ $teacherGroup['percent_complete'] }}% done
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td rowspan="{{ $rowCount }}" class="align-middle text-center">
                                                <span class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-xl bg-white border border-slate-200 text-sm font-bold text-brand-green shadow-sm">
                                                    {{ $teacherGroup['subjects'] }}
                                                </span>
                                            </td>
                                        @endif
                                        <td>
                                            <span class="font-semibold text-slate-800">{{ $row['subject'] }}</span>
                                        </td>
                                        <td>
                                            <span class="inline-flex rounded-full bg-white/70 border border-slate-200 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                                                {{ $row['standard'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize
                                                {{ strtolower($row['medium']) === 'gujarati' ? 'bg-amber-50 text-amber-800 border border-amber-100' : 'bg-sky-50 text-sky-800 border border-sky-100' }}">
                                                {{ $row['medium'] }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <span class="inline-flex min-w-[2rem] justify-end rounded-lg bg-slate-900/5 px-2 py-1 text-sm font-bold text-slate-800">{{ $row['topics_total'] }}</span>
                                        </td>
                                        <td class="text-right">
                                            <span class="inline-flex min-w-[2rem] justify-end rounded-lg bg-emerald-50 px-2 py-1 text-sm font-bold text-emerald-700">{{ $row['topics_complete'] }}</span>
                                        </td>
                                        <td class="text-right">
                                            <span class="inline-flex min-w-[2rem] justify-end rounded-lg bg-amber-50 px-2 py-1 text-sm font-bold text-amber-700">{{ $row['topics_remain'] }}</span>
                                        </td>
                                        <td class="text-right">
                                            <div class="inline-flex flex-col items-end gap-1.5 min-w-[5rem]">
                                                <span class="text-sm font-bold text-slate-900">{{ $row['percent_complete'] }}%</span>
                                                <span class="block h-2 w-20 rounded-full bg-white/80 border border-slate-200/80 overflow-hidden shadow-inner">
                                                    <span class="block h-full rounded-full transition-all
                                                        {{ $row['status'] === 'good' ? 'bg-emerald-500' : ($row['status'] === 'warn' ? 'bg-amber-400' : ($row['status'] === 'empty' ? 'bg-slate-300' : 'bg-rose-500')) }}"
                                                          style="width: {{ min(100, $row['percent_complete']) }}%"></span>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <span class="text-sm font-semibold text-slate-600">{{ $row['percent_remain'] }}%</span>
                                        </td>
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
                                    'hint' => 'Teachers need subjects allotted; progress comes from logout topic complete/remain.',
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    @once
        <style>
            .syllabus-progress-table tbody tr.syllabus-teacher-band--a > td {
                background-color: rgb(248, 250, 252); /* slate-50 */
            }
            .syllabus-progress-table tbody tr.syllabus-teacher-band--b > td {
                background-color: rgb(240, 243, 250); /* brand-green-50 */
            }
            .syllabus-progress-table tbody tr.syllabus-teacher-band--a:hover > td,
            .syllabus-progress-table tbody tr.syllabus-teacher-band--b:hover > td {
                background-color: rgb(214, 222, 240); /* brand-green-100 */
            }
            .syllabus-progress-table tbody tr.syllabus-teacher-band--a .syllabus-teacher-cell {
                border-left: 4px solid rgb(148, 163, 184); /* slate-400 */
            }
            .syllabus-progress-table tbody tr.syllabus-teacher-band--b .syllabus-teacher-cell {
                border-left: 4px solid rgb(26, 54, 124); /* brand-green */
            }
            .syllabus-progress-table tbody tr > td {
                border-bottom-color: rgba(148, 163, 184, 0.25);
            }
            @media print {
                .syllabus-progress-table tbody tr.syllabus-teacher-band--a > td,
                .syllabus-progress-table tbody tr.syllabus-teacher-band--b > td {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
            }
        </style>
    @endonce
</x-principal-layout>
