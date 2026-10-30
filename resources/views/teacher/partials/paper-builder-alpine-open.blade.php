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
    $selectedTargetMarks = (int) ($selectedTargetMarks ?? old('target_marks', 0));
    $holdCounts = (bool) ($holdCounts ?? false);
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
        markOptions: [20, 30, 50, 70, 100],
        targetMarks: Number(cfg.targetMarks || 0),
        holdCounts: Boolean(cfg.holdCounts),
        paperTotal: 0,
        planError: '',
        subjectsUrl: cfg.subjectsUrl,
        chaptersUrl: cfg.chaptersUrl,
        topicsUrl: cfg.topicsUrl,
        countsUrl: cfg.countsUrl,

        async boot() {
            if (this.standard) await this.loadSubjects();
            if (this.subjectId) await this.loadChapters(false);
            if (this.chapterIds.length) await this.loadTopics(false);
            this.refreshPaperTotal();
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
            const keep = String(this.subjectId || '');
            const res = await fetch(this.subjectsUrl + '?standard=' + encodeURIComponent(this.standard));
            this.subjects = await res.json();
            if (!keep) {
                return;
            }
            await this.$nextTick();
            await this.$nextTick();
            this.subjectId = keep;
            const select = this.$root.querySelector('[data-subject-select]');
            if (select && [...select.options].some(option => option.value === keep)) {
                select.value = keep;
            }
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

        selectMarks(marks) {
            this.targetMarks = Number(marks);
            this.holdCounts = false;
            this.loadCounts();
        },

        applyPlan(plan) {
            const form = this.$root.closest('form') || this.$root;
            const counts = plan.type_counts || {};
            const marks = plan.marks_per_type || {};
            form.querySelectorAll('[name^="type_counts["]').forEach(function (input) {
                const match = input.name.match(/type_counts\[(.+)\]/);
                const type = match ? match[1] : '';
                input.value = counts[type] || 0;
            });
            form.querySelectorAll('[name^="marks_per_type["]').forEach(function (input) {
                const match = input.name.match(/marks_per_type\[(.+)\]/);
                const type = match ? match[1] : '';
                if (marks[type]) {
                    input.value = marks[type];
                }
            });
            const subjective = ['one_mark', 'two_marks', 'three_marks', 'four_marks', 'five_marks', 'short_answer', 'long_answer'];
            form.querySelectorAll('[name^="optional_counts["]').forEach(function (input) {
                const match = input.name.match(/optional_counts\[(.+)\]/);
                const type = match ? match[1] : '';
                const count = Number(counts[type] || 0);
                if (subjective.indexOf(type) === -1) {
                    return;
                }
                if (count < 1) {
                    input.value = 0;
                } else if (Number(input.value) < 1) {
                    input.value = 1;
                }
            });
            this.refreshPaperTotal();
        },

        refreshPaperTotal() {
            const form = this.$root.closest('form') || this.$root;
            let total = 0;
            form.querySelectorAll('[name^="type_counts["]').forEach(function (input) {
                const match = input.name.match(/type_counts\[(.+)\]/);
                const type = match ? match[1] : '';
                const count = Math.max(0, Number(input.value) || 0);
                const marksInput = form.querySelector('[name="marks_per_type[' + type + ']"]');
                const marks = marksInput ? Math.max(1, Number(marksInput.value) || 1) : 1;
                if (count > 0) {
                    total += count * marks;
                }
            });
            this.paperTotal = total;
        },

        async loadCounts() {
            if (!this.chapterIds.length) {
                this.available = {};
                this.planError = '';
                return;
            }
            this.loadingCounts = true;
            const params = new URLSearchParams();
            this.chapterIds.forEach(id => params.append('chapter_ids[]', id));
            this.topicIds.forEach(id => params.append('topic_ids[]', id));
            if (this.targetMarks) {
                params.set('target_marks', this.targetMarks);
            }
            try {
                const res = await fetch(this.countsUrl + '?' + params.toString());
                const data = await res.json();
                this.available = data.counts || {};
                this.planError = data.plan_error || '';
                if (data.plan && !this.holdCounts) {
                    this.applyPlan(data.plan);
                }
            } catch (error) {
                this.available = {};
                this.planError = 'Could not load marks for these chapters.';
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
        targetMarks: @js($selectedTargetMarks),
        holdCounts: @js($holdCounts),
     })"
     x-init="boot()">
