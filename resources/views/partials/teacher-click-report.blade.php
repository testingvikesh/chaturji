@php
    $showTeacher = $showTeacher ?? true;
@endphp

<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
    @include('admin.partials.stat-card', ['label' => 'Total clicks', 'value' => $summary['clicks']])
    @include('admin.partials.stat-card', [
        'label' => 'Total points',
        'value' => $summary['points'],
        'hint' => 'Study lines and questions in opened sections. Each section counts once. Another open adds a click only.',
    ])
    @include('admin.partials.stat-card', ['label' => 'Teachers', 'value' => $summary['teachers']])
    @include('admin.partials.stat-card', ['label' => 'Topics opened', 'value' => $summary['topics']])
</div>

<div class="admin-card">
    <div class="admin-card-top"></div>
    <div class="admin-card-body">
        <form method="GET" action="{{ $formAction }}" class="admin-filter-grid">
            @if ($showTeacher)
                <div class="lg:col-span-3">
                    <label class="admin-label">Teacher</label>
                    <select name="teacher_id" class="admin-select">
                        <option value="">All teachers</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected((string) ($filters['teacher_id'] ?? '') === (string) $teacher->id)>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="lg:col-span-3">
                <label class="admin-label">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Subject, chapter, topic..." class="admin-input">
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label">From</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="admin-input">
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label">To</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="admin-input">
            </div>
            <div class="lg:col-span-2 flex gap-2 items-end">
                <button type="submit" class="admin-btn-filter flex-1">Filter</button>
                <a href="{{ $resetUrl }}" class="admin-btn-ghost">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-4">
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="font-bold text-slate-900">Teacher wise</h3>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Teacher</th>
                        <th>Total points</th>
                        <th>Total clicks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($byTeacher as $row)
                        <tr>
                            <td class="font-semibold text-slate-800">{{ $row['teacher'] }}</td>
                            <td>{{ $row['total_points'] }}</td>
                            <td>{{ $row['total_clicks'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-slate-400 py-6">No clicks in this date range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="font-bold text-slate-900">Date wise</h3>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total points</th>
                        <th>Total clicks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($byDate as $row)
                        <tr>
                            <td class="font-semibold text-slate-800">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y') }}</td>
                            <td>{{ $row['total_points'] }}</td>
                            <td>{{ $row['total_clicks'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-slate-400 py-6">No clicks in this date range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h3 class="font-bold text-slate-900">Subject, chapter and topic</h3>
            <p class="text-xs text-slate-500 mt-0.5">
                {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d M Y') }}
                → {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d M Y') }}
                · {{ $rows->total() }} row(s)
            </p>
        </div>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Date</th>
                    @if ($showTeacher)
                        <th>Teacher</th>
                    @endif
                    <th>Subject</th>
                    <th>Chapter</th>
                    <th>Topic</th>
                    <th>Section</th>
                    <th>Points</th>
                    <th>Clicks</th>
                </tr>
            </thead>
            <tbody>
                @php $lastTeacher = null; @endphp
                @forelse ($rows as $row)
                    @if ($showTeacher && $lastTeacher !== $row->teacher_name)
                        <tr class="bg-slate-50">
                            <td colspan="{{ $showTeacher ? 8 : 7 }}" class="font-bold text-slate-900">{{ $row->teacher_name }}</td>
                        </tr>
                        @php $lastTeacher = $row->teacher_name; @endphp
                    @endif
                    <tr>
                        <td class="whitespace-nowrap font-semibold text-slate-800">{{ \Illuminate\Support\Carbon::parse($row->click_date)->format('d M Y') }}</td>
                        @if ($showTeacher)
                            <td>{{ $row->teacher_name }}</td>
                        @endif
                        <td>{{ $row->subject_name }}</td>
                        <td>{{ $row->chapter_name ?: '—' }}</td>
                        <td>{{ $row->topic_name }}</td>
                        <td>{{ $row->section_label ?: '—' }}</td>
                        <td>{{ (int) $row->total_points }}</td>
                        <td>{{ (int) $row->total_clicks }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showTeacher ? 8 : 7 }}" class="text-center text-slate-400 py-8">No topic clicks stored for this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($rows->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">{{ $rows->links() }}</div>
    @endif
</div>
