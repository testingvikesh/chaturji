@props([
    'subject',
    'chapter',
    'material',
    'examples',
    'backUrl' => null,
    'practiceMode' => false,
    'readerNav' => [],
])

@php
    use App\Support\MaterialWorkedExamples;

    $hasTextbookPdf = $material && method_exists($material, 'hasTextbookPdf') && $material->hasTextbookPdf();
    $textbookPdfUrl = $hasTextbookPdf ? route('materials.textbook-pdf', $material) : null;
    $textbookPublicUrl = $material?->textbookPdfPublicUrl();

    if ($material && method_exists($material, 'textbookPageImages')) {
        $material->loadMissing('topics');
        $textbookPageImages = $material->textbookPageImages();
    } else {
        $textbookPageImages = collect();
    }

    $showTextbook = $hasTextbookPdf
        || filled($textbookPublicUrl)
        || $textbookPageImages->isNotEmpty()
        || ($material && filled($material->material_attachment ?? null));

    $pages = MaterialWorkedExamples::pagesForMaterial($material, $examples);
    $requestedPage = trim((string) request('page', ''));
    $defaultPage = $pages->contains($requestedPage)
        ? $requestedPage
        : (string) ($pages->first() ?: '1');
    $pageCounts = $examples
        ->groupBy(fn ($row) => \App\Support\MaterialWorkedExamples::normalizePage($row['page'] ?? '') ?: '0')
        ->map->count()
        ->all();
@endphp

