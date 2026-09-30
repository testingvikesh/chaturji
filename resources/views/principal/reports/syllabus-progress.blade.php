<x-principal-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Reports</span>
            <h2 class="admin-page-title">Syllabus Progress Report</h2>
            <p class="admin-page-subtitle">Teacher → subjects → topics complete / remain with % (your allotted standards)</p>
        </div>
    </x-slot>

    <div class="admin-page space-y-5 syllabus-progress-page">
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

            {{-- Sticky column legend while scrolling teacher cards --}}
            <div class="syllabus-sticky-legend report-print-hide">
                <div class="syllabus-sticky-legend-inner">
                    <span class="syllabus-col syllabus-col-subject">Subject</span>
                    <span class="syllabus-col syllabus-col-std">Standard</span>
                    <span class="syllabus-col syllabus-col-med">Medium</span>
                    <span class="syllabus-col syllabus-col-num">Topics</span>
                    <span class="syllabus-col syllabus-col-num">Complete</span>
                    <span class="syllabus-col syllabus-col-num">Remain</span>
                    <span class="syllabus-col syllabus-col-pct">% Complete</span>
                    <span class="syllabus-col syllabus-col-pct">% Remain</span>
                    <span class="syllabus-col syllabus-col-status">Status</span>
                </div>
            </div>

            <div class="space-y-4">
                @forelse ($teachers as $teacherGroup)
                    @php
                        $band = $loop->iteration % 2 === 1 ? 'syllabus-card--a' : 'syllabus-card--b';
                        $pct = (int) $teacherGroup['percent_complete'];
                        $pctClass = $pct >= 80 ? 'good' : ($pct >= 40 ? 'warn' : ($teacherGroup['topics_total'] === 0 ? 'empty' : 'low'));
                    @endphp
                    <article class="syllabus-card {{ $band }}">
                        <header class="syllabus-card-head">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="syllabus-avatar">{{ strtoupper(substr($teacherGroup['teacher'], 0, 2)) }}</span>
                                <div class="min-w-0">
                                    <h3 class="text-base font-bold text-slate-900 truncate">{{ $teacherGroup['teacher'] }}</h3>
                                    <p class="text-xs text-slate-500">{{ $teacherGroup['mobile'] ?: '—' }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="syllabus-chip">{{ $teacherGroup['subjects'] }} subject{{ $teacherGroup['subjects'] === 1 ? '' : 's' }}</span>
                                <span class="syllabus-chip">{{ $teacherGroup['topics_complete'] }}/{{ $teacherGroup['topics_total'] }} topics</span>
                                <span class="syllabus-chip syllabus-chip--{{ $pctClass }}">{{ $pct }}% done</span>
                            </div>
                            <div class="syllabus-head-bar" aria-hidden="true">
                                <span style="width: {{ min(100, $pct) }}%"></span>
                            </div>
                        </header>

                        <div class="syllabus-card-body">
                            @foreach ($teacherGroup['rows'] as $row)
                                <div class="syllabus-row">
                                    <div class="syllabus-col syllabus-col-subject">
                                        <span class="font-semibold text-slate-900">{{ $row['subject'] }}</span>
                                    </div>
                                    <div class="syllabus-col syllabus-col-std">
                                        <span class="syllabus-pill">{{ $row['standard'] }}</span>
                                    </div>
                                    <div class="syllabus-col syllabus-col-med">
                                        <span class="syllabus-pill syllabus-pill--{{ strtolower($row['medium']) === 'gujarati' ? 'guj' : 'eng' }}">
                                            {{ $row['medium'] }}
                                        </span>
                                    </div>
                                    <div class="syllabus-col syllabus-col-num">
                                        <span class="syllabus-metric">{{ $row['topics_total'] }}</span>
                                        <span class="syllabus-metric-label">Topics</span>
                                    </div>
                                    <div class="syllabus-col syllabus-col-num">
                                        <span class="syllabus-metric syllabus-metric--ok">{{ $row['topics_complete'] }}</span>
                                        <span class="syllabus-metric-label">Complete</span>
                                    </div>
                                    <div class="syllabus-col syllabus-col-num">
                                        <span class="syllabus-metric syllabus-metric--warn">{{ $row['topics_remain'] }}</span>
                                        <span class="syllabus-metric-label">Remain</span>
                                    </div>
                                    <div class="syllabus-col syllabus-col-pct">
                                        <div class="syllabus-pct">
                                            <strong>{{ $row['percent_complete'] }}%</strong>
                                            <span class="syllabus-pct-track"><i style="width: {{ min(100, $row['percent_complete']) }}%" class="is-{{ $row['status'] }}"></i></span>
                                        </div>
                                    </div>
                                    <div class="syllabus-col syllabus-col-pct">
                                        <strong class="text-slate-600">{{ $row['percent_remain'] }}%</strong>
                                    </div>
                                    <div class="syllabus-col syllabus-col-status">
                                        @if ($row['status'] === 'good')
                                            <span class="admin-badge-green">On track</span>
                                        @elseif ($row['status'] === 'warn')
                                            <span class="admin-badge-gold">In progress</span>
                                        @elseif ($row['status'] === 'empty')
                                            <span class="admin-badge-slate">No topics</span>
                                        @else
                                            <span class="rounded-full bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-1 text-xs font-semibold">Behind</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <div class="admin-card">
                        <div class="p-8 text-center text-sm text-slate-500">
                            No teacher subject allotments found. Teachers need subjects allotted; progress comes from logout topic complete/remain.
                        </div>
                    </div>
                @endforelse
            </div>
        @endif
    </div>

    @once
        <style>
            .syllabus-sticky-legend {
                position: sticky;
                top: 3.5rem; /* mobile top bar */
                z-index: 30;
                margin-bottom: 0.25rem;
            }
            @media (min-width: 1024px) {
                .syllabus-sticky-legend { top: 0; }
            }
            .syllabus-sticky-legend-inner {
                display: none;
                align-items: center;
                gap: 0.5rem;
                padding: 0.7rem 1rem;
                border-radius: 0.9rem;
                background: linear-gradient(90deg, rgb(26, 54, 124), rgb(20, 42, 98));
                color: rgba(255,255,255,0.95);
                font-size: 0.68rem;
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                box-shadow: 0 8px 20px rgba(13, 31, 74, 0.25);
            }
            @media (min-width: 1024px) {
                .syllabus-sticky-legend-inner { display: flex; }
            }

            .syllabus-card {
                border-radius: 1rem;
                border: 1px solid rgba(148, 163, 184, 0.35);
                overflow: hidden;
                box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
            }
            .syllabus-card--a {
                background: #fff;
                border-left: 5px solid rgb(148, 163, 184);
            }
            .syllabus-card--b {
                background: rgb(240, 243, 250);
                border-left: 5px solid rgb(26, 54, 124);
            }

            .syllabus-card-head {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem 1rem;
                padding: 0.95rem 1.1rem;
                border-bottom: 1px solid rgba(148, 163, 184, 0.25);
                background: rgba(255,255,255,0.65);
                position: relative;
            }
            .syllabus-card--b .syllabus-card-head {
                background: rgba(255,255,255,0.45);
            }
            .syllabus-avatar {
                height: 2.5rem;
                width: 2.5rem;
                border-radius: 0.8rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 0.75rem;
                font-weight: 800;
                color: #fff;
                background: linear-gradient(135deg, rgb(26, 54, 124), rgb(42, 74, 154));
                box-shadow: 0 4px 10px rgba(26, 54, 124, 0.25);
            }
            .syllabus-chip {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                border: 1px solid rgba(148, 163, 184, 0.45);
                background: #fff;
                padding: 0.2rem 0.65rem;
                font-size: 0.7rem;
                font-weight: 700;
                color: rgb(51, 65, 85);
            }
            .syllabus-chip--good { background: #ecfdf5; border-color: #a7f3d0; color: #065f46; }
            .syllabus-chip--warn { background: #fffbeb; border-color: #fde68a; color: #92400e; }
            .syllabus-chip--low { background: #fff1f2; border-color: #fecdd3; color: #9f1239; }
            .syllabus-chip--empty { background: #f8fafc; border-color: #e2e8f0; color: #64748b; }

            .syllabus-head-bar {
                position: absolute;
                left: 0; right: 0; bottom: 0;
                height: 3px;
                background: rgba(148, 163, 184, 0.25);
            }
            .syllabus-head-bar > span {
                display: block;
                height: 100%;
                background: linear-gradient(90deg, rgb(26, 54, 124), rgb(42, 74, 154));
            }

            .syllabus-card-body { padding: 0.35rem 0.5rem 0.65rem; }
            .syllabus-row {
                display: grid;
                grid-template-columns: 1.3fr 0.8fr 0.75fr 0.55fr 0.55fr 0.55fr 0.9fr 0.55fr 0.85fr;
                gap: 0.4rem;
                align-items: center;
                padding: 0.65rem 0.7rem;
                margin: 0.3rem 0.25rem;
                border-radius: 0.75rem;
                background: rgba(255,255,255,0.9);
                border: 1px solid rgba(148, 163, 184, 0.2);
            }
            .syllabus-card--b .syllabus-row {
                background: rgba(255,255,255,0.78);
            }
            .syllabus-row:hover {
                border-color: rgba(26, 54, 124, 0.35);
                box-shadow: 0 4px 12px rgba(26, 54, 124, 0.08);
            }

            .syllabus-col { min-width: 0; }
            .syllabus-col-num, .syllabus-col-pct, .syllabus-col-status { text-align: right; }
            .syllabus-sticky-legend-inner .syllabus-col { flex: 1; text-align: left; }
            .syllabus-sticky-legend-inner .syllabus-col-num,
            .syllabus-sticky-legend-inner .syllabus-col-pct,
            .syllabus-sticky-legend-inner .syllabus-col-status { text-align: right; }

            .syllabus-pill {
                display: inline-flex;
                border-radius: 999px;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                padding: 0.15rem 0.55rem;
                font-size: 0.72rem;
                font-weight: 700;
                color: #334155;
            }
            .syllabus-pill--eng { background: #eff6ff; border-color: #bfdbfe; color: #1e40af; }
            .syllabus-pill--guj { background: #fffbeb; border-color: #fde68a; color: #92400e; }

            .syllabus-metric {
                display: inline-flex;
                min-width: 1.75rem;
                justify-content: flex-end;
                font-size: 0.95rem;
                font-weight: 800;
                color: #0f172a;
            }
            .syllabus-metric--ok { color: #047857; }
            .syllabus-metric--warn { color: #b45309; }
            .syllabus-metric-label {
                display: none;
                font-size: 0.62rem;
                color: #94a3b8;
                font-weight: 600;
                text-transform: uppercase;
            }

            .syllabus-pct { display: inline-flex; flex-direction: column; align-items: flex-end; gap: 0.25rem; }
            .syllabus-pct-track {
                display: block;
                width: 5rem;
                height: 0.45rem;
                border-radius: 999px;
                background: #e2e8f0;
                overflow: hidden;
            }
            .syllabus-pct-track > i {
                display: block;
                height: 100%;
                border-radius: 999px;
            }
            .syllabus-pct-track > i.is-good { background: #10b981; }
            .syllabus-pct-track > i.is-warn { background: #f59e0b; }
            .syllabus-pct-track > i.is-low { background: #f43f5e; }
            .syllabus-pct-track > i.is-empty { background: #cbd5e1; }

            @media (max-width: 1023px) {
                .syllabus-row {
                    grid-template-columns: 1fr 1fr;
                    gap: 0.55rem 0.75rem;
                }
                .syllabus-col-subject { grid-column: 1 / -1; }
                .syllabus-col-num, .syllabus-col-pct, .syllabus-col-status { text-align: left; }
                .syllabus-metric-label { display: block; }
                .syllabus-pct { align-items: flex-start; }
            }

            @media print {
                .syllabus-sticky-legend { display: none !important; }
                .syllabus-card--a, .syllabus-card--b, .syllabus-row {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                    break-inside: avoid;
                }
            }
        </style>
    @endonce
</x-principal-layout>
