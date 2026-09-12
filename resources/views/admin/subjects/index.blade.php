<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">{{ $standard->name }} — Subjects</h2>
                <p class="text-sm text-slate-500 mt-1"><a href="{{ route('admin.standards.index') }}" class="text-brand-green hover:underline">Standards</a> / Subjects</p>
            </div>
            <a href="{{ route('admin.standards.subjects.create', $standard) }}" class="rounded-xl bg-brand-green px-4 py-2 text-sm font-semibold text-white">Add Subject</a>
        </div>
    </x-slot>
    @include('admin.partials.alert')
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500"><tr><th class="px-4 py-3 text-left">Subject</th><th class="px-4 py-3 text-left">Chapters</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($subjects as $subject)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $subject->name }}</td>
                        <td class="px-4 py-3">{{ $subject->chapters_count }}</td>
                        <td class="px-4 py-3">{{ $subject->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="admin-action-group justify-end">
                                <a href="{{ route('admin.subjects.chapters.index', $subject) }}" class="admin-action-icon-btn admin-action-icon-btn--view" title="Chapters" aria-label="Chapters">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                </a>
                                <x-admin.action-edit :href="route('admin.subjects.edit', $subject)" label="Edit subject" />
                                <x-admin.action-delete :action="route('admin.subjects.destroy', $subject)" label="Delete subject" confirm="Delete this subject?" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400">No subjects yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
