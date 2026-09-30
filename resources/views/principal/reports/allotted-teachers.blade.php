<x-principal-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Principal</span>
            <h2 class="admin-page-title">Allotted Teachers</h2>
            <p class="admin-page-subtitle">Teachers assigned to the standards allotted to you</p>
        </div>
    </x-slot>

    <div class="admin-page">
        @include('principal.partials.reports-nav')

        @if (! $hasAllotments)
            <div class="admin-card">
                <div class="p-8 text-center text-sm text-slate-500">
                    No standard allotted yet. Ask admin to allot standards to your principal account.
                </div>
            </div>
        @else
            <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
                @include('admin.partials.stat-card', ['label' => 'Teachers', 'value' => $summary['teachers']])
                @include('admin.partials.stat-card', ['label' => 'Subject allotments', 'value' => $summary['assignments']])
                @include('admin.partials.stat-card', ['label' => 'Unique subjects', 'value' => $summary['unique_subjects']])
                @include('admin.partials.stat-card', ['label' => 'Your standards', 'value' => $summary['standards']])
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
                        <div class="lg:col-span-3">
                            <label class="admin-label">Standard</label>
                            <select name="standard_id" class="admin-select">
                                <option value="">All allotted standards</option>
                                @foreach ($standards as $standard)
                                    <option value="{{ $standard->id }}" @selected((string) ($filters['standard_id'] ?? '') === (string) $standard->id)>{{ $standard->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lg:col-span-3 flex gap-2 items-end">
                            <button type="submit" class="admin-btn-filter flex-1">Filter</button>
                            @if (! empty(array_filter($filters ?? [])))
                                <a href="{{ route('principal.reports.allotted-teachers') }}" class="admin-btn-ghost">Clear</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <div>
                        <h3 class="font-bold text-slate-900">Teachers on your standards</h3>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $teachers->total() }} teacher(s)</p>
                    </div>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Teacher</th>
                                <th>Contact</th>
                                <th>Allotted subjects</th>
                                <th class="text-right">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($teachers as $teacher)
                                @php $rows = $teacher->teacherSubjects; @endphp
                                <tr>
                                    <td>
                                        <div class="admin-user-cell">
                                            <span class="admin-avatar">{{ strtoupper(substr($teacher->name, 0, 2)) }}</span>
                                            <p class="font-semibold text-slate-900">{{ $teacher->name }}</p>
                                        </div>
                                    </td>
                                    <td class="text-sm">
                                        <p>{{ $teacher->mobile ?: '—' }}</p>
                                        <p class="text-xs text-slate-400">{{ $teacher->email ?: '' }}</p>
                                    </td>
                                    <td>
                                        @if ($rows->isEmpty())
                                            <span class="text-sm text-slate-400">No subjects</span>
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
                                    'message' => 'No allotted teachers found',
                                    'hint' => 'Teachers appear here after they select subjects in Settings for your standards.',
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($teachers->hasPages())
                    <div class="p-4">{{ $teachers->links() }}</div>
                @endif
            </div>
        @endif
    </div>
</x-principal-layout>
