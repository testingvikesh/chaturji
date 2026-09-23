<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Chapter List Report" subtitle="Medium · Standard · Subject · Chapter — printable chapter list" />
    </x-slot>

    <style>
        @media print {
            .admin-shell { padding-left: 0 !important; }
            aside,
            .lg\:hidden,
            .print\:hidden,
            .admin-sidebar-section,
            header .admin-btn-primary,
            header .admin-btn-secondary,
            header .admin-btn-ghost { display: none !important; }
            .admin-header { box-shadow: none !important; border: none !important; }
            body { background: #fff !important; }
            .chapter-print-block { break-inside: avoid; page-break-inside: avoid; }
            .chapter-print-table { width: 100%; border-collapse: collapse; }
            .chapter-print-table th,
            .chapter-print-table td {
                border: 1px solid #cbd5e1;
                padding: 6px 8px;
                font-size: 11px;
                text-align: left;
            }
            .chapter-print-table th { background: #f1f5f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .chapter-print-title {
                font-size: 14px;
                font-weight: 700;
                margin: 0 0 6px;
            }
            .chapter-print-meta { font-size: 11px; color: #475569; margin-bottom: 8px; }
        }
    </style>

    <div class="admin-page">
        <div class="grid sm:grid-cols-2 xl:grid-cols-6 gap-4 print:hidden">
            @include('admin.partials.stat-card', ['label' => 'Chapters', 'value' => $summary['chapters']])
            @include('admin.partials.stat-card', ['label' => 'Subject Groups', 'value' => $summary['subjects']])
            @include('admin.partials.stat-card', ['label' => 'Complete', 'value' => $summary['complete']])
            @include('admin.partials.stat-card', ['label' => 'Partial', 'value' => $summary['partial']])
            @include('admin.partials.stat-card', ['label' => 'English', 'value' => $summary['english']])
            @include('admin.partials.stat-card', ['label' => 'Gujarati', 'value' => $summary['gujarati']])
        </div>

        <div class="admin-card print:hidden">
            <div class="admin-card-top"></div>
            <div class="admin-card-body">
                <form method="GET" class="admin-filter-grid">
                    <div class="lg:col-span-3">
                        <label class="admin-label">Medium</label>
                        <select name="medium" class="admin-select">
                            <option value="all" @selected(($filters['medium'] ?? 'all') === 'all')>All mediums</option>
                            <option value="english" @selected(($filters['medium'] ?? '') === 'english')>English</option>
                            <option value="gujarati" @selected(($filters['medium'] ?? '') === 'gujarati')>Gujarati</option>
                        </select>
                    </div>
                    <div class="lg:col-span-3">
                        <label class="admin-label">Standard</label>
                        <select name="standard" class="admin-select">
                            <option value="all" @selected(($filters['standard'] ?? 'all') === 'all')>All standards</option>
                            @foreach ($standards as $standard)
                                @php
                                    $stdKey = is_array($standard) ? ($standard['key'] ?? '') : (string) ($standard->id ?? '');
                                    $stdName = is_array($standard) ? ($standard['name'] ?? '') : (string) ($standard->name ?? '');
                                @endphp
                                <option value="{{ $stdKey }}" @selected((string) ($filters['standard'] ?? '') === (string) $stdKey)>{{ $stdName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-4">
                        <label class="admin-label">Subject</label>
                        <select name="subject" class="admin-select">
                            <option value="all" @selected(($filters['subject'] ?? 'all') === 'all')>All subjects</option>
                            @foreach ($subjects as $subjectName)
                                <option value="{{ $subjectName }}" @selected(strcasecmp((string) ($filters['subject'] ?? ''), (string) $subjectName) === 0)>{{ $subjectName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-2 flex gap-2 items-end">
                        <button type="submit" class="admin-btn-filter flex-1">Generate</button>
                        @if (($filters['medium'] ?? 'all') !== 'all' || ($filters['standard'] ?? 'all') !== 'all' || ($filters['subject'] ?? 'all') !== 'all')
                            <a href="{{ route('admin.reports.chapter-list') }}" class="admin-btn-ghost">Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        @forelse ($groups as $group)
            <section class="admin-card chapter-print-block mb-4 overflow-hidden">
                <div class="admin-card-top print:hidden"></div>
                <div class="admin-card-header print:hidden">
                    <div>
                        <h3 class="font-bold text-slate-900">{{ $group['medium'] }} · {{ $group['standard'] }} · {{ $group['subject'] }}</h3>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $group['count'] }} chapter(s) · {{ $group['complete'] }} complete</p>
                    </div>
                    <span class="admin-badge-green">{{ $group['count'] }}</span>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="hidden print:block">
                        <p class="chapter-print-title">{{ $group['medium'] }} Medium · {{ $group['standard'] }} · {{ $group['subject'] }}</p>
                        <p class="chapter-print-meta">{{ $group['count'] }} chapter(s) · {{ $group['complete'] }} complete</p>
                    </div>
                    <div class="admin-table-wrap print:overflow-visible">
                        <table class="admin-table chapter-print-table">
                            <thead>
                                <tr>
                                    <th class="w-16">No.</th>
                                    <th>Chapter</th>
                                    <th class="w-28">Type / Status</th>
                                    <th class="w-28 print:hidden">Topics</th>
                                    <th class="w-20 print:hidden">PDF</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($group['chapters'] as $chapter)
                                    <tr>
                                        <td class="font-semibold text-slate-800">{{ $chapter['chapter_no'] }}</td>
                                        <td>
                                            <p class="font-medium text-slate-900">{{ $chapter['chapter_name'] }}</p>
                                            <p class="text-xs text-slate-400 print:hidden">ID {{ $chapter['id'] }}</p>
                                        </td>
                                        <td>
                                            @php $status = (string) ($chapter['status'] ?? 'draft'); @endphp
                                            @if ($status === 'complete')
                                                <span class="admin-badge-green text-xs">Complete</span>
                                            @elseif ($status === 'partial')
                                                <span class="admin-badge-gold text-xs">Partial</span>
                                            @else
                                                <span class="admin-badge-slate text-xs">{{ ucfirst($status) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-sm text-slate-600 print:hidden whitespace-nowrap">{{ $chapter['topics_ready'] }}/{{ $chapter['topics_count'] }}</td>
                                        <td class="print:hidden">
                                            @if (! empty($chapter['has_pdf']))
                                                <span class="admin-badge-green text-xs">Yes</span>
                                            @else
                                                <span class="text-xs text-slate-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @empty
            <div class="admin-card p-10 text-center text-slate-400">
                No chapters found for this filter.
            </div>
        @endforelse
    </div>
</x-app-layout>
