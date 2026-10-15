<div class="grid sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Standard *</label>
        <select name="standard" x-model="standard" @change="onStandardChange()" required class="w-full rounded-xl border-slate-200">
            <option value="">Select Standard</option>
            @foreach ($standards as $slug => $name)
                <option value="{{ $slug }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Subject *</label>
        <input type="hidden" name="subject_id" :value="subjectId">
        <select data-subject-select :key="subjects.map(function (s) { return s.id; }).join(',')" x-model="subjectId" @change="onSubjectChange()" required class="w-full rounded-xl border-slate-200">
            <option value="">Select Subject</option>
            <template x-for="s in subjects" :key="s.id">
                <option :value="String(s.id)" x-text="s.name" :selected="String(s.id) === String(subjectId)"></option>
            </template>
        </select>
    </div>
</div>

<div>
    <div class="mb-1 flex items-center justify-between gap-2">
        <label class="block text-sm font-medium text-slate-700">Chapter *</label>
        <div class="flex items-center gap-2 text-xs" x-show="chapters.length" x-cloak>
            <button type="button" class="font-semibold text-brand-green hover:underline" @click="selectAllChapters()" :disabled="!subjectId">Select all</button>
            <span class="text-slate-300">|</span>
            <button type="button" class="font-semibold text-slate-500 hover:underline" @click="clearChapters()" :disabled="!subjectId">Clear</button>
        </div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-3 max-h-52 overflow-y-auto space-y-2"
         :class="!subjectId || !chapters.length ? 'opacity-50 pointer-events-none' : ''">
        <p class="text-xs text-slate-500" x-show="!chapters.length">Select a subject to load chapters.</p>
        <template x-for="ch in chapters" :key="ch.id">
            <label class="flex items-start gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-50 cursor-pointer">
                <input type="checkbox"
                       class="mt-0.5 rounded border-slate-300 text-brand-green focus:ring-brand-green"
                       :value="String(ch.id)"
                       x-model="chapterIds"
                       @change="onChaptersChange()">
                <span class="text-sm text-slate-700" x-text="ch.name"></span>
            </label>
        </template>
        <template x-for="id in chapterIds" :key="'chapter-hidden-'+id">
            <input type="hidden" name="chapter_ids[]" :value="id">
        </template>
    </div>
    <p class="text-xs text-slate-500 mt-1">
        Select one or more chapters.
        <span x-show="chapterIds.length" x-text="chapterIds.length + ' chapter(s) selected'"></span>
    </p>
</div>

<div>
    <div class="mb-1 flex items-center justify-between gap-2">
        <label class="block text-sm font-medium text-slate-700">Topic *</label>
        <div class="flex items-center gap-2 text-xs" x-show="topics.length" x-cloak>
            <button type="button" class="font-semibold text-brand-green hover:underline" @click="selectAllTopics()" :disabled="!chapterIds.length">Select all</button>
            <span class="text-slate-300">|</span>
            <button type="button" class="font-semibold text-slate-500 hover:underline" @click="clearTopics()" :disabled="!chapterIds.length">Clear</button>
        </div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-3 max-h-52 overflow-y-auto space-y-2"
         :class="!chapterIds.length ? 'opacity-50 pointer-events-none' : ''">
        <p class="text-xs text-slate-500" x-show="!topics.length">Select chapter(s) to load topics.</p>
        <template x-for="tp in topics" :key="tp.id">
            <label class="flex items-start gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-50 cursor-pointer">
                <input type="checkbox"
                       class="mt-0.5 rounded border-slate-300 text-brand-green focus:ring-brand-green"
                       :value="String(tp.id)"
                       x-model="topicIds"
                       @change="loadCounts()">
                <span class="text-sm text-slate-700" x-text="tp.name"></span>
            </label>
        </template>
        <template x-for="id in topicIds" :key="'topic-hidden-'+id">
            <input type="hidden" name="topic_ids[]" :value="id">
        </template>
    </div>
    <p class="text-xs text-slate-500 mt-1">
        Select one or more topics.
        <span x-show="!topicIds.length">None selected = all topics in selected chapters.</span>
        <span x-show="topicIds.length" x-text="topicIds.length + ' topic(s) selected'"></span>
    </p>
</div>

<div class="grid sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Status *</label>
        <select name="status" required class="w-full rounded-xl border-slate-200">
            <option value="draft" @selected(old('status', $selectedStatus ?? 'draft') === 'draft')>Draft</option>
            <option value="published" @selected(old('status', $selectedStatus ?? '') === 'published')>Published (notify students)</option>
        </select>
    </div>
</div>

<p class="text-xs text-slate-500" x-show="loadingCounts">Loading available questions…</p>
<p class="text-xs text-amber-700" x-show="!loadingCounts && chapterIds.length && Object.keys(available).length && Object.values(available).every(v => v === 0)">
    No questions in the bank for these chapters/topics. Admin must upload material first.
</p>
