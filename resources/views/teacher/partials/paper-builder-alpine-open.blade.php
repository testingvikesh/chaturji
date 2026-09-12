@php
    $selectedStandard = $selectedStandard ?? old('standard', '');
    $selectedSubjectId = $selectedSubjectId ?? old('subject_id', '');
    $selectedChapterId = $selectedChapterId ?? old('chapter_id', '');
    $selectedTopicId = $selectedTopicId ?? old('topic_id', '');
@endphp

<div x-data="{
    standard: '{{ $selectedStandard }}',
    subjectId: '{{ $selectedSubjectId }}',
    chapterId: '{{ $selectedChapterId }}',
    topicId: '{{ $selectedTopicId }}',
    subjects: [],
    chapters: [],
    topics: [],
    available: {},
    loadingCounts: false,
    async loadSubjects() {
        if (!this.standard) { this.subjects = []; this.subjectId = ''; return; }
        const res = await fetch('{{ route('teacher.curriculum.subjects') }}?standard=' + encodeURIComponent(this.standard));
        this.subjects = await res.json();
    },
    async loadChapters() {
        if (!this.subjectId) { this.chapters = []; this.chapterId = ''; this.topics = []; this.topicId = ''; this.available = {}; return; }
        const res = await fetch('{{ route('teacher.curriculum.chapters') }}?subject_id=' + this.subjectId);
        this.chapters = await res.json();
    },
    async loadTopics() {
        if (!this.chapterId) { this.topics = []; this.topicId = ''; this.available = {}; return; }
        const res = await fetch('{{ route('teacher.curriculum.topics') }}?chapter_id=' + this.chapterId);
        this.topics = await res.json();
        await this.loadCounts();
    },
    async loadCounts() {
        if (!this.chapterId) { this.available = {}; return; }
        this.loadingCounts = true;
        let url = '{{ route('teacher.curriculum.question-counts') }}?chapter_id=' + this.chapterId;
        if (this.topicId) url += '&topic_id=' + this.topicId;
        const res = await fetch(url);
        const data = await res.json();
        this.available = data.counts || {};
        this.loadingCounts = false;
    }
}" x-init="if (standard) loadSubjects(); if (subjectId) loadChapters(); if (chapterId) loadTopics();">
