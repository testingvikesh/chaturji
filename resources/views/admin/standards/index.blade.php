<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Standards" subtitle="Material counts by medium, standard, subject and topic">
            <x-slot name="actions">
                <a href="{{ route('admin.dashboard.materials') }}" class="admin-btn-secondary">
                    Material Report
                </a>
                <a href="{{ route('admin.standards.create') }}" class="admin-btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Standard
                </a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page">
        @include('admin.partials.alert')

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">All Standards</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $standards->count() }} standards · subjects & topics from materials</p>
                </div>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Standard</th>
                            <th>English Subjects</th>
                            <th>Gujarati Subjects</th>
                            <th>Chapters</th>
                            <th>Topics</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($standards as $standard)
                            <tr>
                                <td class="font-semibold text-slate-900">
                                    <a href="{{ route('admin.dashboard.materials', ['standard' => preg_replace('/\D+/', '', (string) ($standard->slug ?: $standard->name))]) }}" class="hover:text-brand-green">
                                        {{ $standard->name }}
                                    </a>
                                </td>
                                <td><span class="admin-badge-slate">{{ $standard->english_subjects }} subjects</span></td>
                                <td><span class="admin-badge-slate">{{ $standard->gujarati_subjects }} subjects</span></td>
                                <td>{{ $standard->chapters }}</td>
                                <td>
                                    <span class="font-medium text-slate-900">{{ $standard->topics_ready }}</span>
                                    <span class="text-slate-400">/ {{ $standard->topics }} ready</span>
                                </td>
                                <td>
                                    @if ($standard->is_active)
                                        <span class="admin-badge-green">Active</span>
                                    @else
                                        <span class="admin-badge-slate">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="admin-action-group justify-end">
                                        <a href="{{ route('admin.standards.subjects.index', $standard) }}" class="admin-action-icon-btn admin-action-icon-btn--view" title="Subjects" aria-label="Subjects">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                        </a>
                                        <x-admin.action-edit :href="route('admin.standards.edit', $standard)" label="Edit standard" />
                                        <x-admin.action-delete :action="route('admin.standards.destroy', $standard)" label="Delete standard" confirm="Delete standard and all related content?" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            @include('admin.partials.empty-row', ['colspan' => 7, 'message' => 'No standards yet', 'hint' => 'Add standards or run the database seeder.'])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
