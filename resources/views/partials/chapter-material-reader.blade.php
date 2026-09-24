@props([
    'subject',
    'chapter',
    'content',
    'sections',
    'questionGroups',
    'questionGroupLabels',
    'topic' => null,
    'backUrl' => null,
    'practiceMode' => false,
    'material' => null,
    'textbookPoints' => null,
    'readerNav' => [],
    'readerMaterialId' => null,
    'materialTopic' => null,
    'workedExamples' => null,
    'canEditQuestions' => false,
    'questionEditMedium' => null,
])

@php
    use App\Support\ChapterMaterialHelper;
    use App\Support\TeacherChapterAccess;

    [$sections, $questionGroups] = TeacherChapterAccess::filterMaterial($sections, $questionGroups);

    $canEditQuestions = (bool) $canEditQuestions
        && $materialTopic
        && $subject
        && auth()->check()
        && (auth()->user()->role ?? null) === 'teacher';

    $questionEditUrlBuilder = $canEditQuestions
        ? function ($question) use ($subject, $materialTopic, $questionEditMedium) {
            $key = (string) ($question->edit_key ?? '');
            if ($key === '') {
                return null;
            }

            $params = [
                'subject' => $subject,
                'materialTopic' => $materialTopic,
                'questionKey' => $key,
            ];
            if ($questionEditMedium) {
                $params['medium'] = $questionEditMedium;
            }

            return route('teacher.books.questions.edit', $params);
        }
        : null;

    $partition = ChapterMaterialHelper::partitionSections($sections);
    $introduction = $partition['introduction'];
    $trailer = $partition['trailer'];
    $importance = $partition['importance'];
    $bodySections = $partition['body'];

    $orderedQuestionGroups = ChapterMaterialHelper::orderQuestionGroups(
        \App\Models\ChapterQuestion::filterReaderGroups($questionGroups)
    );
    [$knowledgeLadderQuestions, $otherQuestionGroups] = ChapterMaterialHelper::splitKnowledgeLadder($orderedQuestionGroups);
    [$lineToLineQuestions, $otherQuestionGroups] = ChapterMaterialHelper::splitQuestionGroup($otherQuestionGroups, 'line_to_line');

    $visibleQuestionLabels = collect($orderedQuestionGroups)->mapWithKeys(fn ($questions, $type) => [
        $type => $questionGroupLabels[$type] ?? \App\Models\ChapterQuestion::labelForType($type),
    ]);

    $totalQuestions = collect($orderedQuestionGroups)->sum(fn ($questions) => $questions->count());

    $gksSections = ChapterMaterialHelper::gksSections($bodySections);
    $hasGksSections = $gksSections->isNotEmpty();
    $gksPillLabel = $hasGksSections ? ChapterMaterialHelper::gksPillLabel($gksSections) : null;

    $tocPillBase = 'material-reader-toc-link inline-flex h-9 min-h-9 items-center justify-center gap-1 rounded-full px-3 text-xs sm:text-sm font-semibold leading-none shadow-sm transition';
    $tocPillContent = $tocPillBase.' border border-brand-green-200 bg-white text-brand-green hover:bg-brand-green hover:text-white hover:border-brand-green';
    $tocPillQuestion = $tocPillBase.' material-reader-toc-link--question border border-amber-300 bg-amber-50 text-amber-900 hover:bg-amber-500 hover:text-white hover:border-amber-500';

    $hasTextbookPdf = $material && method_exists($material, 'hasTextbookPdf') && $material->hasTextbookPdf();
    $textbookPoints = collect($textbookPoints ?? []);
    $hasTextbookPoints = $textbookPoints->isNotEmpty();
    $textbookPageImages = collect();
    try {
        if ($material && method_exists($material, 'textbookPageImages')) {
            // Prefer already-loaded lightweight topics; avoid pulling section_json blobs.
            if (! $material->relationLoaded('topics')) {
                $material->setRelation(
                    'topics',
                    $material->topics()
                        ->orderBy('topic_order')
                        ->get(['id', 'material_id', 'topic_order', 'title', 'title_gu', 'image_url', 'generated'])
                );
            }
            $textbookPageImages = $material->textbookPageImages();
        }
    } catch (\Throwable $e) {
        report($e);
        $textbookPageImages = collect();
    }
    $topicPageNumber = is_object($materialTopic) ? (int) ($materialTopic->topic_order ?? 0) : 0;
    $topicPageImage = $textbookPageImages->firstWhere('topic_id', (int) (is_object($materialTopic) ? ($materialTopic->id ?? 0) : 0))
        ?: ($topicPageNumber > 0 ? $textbookPageImages->firstWhere('page', $topicPageNumber) : null);
    $showTextbookModal = $hasTextbookPdf || $hasTextbookPoints || $textbookPageImages->isNotEmpty() || ($material && filled($material->material_attachment ?? null));

    $workedExamples = collect($workedExamples ?? []);
    if ($workedExamples->isEmpty() && $materialTopic) {
        $workedExamples = \App\Support\MaterialWorkedExamples::fromTopic($materialTopic);
    }

    $summaryLinks = collect();
    if ($introduction) {
        $summaryLinks->push(['label' => 'Introduction', 'anchor' => 'section-'.$introduction->id, 'style' => 'content']);
    }
    if ($trailer) {
        $summaryLinks->push(['label' => 'Trailer', 'anchor' => 'section-'.$trailer->id, 'style' => 'content']);
    }
    if ($importance) {
        $summaryLinks->push(['label' => 'Importance of this topic', 'anchor' => 'section-'.$importance->id, 'style' => 'content']);
    }
    if ($knowledgeLadderQuestions) {
        $summaryLinks->push([
            'label' => 'Knowledge Ladder ('.$knowledgeLadderQuestions->count().')',
            'anchor' => 'questions-knowledge_ladder',
            'style' => 'content',
        ]);
    }
    if ($lineToLineQuestions) {
        $summaryLinks->push([
            'label' => 'Line to Line ('.$lineToLineQuestions->count().')',
            'anchor' => 'questions-line_to_line',
            'style' => 'content',
        ]);
    }
    foreach ($bodySections as $section) {
        if ($section->isGksSection()) {
            continue;
        }
        $summaryLinks->push([
            'label' => $section->title ?: $section->typeLabel(),
            'anchor' => 'section-'.$section->id,
            'style' => 'content',
        ]);
    }
    if ($hasGksSections) {
        $summaryLinks->push(['label' => $gksPillLabel, 'anchor' => 'section-gks', 'style' => 'content']);
    }
    if ($workedExamples->isNotEmpty()) {
        $summaryLinks->push([
            'label' => 'Examples ('.$workedExamples->count().')',
            'anchor' => 'worked-examples',
            'style' => 'content',
        ]);
    }
    foreach ($otherQuestionGroups as $type => $questions) {
        $summaryLinks->push([
            'label' => ($visibleQuestionLabels[$type] ?? $type).' ('.$questions->count().')',
            'anchor' => 'questions-'.$type,
            'style' => 'question',
        ]);
    }
    $hasSummaryLinks = $summaryLinks->isNotEmpty();
