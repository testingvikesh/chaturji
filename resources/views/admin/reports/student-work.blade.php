<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header
            title="Student Work Report"
            subtitle="Logins · exam / homework attempts · objective submit · work done or not">
            <x-slot name="actions">
                <a href="{{ route('admin.reports.student-logins') }}" class="admin-btn-secondary">Student logins</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page space-y-5">
        @unless ($workAttemptsEnabled)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Run <code class="font-mono text-xs">php artisan migrate</code> on the server to enable objective attempt logging. Exam/homework answer-sheet uploads still count as work.
            </div>
        @endunless

        <div class="grid sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5 gap-4">
            @include('admin.partials.stat-card', ['label' => 'Students', 'value' => $summary['students']])
            @include('admin.partials.stat-card', ['label' => 'Logged in', 'value' => $summary['login_students']])
            @include('admin.partials.stat-card', ['label' => 'Login attempts', 'value' => $summary['login_attempts']])
            @include('admin.partials.stat-card', ['label' => 'Exam attempted', 'value' => $summary['exam_students']])
            @include('admin.partials.stat-card', ['label' => 'Homework attempted', 'value' => $summary['homework_students']])
            @include('admin.partials.stat-card', ['label' => 'Objective attempted', 'value' => $summary['objective_students']])
            @include('admin.partials.stat-card', ['label' => 'Work done', 'value' => $summary['worked']])
            @include('admin.partials.stat-card', ['label' => 'Login only', 'value' => $summary['login_only']])
            @include('admin.partials.stat-card', ['label' => 'No activity', 'value' => $summary['inactive']])
        </div>

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-body">
                <form method="GET" class="admin-filter-grid">
                    <div class="lg:col-span-3">
                        <label class="admin-label">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Name, mobile, email..." class="admin-input">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Work status</label>
                        <select name="work" class="admin-select">
                            <option value="">All</option>
                            <option value="worked" @selected($filters['work'] === 'worked')>Work done</option>
                            <option value="login_only" @selected($filters['work'] === 'login_only')>Login only</option>
                            <option value="inactive" @selected($filters['work'] === 'inactive')>No activity</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Medium</label>
                        <select name="medium" class="admin-select">
                            <option value="">All</option>
                            @foreach ($mediums as $key => $label)
                                <option value="{{ $key }}" @selected($filters['medium'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Standard</label>
                        <input type="text" name="standard" value="{{ $filters['standard'] }}" placeholder="e.g. 10" class="admin-input">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">From</label>
                        <input type="date" name="from" value="{{ $filters['from'] }}" class="admin-input">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">To</label>
                        <input type="date" name="to" value="{{ $filters['to'] }}" class="admin-input">
                    </div>
                    <div class="lg:col-span-3 flex gap-2 items-end">
                        <button type="submit" class="admin-btn-filter flex-1">Filter</button>
                        <a href="{{ route('admin.reports.student-work') }}" class="admin-btn-ghost">Today</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">Student activity</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d M Y') }}
                        → {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d M Y') }}
                        · {{ $rows->count() }} shown
                    </p>
                </div>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Medium · Std</th>
                            <th>Logins</th>
                            <th>Exam</th>
                            <th>Homework</th>
                            <th>Objective</th>
                            <th>Work?</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>
                                    <p class="font-semibold text-slate-900">{{ $row['name'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $row['mobile'] ?: '—' }}</p>
                                </td>
                                <td class="text-sm">
                                    <span class="capitalize">{{ $row['medium'] ?: '—' }}</span>
                                    · {{ $row['standard'] ?: '—' }}
                                </td>
                                <td class="text-sm">
                                    <p class="font-semibold">{{ $row['logins'] }}</p>
                                    @if ($row['last_login'])
                                        <p class="text-[11px] text-slate-400">{{ \Illuminate\Support\Carbon::parse($row['last_login'])->format('d M, h:i A') }}</p>
                                    @endif
                                </td>
                                <td class="text-sm font-semibold {{ $row['exam_attempts'] > 0 ? 'text-brand-green' : 'text-slate-400' }}">
                                    {{ $row['exam_attempts'] > 0 ? $row['exam_attempts'].' attempt' : 'No' }}
                                </td>
                                <td class="text-sm font-semibold {{ $row['homework_attempts'] > 0 ? 'text-brand-green' : 'text-slate-400' }}">
                                    {{ $row['homework_attempts'] > 0 ? $row['homework_attempts'].' attempt' : 'No' }}
                                </td>
                                <td class="text-sm font-semibold {{ $row['objective_attempts'] > 0 ? 'text-brand-green' : 'text-slate-400' }}">
                                    {{ $row['objective_attempts'] > 0 ? $row['objective_attempts'].' submit' : 'No' }}
                                </td>
                                <td>
                                    @if ($row['did_work'])
                                        <span class="admin-badge-green">Yes</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 text-slate-500 border border-slate-200 px-2.5 py-1 text-xs font-semibold">No</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['status'] === 'worked')
                                        <span class="admin-badge-green">{{ $row['status_label'] }}</span>
                                    @elseif ($row['status'] === 'login_only')
                                        <span class="admin-badge-gold">{{ $row['status_label'] }}</span>
                                    @else
                                        <span class="admin-badge-slate">{{ $row['status_label'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            @include('admin.partials.empty-row', [
                                'colspan' => 8,
                                'message' => 'No students found for these filters',
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
