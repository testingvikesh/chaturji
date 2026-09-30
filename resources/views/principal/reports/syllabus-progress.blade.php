<x-principal-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Reports</span>
            <h2 class="admin-page-title">Syllabus Progress Report</h2>
            <p class="admin-page-subtitle">Teacher → subjects → topics complete / remain with % (your allotted standards)</p>
        </div>
    </x-slot>

    @php
        $flatTopics = collect($rows ?? [])->flatMap(function ($row) {
            return collect($row['topic_details'] ?? [])->map(fn ($t) => [
                'teacher' => $row['teacher'],
                'subject' => $row['subject'],
                'standard' => $row['standard'],
                'medium' => $row['medium'],
                'chapter' => $t['chapter'] ?? '—',
                'title' => $t['title'] ?? '—',
                'status' => $t['status'] ?? 'remain',
            ]);
        })->values();

        $modalLists = [
            'teachers' => collect($teachers ?? [])->map(fn ($t) => [
                'title' => $t['teacher'],
                'meta' => ($t['mobile'] ?: '—').' · '.$t['subjects'].' subjects · '.$t['topics_complete'].'/'.$t['topics_total'].' topics · '.$t['percent_complete'].'%',
                'status' => $t['status'],
            ])->values(),
            'subjects' => collect($rows ?? [])->map(fn ($r) => [
                'title' => $r['subject'].' · '.$r['standard'],
                'meta' => $r['teacher'].' · '.$r['medium'].' · '.$r['topics_complete'].'/'.$r['topics_total'].' · '.$r['percent_complete'].'%',
                'status' => $r['status'],
            ])->values(),
            'topics_total' => $flatTopics->map(fn ($t) => [
                'title' => $t['title'],
                'meta' => $t['teacher'].' · '.$t['subject'].' · '.$t['standard'].' · '.$t['chapter'],
                'status' => $t['status'],
            ])->values(),
            'topics_complete' => $flatTopics->where('status', 'complete')->values()->map(fn ($t) => [
                'title' => $t['title'],
                'meta' => $t['teacher'].' · '.$t['subject'].' · '.$t['standard'].' · '.$t['chapter'],
                'status' => 'complete',
            ])->values(),
            'topics_remain' => $flatTopics->where('status', 'remain')->values()->map(fn ($t) => [
                'title' => $t['title'],
                'meta' => $t['teacher'].' · '.$t['subject'].' · '.$t['standard'].' · '.$t['chapter'],
                'status' => 'remain',
            ])->values(),
        ];
        $modalLists['percent_complete'] = $modalLists['topics_complete'];
        $modalLists['percent_remain'] = $modalLists['topics_remain'];
    @endphp

    <div class="admin-page space-y-5 syllabus-progress-page"
         x-data="{
            open: false,
            title: '',
            subtitle: '',
            items: [],
            showList(key, title, subtitle) {
                const lists = @js($modalLists);
                this.title = title;
                this.subtitle = subtitle || '';
                this.items = lists[key] || [];
                this.open = true;
            },
            showTopics(details, filter, title, subtitle) {
                let items = Array.isArray(details) ? details : [];
                if (filter === 'complete') items = items.filter(t => t.status === 'complete');
                if (filter === 'remain') items = items.filter(t => t.status === 'remain');
                this.title = title;
                this.subtitle = subtitle || '';
                this.items = items.map(t => ({
                    title: t.title || '—',
                    meta: (t.chapter || '—') + (t.status ? ' · ' + t.status : ''),
                    status: t.status || '',
                }));
                this.open = true;
            },
            close() { this.open = false; }
         }">
        @include('principal.partials.reports-nav')

        @if (! ($hasAllotments ?? false))
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                No standard allotted yet. Ask admin to allot standards first.
            </div>
        @else
            <div class="grid sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7 gap-4 report-print-hide">
                <button type="button" class="admin-stat-card text-left w-full hover:ring-2 hover:ring-brand-green/30 cursor-pointer"
                        @click="showList('teachers', 'Teachers', '{{ $summary['teachers'] }} teachers in this report')">
                    <p class="admin-stat-label">Teachers</p>
                    <p class="admin-stat-value">{{ $summary['teachers'] }}</p>
                    <p class="text-xs text-slate-400 mt-1.5">Click for list</p>
                </button>
                <button type="button" class="admin-stat-card text-left w-full hover:ring-2 hover:ring-brand-green/30 cursor-pointer"
                        @click="showList('subjects', 'Subjects', '{{ $summary['subjects'] }} subject allotments')">
                    <p class="admin-stat-label">Subjects</p>
                    <p class="admin-stat-value">{{ $summary['subjects'] }}</p>
                    <p class="text-xs text-slate-400 mt-1.5">Click for list</p>
                </button>
                <button type="button" class="admin-stat-card text-left w-full hover:ring-2 hover:ring-brand-green/30 cursor-pointer"
                        @click="showList('topics_total', 'All topics', '{{ $summary['topics_total'] }} topics')">
                    <p class="admin-stat-label">Topics total</p>
                    <p class="admin-stat-value">{{ $summary['topics_total'] }}</p>
                    <p class="text-xs text-slate-400 mt-1.5">Click for list</p>
                </button>
                <button type="button" class="admin-stat-card text-left w-full hover:ring-2 hover:ring-brand-green/30 cursor-pointer"
                        @click="showList('topics_complete', 'Complete topics', '{{ $summary['topics_complete'] }} complete')">
                    <p class="admin-stat-label">Complete</p>
                    <p class="admin-stat-value">{{ $summary['topics_complete'] }}</p>
                    <p class="text-xs text-slate-400 mt-1.5">Click for list</p>
                </button>
                <button type="button" class="admin-stat-card text-left w-full hover:ring-2 hover:ring-brand-green/30 cursor-pointer"
                        @click="showList('topics_remain', 'Remain topics', '{{ $summary['topics_remain'] }} remain')">
                    <p class="admin-stat-label">Remain</p>
                    <p class="admin-stat-value">{{ $summary['topics_remain'] }}</p>
                    <p class="text-xs text-slate-400 mt-1.5">Click for list</p>
                </button>
                <button type="button" class="admin-stat-card text-left w-full hover:ring-2 hover:ring-brand-green/30 cursor-pointer"
                        @click="showList('percent_complete', '% Complete topics', '{{ $summary['percent_complete'] }}% complete')">
                    <p class="admin-stat-label">% Complete</p>
                    <p class="admin-stat-value">{{ $summary['percent_complete'] }}%</p>
                    <p class="text-xs text-slate-400 mt-1.5">Click for list</p>
                </button>
                <button type="button" class="admin-stat-card text-left w-full hover:ring-2 hover:ring-brand-green/30 cursor-pointer"
                        @click="showList('percent_remain', '% Remain topics', '{{ $summary['percent_remain'] }}% remain')">
                    <p class="admin-stat-label">% Remain</p>
                    <p class="admin-stat-value">{{ $summary['percent_remain'] }}%</p>
                    <p class="text-xs text-slate-400 mt-1.5">Click for list</p>
                </button>
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
                                <button type="button" class="syllabus-chip hover:ring-2 hover:ring-brand-green/30"
                                        @click="showTopics(@js(collect($teacherGroup['rows'])->flatMap(fn ($r) => $r['topic_details'] ?? [])->values()), 'all', @js($teacherGroup['teacher'].' — topics'), @js($teacherGroup['topics_complete'].'/'.$teacherGroup['topics_total'].' topics'))">
                                    {{ $teacherGroup['topics_complete'] }}/{{ $teacherGroup['topics_total'] }} topics
                                </button>
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
                                        <button type="button" class="syllabus-count-btn"
                                                @click="showTopics(@js($row['topic_details'] ?? []), 'all', @js($row['subject'].' — all topics'), @js($row['teacher'].' · '.$row['standard'].' · '.$row['medium']))">
                                            <span class="syllabus-metric">{{ $row['topics_total'] }}</span>
                                            <span class="syllabus-metric-label">Topics</span>
                                        </button>
                                    </div>
                                    <div class="syllabus-col syllabus-col-num">
                                        <button type="button" class="syllabus-count-btn"
                                                @click="showTopics(@js($row['topic_details'] ?? []), 'complete', @js($row['subject'].' — complete'), @js($row['teacher'].' · '.$row['topics_complete'].' topics'))">
                                            <span class="syllabus-metric syllabus-metric--ok">{{ $row['topics_complete'] }}</span>
                                            <span class="syllabus-metric-label">Complete</span>
                                        </button>
                                    </div>
                                    <div class="syllabus-col syllabus-col-num">
                                        <button type="button" class="syllabus-count-btn"
                                                @click="showTopics(@js($row['topic_details'] ?? []), 'remain', @js($row['subject'].' — remain'), @js($row['teacher'].' · '.$row['topics_remain'].' topics'))">
                                            <span class="syllabus-metric syllabus-metric--warn">{{ $row['topics_remain'] }}</span>
                                            <span class="syllabus-metric-label">Remain</span>
                                        </button>
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

            {{-- Detail list modal --}}
            <div x-show="open" x-cloak
                 class="syllabus-modal report-print-hide"
                 @keydown.escape.window="close()"
                 role="dialog" aria-modal="true">
                <div class="syllabus-modal-backdrop" @click="close()"></div>
                <div class="syllabus-modal-panel" @click.stop>
                    <div class="syllabus-modal-head">
                        <div class="min-w-0">
                            <h3 class="text-lg font-bold text-slate-900 truncate" x-text="title"></h3>
                            <p class="text-xs text-slate-500 mt-0.5" x-text="subtitle"></p>
                        </div>
                        <button type="button" class="admin-btn-ghost text-sm" @click="close()">Close</button>
                    </div>
                    <div class="syllabus-modal-body">
                        <template x-if="items.length === 0">
                            <p class="p-6 text-center text-sm text-slate-500">No details found.</p>
                        </template>
                        <ul class="divide-y divide-slate-100" x-show="items.length > 0">
                            <template x-for="(item, idx) in items" :key="idx">
                                <li class="px-4 py-3 flex items-start gap-3">
                                    <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[11px] font-bold text-slate-600"
                                          x-text="idx + 1"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-slate-900" x-text="item.title"></p>
                                        <p class="text-xs text-slate-500 mt-0.5" x-text="item.meta"></p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                          :class="{
                                              'bg-emerald-50 text-emerald-700 border border-emerald-200': item.status === 'complete' || item.status === 'good',
                                              'bg-amber-50 text-amber-800 border border-amber-200': item.status === 'remain' || item.status === 'warn',
                                              'bg-rose-50 text-rose-700 border border-rose-200': item.status === 'low',
                                              'bg-slate-50 text-slate-600 border border-slate-200': !item.status || item.status === 'empty'
                                          }"
                                          x-text="item.status || '—'"
                                          x-show="item.status"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                    <div class="syllabus-modal-foot">
                        <span class="text-xs text-slate-500" x-text="items.length + ' item' + (items.length === 1 ? '' : 's')"></span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @once
        <style>
            .syllabus-count-btn {
                display: inline-flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                background: transparent;
                border: 0;
                padding: 0.15rem 0.35rem;
                border-radius: 0.5rem;
                cursor: pointer;
            }
            .syllabus-count-btn:hover {
                background: rgba(26, 54, 124, 0.08);
            }
            .syllabus-modal {
                position: fixed;
                inset: 0;
                z-index: 80;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1rem;
            }
            .syllabus-modal-backdrop {
                position: absolute;
                inset: 0;
                background: rgba(15, 23, 42, 0.55);
            }
            .syllabus-modal-panel {
                position: relative;
                z-index: 1;
                width: min(720px, 100%);
                max-height: min(86dvh, 900px);
                display: flex;
                flex-direction: column;
                background: #fff;
                border-radius: 1rem;
                box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25);
                overflow: hidden;
            }
            .syllabus-modal-head {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 1rem;
                padding: 1rem 1.1rem;
                border-bottom: 1px solid #e2e8f0;
                background: linear-gradient(180deg, #f8fafc, #fff);
            }
            .syllabus-modal-body {
                overflow-y: auto;
                flex: 1;
                min-height: 0;
            }
            .syllabus-modal-foot {
                padding: 0.65rem 1.1rem;
                border-top: 1px solid #e2e8f0;
                background: #f8fafc;
            }
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

            .syllabus-col {
                min-width: 0;
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }
            .syllabus-sticky-legend-inner .syllabus-col {
                flex: 1;
                text-align: center;
                justify-content: center;
            }

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
                justify-content: center;
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
                text-align: center;
            }

            .syllabus-pct { display: inline-flex; flex-direction: column; align-items: center; gap: 0.25rem; width: 100%; }
            .syllabus-pct-track {
                display: block;
                width: 5rem;
                max-width: 100%;
                height: 0.45rem;
                border-radius: 999px;
                background: #e2e8f0;
                overflow: hidden;
                margin: 0 auto;
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
                .syllabus-metric-label { display: block; }
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
