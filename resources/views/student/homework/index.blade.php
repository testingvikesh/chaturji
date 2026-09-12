<x-student-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="admin-section-label">Student</span>
                <h2 class="admin-page-title">Self Homework</h2>
                <p class="admin-page-subtitle">Create chapter/topic homework, then attempt and upload answers</p>
            </div>
            <a href="{{ $createUrl ?? url('/student/homework/create') }}" class="inline-flex h-10 shrink-0 items-center justify-center self-start rounded-lg bg-brand-green px-4 text-sm font-semibold leading-none text-white hover:bg-brand-green-dark transition">
                + Create Self Homework
            </a>
        </div>
    </x-slot>

    <div class="admin-page space-y-8">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif

        @if (empty($selfHomeworkDbReady))
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Run this MySQL once, then reload:
                <pre class="mt-2 overflow-x-auto rounded-lg border border-amber-100 bg-white p-2 text-xs">ALTER TABLE `homeworks`
  ADD COLUMN `student_id` BIGINT UNSIGNED NULL AFTER `teacher_id`,
  ADD COLUMN `is_self_homework` TINYINT(1) NOT NULL DEFAULT 0 AFTER `student_id`;</pre>
            </div>
        @endif

        <div class="admin-card overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="font-bold text-slate-900">My Self Homework</h3>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table w-full">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Chapter</th>
                            <th>Questions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($selfHomeworks ?? []) as $hw)
                            <tr>
                                <td class="font-semibold text-slate-900">{{ $hw->title }}</td>
                                <td>{{ optional($hw->subject)->name ?? '—' }}</td>
                                <td>{{ optional($hw->chapter)->name ?? '—' }}</td>
                                <td>{{ $hw->questions_count ?? 0 }}</td>
                                <td>
                                    <a href="{{ url('/student/homework/'.$hw->id) }}" class="text-xs font-semibold text-brand-green hover:underline">Attempt</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-slate-500 py-10">
                                    No self homework yet.
                                    <a href="{{ $createUrl ?? url('/student/homework/create') }}" class="font-semibold text-brand-green hover:underline">Create one</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-card overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="font-bold text-slate-900">Teacher Homework</h3>
                <p class="text-xs text-slate-500 mt-0.5">For {{ $standardName ?? 'your standard' }}</p>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table w-full">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Questions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($teacherHomeworks ?? []) as $hw)
                            <tr>
                                <td class="font-semibold text-slate-900">{{ $hw->title }}</td>
                                <td>{{ optional($hw->subject)->name ?? '—' }}</td>
                                <td>{{ optional($hw->teacher)->name ?? 'Teacher' }}</td>
                                <td>{{ $hw->questions_count ?? 0 }}</td>
                                <td>
                                    <a href="{{ url('/student/homework/'.$hw->id) }}" class="text-xs font-semibold text-brand-green hover:underline">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-slate-500 py-10">No teacher homework yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-student-layout>
