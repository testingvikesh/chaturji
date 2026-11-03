<x-teacher-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="admin-page-title">Exam Paper Upload</h2>
                <p class="admin-page-subtitle">Upload a paper, then each student’s answer sheet, and check it</p>
            </div>
            <a href="{{ route('teacher.exam-papers.create') }}" class="rounded-xl bg-brand-green px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-green-dark">Upload paper</a>
        </div>
    </x-slot>

    <div class="admin-page">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="admin-card overflow-hidden">
            <div class="admin-table-wrap">
                <table class="admin-table min-w-[860px]">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Standard</th>
                            <th>Subject</th>
                            <th>Marks</th>
                            <th>Sheets</th>
                            <th>Checked</th>
                            <th>Date</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($papers as $paper)
                            <tr>
                                <td class="font-semibold text-slate-900">{{ $paper->title }}</td>
                                <td>{{ $paper->standard?->name }} · {{ $paper->standard?->mediumLabel() }}</td>
                                <td>{{ $paper->subject_name }}</td>
                                <td>{{ $paper->total_marks }}</td>
                                <td>{{ $paper->sheets_count }}</td>
                                <td>{{ $paper->checked_count }}</td>
                                <td class="whitespace-nowrap">{{ $paper->exam_date?->format('d M Y') ?? '—' }}</td>
                                <td class="whitespace-nowrap text-right text-xs font-semibold">
                                    <a href="{{ route('teacher.exam-papers.show', $paper) }}" class="text-brand-green hover:underline">Open</a>
                                    <form method="POST" action="{{ route('teacher.exam-papers.destroy', $paper) }}" class="inline" onsubmit="return confirm('Delete this paper and its answer sheets?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-red-500 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-500">No exam papers uploaded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-teacher-layout>
