<x-teacher-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="admin-page-title">My Exams</h2>
                <p class="admin-page-subtitle">Create and publish exams for students</p>
            </div>
            <a href="{{ route('teacher.exams.create') }}" class="rounded-xl bg-brand-green text-white px-4 py-2.5 text-sm font-semibold hover:bg-brand-green-dark transition">+ Create Exam</a>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="admin-card overflow-hidden">
            <table class="admin-table w-full">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Standard</th>
                        <th>Subject</th>
                        <th>Questions</th>
                        <th>Marks</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($exams as $exam)
                        <tr>
                            <td class="font-semibold">{{ $exam->title }}</td>
                            <td>{{ str_replace('_', ' ', ucwords($exam->standard, '_')) }}</td>
                            <td>{{ $exam->subject?->name ?? '—' }}</td>
                            <td>{{ $exam->questions_count }}</td>
                            <td>{{ $exam->total_marks }}</td>
                            <td>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $exam->status === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($exam->status) }}
                                </span>
                            </td>
                            <td class="space-x-2 whitespace-nowrap">
                                <a href="{{ route('teacher.exams.show', $exam) }}" class="text-xs font-semibold text-brand-green hover:underline">View</a>
                                <a href="{{ route('teacher.exams.edit', $exam) }}" class="text-xs font-semibold text-slate-600 hover:underline">Edit</a>
                                <form method="POST" action="{{ route('teacher.exams.destroy', $exam) }}" class="inline" onsubmit="return confirm('Delete this exam?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-red-500 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-slate-500 py-8">No exams yet. Create your first exam.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-teacher-layout>