<div class="admin-page material-reader"
     x-data="{
        currentPage: @js($defaultPage),
        counts: @js($pageCounts),
        textbookOpen: false,
        textbookLoading: false,
        textbookTimer: null,
        setPage(page) {
            this.currentPage = String(page);
            const url = new URL(window.location.href);
            url.searchParams.set('page', this.currentPage);
            history.replaceState({}, '', url);
            if (this.textbookOpen) {
                this.startTextbookLoading();
            }
        },
        isPage(page) {
            return String(this.currentPage) === String(page);
        },
        pageCountLabel() {
            const n = this.counts[this.currentPage] || 0;
            return n + (n === 1 ? ' Example' : ' Examples') + ' · Page ' + this.currentPage;
        },
        startTextbookLoading() {
            this.textbookLoading = true;
            clearTimeout(this.textbookTimer);
            this.textbookTimer = setTimeout(() => { this.textbookLoading = false; }, 12000);
        },
        onTextbookLoaded() {
            this.textbookLoading = false;
            clearTimeout(this.textbookTimer);
        },
        openTextbook() {
            this.startTextbookLoading();
            this.textbookOpen = true;
            document.body.classList.add('overflow-hidden');
            this.$nextTick(() => {
                const el = document.getElementById('textbook-page-' + this.currentPage);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        },
        closeTextbook() {
            this.textbookOpen = false;
            this.textbookLoading = false;
            clearTimeout(this.textbookTimer);
            document.body.classList.remove('overflow-hidden');
        }
     }"
     @keydown.escape.window="closeTextbook()">
    <style>
        .material-reader,
        .material-reader button,
        .material-reader input,
        .material-reader select {
            font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
        }
        .example-page-btn {
            display: inline-flex;
            height: 2.4rem;
            min-width: 2.6rem;
            align-items: center;
            justify-content: center;
            border-radius: 0.7rem;
            border: 2px solid #e2e8f0;
            background: #fff;
            padding: 0 0.7rem;
            font-size: 0.9rem;
            font-weight: 800;
            color: #334155;
            cursor: pointer;
        }
        .example-page-btn:hover { border-color: #15803d; color: #15803d; background: #f0fdf4; }
        .example-page-btn--active {
            background: #15803d !important;
            border-color: #15803d !important;
            color: #fff !important;
        }
    </style>
    <div class="mb-4 flex flex-wrap items-center gap-3 text-sm">
        @if ($backUrl)
            <a href="{{ $backUrl }}" class="text-brand-green hover:underline font-medium">&larr; Back to Index</a>
        @endif
        <span class="admin-badge-green">
            <span x-text="pageCountLabel()"></span>
        </span>
        @if ($showTextbook)
            <button type="button"
                    @click="openTextbook()"
                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-full border border-brand-green-200 bg-brand-green-50 px-3 text-xs sm:text-sm font-semibold text-brand-green shadow-sm transition hover:bg-brand-green hover:text-white hover:border-brand-green">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                Textbook
            </button>
        @endif
    </div>

    @if ($showTextbook)
        <div x-show="textbookOpen"
             x-cloak
             class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-6"
             style="display: none;">
            <div class="absolute inset-0 bg-slate-900/60" @click="closeTextbook()"></div>
            <div class="relative flex h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                 @click.stop>
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-900">Textbook</p>
                        <p class="truncate text-xs text-slate-500">
                            {{ $material?->displayChapterName() }}
                            @if ($material?->material_attachment)
                                · {{ $material->textbookPdfFilename() }}
                            @endif
                            · Page <span x-text="currentPage"></span>
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        @if ($textbookPdfUrl || $textbookPublicUrl)
                            <a href="{{ $textbookPdfUrl ?: $textbookPublicUrl }}"
                               target="_blank"
                               rel="noopener"
                               class="inline-flex h-9 items-center rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                Open PDF
                            </a>
                        @endif
                        <button type="button"
                                @click="closeTextbook()"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                                aria-label="Close">
                            &times;
                        </button>
                    </div>
                </div>

                <div class="relative min-h-0 flex-1 overflow-hidden bg-slate-100">
                    <div x-show="textbookLoading"
                         x-cloak
                         class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 bg-gradient-to-b from-brand-green-50 via-white to-amber-50">
                        <img src="{{ asset('images/brand/ganpati.png') }}"
                             alt="Shree Ganpati"
                             class="h-20 w-20 sm:h-24 sm:w-24 object-contain drop-shadow-lg animate-pulse">
                        <p class="text-sm font-bold text-brand-green">જય શ્રી ગણેશ</p>
                        <p class="text-sm font-semibold text-slate-700">Loading textbook…</p>
                        <p class="text-xs text-slate-500">Please wait while the PDF opens</p>
                    </div>
                    @if ($textbookPdfUrl || $textbookPublicUrl)
                        <iframe
                            x-show="textbookOpen"
                            :src="textbookOpen ? (@js($textbookPdfUrl ?: $textbookPublicUrl) + '#page=' + currentPage) : ''"
                            @load="onTextbookLoaded()"
                            class="absolute inset-0 h-full w-full border-0 bg-slate-100"
                            title="Textbook PDF"></iframe>
                    @elseif ($textbookPageImages->isNotEmpty())
                        <div id="textbook-pages" class="h-full overflow-y-auto">
                            <div class="textbook-page-list mx-auto w-full max-w-3xl space-y-4 p-3 sm:p-5">
                                @foreach ($textbookPageImages as $image)
                                    @php
                                        $pageNo = (int) ($image['page'] ?? 0);
                                        $pageRef = $pageNo > 0 ? (string) $pageNo : ($image['file'] ?? '1');
                                    @endphp
                                    <figure id="textbook-page-{{ $pageNo ?: $loop->iteration }}"
                                            class="textbook-page-card"
                                            :class="isPage(@js((string) ($pageNo ?: $loop->iteration))) ? 'ring-2 ring-brand-green' : ''">
                                        <figcaption class="textbook-page-caption">{{ $image['label'] ?? ('Page '.$pageRef) }}</figcaption>
                                        <img src="{{ route('materials.textbook-page', [$material, $pageRef]) }}"
                                             alt="{{ $image['label'] ?? 'Textbook page' }}"
                                             class="textbook-page-image"
                                             loading="{{ $loop->iteration < 3 ? 'eager' : 'lazy' }}">
                                    </figure>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="flex h-full items-center justify-center p-6 text-center">
                            <div>
                                <p class="text-sm font-semibold text-slate-700">Textbook PDF not found on server</p>
                                <p class="mt-1 text-xs text-slate-500 break-all">{{ $material?->material_attachment ?: 'No attachment path saved' }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="admin-card subject-chapter-topic-sticky-wrap sticky top-14 lg:top-0 z-40 overflow-hidden mb-4 shadow-lg">
        <div class="admin-card-top"></div>
        @include('partials.subject-chapter-topic-row', [
            'subjectName' => $subject->name,
            'chapterName' => $material->displayChapterName(),
            'topicName' => 'Page '.$defaultPage,
            'readerNav' => $readerNav ?? [],
            'subjectId' => $subject->id,
            'materialId' => $material->id,
            'topicId' => null,
            'pageMode' => true,
            'pages' => $pages,
        ])

        @if ($pages->isNotEmpty())
            <div class="example-page-strip border-t border-brand-green-100 bg-white px-3 py-3 sm:px-4">
                <p class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-500">Page no</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($pages as $page)
                        <button type="button"
                                @click="setPage(@js((string) $page))"
                                :class="isPage(@js((string) $page))
                                    ? 'example-page-btn example-page-btn--active'
                                    : 'example-page-btn'"
                                aria-label="Page {{ $page }}">
                            {{ str_pad((string) $page, 2, '0', STR_PAD_LEFT) }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @include('partials.worked-examples', [
        'examples' => $examples,
        'practiceMode' => $practiceMode,
        'heading' => 'Solved Examples — '.$material->displayChapterName(),
        'filterByPage' => true,
        'pages' => $pages,
    ])
</div>
