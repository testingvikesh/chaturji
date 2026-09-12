@props(['tree'])

<div class="space-y-4" x-data="{ open: {} }">
    @foreach ($tree as $medium)
        @php $mediumKey = 'm-'.$medium['key']; @endphp
        <div class="admin-card overflow-hidden">
            <div class="admin-card-top"></div>
            <button
                type="button"
                class="admin-card-header w-full text-left flex flex-wrap items-center justify-between gap-3"
                @click="open['{{ $mediumKey }}'] = !open['{{ $mediumKey }}']"
            >
                <div>
                    <h3 class="font-bold text-slate-900">{{ $medium['name'] }} Medium</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ $medium['standards_count'] }} standard(s) · {{ $medium['subjects_count'] }} subject(s) · {{ $medium['chapters_count'] }} chapter(s) · {{ $medium['topics_ready'] }}/{{ $medium['topics_count'] }} topics ready
                    </p>
                </div>
                <span class="admin-badge-green text-xs">{{ $medium['chapters_count'] }} chapters</span>
            </button>

            <div class="px-4 pb-4 sm:px-5 space-y-3 print:block" x-show="open['{{ $mediumKey }}'] !== false" x-cloak>
                @foreach ($medium['standards'] as $standard)
                    @php $stdKey = $mediumKey.'-s-'.$standard['key']; @endphp
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70" x-data="{ showStd: true }">
                        <button
                            type="button"
                            class="w-full px-4 py-3 text-left flex flex-wrap items-center justify-between gap-2"
                            @click="showStd = !showStd"
                        >
                            <div>
                                <p class="font-semibold text-slate-900">{{ $standard['name'] }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $standard['subjects_count'] }} subject(s) · {{ $standard['chapters_count'] }} chapter(s) · {{ $standard['topics_ready'] }}/{{ $standard['topics_count'] }} topics ready
                                </p>
                            </div>
                            <span class="text-xs font-semibold text-brand-green">{{ $standard['subjects_count'] }} subjects</span>
                        </button>

                        <div class="px-3 pb-3 space-y-3 print:block" x-show="showStd">
                            @foreach ($standard['subjects'] as $subject)
                                @php $subKey = $stdKey.'-sub-'.$loop->index; @endphp
                                <div class="rounded-xl border border-white bg-white shadow-sm">
                                    <button
                                        type="button"
                                        class="w-full px-4 py-3 text-left flex flex-wrap items-center justify-between gap-2"
                                        @click="open['{{ $subKey }}'] = !open['{{ $subKey }}']"
                                    >
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $subject['name'] }}</p>
                                            <p class="text-xs text-slate-500">
                                                {{ $subject['chapters_count'] }} chapter(s) · {{ $subject['topics_ready'] }}/{{ $subject['topics_count'] }} topics ready
                                            </p>
                                        </div>
                                        <span class="admin-badge-slate text-xs">{{ $subject['topics_count'] }} topics</span>
                                    </button>

                                    <div class="px-3 pb-3 space-y-2 print:block" x-show="open['{{ $subKey }}']" x-cloak>
                                        @foreach ($subject['chapters'] as $chapter)
                                            @php $chKey = $subKey.'-ch-'.$chapter['id']; @endphp
                                            <div class="rounded-lg border border-slate-100">
                                                <button
                                                    type="button"
                                                    class="w-full px-3 py-2.5 text-left flex flex-wrap items-center justify-between gap-2"
                                                    @click="open['{{ $chKey }}'] = !open['{{ $chKey }}']"
                                                >
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium text-slate-900">
                                                            <span class="text-slate-400 mr-1">Ch {{ $chapter['chapter_no'] }}</span>
                                                            {{ $chapter['chapter_name'] }}
                                                        </p>
                                                        <p class="text-[11px] text-slate-400">#{{ $chapter['id'] }} · {{ $chapter['topics_ready'] }}/{{ $chapter['topics_count'] }} topics ready</p>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        @if ($chapter['status'] === 'complete')
                                                            <span class="admin-badge-green text-xs">Complete</span>
                                                        @elseif ($chapter['status'] === 'partial')
                                                            <span class="admin-badge-gold text-xs">Partial</span>
                                                        @else
                                                            <span class="admin-badge-slate text-xs">{{ ucfirst($chapter['status']) }}</span>
                                                        @endif
                                                        @if ($chapter['has_pdf'])
                                                            <span class="admin-badge-green text-xs">PDF</span>
                                                        @endif
                                                    </div>
                                                </button>

                                                <div class="px-3 pb-3 print:block" x-show="open['{{ $chKey }}']" x-cloak>
                                                    @if (empty($chapter['topics']))
                                                        @if (($chapter['topics_count'] ?? 0) > 0)
                                                            <p class="text-xs text-slate-500 py-2">{{ $chapter['topics_ready'] }}/{{ $chapter['topics_count'] }} topics ready. Topic names are not loaded on this report to keep the page fast.</p>
                                                        @else
                                                            <p class="text-xs text-slate-400 py-2">No topics generated for this chapter.</p>
                                                        @endif
                                                    @else
                                                        <div class="overflow-x-auto">
                                                            <table class="admin-table w-full text-sm">
                                                                <thead>
                                                                    <tr>
                                                                        <th>No</th>
                                                                        <th>Topic</th>
                                                                        <th>Status</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($chapter['topics'] as $topic)
                                                                        <tr>
                                                                            <td class="text-slate-500 whitespace-nowrap">{{ $chapter['chapter_no'] }}.{{ $topic['order'] ?: $loop->iteration }}</td>
                                                                            <td class="font-medium text-slate-900">{{ $topic['name'] }}</td>
                                                                            <td>
                                                                                @if ($topic['ready'])
                                                                                    <span class="admin-badge-green text-xs">Ready</span>
                                                                                @else
                                                                                    <span class="admin-badge-slate text-xs">Pending</span>
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
