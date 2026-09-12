@php
    $selectedStandard = $selectedStandard ?? old('standard', '');
    $selectedSubjectId = $selectedSubjectId ?? old('subject_id', '');
@endphp

<div x-data="{
    standard: '{{ $selectedStandard }}',
    subjectId: '{{ $selectedSubjectId }}',
    subjects: [],
    chapters: [],
    topics: [],
    async loadSubjects() {
        if (!this.standard) { this.subjects = []; this.subjectId = ''; this.chapters = []; this.topics = []; return; }
        const res = await fetch('{{ route('teacher.curriculum.subjects') }}?standard=' + encodeURIComponent(this.standard));
        this.subjects = await res.json();
    },
    async loadChapters() {
        if (!this.subjectId) { this.chapters = []; this.topics = []; return; }
        const res = await fetch('{{ route('teacher.curriculum.chapters') }}?subject_id=' + this.subjectId);
        this.chapters = await res.json();
        this.topics = [];
    },
    async loadTopicsForChapters() {
        const checked = [...this.$el.querySelectorAll('input[name=\'chapter_ids[]\']:checked')].map(el => el.value);
        if (checked.length === 0) { this.topics = []; return; }
        const all = [];
        for (const chapterId of checked) {
            const res = await fetch('{{ route('teacher.curriculum.topics') }}?chapter_id=' + chapterId);
            const items = await res.json();
            const chapter = this.chapters.find(c => String(c.id) === String(chapterId));
            const chapterName = chapter ? chapter.name : 'Chapter';
            items.forEach(t => all.push({ id: t.id, label: chapterName + ' — ' + t.name }));
        }
        this.topics = all;
    }
}" x-init="if (standard) loadSubjects(); if (subjectId) loadChapters();">
