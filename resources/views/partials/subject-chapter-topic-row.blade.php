@props([
    'subjectName',
    'chapterName' => null,
    'topicName' => null,
    'readerNav' => [],
    'subjectId' => null,
    'materialId' => null,
    'topicId' => null,
    'pageMode' => false,
    'pages' => [],
])

@php
    $metaChipBase = 'material-reader-meta-chip flex min-h-9 w-full items-center justify-center gap-1 rounded-lg border-2 px-2 py-1.5 text-xs sm:text-sm font-semibold leading-snug shadow-sm text-center';
    $metaChipLabel = 'shrink-0 uppercase text-slate-600';
    $metaChipSep = 'shrink-0 text-slate-400';
    $metaChipValue = 'min-w-0 break-words leading-snug';
    $chapterDisplay = $chapterName ?: '—';
    $topicDisplay = $topicName ?: '—';
    $canChange = is_array($readerNav) && count($readerNav) > 0;

    $currentSubject = collect($readerNav)->firstWhere('id', (int) $subjectId) ?? ($readerNav[0] ?? null);
    $chapters = $currentSubject['chapters'] ?? [];
    $currentChapter = collect($chapters)->firstWhere('id', (int) $materialId) ?? ($chapters[0] ?? null);
    $topics = $currentChapter['topics'] ?? [];

    $selectBase = 'material-reader-meta-select appearance-none bg-transparent border-0 p-0 m-0 font-semibold text-xs sm:text-sm leading-snug cursor-pointer focus:outline-none focus:ring-0 max-w-[12rem] sm:max-w-[16rem] truncate text-center';
@endphp

@if ($canChange)
    <div class="material-reader-meta-row border-b-2 border-brand-green-200 bg-gradient-to-r from-brand-green-50 via-white to-amber-50 px-2 py-2.5 sm:px-4 sm:py-3" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0.5rem;width:100%;">
        <label class="{{ $metaChipBase }} border-brand-green bg-white cursor-pointer hover:bg-brand-green-50 transition" title="Change subject">
            <span class="{{ $metaChipLabel }}">Subject</span>
            <span class="{{ $metaChipSep }}">:</span>
            <span class="relative inline-flex min-w-0 max-w-full items-center gap-0.5">
                <select
                    class="{{ $selectBase }} text-brand-green"
                    aria-label="Change subject"
                    onchange="if (this.value) window.location.href = this.value"
                >
                    @foreach ($readerNav as $navSubject)
                        <option
                            value="{{ $navSubject['url'] }}"
                            @selected((int) $navSubject['id'] === (int) $subjectId)
                        >{{ $navSubject['name'] }}</option>
                    @endforeach
                </select>
                <svg class="h-3.5 w-3.5 shrink-0 text-brand-green pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </span>
        </label>

        <label class="{{ $metaChipBase }} border-slate-300 bg-slate-50 cursor-pointer hover:bg-slate-100 transition" title="Change chapter">
            <span class="{{ $metaChipLabel }}">Chapter</span>
            <span class="{{ $metaChipSep }}">:</span>
            <span class="relative inline-flex min-w-0 max-w-full items-center gap-0.5">
                <select
                    class="{{ $selectBase }} text-slate-800"
                    aria-label="Change chapter"
                    onchange="if (this.value) window.location.href = this.value"
                >
                    @foreach ($chapters as $navChapter)
                        <option
                            value="{{ $navChapter['url'] }}"
                            @selected((int) $navChapter['id'] === (int) $materialId)
                        >{{ $navChapter['name'] }}</option>
                    @endforeach
                </select>
                <svg class="h-3.5 w-3.5 shrink-0 text-slate-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </span>
        </label>

        @if ($pageMode)
            <label class="{{ $metaChipBase }} border-amber-400 bg-amber-50 cursor-pointer hover:bg-amber-100 transition" title="Change page">
                <span class="{{ $metaChipLabel }} text-amber-800">Page</span>
                <span class="{{ $metaChipSep }} text-amber-500">:</span>
                <span class="relative inline-flex min-w-0 max-w-full items-center gap-0.5">
                    <select
                        class="{{ $selectBase }} text-amber-900"
                        aria-label="Change page"
                        x-model="currentPage"
                        @change="setPage($event.target.value)"
                    >
                        @foreach ($pages as $page)
                            <option value="{{ $page }}">Page {{ $page }}</option>
                        @endforeach
                    </select>
                    <svg class="h-3.5 w-3.5 shrink-0 text-amber-700 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </span>
            </label>
        @else
            <label class="{{ $metaChipBase }} border-amber-400 bg-amber-50 cursor-pointer hover:bg-amber-100 transition" title="Change topic">
                <span class="{{ $metaChipLabel }} text-amber-800">Topic</span>
                <span class="{{ $metaChipSep }} text-amber-500">:</span>
                <span class="relative inline-flex min-w-0 max-w-full items-center gap-0.5">
                    <select
                        class="{{ $selectBase }} text-amber-900"
                        aria-label="Change topic"
                        onchange="if (this.value) window.location.href = this.value"
                    >
                        @foreach ($topics as $navTopic)
                            <option
                                value="{{ $navTopic['url'] }}"
                                @selected((int) $navTopic['id'] === (int) $topicId)
                            >{{ $navTopic['name'] }}</option>
                        @endforeach
                    </select>
                    <svg class="h-3.5 w-3.5 shrink-0 text-amber-700 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </span>
            </label>
        @endif
    </div>
@else
    <div class="material-reader-meta-row border-b-2 border-brand-green-200 bg-gradient-to-r from-brand-green-50 via-white to-amber-50 px-2 py-2.5 sm:px-4 sm:py-3" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0.5rem;width:100%;">
        <div class="{{ $metaChipBase }} border-brand-green bg-white" title="{{ $subjectName }}">
            <span class="{{ $metaChipLabel }}">Subject</span>
            <span class="{{ $metaChipSep }}">:</span>
            <span class="{{ $metaChipValue }} text-brand-green">{{ $subjectName }}</span>
        </div>
        <div class="{{ $metaChipBase }} border-slate-300 bg-slate-50" title="{{ $chapterDisplay }}">
            <span class="{{ $metaChipLabel }}">Chapter</span>
            <span class="{{ $metaChipSep }}">:</span>
            <span class="{{ $metaChipValue }} text-slate-800">{{ $chapterDisplay }}</span>
        </div>
        <div class="{{ $metaChipBase }} border-amber-400 bg-amber-50" title="{{ $topicDisplay }}">
            <span class="{{ $metaChipLabel }} text-amber-800">{{ $pageMode ? 'Page' : 'Topic' }}</span>
            <span class="{{ $metaChipSep }} text-amber-500">:</span>
            <span class="{{ $metaChipValue }} text-amber-900">
                @if ($pageMode)
                    Page <span x-text="currentPage"></span>
                @else
                    {{ $topicDisplay }}
                @endif
            </span>
        </div>
    </div>
@endif
