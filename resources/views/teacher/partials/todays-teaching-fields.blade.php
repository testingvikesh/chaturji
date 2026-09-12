<div class="grid sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Standard *</label>
        <select name="standard" x-model="standard" @change="loadSubjects(); subjectId=''; chapters=[]; topics=[];" required class="w-full rounded-xl border-slate-200">
            <option value="">Select Standard</option>
            @foreach ($standards as $slug => $name)
                <option value="{{ $slug }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Subject *</label>
        <select name="subject_id" x-model="subjectId" @change="loadChapters(); topics=[];" required class="w-full rounded-xl border-slate-200">
            <option value="">Select Subject</option>
            <template x-for="s in subjects" :key="s.id">
                <option :value="s.id" x-text="s.name"></option>
            </template>
        </select>
    </div>
</div>

<div x-show="chapters.length > 0" x-cloak class="mt-4">
    <label class="block text-sm font-medium text-slate-700 mb-2">Chapters * (select one or more)</label>
    <div class="max-h-40 overflow-y-auto rounded-xl border border-slate-200 p-3 space-y-2">
        <template x-for="chapter in chapters" :key="chapter.id">
            <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                <input
                    type="checkbox"
                    name="chapter_ids[]"
                    :value="chapter.id"
                    class="rounded border-slate-300 text-brand-green"
                    @change="loadTopicsForChapters()"
                >
                <span x-text="chapter.name"></span>
            </label>
        </template>
    </div>
</div>

<div x-show="topics.length > 0" x-cloak class="mt-4">
    <label class="block text-sm font-medium text-slate-700 mb-2">Topics (optional — select multiple)</label>
    <p class="text-xs text-slate-500 mb-2">Leave empty to track full chapter(s) only</p>
    <div class="max-h-48 overflow-y-auto rounded-xl border border-slate-200 p-3 space-y-2">
        <template x-for="topic in topics" :key="topic.id">
            <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                <input type="checkbox" name="topic_ids[]" :value="topic.id" class="rounded border-slate-300 text-brand-green">
                <span x-text="topic.label"></span>
            </label>
        </template>
    </div>
</div>
