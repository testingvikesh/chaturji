@props([
    'chapters',
    'subject',
    'topicRoute' => 'student.topics.show',
    'chapterRoute' => 'student.chapters.show',
    'materialTopicRoute' => 'student.material-topics.show',
])

<div class="book-index-header-note">
    અનુક્રમણિકા / Index — click a topic to read introduction, content & questions
</div>

@if ($chapters->isEmpty())
    <div class="p-8 text-center text-sm text-slate-400">No chapters added for this subject yet.</div>
@else
    <div class="book-index" x-data="{ openChapter: null }">
        @foreach ($chapters as $chapter)
            @php
                $chapterNo = $chapter->sort_order > 0 ? $chapter->sort_order : $loop->iteration;
                $preferredMaterial = $chapter->preferredMaterial();
                $materialTopics = $preferredMaterial?->topics ?? collect();
                $curriculumTopics = $chapter->topics ?? collect();
                $hasChapterContent = (bool) $chapter->content;
                $useMaterials = $materialTopics->isNotEmpty();

                $displayTopics = $useMaterials
                    ? $materialTopics
                    : ($curriculumTopics->isNotEmpty()
                        ? $curriculumTopics
                        : ($hasChapterContent ? collect([(object) ['id' => 0, 'name' => $chapter->content->title, 'sort_order' => 1, '_fallback' => true]]) : collect()));

                $chapterKey = 'chapter-'.$chapter->id;
                $readyCount = $useMaterials
                    ? $materialTopics->filter(fn ($t) => $t->hasContent())->count()
                    : ($hasChapterContent ? $displayTopics->count() : 0);
            @endphp

            <article class="book-index-chapter">
                <button
                    type="button"
                    class="book-index-chapter-head"
                    @click="openChapter = openChapter === '{{ $chapterKey }}' ? null : '{{ $chapterKey }}'"
                    :aria-expanded="openChapter === '{{ $chapterKey }}'"
                >
                    <span class="book-index-chapter-no">{{ $chapterNo }}</span>
                    <div class="book-index-chapter-title">
                        <h4 class="book-index-chapter-name">{{ $chapter->name }}</h4>
                        <p class="book-index-chapter-meta">
                            @if ($displayTopics->isNotEmpty())
                                {{ $displayTopics->count() }} {{ Str::plural('topic', $displayTopics->count()) }}
                            @else
                                No topics yet
                            @endif
                            @if ($useMaterials)
                                <span class="text-brand-green"> &middot; Material ({{ $readyCount }} ready)</span>
                            @elseif ($hasChapterContent)
                                <span class="text-brand-green"> &middot; Material available</span>
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
                    @if ($displayTopics->isNotEmpty())
                        <div class="book-index-topics">
                            @foreach ($displayTopics as $topic)
                                @php
                                    $topicNo = $chapterNo.'.'.(
                                        $useMaterials
                                            ? ($topic->topic_order ?: $loop->iteration)
                                            : (($topic->sort_order ?? 0) > 0 ? $topic->sort_order : $loop->iteration)
                                    );
                                    $topicName = $useMaterials
                                        ? $topic->displayName()
                                        : ($topic->name ?? 'Topic');
                                    $canOpen = $useMaterials
                                        ? $topic->hasContent()
                                        : ($hasChapterContent && empty($topic->_fallback) ? true : $hasChapterContent);
                                    $topicUrl = null;
                                    if ($canOpen) {
                                        if ($useMaterials) {
                                            $topicUrl = route($materialTopicRoute, [$subject, $topic]);
                                        } elseif (($topic->id ?? 0) > 0) {
                                            $topicUrl = route($topicRoute, [$subject, $topic]);
                                        } else {
                                            $topicUrl = route($chapterRoute, [$subject, $chapter]);
                                        }
                                    }
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
                        <p class="book-index-empty">Topics will appear here after material is uploaded in the materials table.</p>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
@endif
