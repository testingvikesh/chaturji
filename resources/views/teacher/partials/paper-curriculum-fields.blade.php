<div class="grid sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Standard *</label>
        <select name="standard" x-model="standard" @change="loadSubjects(); subjectId=''; chapterId=''; topicId='';" required class="w-full rounded-xl border-slate-200">
            <option value="">Select Standard</option>
            @foreach ($standards as $slug => $name)
                <option value="{{ $slug }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Subject *</label>
        <select name="subject_id" x-model="subjectId" @change="loadChapters(); chapterId=''; topicId='';" required class="w-full rounded-xl border-slate-200">
            <option value="">Select Subject</option>
            <template x-for="s in subjects" :key="s.id">
                <option :value="s.id" x-text="s.name"></option>
            </template>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Chapter *</label>
        <select name="chapter_id" x-model="chapterId" @change="loadTopics(); topicId='';" required class="w-full rounded-xl border-slate-200">
            <option value="">Select Chapter</option>
            <template x-for="c in chapters" :key="c.id">
                <option :value="c.id" x-text="c.name"></option>
            </template>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Topic</label>
        <select name="topic_id" x-model="topicId" @change="loadCounts()" class="w-full rounded-xl border-slate-200">
            <option value="">All topics in chapter</option>
            <template x-for="t in topics" :key="t.id">
                <option :value="t.id" x-text="t.name"></option>
            </template>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Status *</label>
        <select name="status" required class="w-full rounded-xl border-slate-200">
            <option value="draft" @selected(old('status', $selectedStatus ?? 'draft') === 'draft')>Draft</option>
            <option value="published" @selected(old('status', $selectedStatus ?? '') === 'published')>Published (notify students)</option>
        </select>
    </div>
</div>

<p class="text-xs text-slate-500" x-show="loadingCounts">Loading available questions…</p>
<p class="text-xs text-amber-700" x-show="!loadingCounts && chapterId && Object.values(available).every(v => v === 0)">
    No questions in the bank for this chapter/topic. Admin must upload material first.
</p>
