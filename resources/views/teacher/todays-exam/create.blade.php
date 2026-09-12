<x-teacher-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('teacher.todays-exam.index', ['date' => $date]) }}" class="text-xs font-medium text-brand-green hover:underline mb-1 inline-block">&larr; Back to Today's Exam</a>
            <h2 class="admin-page-title">Add Exam Topics</h2>
            <p class="admin-page-subtitle">Select chapter → topics → set marks & question types → preview exam</p>
        </div>
    </x-slot>

    <form
        method="POST"
        action="{{ route('teacher.todays-exam.store') }}"
        class="admin-page max-w-4xl space-y-6"
        x-data="{
            standard: '{{ old('standard', '') }}',
            subjectId: '{{ old('subject_id', '') }}',
            chapterId: '{{ old('chapter_id', '') }}',
            subjects: [],
            chapters: [],
            topics: [],
            selectedTopics: @js(
                collect(old('entries', []))
                    ->mapWithKeys(fn ($entry, $key) => [(string) ($entry['topic_id'] ?? $key) => $entry['status'] ?? 'remaining'])
                    ->all()
            ),
            async loadSubjects() {
                if (!this.standard) { this.subjects = []; this.subjectId = ''; this.chapters = []; this.chapterId = ''; this.topics = []; return; }
                const res = await fetch('{{ route('teacher.curriculum.subjects') }}?standard=' + encodeURIComponent(this.standard));
                this.subjects = await res.json();
            },
            async loadChapters() {
                if (!this.subjectId) { this.chapters = []; this.chapterId = ''; this.topics = []; return; }
                const res = await fetch('{{ route('teacher.curriculum.chapters') }}?subject_id=' + this.subjectId);
                this.chapters = await res.json();
                this.chapterId = '';
                this.topics = [];
            },
            async loadTopics() {
                if (!this.chapterId) { this.topics = []; return; }
                const res = await fetch('{{ route('teacher.curriculum.topics') }}?chapter_id=' + this.chapterId);
                this.topics = await res.json();
            },
            toggleTopic(topicId, checked) {
                const id = String(topicId);
                if (checked) {
                    if (!this.selectedTopics[id]) this.selectedTopics[id] = 'remaining';
                } else {
                    delete this.selectedTopics[id];
                }
            },
            selectedCount() {
                return Object.keys(this.selectedTopics).length;
            },
            completedCount() {
                return Object.values(this.selectedTopics).filter(status => status === 'completed').length;
            },
            hasCompleted() {
                return this.completedCount() > 0;
            }
        }"
        x-init="if (standard) loadSubjects(); if (subjectId) { loadChapters().then(() => { if (chapterId) loadTopics(); }); }"
        x-on:submit="
            if (selectedCount() === 0) { $event.preventDefault(); alert('Please select at least one topic.'); return; }
            if (hasCompleted() && !document.querySelector('input[name=target_marks]:checked')) { $event.preventDefault(); alert('Please select exam marks for completed topics.'); }
        "
    >
        @csrf

        <div class="admin-card p-6 space-y-4">
            <h3 class="font-bold text-slate-900">Basic Details</h3>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Exam Date *</label>
                    <input type="date" name="exam_date" value="{{ old('exam_date', $date) }}" required class="w-full rounded-xl border-slate-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Standard *</label>
                    <select name="standard" x-model="standard" @change="loadSubjects(); subjectId=''; chapterId=''; topics=[]; selectedTopics={};" required class="w-full rounded-xl border-slate-200">
                        <option value="">Select Standard</option>
                        @foreach ($standards as $slug => $name)
                            <option value="{{ $slug }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Subject *</label>
                    <select name="subject_id" x-model="subjectId" @change="loadChapters(); chapterId=''; topics=[]; selectedTopics={};" required class="w-full rounded-xl border-slate-200">
                        <option value="">Select Subject</option>
                        <template x-for="s in subjects" :key="s.id">
                            <option :value="s.id" x-text="s.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Chapter *</label>
                    <select name="chapter_id" x-model="chapterId" @change="loadTopics(); selectedTopics={};" required class="w-full rounded-xl border-slate-200">
                        <option value="">Select Chapter</option>
                        <template x-for="c in chapters" :key="c.id">
                            <option :value="c.id" x-text="c.name"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full rounded-xl border-slate-200 text-sm" placeholder="Optional notes for today">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="admin-card p-6" x-show="chapterId" x-cloak>
            <div class="flex items-center justify-between gap-4 mb-4">
                <div>
                    <h3 class="font-bold text-slate-900">Topics</h3>
                    <p class="text-xs text-slate-500 mt-1">Completed = preview exam · Remaining = report only</p>
                </div>
                <span class="text-xs font-semibold text-brand-green" x-text="selectedCount() + ' selected'"></span>
            </div>

            <div x-show="topics.length === 0" class="rounded-xl border border-dashed border-slate-200 py-10 text-center text-sm text-slate-500">
                No topics found for this chapter.
            </div>

            <div class="space-y-2" x-show="topics.length > 0">
                <template x-for="topic in topics" :key="topic.id">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 hover:border-brand-green/40 transition">
                        <label class="flex items-center gap-3 flex-1 cursor-pointer min-w-0">
                            <input
                                type="checkbox"
                                class="rounded border-slate-300 text-brand-green shrink-0"
                                :checked="selectedTopics[String(topic.id)] !== undefined"
                                @change="toggleTopic(topic.id, $event.target.checked)"
                            >
                            <span class="font-medium text-slate-900 truncate" x-text="topic.name"></span>
                        </label>
                        <div class="flex items-center gap-4 pl-7 sm:pl-0 shrink-0" x-show="selectedTopics[String(topic.id)] !== undefined">
                            <label class="inline-flex items-center gap-1.5 text-sm cursor-pointer">
                                <input type="radio" :name="'status_' + topic.id" value="remaining" class="text-brand-green" x-model="selectedTopics[String(topic.id)]">
                                <span class="text-slate-600">Remaining</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 text-sm cursor-pointer">
                                <input type="radio" :name="'status_' + topic.id" value="completed" class="text-brand-green" x-model="selectedTopics[String(topic.id)]">
                                <span class="text-brand-green-dark font-medium">Completed</span>
                            </label>
                        </div>
                        <template x-if="selectedTopics[String(topic.id)] !== undefined">
                            <div class="hidden">
                                <input type="hidden" :name="'entries[' + topic.id + '][topic_id]'" :value="topic.id">
                                <input type="hidden" :name="'entries[' + topic.id + '][status]'" :value="selectedTopics[String(topic.id)]">
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <div class="admin-card p-6 border-brand-green/30 bg-brand-green-50/40" x-show="hasCompleted()" x-cloak>
            <h3 class="font-bold text-slate-900">Exam Paper</h3>
            <p class="text-xs text-slate-600 mt-1">
                <span x-text="completedCount()"></span> completed topic(s) — quantities auto-set from marks
            </p>
            <div class="mt-4">
                @include('teacher.partials.teaching-homework-builder', ['markOptions' => $markOptions])
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="rounded-xl bg-brand-green text-white px-6 py-2.5 text-sm font-semibold hover:bg-brand-green-dark transition">
                <span x-show="hasCompleted()">Preview Exam</span>
                <span x-show="!hasCompleted()" x-cloak>Add to Report</span>
            </button>
            <a href="{{ route('teacher.todays-exam.index', ['date' => $date]) }}" class="rounded-xl border border-slate-200 px-6 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">Cancel</a>
        </div>
    </form>
</x-teacher-layout>
