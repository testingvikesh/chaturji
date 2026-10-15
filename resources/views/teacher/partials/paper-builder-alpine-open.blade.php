@php
    $selectedStandard = $selectedStandard ?? old('standard', '');
    $selectedSubjectId = $selectedSubjectId ?? old('subject_id', '');

    if (is_array(old('chapter_ids'))) {
        $chapterIdList = old('chapter_ids');
    } elseif (isset($selectedChapterIds) && is_array($selectedChapterIds)) {
        $chapterIdList = $selectedChapterIds;
    } else {
        $singleChapter = old('chapter_id', $selectedChapterId ?? '');
        $chapterIdList = filled($singleChapter) ? [$singleChapter] : [];
    }

    if (is_array(old('topic_ids'))) {
        $topicIdList = old('topic_ids');
    } elseif (isset($selectedTopicIds) && is_array($selectedTopicIds)) {
        $topicIdList = $selectedTopicIds;
    } else {
        $singleTopic = old('topic_id', $selectedTopicId ?? '');
        $topicIdList = filled($singleTopic) ? [$singleTopic] : [];
    }

    $selectedChapterIds = array_values(array_filter(array_map('strval', (array) $chapterIdList), fn ($id) => $id !== ''));
    $selectedTopicIds = array_values(array_filter(array_map('strval', (array) $topicIdList), fn ($id) => $id !== ''));
@endphp

<script>
function teacherPaperBuilder(cfg) {
    return {
        standard: cfg.standard || '',
        subjectId: cfg.subjectId || '',
        chapterIds: Array.isArray(cfg.chapterIds) ? cfg.chapterIds.map(String) : [],
        topicIds: Array.isArray(cfg.topicIds) ? cfg.topicIds.map(String) : [],
        subjects: [],
        chapters: [],
        topics: [],
        available: {},
        loadingCounts: false,
        subjectsUrl: cfg.subjectsUrl,
        chaptersUrl: cfg.chaptersUrl,
        topicsUrl: cfg.topicsUrl,
        countsUrl: cfg.countsUrl,

        async boot() {
            if (this.standard) await this.loadSubjects();
            if (this.subjectId) await this.loadChapters(false);
            if (this.chapterIds.length) await this.loadTopics(false);
        },

        async onStandardChange() {
            this.subjectId = '';
            this.subjects = [];
            this.chapters = [];
            this.chapterIds = [];
            this.topics = [];
            this.topicIds = [];
            this.available = {};
            await this.loadSubjects();
        },

        async onSubjectChange() {
            this.chapterIds = [];
            this.topicIds = [];
            this.topics = [];
            this.available = {};
            await this.loadChapters(true);
        },

        async loadSubjects() {
            if (!this.standard) {
                this.subjects = [];
                return;
            }
            const res = await fetch(this.subjectsUrl + '?standard=' + encodeURIComponent(this.standard));
            this.subjects = await res.json();
        },

        async loadChapters(reset) {
            if (!this.subjectId) {
                this.chapters = [];
                this.chapterIds = [];
                this.topics = [];
                this.topicIds = [];
                this.available = {};
                return;
            }
            const res = await fetch(this.chaptersUrl + '?subject_id=' + encodeURIComponent(this.subjectId));
            this.chapters = await res.json();
            if (reset) {
                this.chapterIds = [];
                this.topics = [];
                this.topicIds = [];
                this.available = {};
                return;
            }
            const allowed = new Set(this.chapters.map(ch => String(ch.id)));
            this.chapterIds = this.chapterIds.filter(id => allowed.has(String(id)));
        },

        async onChaptersChange() {
            await this.$nextTick();
            await this.loadTopics(true);
        },

        async loadTopics(reset) {
            if (!this.chapterIds.length) {
                this.topics = [];
                this.topicIds = [];
                this.available = {};
                return;
            }
            const params = new URLSearchParams();
            this.chapterIds.forEach(id => params.append('chapter_ids[]', id));
            const res = await fetch(this.topicsUrl + '?' + params.toString());
            this.topics = await res.json();
            if (reset) {
                this.topicIds = [];
            } else {
                const allowed = new Set(this.topics.map(tp => String(tp.id)));
                this.topicIds = this.topicIds.filter(id => allowed.has(String(id)));
            }
            await this.loadCounts();
        },

        selectAllChapters() {
            this.chapterIds = this.chapters.map(ch => String(ch.id));
            this.loadTopics(true);
        },

        clearChapters() {
            this.chapterIds = [];
            this.topics = [];
            this.topicIds = [];
            this.available = {};
        },

        selectAllTopics() {
            this.topicIds = this.topics.map(tp => String(tp.id));
            this.loadCounts();
        },

        clearTopics() {
            this.topicIds = [];
            this.loadCounts();
        },

        async loadCounts() {
            if (!this.chapterIds.length) {
                this.available = {};
                return;
            }
            this.loadingCounts = true;
            const params = new URLSearchParams();
            this.chapterIds.forEach(id => params.append('chapter_ids[]', id));
            this.topicIds.forEach(id => params.append('topic_ids[]', id));
            try {
                const res = await fetch(this.countsUrl + '?' + params.toString());
                const data = await res.json();
                this.available = data.counts || {};
            } catch (error) {
                this.available = {};
            } finally {
                this.loadingCounts = false;
            }
        },
    };
}
</script>
<div x-data="teacherPaperBuilder({
        standard: @js((string) $selectedStandard),
        subjectId: @js((string) $selectedSubjectId),
        chapterIds: @js($selectedChapterIds),
        topicIds: @js($selectedTopicIds),
        subjectsUrl: @js(route('teacher.curriculum.subjects')),
        chaptersUrl: @js(route('teacher.curriculum.chapters')),
        topicsUrl: @js(route('teacher.curriculum.topics')),
        countsUrl: @js(route('teacher.curriculum.question-counts')),
     })"
     x-init="boot()">
