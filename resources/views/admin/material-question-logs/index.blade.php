<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Material Question Edit Log" subtitle="Teacher edits to subjective and objective questions">
            <x-slot name="actions">
                <form method="GET" class="flex flex-wrap gap-2">
                    <input type="text" name="search" value="{{ $filters['search'] }}" class="admin-input !py-2" placeholder="Search teacher / topic / question">
                    <select name="medium" class="admin-select !py-2 !w-auto">
                        <option value="">All mediums</option>
                        <option value="english" @selected($filters['medium'] === 'english')>English</option>
                        <option value="gujarati" @selected($filters['medium'] === 'gujarati')>Gujarati</option>
                    </select>
                    <button class="admin-btn-secondary" type="submit">Filter</button>
                </form>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page">
        @include('admin.partials.alert')

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Teacher</th>
                            <th>Material</th>
                            <th>Type</th>
                            <th>Question change</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="text-xs text-slate-500 whitespace-nowrap">{{ $log->created_at?->format('d M Y, h:i A') }}</td>
                                <td>
                                    <p class="font-semibold text-slate-900">{{ $log->teacher?->name ?? '—' }}</p>
                                    <p class="text-xs text-slate-400">{{ $log->teacher?->mobile }}</p>
                                </td>
                                <td class="text-sm">
                                    <p class="font-semibold text-slate-800">{{ $log->topic_title ?: 'Topic' }}</p>
                                    <p class="text-xs text-slate-500">{{ collect([$log->medium, $log->subject_name, $log->chapter_name])->filter()->implode(' · ') }}</p>
                                </td>
                                <td><span class="admin-badge-slate">{{ \App\Models\ChapterQuestion::labelForType((string) $log->question_type) }}</span></td>
                                <td class="text-sm text-slate-700 max-w-xs">
                                    <p class="truncate" title="{{ $log->new_question_text }}">{{ \Illuminate\Support\Str::limit($log->new_question_text, 80) }}</p>
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.material-question-logs.show', $log) }}" class="admin-btn-secondary text-xs py-2 px-3 inline-flex">View</a>
                                </td>
                            </tr>
                        @empty
                            @include('admin.partials.empty-row', ['colspan' => 6, 'message' => 'No question edits yet', 'hint' => 'Teacher material question updates will appear here.'])
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="p-4">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
