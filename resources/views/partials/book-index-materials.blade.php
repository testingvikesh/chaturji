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
    $topicRouteExtraQuery = $topicRouteExtra === [] ? '' : ('?'.http_build_query($topicRouteExtra));

    // Build topic URLs without calling route() hundreds of times.
    $studentTopicBase = null;
    $teacherTopicBase = null;
    $adminTopicBase = null;
    if ($materialTopicRoute === 'student.material-topics.show') {
        $studentTopicBase = url('/student/subjects/'.$subject->id.'/material-topics');
    } elseif ($materialTopicRoute === 'teacher.books.topics.show') {
        $teacherTopicBase = url('/teacher/books/'.$subject->id.'/topics');
    } elseif ($materialTopicRoute === 'admin.materials.topics.show') {
        $adminTopicBase = url('/admin/materials/'.($topicRouteExtra['medium'] ?? 'english').'/subjects/'.$subject->id.'/topics');
    }
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
                $readyCount = $topics->count();
                $chapterKey = 'material-'.$material->id;
                $examplesCount = 0;
                $openExamplesDirect = false;
                if ($isExampleSubject) {
                    // Do NOT parse section_json here — that made Maths/Accounts subject pages hang.
                    $examplesCount = max($topics->count(), (int) ($material->topics_done ?? 0));
                    $openExamplesDirect = $examplesCount > 0;
                }
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
                                {{ $examplesCount }} {{ Str::plural('page', $examplesCount) }} ready
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
                                        if ($studentTopicBase) {
                                            $topicUrl = $studentTopicBase.'/'.$topic->id.$topicRouteExtraQuery;
                                        } elseif ($teacherTopicBase) {
                                            $topicUrl = $teacherTopicBase.'/'.$topic->id.$topicRouteExtraQuery;
                                        } elseif ($adminTopicBase) {
                                            $topicUrl = $adminTopicBase.'/'.$topic->id;
                                        } else {
                                            $topicUrl = route($materialTopicRoute, array_merge([
                                                'subject' => $subject,
                                                'materialTopic' => $topic,
                                            ], $topicRouteExtra));
                                        }
                                    @endphp

                                    <a href="{{ $topicUrl }}" class="book-index-topic book-index-topic--link group">
                                        <span class="book-index-topic-no">{{ $topicNo }}</span>
                                        <span class="book-index-topic-name group-hover:text-brand-green">{{ $topicName }}</span>
                                        <span class="book-index-topic-dots" aria-hidden="true"></span>
                                        <span class="book-index-topic-page">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="book-index-topic-arrow" aria-hidden="true">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </span>
                                    </a>
                                @endforeach
                                @php
                                    $swadhyayUrl = null;
                                    if ($adminTopicBase) {
                                        $swadhyayUrl = url('/admin/materials/'.($topicRouteExtra['medium'] ?? 'english').'/subjects/'.$subject->id.'/materials/'.$material->id.'/swadhyay');
                                    } elseif ($studentTopicBase) {
                                        $swadhyayUrl = url('/student/subjects/'.$subject->id.'/materials/'.$material->id.'/swadhyay');
                                    } elseif ($teacherTopicBase) {
                                        $swadhyayUrl = url('/teacher/books/'.$subject->id.'/materials/'.$material->id.'/swadhyay').$topicRouteExtraQuery;
                                    } elseif ($materialTopicRoute === 'student.self-practice.material-topics.show') {
                                        $swadhyayUrl = url('/student/self-practice/subjects/'.$subject->id.'/materials/'.$material->id.'/swadhyay');
                                    }
                                @endphp
                                @if ($swadhyayUrl)
                                    <a href="{{ $swadhyayUrl }}" class="book-index-topic book-index-topic--link group">
                                        <span class="book-index-topic-no">{{ $chapterNo }}.{{ $topics->count() + 1 }}</span>
                                        <span class="book-index-topic-name group-hover:text-brand-green">સ્વાધ્યાય</span>
                                        <span class="book-index-topic-dots" aria-hidden="true"></span>
                                        <span class="book-index-topic-page">
                                            <svg class="inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </span>
                                        <span class="book-index-topic-arrow" aria-hidden="true">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </span>
                                    </a>
                                @endif
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
