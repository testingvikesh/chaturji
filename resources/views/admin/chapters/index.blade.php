<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">{{ $subject->name }} — Chapters</h2>
                <p class="text-sm text-slate-500 mt-1">{{ $subject->standard->name }} / Chapters</p>
            </div>
            <a href="{{ route('admin.subjects.chapters.create', $subject) }}" class="rounded-xl bg-brand-green px-4 py-2 text-sm font-semibold text-white">Add Chapter</a>
        </div>
    </x-slot>
    @include('admin.partials.alert')
    <div class="mb-4"><a href="{{ route('admin.standards.subjects.index', $subject->standard) }}" class="text-sm text-brand-green hover:underline">&larr; Back to Subjects</a></div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500"><tr><th class="px-4 py-3 text-left">Chapter</th><th class="px-4 py-3 text-left">Topics</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($chapters as $chapter)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $chapter->name }}</td>
                        <td class="px-4 py-3">{{ $chapter->topics_count }}</td>
                        <td class="px-4 py-3">
                            @if ($chapter->content)
                                <span class="admin-badge-green">Data Uploaded</span>
                            @else
                                {{ $chapter->is_active ? 'Active' : 'Inactive' }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="admin-action-group justify-end">
                                @if ($chapter->content)
                                    <x-admin.action-view :href="route('admin.upload-data.show', $chapter->content)" label="View uploaded data" />
                                @endif
                                <a href="{{ route('admin.chapters.topics.index', $chapter) }}" class="admin-action-icon-btn admin-action-icon-btn--view" title="Topics" aria-label="Topics">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                </a>
                                <x-admin.action-edit :href="route('admin.chapters.edit', $chapter)" label="Edit chapter" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400">No chapters yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
