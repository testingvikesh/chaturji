<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">{{ $chapter->name }} — Topics</h2>
                <p class="text-sm text-slate-500 mt-1">{{ $chapter->subject->standard->name }} / {{ $chapter->subject->name }} / Topics</p>
            </div>
            <a href="{{ route('admin.chapters.topics.create', $chapter) }}" class="rounded-xl bg-brand-green px-4 py-2 text-sm font-semibold text-white">Add Topic</a>
        </div>
    </x-slot>
    @include('admin.partials.alert')
    <div class="mb-4"><a href="{{ route('admin.subjects.chapters.index', $chapter->subject) }}" class="text-sm text-brand-green hover:underline">&larr; Back to Chapters</a></div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500"><tr><th class="px-4 py-3 text-left">Topic</th><th class="px-4 py-3 text-left">Order</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($topics as $topic)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $topic->name }}</td>
                        <td class="px-4 py-3">{{ $topic->sort_order }}</td>
                        <td class="px-4 py-3">{{ $topic->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="admin-action-group justify-end">
                                <x-admin.action-edit :href="route('admin.topics.edit', $topic)" label="Edit topic" />
                                <x-admin.action-delete :action="route('admin.topics.destroy', $topic)" label="Delete topic" confirm="Delete this topic?" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400">No topics yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