@endphp

<div class="admin-page material-reader" x-data="{
    pdfOpen: false,
    textbookOpen: false,
    textbookLoading: false,
    textbookTimer: null,
    summaryOpen: false,
    topicPage: {{ $topicPageNumber ?: 0 }},
    startTextbookLoading() {
        this.textbookLoading = true;
        clearTimeout(this.textbookTimer);
        this.textbookTimer = setTimeout(() => { this.textbookLoading = false; }, 15000);
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
            const el = document.getElementById(this.topicPage ? 'textbook-page-' + this.topicPage : 'textbook-pages');
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            // Page-image mode: hide loader once DOM is ready
            if (document.getElementById('textbook-pages')) {
                setTimeout(() => this.onTextbookLoaded(), 600);
            }
        });
    },
    closeTextbook() {
        this.textbookOpen = false;
        this.textbookLoading = false;
        clearTimeout(this.textbookTimer);
        document.body.classList.remove('overflow-hidden');
    },
    goToSummaryPoint(anchor) {
        this.summaryOpen = false;
        this.$nextTick(() => {
            const el = document.getElementById(anchor);
            if (!el) return;
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            el.classList.add('ring-2', 'ring-brand-green', 'bg-emerald-50');
            setTimeout(() => el.classList.remove('ring-2', 'ring-brand-green', 'bg-emerald-50'), 1800);
        });
    }
}" @keydown.escape.window="closeTextbook()">
    @if ($hasSummaryLinks)
        <button type="button"
                @click="summaryOpen = true"
                class="fixed right-3 sm:right-5 top-1/2 -translate-y-1/2 z-50 inline-flex flex-col items-center justify-center gap-1 rounded-2xl border-2 border-brand-green bg-brand-green px-3 py-4 text-xs sm:text-sm font-bold text-white shadow-xl shadow-brand-green/30 hover:bg-brand-green-dark hover:border-brand-green-dark transition"
                aria-label="Open summary">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h14"/>
            </svg>
            <span class="writing-mode-vertical tracking-wide" style="writing-mode: vertical-rl; text-orientation: mixed;">Summary</span>
        </button>
    @endif
    <div class="mb-4 flex flex-wrap items-center gap-3 text-sm">
        @if ($backUrl)
            <a href="{{ $backUrl }}" class="text-brand-green hover:underline font-medium">&larr; Back to Index</a>
        @endif
        <span class="admin-badge-green">{{ $content->languageLabel() }}</span>
        <span class="admin-badge-gold">{{ $totalQuestions }} Questions</span>
        @if ($showTextbookModal)
            <button type="button"
                    @click="openTextbook()"
                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-full border border-brand-green-200 bg-brand-green-50 px-3 text-xs sm:text-sm font-semibold text-brand-green shadow-sm transition hover:bg-brand-green hover:text-white hover:border-brand-green">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                Textbook
            </button>
        @endif
        @if ($content->hasOriginalPdf())
            <button type="button"
                    @click="pdfOpen = true"
                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-full border border-amber-300 bg-amber-50 px-3 text-xs sm:text-sm font-semibold text-amber-900 shadow-sm transition hover:bg-amber-500 hover:text-white hover:border-amber-500">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Practice PDF
            </button>
        @endif
    </div>

    @if ($topicPageImage && $material)
        @php
            $topicPageRef = ((int) ($topicPageImage['page'] ?? 0)) > 0
                ? (string) $topicPageImage['page']
                : ($topicPageImage['file'] ?? '1');
        @endphp
        <figure class="textbook-topic-image-wrap">
            <img src="{{ route('materials.textbook-page', [$material, $topicPageRef]) }}"
                 alt="{{ $topicPageImage['label'] ?? 'Textbook page' }}"
                 class="textbook-topic-image">
            <figcaption class="textbook-topic-image-caption">{{ $topicPageImage['label'] ?? 'Textbook page' }}</figcaption>
        </figure>
    @endif

    @php
        $textbookPdfUrl = null;
        if ($material && $material->hasTextbookPdf()) {
            $textbookPdfUrl = route('materials.textbook-pdf', $material);
        }
        $textbookPublicUrl = $material?->textbookPdfPublicUrl();
    @endphp

    @if ($showTextbookModal)
        <div x-show="textbookOpen"
             x-cloak
             class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-6"
             style="display: none;">
            <div class="absolute inset-0 bg-slate-900/60" @click="closeTextbook()"></div>
            <div class="relative flex h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                 @click.stop>
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                    <div class="flex min-w-0 items-center gap-2">
                        <button type="button"
                                @click="closeTextbook()"
                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50"
                                aria-label="Back">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-900">Textbook</p>
                        <p class="truncate text-xs text-slate-500">
                            {{ $material?->displayChapterName() ?? ($topic->name ?? 'Material') }}
                            @if ($material?->material_attachment)
                                · {{ $material->textbookPdfFilename() }}
                            @endif
                        </p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        @if ($textbookPdfUrl || $textbookPublicUrl)
                            <a href="{{ route('materials.textbook', $material) }}?return={{ urlencode(url()->full()) }}"
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

                    @if ($textbookPageImages->isNotEmpty())
                        <div id="textbook-pages" class="h-full overflow-y-auto">
                            <div class="textbook-page-list mx-auto w-full max-w-3xl space-y-4 p-3 sm:p-5">
                                @foreach ($textbookPageImages as $image)
                                    @php
                                        $pageNo = (int) ($image['page'] ?? 0);
                                        $pageRef = $pageNo > 0 ? (string) $pageNo : ($image['file'] ?? '1');
                                        $isCurrent = $topicPageImage && (
                                            ((int) ($topicPageImage['page'] ?? 0) === $pageNo && $pageNo > 0)
                                            || (($topicPageImage['file'] ?? '') !== '' && ($topicPageImage['file'] ?? '') === ($image['file'] ?? ''))
                                        );
                                    @endphp
                                    <figure id="textbook-page-{{ $pageNo ?: $loop->iteration }}"
                                            class="textbook-page-card {{ $isCurrent ? 'textbook-page-card-current' : '' }}">
                                        <figcaption class="textbook-page-caption">{{ $image['label'] ?? ('Page '.$pageRef) }}</figcaption>
                                        <img src="{{ route('materials.textbook-page', [$material, $pageRef]) }}"
                                             alt="{{ $image['label'] ?? 'Textbook page' }}"
                                             class="textbook-page-image"
                                             loading="{{ $isCurrent || $loop->iteration < 3 ? 'eager' : 'lazy' }}"
                                             @if ($loop->first) @load="onTextbookLoaded()" @endif>
                                    </figure>
                                @endforeach
                            </div>
                        </div>
                    @elseif ($textbookPdfUrl)
                        <iframe
                            x-show="textbookOpen"
                            :src="textbookOpen ? @js($textbookPdfUrl) : ''"
                            @load="onTextbookLoaded()"
                            class="absolute inset-0 h-full w-full border-0 bg-slate-100"
                            title="Textbook PDF"></iframe>
                    @elseif ($textbookPublicUrl)
                        <iframe
                            x-show="textbookOpen"
                            :src="textbookOpen ? @js($textbookPublicUrl) : ''"
                            @load="onTextbookLoaded()"
                            class="absolute inset-0 h-full w-full border-0 bg-slate-100"
                            title="Textbook PDF"></iframe>
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

    @if ($content->hasOriginalPdf())
        <div x-show="pdfOpen"
             x-cloak
             class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-6"
             style="display: none;">
            <div class="absolute inset-0 bg-slate-900/60" @click="pdfOpen = false"></div>
            <div class="relative flex h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                 @click.stop>
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-900">Practice PDF</p>
                        <p class="truncate text-xs text-slate-500">{{ $content->original_pdf_filename }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ route('chapter-content.original-pdf', $content) }}"
                           target="_blank"
                           class="inline-flex h-9 items-center rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Open in new tab
                        </a>
                        <button type="button"
                                @click="pdfOpen = false"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                                aria-label="Close">
                            &times;
                        </button>
                    </div>
                </div>
                <div class="min-h-0 flex-1 bg-slate-100">
                    <iframe
                        x-show="pdfOpen"
                        src="{{ route('chapter-content.original-pdf', $content) }}"
                        class="h-full w-full border-0"
                        title="Practice PDF"></iframe>
                </div>
            </div>
        </div>
    @endif

    @if ($introduction || $trailer || $importance || $knowledgeLadderQuestions || $lineToLineQuestions || $bodySections->isNotEmpty() || ! empty($otherQuestionGroups) || $workedExamples->isNotEmpty())
        <div class="admin-card subject-chapter-topic-sticky-wrap sticky top-14 lg:top-0 z-40 overflow-hidden mb-4 shadow-lg">
            <div class="admin-card-top"></div>
            @include('partials.subject-chapter-topic-row', [
                'subjectName' => $subject->name,
                'chapterName' => $chapter->name,
                'topicName' => $topic?->name ?? $content->title,
                'readerNav' => $readerNav ?? [],
                'subjectId' => $subject->id,
                'materialId' => $readerMaterialId ?? $material?->id,
                'topicId' => $topic?->id,
            ])
        </div>

        <nav class="material-reader-toc admin-card" aria-label="Page contents">
            <div class="material-reader-toc-body pt-4">
                <div class="material-reader-toc-pills flex flex-wrap items-center gap-2">
                @if ($introduction)
                    <a href="#section-{{ $introduction->id }}" class="{{ $tocPillContent }}">Introduction</a>
                @endif
                @if ($trailer)
                    <a href="#section-{{ $trailer->id }}" class="{{ $tocPillContent }}">Trailer</a>
                @endif
                @if ($importance)
                    <a href="#section-{{ $importance->id }}" class="{{ $tocPillContent }}">Importance of this topic</a>
                @endif
                @if ($knowledgeLadderQuestions)
                    <a href="#questions-knowledge_ladder" class="{{ $tocPillContent }}">
                        Knowledge Ladder
                        <span class="opacity-70">({{ $knowledgeLadderQuestions->count() }})</span>
                    </a>
                @endif
                @if ($lineToLineQuestions)
                    <a href="#questions-line_to_line" class="{{ $tocPillContent }}">
                        Line to Line
                        <span class="opacity-70">({{ $lineToLineQuestions->count() }})</span>
                    </a>
                @endif
                @foreach ($bodySections as $section)
                    @continue($section->isGksSection())
                    <a href="#section-{{ $section->id }}" class="{{ $tocPillContent }}">
                        {{ $section->title ?: $section->typeLabel() }}
                    </a>
                @endforeach
                @if ($hasGksSections)
                    <a href="#section-gks" class="{{ $tocPillContent }}">{{ $gksPillLabel }}</a>
                @endif
                @if ($workedExamples->isNotEmpty())
                    <a href="#worked-examples" class="{{ $tocPillContent }}">
                        Examples
                        <span class="opacity-70">({{ $workedExamples->count() }})</span>
                    </a>
                @endif
                @foreach ($otherQuestionGroups as $type => $questions)
                    <a href="#questions-{{ $type }}" class="{{ $tocPillQuestion }}">
                        {{ $visibleQuestionLabels[$type] }}
                        <span>({{ $questions->count() }})</span>
                    </a>
                @endforeach
                </div>
            </div>
        </nav>

        @if ($hasSummaryLinks)
            <div x-show="summaryOpen"
                 x-cloak
                 class="fixed inset-0 z-[80] flex items-center justify-end p-3 sm:p-6"
                 style="display: none;"
                 @keydown.escape.window="summaryOpen = false">
                <div class="absolute inset-0 bg-slate-900/50" @click="summaryOpen = false"></div>
                <div class="relative flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                     @click.stop>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-900">Summary</p>
                            <p class="truncate text-xs text-slate-500">Jump to a section</p>
                        </div>
                        <button type="button"
                                @click="summaryOpen = false"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                                aria-label="Close">
                            &times;
                        </button>
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto p-4 bg-gradient-to-b from-brand-green-50/40 to-amber-50/30">
                        <div class="flex flex-wrap gap-2">
                            @foreach ($summaryLinks as $link)
                                <button type="button"
                                        @click="goToSummaryPoint(@js($link['anchor']))"
                                        class="{{ ($link['style'] ?? 'content') === 'question' ? $tocPillQuestion : $tocPillContent }}">
                                    {{ $link['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    @php
        $blockIndex = 0;
        $nextBlockClass = function () use (&$blockIndex) {
            $class = ($blockIndex % 2 === 0) ? 'material-block--a' : 'material-block--b';
            $blockIndex++;

            return $class;
        };
    @endphp

    @if ($introduction)
        @include('partials.material-sections.introduction', [
            'section' => $introduction,
            'blockClass' => $nextBlockClass(),
        ])
    @endif

    @if ($trailer)
        @include('partials.material-sections.trailer', [
            'section' => $trailer,
            'blockClass' => $nextBlockClass(),
        ])
    @endif

    @if ($importance)
        @include('partials.material-sections.importance', [
            'section' => $importance,
            'blockClass' => $nextBlockClass(),
        ])
    @endif

    @if ($knowledgeLadderQuestions)
        @include('partials.knowledge-ladder-grid', [
            'questions' => $knowledgeLadderQuestions,
            'label' => $visibleQuestionLabels['knowledge_ladder'] ?? 'Knowledge Ladder',
            'toggleAnswer' => $practiceMode,
            'sectionId' => 'questions-knowledge_ladder',
            'blockClass' => $nextBlockClass(),
            'questionEditUrlBuilder' => $questionEditUrlBuilder,
        ])
    @endif

    @if ($lineToLineQuestions)
        @include('partials.knowledge-ladder-grid', [
            'questions' => $lineToLineQuestions,
            'label' => $visibleQuestionLabels['line_to_line'] ?? 'Line to Line',
            'subtitle' => 'Linked question → answer → next question',
            'icon' => '↔️',
            'badgeClass' => 'admin-badge-gold',
            'variant' => 'line',
            'toggleAnswer' => $practiceMode,
            'sectionId' => 'questions-line_to_line',
            'blockClass' => $nextBlockClass(),
            'questionEditUrlBuilder' => $questionEditUrlBuilder,
        ])
    @endif

    @php
        $gksRendered = false;
    @endphp

    @foreach ($bodySections as $section)
        @if ($section->isGksSection())
            @if (! $gksRendered)
                <div id="section-gks" class="material-gks-grid scroll-mt-36 lg:scroll-mt-32 {{ $nextBlockClass() }}">
                    @foreach (\App\Models\ChapterContentSection::GKS_TYPES as $gksType)
                        @if ($gksSections->has($gksType))
                            @include('student.topics.partials.section', [
                                'section' => $gksSections->get($gksType),
                                'column' => true,
                            ])
                        @endif
                    @endforeach
                </div>
                @php $gksRendered = true; @endphp
            @endif
            @continue
        @endif

        @include('student.topics.partials.section', [
            'section' => $section,
            'blockClass' => $nextBlockClass(),
        ])
    @endforeach

    @foreach ($otherQuestionGroups as $type => $questions)
        @include('partials.material-question-group', [
            'type' => $type,
            'questions' => $questions,
            'label' => $visibleQuestionLabels[$type],
            'toggleAnswer' => $practiceMode,
            'blockClass' => $nextBlockClass(),
            'questionEditUrlBuilder' => $questionEditUrlBuilder,
        ])
    @endforeach

    @include('partials.worked-examples', [
        'examples' => $workedExamples,
        'practiceMode' => $practiceMode,
        'heading' => 'Solved Examples',
    ])

    @if (! $introduction && ! $trailer && ! $importance && ! $knowledgeLadderQuestions && ! $lineToLineQuestions && $bodySections->isEmpty() && empty($otherQuestionGroups) && $workedExamples->isEmpty())
        <div class="admin-card p-10 text-center text-slate-400">
            No study material available for this topic yet.
        </div>
    @endif
</div>
