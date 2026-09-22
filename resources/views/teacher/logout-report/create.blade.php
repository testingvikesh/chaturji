<x-teacher-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Logout reporting</span>
            <h2 class="admin-page-title">Submit work report before logout</h2>
            <p class="admin-page-subtitle">Your Medium · Standard · Subject → select Chapter → tick Topics → Complete / Remain → submit</p>
        </div>
    </x-slot>

    <div class="admin-page max-w-3xl"
         x-data="{
            options: @js($options),
            assignmentKey: @js($initialKey ?: ''),
            chapterId: @js((string) old('chapter_id', '')),
            selectedTopics: @js(collect(old('topic_ids', []))->map(fn ($id) => (string) $id)->values()),
            chapters: [],
            topics: [],
            chkComplete: @js((bool) old('chk_complete', true)),
            chkRemain: @js((bool) old('chk_remain', false)),
            get current() {
                return this.options.find(o => o.key === this.assignmentKey) || null;
            },
            async onAssignmentChange() {
                this.chapterId = '';
                this.selectedTopics = [];
                this.chapters = [];
                this.topics = [];
                await this.loadChapters();
            },
            async loadChapters() {
                const cur = this.current;
                if (!cur) { this.chapters = []; return; }
                const res = await fetch('{{ route('teacher.curriculum.chapters') }}?subject_id=' + cur.subject_id);
                this.chapters = await res.json();
            },
            async loadTopics(keepSelection = false) {
                if (!keepSelection) this.selectedTopics = [];
                this.topics = [];
                if (!this.chapterId) return;
                const res = await fetch('{{ route('teacher.curriculum.topics') }}?chapter_id=' + this.chapterId);
                this.topics = await res.json();
            },
            pickComplete() { this.chkComplete = true; this.chkRemain = false; },
            pickRemain() { this.chkRemain = true; this.chkComplete = false; }
         }"
         x-init="
            if (assignmentKey) {
                loadChapters().then(() => { if (chapterId) return loadTopics(true); });
            }
         ">
        @include('admin.partials.alert')

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 mb-4">
            Medium / Standard / Subject come from your Settings. Select chapter, tick topics, then submit to logout.
        </div>

        @if ($options->isEmpty())
            <div class="admin-card p-6 text-center space-y-3">
                <p class="text-slate-600">No medium / standard / subject assigned in Settings.</p>
                <a href="{{ route('teacher.settings.edit') }}" class="admin-btn-primary inline-flex">Go to Settings</a>
            </div>
        @else
            <form method="POST" action="{{ route('teacher.logout-report.store') }}" class="admin-form-card space-y-0">
                <div class="admin-card-top"></div>
                <div class="admin-card-body space-y-4">
                    @csrf

                    <div class="grid sm:grid-cols-2 gap-3 text-sm">
                        <p><span class="text-slate-500">Employee:</span> <span class="font-semibold">{{ $employeeCode }}</span></p>
                        <p><span class="text-slate-500">Teacher:</span> <span class="font-semibold">{{ $teacher->name }}</span></p>
                        <p><span class="text-slate-500">Date:</span> <span class="font-semibold">{{ \Illuminate\Support\Carbon::parse($today)->format('d M Y') }}</span></p>
                    </div>

                    <div>
                        <label class="admin-label">Medium · Standard · Subject <span class="text-red-500">*</span></label>
                        <select name="assignment_key" x-model="assignmentKey" @change="onAssignmentChange()" required class="admin-select">
                            <option value="">Select your assigned subject</option>
                            @foreach ($options as $opt)
                                <option value="{{ $opt['key'] }}">
                                    {{ $opt['medium_label'] }} · {{ $opt['standard_name'] }} · {{ $opt['subject_name'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('assignment_key')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="admin-label">Chapter <span class="text-red-500">*</span></label>
                        <select name="chapter_id" x-model="chapterId" @change="loadTopics()" required class="admin-select">
                            <option value="">Select chapter</option>
                            <template x-for="c in chapters" :key="c.id">
                                <option :value="String(c.id)" x-text="c.name"></option>
                            </template>
                        </select>
                        @error('chapter_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-bold text-slate-900">Chapter topics <span class="font-normal text-slate-500">(checkbox)</span></p>
                            <span class="text-xs text-slate-500" x-text="selectedTopics.length + ' selected'"></span>
                        </div>

                        <div x-show="!chapterId" class="text-sm text-slate-400">Select a chapter to load topics.</div>
                        <div x-show="chapterId && topics.length === 0" x-cloak class="text-sm text-slate-400">No topics found for this chapter.</div>

                        <div class="space-y-2 max-h-64 overflow-y-auto" x-show="topics.length > 0" x-cloak>
                            <template x-for="t in topics" :key="t.id">
                                <label class="flex items-start gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm cursor-pointer hover:border-brand-green">
                                    <input type="checkbox"
                                           class="mt-0.5 rounded border-slate-300 text-brand-green"
                                           name="topic_ids[]"
                                           :value="String(t.id)"
                                           x-model="selectedTopics">
                                    <span class="text-slate-800" x-text="t.name"></span>
                                </label>
                            </template>
                        </div>
                        @error('topic_ids')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 grid sm:grid-cols-2 gap-2 text-sm">
                        <label class="inline-flex items-center gap-2 font-semibold text-brand-green">
                            <input type="checkbox" name="chk_complete" value="1" class="rounded border-slate-300 text-brand-green"
                                   x-model="chkComplete" @change="if (chkComplete) pickComplete()">
                            Complete
                        </label>
                        <label class="inline-flex items-center gap-2 font-semibold text-amber-700">
                            <input type="checkbox" name="chk_remain" value="1" class="rounded border-slate-300 text-amber-600"
                                   x-model="chkRemain" @change="if (chkRemain) pickRemain()">
                            Remain
                        </label>
                        @error('chk_complete')<p class="text-sm text-red-600 sm:col-span-2">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="admin-label">Notes</label>
                        <textarea name="notes" rows="3" class="admin-input" placeholder="Optional remark">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-1">
                        <button type="submit" class="admin-btn-primary">Submit report &amp; Logout</button>
                        <a href="{{ route('teacher.dashboard') }}" class="admin-btn-ghost">Cancel · stay logged in</a>
                    </div>
                </div>
            </form>
        @endif
    </div>
</x-teacher-layout>
