<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="admin-section-label">Curriculum</span>
                <h2 class="admin-page-title">Upload Data</h2>
                <p class="admin-page-subtitle">Chapter-wise material JSON uploads by standard & subject</p>
            </div>
            <a href="{{ route('admin.upload-data.create') }}" class="admin-btn-primary">Upload New Data</a>
        </div>
    </x-slot>

    @include('admin.partials.alert')

    <div class="admin-card">
        <div class="admin-card-top"></div>
        <div class="admin-card-header">
            <h3 class="font-bold text-slate-900">Uploaded Chapter Data</h3>
            <p class="text-xs text-slate-500">{{ $uploads->total() }} upload(s)</p>
        </div>
        <div class="overflow-x-auto">
            <table class="admin-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th>Chapter</th>
                        <th>Standard / Subject</th>
                        <th>Language</th>
                        <th>Questions</th>
                        <th>Uploaded By</th>
                        <th>Date</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($uploads as $upload)
                        <tr>
                            <td>
                                <p class="font-semibold text-slate-900">{{ $upload->title }}</p>
                                <p class="text-xs text-slate-500">{{ $upload->source_filename }}</p>
                            </td>
                            <td>
                                <p class="font-medium">{{ $upload->chapter->subject->standard->name }}</p>
                                <p class="text-xs text-slate-500">{{ $upload->chapter->subject->name }} / {{ $upload->chapter->name }}</p>
                            </td>
                            <td><span class="admin-badge-gold capitalize">{{ $upload->languageLabel() }}</span></td>
                            <td><span class="admin-badge-green">{{ $upload->total_questions }}</span></td>
                            <td>{{ $upload->uploader?->name ?? '—' }}</td>
                            <td>{{ $upload->created_at->format('d M Y, h:i A') }}</td>
                            <td class="text-right">
                                <div class="admin-action-group justify-end">
                                    <x-admin.action-view :href="route('admin.upload-data.show', $upload)" label="View upload" />
                                    <x-admin.action-edit :href="route('admin.upload-data.edit', $upload)" label="Edit upload" />
                                    <x-admin.action-delete :action="route('admin.upload-data.destroy', $upload)" label="Delete upload" confirm="Delete this uploaded data?" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-400">No data uploaded yet. Click "Upload New Data" to start.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($uploads->hasPages())
            <div class="px-5 py-4 border-t border-slate-100">{{ $uploads->links() }}</div>
        @endif
    </div>
</x-app-layout>
