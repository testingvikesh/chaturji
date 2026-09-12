@props([
    'materials',
    'subject',
    'materialTopicRoute' => 'student.material-topics.show',
    'materialChapterRoute' => 'student.materials.show',
    'topicRouteExtra' => [],
    'emptyText' => 'No material chapters available for this subject yet.',
])

@php
    use App\Support\MaterialWorkedExamples;

    $isExampleSubject = MaterialWorkedExamples::isExampleSubject($subject);
@endphp

<div class="book-index-header-note">
    @if ($isExampleSubject)
        અનુક્રમણિકા / Index — click a chapter to view solved examples
    @else
        અનુક્રમણિકા / Index — chapters from materials table, topics from material_topics
    @endif
</div>

@if ($materials->isEmpty())
    <div class="p-8 text-center text-sm text-slate-400">{{ $emptyText }}</div>
@else
    <div class="book-index" x-data="{ openChapter: null }">
        @foreach ($materials as $material)
            @php
                $chapterNo = $material->displayChapterNo($loop->iteration);
                $topics = $material->topics ?? collect();
                $readyCount = $topics->filter(fn ($t) => $t->hasContent())->count();
                $chapterKey = 'material-'.$material->id;
                $examples = $isExampleSubject ? MaterialWorkedExamples::fromMaterial($material) : collect();
                $openExamplesDirect = $isExampleSubject && $examples->isNotEmpty();
                $chapterUrl = $openExamplesDirect
                    ? route($materialChapterRoute, array_merge([
                        'subject' => $subject,
                        'material' => $material,
                    ], $topicRouteExtra))
                    : null;
            @endphp

            <article class="book-index-chapter">
                @if ($chapterUrl)
                    <a href="{{ $chapterUrl }}" class="book-index-chapter-head group">
                        <span class="book-index-chapter-no">{{ $chapterNo }}</span>
                        <div class="book-index-chapter-title">
                            <h4 class="book-index-chapter-name group-hover:text-brand-green">{{ $material->displayChapterName() }}</h4>
                            <p class="book-index-chapter-meta">
                                {{ $examples->count() }} {{ Str::plural('example', $examples->count()) }}
                                <span class="text-brand-green"> &middot; Click to view examples</span>
                                @if ($material->medium)
                                    <span class="text-slate-400"> &middot; {{ $material->medium }}</span>
                                @endif
                            </p>
                        </div>
                        <span class="book-index-chapter-chevron" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>
                @else
                    <button
                        type="button"
                        class="book-index-chapter-head"
                        @click="openChapter = openChapter === '{{ $chapterKey }}' ? null : '{{ $chapterKey }}'"
                        :aria-expanded="openChapter === '{{ $chapterKey }}'"
                    >
                        <span class="book-index-chapter-no">{{ $chapterNo }}</span>
                        <div class="book-index-chapter-title">
                            <h4 class="book-index-chapter-name">{{ $material->displayChapterName() }}</h4>
                            <p class="book-index-chapter-meta">
                                @if ($topics->isNotEmpty())
                                    {{ $topics->count() }} {{ Str::plural('topic', $topics->count()) }}
                                @else
                                    No topics yet
                                @endif
                                <span class="text-brand-green"> &middot; Material ({{ $readyCount }} ready)</span>
                                @if ($material->medium)
                                    <span class="text-slate-400"> &middot; {{ $material->medium }}</span>
                                @endif
                            </p>
                        </div>
                        <span
                            class="book-index-chapter-chevron"
                            :class="{ 'book-index-chapter-chevron--open': openChapter === '{{ $chapterKey }}' }"
                            aria-hidden="true"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </button>

                    <div
                        x-show="openChapter === '{{ $chapterKey }}'"
                        x-cloak
                        class="book-index-chapter-body"
                    >
                        @if ($topics->isNotEmpty())
                            <div class="book-index-topics">
                                @foreach ($topics as $topic)
                                    @php
                                        $topicNo = $chapterNo.'.'.($topic->topic_order ?: $loop->iteration);
                                        $topicName = $topic->displayName();
                                        $canOpen = $topic->hasContent();
                                        $topicUrl = $canOpen
                                            ? route($materialTopicRoute, array_merge([
                                                'subject' => $subject,
                                                'materialTopic' => $topic,
                                            ], $topicRouteExtra))
                                            : null;
                                    @endphp

                                    @if ($topicUrl)
                                        <a href="{{ $topicUrl }}" class="book-index-topic book-index-topic--link group">
                                            <span class="book-index-topic-no">{{ $topicNo }}</span>
                                            <span class="book-index-topic-name group-hover:text-brand-green">{{ $topicName }}</span>
                                            <span class="book-index-topic-dots" aria-hidden="true"></span>
                                            <span class="book-index-topic-page">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                            <span class="book-index-topic-arrow" aria-hidden="true">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </span>
                                        </a>
                                    @else
                                        <div class="book-index-topic">
                                            <span class="book-index-topic-no">{{ $topicNo }}</span>
                                            <span class="book-index-topic-name">{{ $topicName }}</span>
                                            <span class="book-index-topic-dots" aria-hidden="true"></span>
                                            <span class="book-index-topic-page text-slate-400">Soon</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <p class="book-index-empty">Topics will appear here after material topics are generated.</p>
                        @endif
                    </div>
                @endif
            </article>
        @endforeach
    </div>
@endif
