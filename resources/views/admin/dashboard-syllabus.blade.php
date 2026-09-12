<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header
            title="Syllabus Dashboard"
            subtitle="Management syllabus progress — filters, tables & drill-down from teaching updates"
        />
    </x-slot>

    @php
        $filters = $filters ?? [];
        $view = $filters['view'] ?? 'overview';
        $qs = fn (array $extra = []) => array_filter(array_merge($filters, $extra), fn ($v) => $v !== null && $v !== '');
        $drill = fn (array $extra = []) => route('admin.dashboard.syllabus', $qs($extra));
        $show = fn (string ...$keys) => in_array($view, ['overview', ...$keys], true);
    @endphp

    <div class="admin-page">
        @include('admin.partials.dashboard-tabs')
        @include('admin.partials.alert')

        {{-- Quick nav buttons --}}
        <div class="flex flex-wrap gap-2 mb-5">
            @foreach ([
                'overview' => 'Overall Syllabus',
                'class' => 'Class Report',
                'subject' => 'Subject Report',
                'teachers' => 'Teacher Report',
                'chapter' => 'Chapter Report',
                'daily' => 'Daily Updates',
                'pending' => 'Pending Syllabus',
                'compliance' => 'No Update / Compliance',
                'alerts' => 'Alerts',
            ] as $key => $label)
                <a href="{{ $drill(['view' => $key]) }}"
                   class="rounded-xl px-3 py-1.5 text-xs font-semibold transition {{ $view === $key ? 'bg-brand-green text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.dashboard.syllabus') }}" class="admin-card p-4 sm:p-5 mb-6">
            <input type="hidden" name="view" value="{{ $view }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Medium</label>
                    <select name="medium" class="w-full rounded-xl border-slate-200 text-sm">
                        <option value="">All</option>
                        @foreach ($filterOptions['mediums'] as $slug => $label)
                            <option value="{{ $slug }}" @selected(($filters['medium'] ?? '') === $slug)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Class / Standard</label>
                    <select name="standard" class="w-full rounded-xl border-slate-200 text-sm">
                        <option value="">All</option>
                        @foreach ($filterOptions['standards'] as $std)
                            <option value="{{ $std->slug }}" @selected(($filters['standard'] ?? '') === $std->slug)>
                                {{ $std->name }} ({{ $std->mediumLabel() }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Subject</label>
                    <select name="subject_id" class="w-full rounded-xl border-slate-200 text-sm">
                        <option value="">All</option>
                        @foreach ($filterOptions['subjects'] as $subject)
                            <option value="{{ $subject->id }}" @selected((int) ($filters['subject_id'] ?? 0) === (int) $subject->id)>
                                {{ $subject->name }}{{ $subject->standard ? ' — '.$subject->standard->name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Teacher</label>
                    <select name="teacher_id" class="w-full rounded-xl border-slate-200 text-sm">
                        <option value="">All</option>
                        @foreach ($filterOptions['teachers'] as $teacher)
                            <option value="{{ $teacher->id }}" @selected((int) ($filters['teacher_id'] ?? 0) === (int) $teacher->id)>
                                {{ $teacher->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Chapter</label>
                    <select name="chapter_id" class="w-full rounded-xl border-slate-200 text-sm">
                        <option value="">All</option>
                        @foreach ($filterOptions['chapters'] as $chapter)
                            <option value="{{ $chapter->id }}" @selected((int) ($filters['chapter_id'] ?? 0) === (int) $chapter->id)>
                                {{ $chapter->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Status</label>
                    <select name="status" class="w-full rounded-xl border-slate-200 text-sm">
                        <option value="">All</option>
                        @foreach ($filterOptions['statuses'] as $slug => $label)
                            <option value="{{ $slug }}" @selected(($filters['status'] ?? '') === $slug)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Date From</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded-xl border-slate-200 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Date To</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full rounded-xl border-slate-200 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Teacher, subject, chapter, topic, remark…" class="w-full rounded-xl border-slate-200 text-sm">
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <button type="submit" class="rounded-xl bg-brand-green px-4 py-2 text-sm font-semibold text-white hover:bg-brand-green-dark">Apply Filter</button>
                <a href="{{ route('admin.dashboard.syllabus', ['view' => $view]) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
            </div>
        </form>

        {{-- Alerts strip --}}
        @if ($show('alerts', 'overview'))
            <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                @foreach ($alerts as $alert)
                    <a href="{{ $drill(['view' => $alert['view']]) }}" class="admin-card p-4 hover:shadow-md transition block">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $alert['type'] }}</p>
                        <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ $alert['count'] }}</p>
                        <p class="mt-1 text-xs font-semibold
                            {{ $alert['priority'] === 'high' ? 'text-red-600' : ($alert['priority'] === 'medium' ? 'text-amber-600' : 'text-emerald-600') }}">
                            {{ strtoupper($alert['priority']) }} · View
                        </p>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- 2. Overall summary --}}
        @if ($show('overview'))
            <div class="admin-card overflow-hidden mb-6">
                <div class="admin-card-top"></div>
                <div class="admin-card-header flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="font-bold text-slate-900">Overall Syllabus Summary</h3>
                        <p class="text-xs text-slate-500 mt-1">From teacher daily teaching updates</p>
                    </div>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table w-full text-sm">
                        <thead>
                            <tr>
                                <th>Sr.</th>
                                <th>Metric</th>
                                <th>Total</th>
                                <th>Completed</th>
                                <th>In Progress</th>
                                <th>Pending</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($summary as $i => $row)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td class="font-semibold text-slate-900">{{ $row['metric'] }}</td>
                                    <td>{{ $row['total'] }}</td>
                                    <td>{{ $row['completed'] }}</td>
                                    <td>{{ $row['in_progress'] }}</td>
                                    <td>{{ $row['pending'] }}</td>
                                    <td>
                                        <span class="admin-badge-green text-xs">{{ $row['percent'] }}%</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 3. Class-wise --}}
        @if ($show('class'))
            <div class="admin-card overflow-hidden mb-6">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <h3 class="font-bold text-slate-900">Class-Wise Syllabus Report</h3>
                    <p class="text-xs text-slate-500 mt-1">Click class → subject-wise drill-down</p>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table w-full text-sm">
                        <thead>
                            <tr>
                                <th>Sr.</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Subjects</th>
                                <th>Chapters</th>
                                <th>Completed</th>
                                <th>Pending</th>
                                <th>Progress %</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($classRows as $i => $row)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <a href="{{ $drill(['view' => 'subject', 'standard' => $row['standard']]) }}" class="font-semibold text-brand-green hover:underline">
                                            {{ $row['class'] }}
                                        </a>
                                    </td>
                                    <td>{{ $row['section'] }}</td>
                                    <td>{{ $row['subjects'] }}</td>
                                    <td>{{ $row['chapters'] }}</td>
                                    <td>{{ $row['completed'] }}</td>
                                    <td>{{ $row['pending'] }}</td>
                                    <td>{{ $row['percent'] }}%</td>
                                    <td><span class="{{ $row['status']['badge'] }} text-xs">{{ $row['status']['emoji'] }} {{ $row['status']['label'] }}</span></td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-row', ['colspan' => 9, 'message' => 'No class syllabus data for selected filters.'])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 4. Subject-wise --}}
        @if ($show('subject'))
            <div class="admin-card overflow-hidden mb-6">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <h3 class="font-bold text-slate-900">Subject-Wise Report</h3>
                    <p class="text-xs text-slate-500 mt-1">Click subject → chapter-wise drill-down</p>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table w-full text-sm">
                        <thead>
                            <tr>
                                <th>Sr.</th>
                                <th>Subject</th>
                                <th>Class</th>
                                <th>Teacher</th>
                                <th>Chapters</th>
                                <th>Completed</th>
                                <th>Pending</th>
                                <th>Progress %</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($subjectRows as $i => $row)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <a href="{{ $drill(['view' => 'chapter', 'standard' => $row['standard'], 'subject_id' => $row['subject_id']]) }}" class="font-semibold text-brand-green hover:underline">
                                            {{ $row['subject'] }}
                                        </a>
                                    </td>
                                    <td>{{ $row['class'] }}</td>
                                    <td>
                                        <a href="{{ $drill(['view' => 'teachers', 'teacher_id' => $row['teacher_id']]) }}" class="hover:underline">{{ $row['teacher'] }}</a>
                                    </td>
                                    <td>{{ $row['chapters'] }}</td>
                                    <td>{{ $row['completed'] }}</td>
                                    <td>{{ $row['pending'] }}</td>
                                    <td>{{ $row['percent'] }}%</td>
                                    <td><span class="{{ $row['status']['badge'] }} text-xs">{{ $row['status']['emoji'] }} {{ $row['status']['label'] }}</span></td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-row', ['colspan' => 9, 'message' => 'No subject data for selected filters.'])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 5. Teacher-wise --}}
        @if ($show('teachers'))
            <div class="admin-card overflow-hidden mb-6">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <h3 class="font-bold text-slate-900">Teacher-Wise Syllabus Performance</h3>
                    <p class="text-xs text-slate-500 mt-1">Click teacher → daily syllabus history</p>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table w-full text-sm">
                        <thead>
                            <tr>
                                <th>Sr.</th>
                                <th>Teacher</th>
                                <th>Subject</th>
                                <th>Class</th>
                                <th>Planned %</th>
                                <th>Actual %</th>
                                <th>Gap %</th>
                                <th>Daily Update</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($teacherRows as $i => $row)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <a href="{{ $drill(['view' => 'daily', 'teacher_id' => $row['teacher_id']]) }}" class="font-semibold text-brand-green hover:underline">
                                            {{ $row['teacher'] }}
                                        </a>
                                    </td>
                                    <td>{{ $row['subject'] }}</td>
                                    <td>{{ $row['class'] }}</td>
                                    <td>{{ $row['planned'] }}%</td>
                                    <td>{{ $row['actual'] }}%</td>
                                    <td class="{{ $row['gap'] < 0 ? 'text-red-600 font-semibold' : 'text-emerald-700 font-semibold' }}">
                                        {{ $row['gap'] > 0 ? '+' : '' }}{{ $row['gap'] }}%
                                    </td>
                                    <td>{{ $row['daily_update'] ? '✅' : '❌' }}</td>
                                    <td><span class="{{ $row['status']['badge'] }} text-xs">{{ $row['status']['emoji'] }} {{ $row['status']['label'] }}</span></td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-row', ['colspan' => 9, 'message' => 'No teacher performance data.'])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-6 mb-6">
                <div class="admin-card overflow-hidden">
                    <div class="admin-card-header">
                        <h3 class="font-bold text-slate-900">🏆 Top Performing Teachers</h3>
                    </div>
                    <div class="admin-table-wrap">
                        <table class="admin-table w-full text-sm">
                            <thead>
                                <tr><th>Rank</th><th>Teacher</th><th>Subject</th><th>Class</th><th>Progress</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($topTeachers as $i => $row)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td class="font-semibold">{{ $row['teacher'] }}</td>
                                        <td>{{ $row['subject'] }}</td>
                                        <td>{{ $row['class'] }}</td>
                                        <td>{{ $row['actual'] }}%</td>
                                    </tr>
                                @empty
                                    @include('admin.partials.empty-row', ['colspan' => 5, 'message' => 'No data.'])
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="admin-card overflow-hidden">
                    <div class="admin-card-header">
                        <h3 class="font-bold text-slate-900">⚠️ Teachers Requiring Attention</h3>
                    </div>
                    <div class="admin-table-wrap">
                        <table class="admin-table w-full text-sm">
                            <thead>
                                <tr><th>Rank</th><th>Teacher</th><th>Subject</th><th>Expected</th><th>Actual</th><th>Gap</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($attentionTeachers as $i => $row)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td class="font-semibold">{{ $row['teacher'] }}</td>
                                        <td>{{ $row['subject'] }}</td>
                                        <td>{{ $row['planned'] }}%</td>
                                        <td>{{ $row['actual'] }}%</td>
                                        <td class="text-red-600 font-semibold">{{ $row['gap'] }}%</td>
                                    </tr>
                                @empty
                                    @include('admin.partials.empty-row', ['colspan' => 6, 'message' => 'No teachers behind planned progress.'])
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- 6. Chapter-wise --}}
        @if ($show('chapter'))
            <div class="admin-card overflow-hidden mb-6">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <h3 class="font-bold text-slate-900">Chapter-Wise Report</h3>
                    <p class="text-xs text-slate-500 mt-1">Click chapter → topic daily updates</p>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table w-full text-sm">
                        <thead>
                            <tr>
                                <th>Sr.</th>
                                <th>Chapter</th>
                                <th>Subject</th>
                                <th>Class</th>
                                <th>Total Topics</th>
                                <th>Completed</th>
                                <th>Partial</th>
                                <th>Pending</th>
                                <th>Progress %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($chapterRows as $i => $row)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <a href="{{ $drill(['view' => 'daily', 'chapter_id' => $row['chapter_id'], 'subject_id' => $row['subject_id'], 'standard' => $row['standard']]) }}" class="font-semibold text-brand-green hover:underline">
                                            {{ $row['chapter'] }}
                                        </a>
                                    </td>
                                    <td>{{ $row['subject'] }}</td>
                                    <td>{{ $row['class'] }}</td>
                                    <td>{{ $row['total_topics'] }}</td>
                                    <td>{{ $row['completed'] }}</td>
                                    <td>{{ $row['partial'] }}</td>
                                    <td>{{ $row['pending'] }}</td>
                                    <td>{{ $row['percent'] }}%</td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-row', ['colspan' => 9, 'message' => 'No chapter data.'])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 7. Daily topic updates --}}
        @if ($show('daily'))
            <div class="admin-card overflow-hidden mb-6">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <h3 class="font-bold text-slate-900">Topic-Wise Daily Update</h3>
                    <p class="text-xs text-slate-500 mt-1">Latest teacher syllabus updates (max 100)</p>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table w-full text-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Teacher</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Chapter</th>
                                <th>Topic</th>
                                <th>Status</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dailyRows as $row)
                                <tr>
                                    <td class="whitespace-nowrap">{{ $row['date'] }}</td>
                                    <td>
                                        <a href="{{ $drill(['view' => 'daily', 'teacher_id' => $row['teacher_id']]) }}" class="font-semibold text-brand-green hover:underline">{{ $row['teacher'] }}</a>
                                    </td>
                                    <td>
                                        <a href="{{ $drill(['view' => 'subject', 'standard' => $row['standard']]) }}" class="hover:underline">{{ $row['class'] }}</a>
                                    </td>
                                    <td>
                                        <a href="{{ $drill(['view' => 'chapter', 'subject_id' => $row['subject_id'], 'standard' => $row['standard']]) }}" class="hover:underline">{{ $row['subject'] }}</a>
                                    </td>
                                    <td>
                                        <a href="{{ $drill(['view' => 'daily', 'chapter_id' => $row['chapter_id']]) }}" class="hover:underline">{{ $row['chapter'] }}</a>
                                    </td>
                                    <td>{{ $row['topic'] }}</td>
                                    <td>
                                        @if ($row['status'] === 'completed')
                                            <span class="admin-badge-green text-xs">✅ Completed</span>
                                        @else
                                            <span class="admin-badge-gold text-xs">🟡 Remaining</span>
                                        @endif
                                    </td>
                                    <td class="max-w-[14rem] truncate" title="{{ $row['remark'] }}">{{ $row['remark'] }}</td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-row', ['colspan' => 8, 'message' => 'No daily updates found.'])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 10. Pending --}}
        @if ($show('pending'))
            <div class="admin-card overflow-hidden mb-6">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <h3 class="font-bold text-slate-900">Pending Syllabus Report</h3>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table w-full text-sm">
                        <thead>
                            <tr>
                                <th>Sr.</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Chapter</th>
                                <th>Topic</th>
                                <th>Expected Date</th>
                                <th>Status</th>
                                <th>Delay</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pendingRows as $i => $row)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $row['class'] }}</td>
                                    <td>{{ $row['subject'] }}</td>
                                    <td>{{ $row['teacher'] }}</td>
                                    <td>{{ $row['chapter'] }}</td>
                                    <td>{{ $row['topic'] }}</td>
                                    <td>{{ $row['expected_date'] }}</td>
                                    <td><span class="admin-badge-gold text-xs">{{ $row['status'] }}</span></td>
                                    <td class="{{ $row['delay_days'] > 3 ? 'text-red-600 font-semibold' : 'text-slate-600' }}">{{ $row['delay'] }}</td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-row', ['colspan' => 9, 'message' => 'No pending topics.'])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 9. Compliance --}}
        @if ($show('compliance'))
            <div class="admin-card overflow-hidden mb-6">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <h3 class="font-bold text-slate-900">Daily Teacher Update Compliance</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        Based on {{ $filters['date_to'] ?: ($filters['date_from'] ?: now()->toDateString()) }}
                        (timetable periods not configured yet — shows who updated today)
                    </p>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table w-full text-sm">
                        <thead>
                            <tr>
                                <th>Sr.</th>
                                <th>Teacher</th>
                                <th>Updates Done</th>
                                <th>Pending</th>
                                <th>Compliance %</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($complianceRows as $i => $row)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <a href="{{ $drill(['view' => 'daily', 'teacher_id' => $row['teacher_id']]) }}" class="font-semibold text-brand-green hover:underline">{{ $row['teacher'] }}</a>
                                    </td>
                                    <td>{{ $row['done'] }}</td>
                                    <td>{{ $row['pending'] }}</td>
                                    <td>{{ $row['percent'] }}%</td>
                                    <td><span class="{{ $row['status']['badge'] }} text-xs">{{ $row['status']['emoji'] }} {{ $row['status']['label'] }}</span></td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-row', ['colspan' => 6, 'message' => 'No teachers found.'])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 13. Alerts table --}}
        @if ($show('alerts'))
            <div class="admin-card overflow-hidden mb-6">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <h3 class="font-bold text-slate-900">Management Alert Table</h3>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table w-full text-sm">
                        <thead>
                            <tr>
                                <th>Alert Type</th>
                                <th>Count</th>
                                <th>Priority</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($alerts as $alert)
                                <tr>
                                    <td class="font-semibold">{{ $alert['type'] }}</td>
                                    <td>{{ $alert['count'] }}</td>
                                    <td>
                                        @if ($alert['priority'] === 'high')
                                            <span class="text-red-600 font-semibold">🔴 High</span>
                                        @elseif ($alert['priority'] === 'medium')
                                            <span class="text-amber-600 font-semibold">🟠 Medium</span>
                                        @else
                                            <span class="text-emerald-600 font-semibold">🟢 Low</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ $drill(['view' => $alert['view']]) }}" class="rounded-lg border border-slate-200 px-3 py-1 text-xs font-semibold hover:bg-slate-50">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900 mb-2">
            Data source: teacher <strong>Today's Teaching</strong> logs.
            Section / Period / Planned % vs timetable will unlock when those masters are added.
            Drill-down: Class → Subject → Teacher → Chapter → Daily Topic → Status / Remark.
        </div>
    </div>
</x-app-layout>
