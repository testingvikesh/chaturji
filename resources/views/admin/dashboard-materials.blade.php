<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Material Report" subtitle="Generated from the materials table: Medium → Standard → Subject → Chapter → Topic" />
    </x-slot>

    <style>
        @media print {
            [x-cloak] { display: block !important; }
            .admin-shell { padding-left: 0 !important; }
            aside, .lg\\:hidden, .print\\:hidden { display: none !important; }
        }
    </style>
    <div class="admin-page">
        @include('admin.partials.dashboard-tabs')
        @include('admin.partials.alert')

        <form method="GET" action="{{ route('admin.dashboard.materials') }}" class="admin-card p-4 sm:p-5 mb-6 print:hidden">
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Medium</label>
                    <select name="medium" class="rounded-xl border-slate-200 text-sm min-w-[10rem]">
                        <option value="all" @selected($medium_filter === 'all')>All Mediums</option>
                        <option value="english" @selected($medium_filter === 'english')>English</option>
                        <option value="gujarati" @selected($medium_filter === 'gujarati')>Gujarati</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Standard</label>
                    <select name="standard" class="rounded-xl border-slate-200 text-sm min-w-[10rem]">
                        <option value="all" @selected($standard_filter === 'all' || $standard_filter === '')>All Standards</option>
                        @foreach ($standards as $standard)
                            @php
                                $stdKey = is_array($standard) ? ($standard['key'] ?? '') : (string) ($standard->id ?? '');
                                $stdName = is_array($standard) ? ($standard['name'] ?? '') : (string) ($standard->name ?? '');
                            @endphp
                            <option value="{{ $stdKey }}" @selected((string) $standard_filter === (string) $stdKey)>
                                {{ $stdName }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Subject</label>
                    <select name="subject" class="rounded-xl border-slate-200 text-sm min-w-[12rem]">
                        <option value="all" @selected($subject_filter === 'all' || $subject_filter === '')>All Subjects</option>
                        @foreach ($subjects as $subjectName)
                            <option value="{{ $subjectName }}" @selected(strcasecmp((string) $subject_filter, (string) $subjectName) === 0)>{{ $subjectName }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-xl bg-brand-green px-4 py-2 text-sm font-semibold text-white hover:bg-brand-green-dark">
                    Show Report
                </button>
                <a href="{{ route('admin.dashboard.materials') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>

        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            @include('admin.partials.stat-card', ['label' => 'Standards', 'value' => $totals['standards'], 'hint' => 'Medium + standard groups'])
            @include('admin.partials.stat-card', ['label' => 'Subjects', 'value' => $totals['subjects'], 'hint' => 'Medium + standard + subject'])
            @include('admin.partials.stat-card', ['label' => 'Chapters', 'value' => $totals['all'], 'hint' => 'Rows in materials'])
            @include('admin.partials.stat-card', ['label' => 'Topics Ready', 'value' => $totals['topics_ready'], 'hint' => $totals['topics'].' topics in material_topics'])
        </div>

        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            @include('admin.partials.stat-card', ['label' => 'English Chapters', 'value' => $totals['english']])
            @include('admin.partials.stat-card', ['label' => 'Gujarati Chapters', 'value' => $totals['gujarati']])
            @include('admin.partials.stat-card', ['label' => 'Complete', 'value' => $totals['complete']])
            @include('admin.partials.stat-card', ['label' => 'Planned', 'value' => $totals['planned']])
        </div>

        @if (empty($tree))
            <div class="admin-card">
                <div class="p-8 text-center text-sm text-slate-400">No materials found in the materials table for this filter.</div>
            </div>
        @else
            @include('admin.partials.material-report-tree', ['tree' => $tree])

            <div class="admin-card mt-6">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <div>
                        <h3 class="font-bold text-slate-900">Materials table</h3>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $rows->count() }} row(s) from materials · medium, standard, subject, chapter_no, chapter_name</p>
                    </div>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Medium</th>
                                <th>Standard</th>
                                <th>Subject</th>
                                <th>Chapter No</th>
                                <th>Chapter Name</th>
                                <th>Status</th>
                                <th>Topics</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="text-xs text-slate-400">{{ $row['id'] }}</td>
                                    <td>{{ $row['medium'] }}</td>
                                    <td>{{ $row['standard'] }}</td>
                                    <td class="font-medium text-slate-900">{{ $row['subject'] }}</td>
                                    <td>{{ $row['chapter_no'] }}</td>
                                    <td>{{ $row['chapter_name'] }}</td>
                                    <td>
                                        @if ($row['status'] === 'complete')
                                            <span class="admin-badge-green text-xs">Complete</span>
                                        @elseif ($row['status'] === 'partial')
                                            <span class="admin-badge-gold text-xs">Partial</span>
                                        @else
                                            <span class="admin-badge-slate text-xs">{{ ucfirst($row['status']) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-xs text-slate-500 whitespace-nowrap">{{ $row['topics_ready'] }}/{{ $row['topics_count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
