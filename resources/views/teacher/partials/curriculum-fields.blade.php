@php
    $curriculumBase = url('/teacher/curriculum');
@endphp

<div class="grid sm:grid-cols-2 gap-4" x-data="{
    standard: '{{ old('standard', $selectedStandard ?? '') }}',
    subjectId: '{{ old('subject_id', $selectedSubjectId ?? '') }}',
    subjects: [],
    chapters: [],
    async loadSubjects() {
        if (!this.standard) { this.subjects = []; this.subjectId = ''; return; }
        const res = await fetch('{{ route('teacher.curriculum.subjects') }}?standard=' + this.standard);
        this.subjects = await res.json();
    },
    async loadChapters() {
        if (!this.subjectId) { this.chapters = []; return; }
        const res = await fetch('{{ route('teacher.curriculum.chapters') }}?subject_id=' + this.subjectId);
        this.chapters = await res.json();
    }
}" x-init="if (standard) loadSubjects(); if (subjectId) loadChapters();">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Standard *</label>
        <select name="standard" x-model="standard" @change="loadSubjects(); subjectId = ''" required class="w-full rounded-xl border-slate-200">
            <option value="">Select Standard</option>
            @foreach ($standards as $slug => $name)
                <option value="{{ $slug }}" @selected(old('standard', $selectedStandard ?? '') === $slug)>{{ $name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Subject</label>
        <select name="subject_id" x-model="subjectId" @change="loadChapters()" class="w-full rounded-xl border-slate-200">
            <option value="">Select Subject</option>
            <template x-for="s in subjects" :key="s.id">
                <option :value="s.id" x-text="s.name" :selected="subjectId == s.id"></option>
            </template>
        </select>
    </div>

    @if ($showChapter ?? false)
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Chapter</label>
            <select name="chapter_id" class="w-full rounded-xl border-slate-200">
                <option value="">Select Chapter</option>
                <template x-for="c in chapters" :key="c.id">
                    <option :value="c.id" x-text="c.name" :selected="'{{ old('chapter_id', $selectedChapterId ?? '') }}' == c.id"></option>
                </template>
            </select>
        </div>
    @endif

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Status *</label>
        <select name="status" required class="w-full rounded-xl border-slate-200">
            <option value="draft" @selected(old('status', $selectedStatus ?? 'draft') === 'draft')>Draft</option>
            <option value="published" @selected(old('status', $selectedStatus ?? '') === 'published')>Published (notify students)</option>
        </select>
    </div>
</div>
