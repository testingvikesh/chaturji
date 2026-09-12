@props([
    'title',
    'rows',
    'sort',
    'dir',
    'standardFilter',
    'subjectFilter',
    'badgeClass' => 'admin-badge-green',
])

@php
    $sortUrl = function (string $column) use ($sort, $dir, $standardFilter, $subjectFilter) {
        $nextDir = ($sort === $column && $dir === 'asc') ? 'desc' : 'asc';

        return route('admin.dashboard.materials', [
            'standard' => $standardFilter,
            'subject' => $subjectFilter,
            'sort' => $column,
            'dir' => $nextDir,
        ]);
    };

    $sortMark = function (string $column) use ($sort, $dir) {
        if ($sort !== $column) {
            return '';
        }

        return $dir === 'desc' ? ' ↓' : ' ↑';
    };
@endphp

<div class="admin-card overflow-hidden mb-6">
    <div class="admin-card-top"></div>
    <div class="admin-card-header flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="font-bold text-slate-900">{{ $title }}</h3>
            <p class="text-xs text-slate-500 mt-1">Uploaded chapters from materials table · click column headers to sort</p>
        </div>
        <span class="{{ $badgeClass }} text-xs">{{ $rows->count() }} chapters</span>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table w-full text-sm">
            <thead>
                <tr>
                    <th><a href="{{ $sortUrl('standard') }}" class="hover:text-brand-green">Standard{{ $sortMark('standard') }}</a></th>
                    <th><a href="{{ $sortUrl('subject') }}" class="hover:text-brand-green">Subject{{ $sortMark('subject') }}</a></th>
                    <th><a href="{{ $sortUrl('chapter_no') }}" class="hover:text-brand-green">Ch. No{{ $sortMark('chapter_no') }}</a></th>
                    <th><a href="{{ $sortUrl('chapter_name') }}" class="hover:text-brand-green">Chapter{{ $sortMark('chapter_name') }}</a></th>
                    <th><a href="{{ $sortUrl('status') }}" class="hover:text-brand-green">Status{{ $sortMark('status') }}</a></th>
                    <th><a href="{{ $sortUrl('topics_done') }}" class="hover:text-brand-green">Topics{{ $sortMark('topics_done') }}</a></th>
                    <th>PDF</th>
                    <th><a href="{{ $sortUrl('updated_at') }}" class="hover:text-brand-green">Updated{{ $sortMark('updated_at') }}</a></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="font-medium text-slate-900">{{ $row['standard'] }}</td>
                        <td class="text-slate-700">{{ $row['subject'] }}</td>
                        <td class="text-slate-600">{{ $row['chapter_no'] }}</td>
                        <td>
                            <p class="font-medium text-slate-900">{{ $row['chapter_name'] }}</p>
                            <p class="text-[11px] text-slate-400">#{{ $row['id'] }}</p>
                        </td>
                        <td>
                            @if ($row['status'] === 'complete')
                                <span class="admin-badge-green text-xs">Complete</span>
                            @elseif ($row['status'] === 'partial')
                                <span class="admin-badge-gold text-xs">Partial</span>
                            @else
                                <span class="admin-badge-slate text-xs">{{ ucfirst($row['status']) }}</span>
                            @endif
                        </td>
                        <td class="text-slate-700 whitespace-nowrap">
                            {{ $row['topics_done'] }}/{{ $row['topics_total'] }}
                            <span class="text-xs text-slate-400">({{ $row['ready_topics'] }} ready)</span>
                        </td>
                        <td>
                            @if ($row['has_pdf'])
                                <span class="admin-badge-green text-xs" title="{{ $row['pdf_name'] }}">Yes</span>
                            @else
                                <span class="admin-badge-slate text-xs">No</span>
                            @endif
                        </td>
                        <td class="text-xs text-slate-500 whitespace-nowrap">{{ $row['updated_at'] }}</td>
                    </tr>
                @empty
                    @include('admin.partials.empty-row', ['colspan' => 8, 'message' => 'No uploaded chapters for this medium'])
                @endforelse
            </tbody>
        </table>
    </div>
</div>
