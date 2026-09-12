<div class="admin-card subject-chapter-topic-sticky-wrap sticky top-14 lg:top-0 z-40 overflow-hidden mb-6 shadow-lg">
    <div class="admin-card-top"></div>
    @include('partials.subject-chapter-topic-row', [
        'subjectName' => $subjectName,
        'chapterName' => $chapterName ?? null,
        'topicName' => $topicName ?? null,
    ])
</div>
