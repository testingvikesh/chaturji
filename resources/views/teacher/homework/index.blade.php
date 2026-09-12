<x-teacher-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="admin-page-title">My Homework</h2>
                <p class="admin-page-subtitle">Create and assign homework to students</p>
            </div>
            <a href="{{ route('teacher.homework.create') }}" class="rounded-xl bg-brand-green text-white px-4 py-2.5 text-sm font-semibold hover:bg-brand-green-dark transition">+ Create Homework</a>
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
                        <th>Chapter</th>
                        <th>Questions</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($homeworks as $homework)
                        <tr>
                            <td class="font-semibold">{{ $homework->title }}</td>
                            <td>{{ str_replace('_', ' ', ucwords($homework->standard, '_')) }}</td>
                            <td>{{ $homework->subject?->name ?? '—' }}</td>
                            <td>{{ $homework->chapter?->name ?? '—' }}</td>
                            <td>{{ $homework->questions_count }}</td>
                            <td>{{ $homework->due_at?->format('d M Y') ?? '—' }}</td>
                            <td>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $homework->status === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($homework->status) }}
                                </span>
                            </td>
                            <td class="space-x-2 whitespace-nowrap">
                                <a href="{{ route('teacher.homework.show', $homework) }}" class="text-xs font-semibold text-brand-green hover:underline">View</a>
                                <a href="{{ route('teacher.homework.edit', $homework) }}" class="text-xs font-semibold text-slate-600 hover:underline">Edit</a>
                                <form method="POST" action="{{ route('teacher.homework.destroy', $homework) }}" class="inline" onsubmit="return confirm('Delete this homework?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-red-500 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-slate-500 py-8">No homework yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-teacher-layout>
