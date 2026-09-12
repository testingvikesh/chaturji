<x-teacher-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <span class="admin-section-label">Teacher</span>
                <h2 class="admin-page-title">Today's Exam</h2>
                <p class="admin-page-subtitle">Track standard · subject · chapter · topic — completed topics — preview & generate exam</p>
            </div>
            <a
                href="{{ route('teacher.todays-exam.create', ['date' => $date]) }}"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-brand-green px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-green-dark"
            >
                + Add Topics
            </a>
        </div>
    </x-slot>

    <div class="admin-page space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-brand-green-200 bg-brand-green-50 px-4 py-3 text-sm text-brand-green-dark">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <form method="GET" action="{{ route('teacher.todays-exam.index') }}" class="admin-card p-4 sm:p-5">
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Date</label>
                    <input type="date" name="date" value="{{ $date }}" class="rounded-xl border-slate-200 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Standard</label>
                    <select name="standard" class="rounded-xl border-slate-200 text-sm min-w-[10rem]">
                        <option value="">All standards</option>
                        @foreach ($standards as $slug => $name)
                            <option value="{{ $slug }}" @selected($standard === $slug)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-xl bg-brand-green px-4 py-2 text-sm font-semibold text-white hover:bg-brand-green-dark">Show Report</button>
            </div>
        </form>

        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @include('admin.partials.stat-card', ['label' => 'Total Topics', 'value' => $stats['total']])
            @include('admin.partials.stat-card', ['label' => 'Completed', 'value' => $stats['completed']])
            @include('admin.partials.stat-card', ['label' => 'Remaining', 'value' => $stats['remaining']])
            @include('admin.partials.stat-card', ['label' => 'Exam Generated', 'value' => $stats['exam_generated']])
        </div>

        <div class="admin-card overflow-hidden">
            <form id="generate-todays-exam" method="POST" action="{{ route('teacher.todays-exam.generate-exam') }}" class="hidden" aria-hidden="true">
                @csrf
            </form>

            @if ($logs->where('status', 'completed')->whereNull('exam_id')->isNotEmpty())
                <div class="border-b border-slate-100 bg-brand-green-50/50 px-4 py-4 sm:px-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Generate Exam</h3>
                        <p class="text-xs text-slate-500">Select completed rows, then choose marks to preview exam</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($markOptions as $marks)
                            <button
                                type="submit"
                                form="generate-todays-exam"
                                name="target_marks"
                                value="{{ $marks }}"
                                class="rounded-xl border border-brand-green bg-white px-4 py-2 text-sm font-bold text-brand-green-dark hover:bg-brand-green hover:text-white transition"
                                onclick="return document.querySelectorAll('[form=generate-todays-exam]:checked').length > 0 || (alert('Please select at least one completed topic.'), false)"
                            >
                                {{ $marks }} Marks
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="admin-card-header border-b-0 pb-0">
                <h3 class="font-bold text-slate-900">Exam Report — {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h3>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table w-full text-sm">
                    <thead>
                        <tr>
                            <th class="w-10"></th>
                            <th>Standard</th>
                            <th>Subject</th>
                            <th>Chapter</th>
                            <th>Topic</th>
                            <th>Status</th>
                            <th>Exam</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>
                                    @if ($log->isCompleted() && ! $log->hasExam())
                                        <input type="checkbox" form="generate-todays-exam" name="exam_log_ids[]" value="{{ $log->id }}" class="rounded border-slate-300 text-brand-green">
                                    @endif
                                </td>
                                    <td>{{ $standards[$log->standard] ?? $log->standard }}</td>
                                    <td>{{ $log->subject?->name ?? '—' }}</td>
                                    <td>{{ $log->chapter?->name ?? '—' }}</td>
                                    <td class="font-medium text-slate-900">{{ $log->topic?->name ?? '—' }}</td>
                                    <td>
                                        @if ($log->isCompleted())
                                            <span class="admin-badge-green text-xs">Completed</span>
                                        @else
                                            <span class="admin-badge-slate text-xs">Remaining</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($log->exam)
                                            <a href="{{ route('teacher.exams.show', $log->exam) }}" class="text-brand-green font-semibold hover:underline text-xs">
                                                {{ $log->target_marks ? $log->target_marks.' marks' : 'View' }}
                                            </a>
                                        @else
                                            <span class="text-slate-400 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap space-x-2">
                                        @if (! $log->hasExam())
                                            @if ($log->isRemaining())
                                                <form method="POST" action="{{ route('teacher.todays-exam.status', $log) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="completed">
                                                    <input type="hidden" name="target_marks" value="20">
                                                    <button type="submit" class="text-xs font-semibold text-brand-green hover:underline" title="Marks exam 20 by default">Mark Completed</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('teacher.todays-exam.status', $log) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="remaining">
                                                    <button type="submit" class="text-xs font-semibold text-slate-500 hover:underline">Mark Remaining</button>
                                                </form>
                                            @endif
                                        @endif
                                        <form
                                            method="POST"
                                            action="{{ route('teacher.todays-exam.destroy', $log) }}"
                                            class="inline"
                                            onsubmit="return confirm(@js($log->hasExam()
                                                ? 'Remove this topic from today\'s report? Exam will stay in My Exams.'
                                                : 'Remove this exam entry?'))"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-12 text-slate-500">
                                        No exam entries for this date. Click <strong>+ Add Topics</strong> to start.
                                    </td>
                                </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-teacher-layout>
