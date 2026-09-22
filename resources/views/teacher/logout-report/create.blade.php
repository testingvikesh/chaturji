<x-teacher-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Logout reporting</span>
            <h2 class="admin-page-title">Submit work report before logout</h2>
            <p class="admin-page-subtitle">Select Medium · Standard · Subject · Chapter · Topic, tick Complete / Remain, then submit to logout</p>
        </div>
    </x-slot>

    <div class="admin-page max-w-3xl"
         x-data="{
            medium: @js(old('medium', $mediums->first() ?? '')),
            standard: @js(old('standard', '')),
            subjectId: @js((string) old('subject_id', '')),
            chapterId: @js((string) old('chapter_id', '')),
            topicId: @js((string) old('topic_id', '')),
            subjects: [],
            chapters: [],
            topics: [],
            chkComplete: @js((bool) old('chk_complete', true)),
            chkRemain: @js((bool) old('chk_remain', false)),
            async loadSubjects() {
                if (!this.standard) { this.subjects = []; this.subjectId = ''; this.chapters = []; this.chapterId = ''; this.topics = []; this.topicId = ''; return; }
                const res = await fetch('{{ route('teacher.curriculum.subjects') }}?standard=' + encodeURIComponent(this.standard));
                this.subjects = await res.json();
            },
            async loadChapters() {
                if (!this.subjectId) { this.chapters = []; this.chapterId = ''; this.topics = []; this.topicId = ''; return; }
                const res = await fetch('{{ route('teacher.curriculum.chapters') }}?subject_id=' + this.subjectId);
                this.chapters = await res.json();
            },
            async loadTopics() {
                if (!this.chapterId) { this.topics = []; this.topicId = ''; return; }
                const res = await fetch('{{ route('teacher.curriculum.topics') }}?chapter_id=' + this.chapterId);
                this.topics = await res.json();
            },
            pickComplete() { this.chkComplete = true; this.chkRemain = false; },
            pickRemain() { this.chkRemain = true; this.chkComplete = false; }
         }"
         x-init="
            if (standard) {
                loadSubjects().then(() => { if (subjectId) return loadChapters(); })
                    .then(() => { if (chapterId) return loadTopics(); });
            }
         ">
        @include('admin.partials.alert')

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 mb-4">
            Logout is allowed only after this report is submitted. Report is emailed to admin and saved in logs.
        </div>

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
                    <label class="admin-label">Medium <span class="text-red-500">*</span></label>
                    <select name="medium" x-model="medium" required class="admin-select">
                        <option value="">Select medium</option>
                        @foreach ($mediums as $m)
                            <option value="{{ $m }}">{{ \App\Models\Standard::MEDIUMS[$m] ?? ucfirst($m) }}</option>
                        @endforeach
                        @if ($mediums->isEmpty())
                            <option value="english">English</option>
                            <option value="gujarati">Gujarati</option>
                        @endif
                    </select>
                    @error('medium')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="admin-label">Standard <span class="text-red-500">*</span></label>
                    <select name="standard" x-model="standard" @change="loadSubjects()" required class="admin-select">
                        <option value="">Select standard</option>
                        @foreach ($standards as $standard)
                            <option value="{{ $standard->slug ?: $standard->name }}">{{ $standard->name }}</option>
                        @endforeach
                    </select>
                    @error('standard')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="admin-label">Subject <span class="text-red-500">*</span></label>
                    <select name="subject_id" x-model="subjectId" @change="loadChapters()" required class="admin-select">
                        <option value="">Select subject</option>
                        <template x-for="s in subjects" :key="s.id">
                            <option :value="String(s.id)" x-text="s.name"></option>
                        </template>
                    </select>
                    @error('subject_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
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

                <div>
                    <label class="admin-label">Topic</label>
                    <select name="topic_id" x-model="topicId" class="admin-select">
                        <option value="">Select topic (optional)</option>
                        <template x-for="t in topics" :key="t.id">
                            <option :value="String(t.id)" x-text="t.name"></option>
                        </template>
                    </select>
                    @error('topic_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                    <p class="text-sm font-bold text-slate-900">Checklist (tick all that apply)</p>
                    <div class="grid sm:grid-cols-2 gap-2 text-sm">
                        <label class="inline-flex items-center gap-2"><input type="checkbox" name="chk_medium" value="1" class="rounded border-slate-300 text-brand-green" @checked(old('chk_medium', true))> Medium</label>
                        <label class="inline-flex items-center gap-2"><input type="checkbox" name="chk_standard" value="1" class="rounded border-slate-300 text-brand-green" @checked(old('chk_standard', true))> Standard</label>
                        <label class="inline-flex items-center gap-2"><input type="checkbox" name="chk_subject" value="1" class="rounded border-slate-300 text-brand-green" @checked(old('chk_subject', true))> Subject</label>
                        <label class="inline-flex items-center gap-2"><input type="checkbox" name="chk_chapter" value="1" class="rounded border-slate-300 text-brand-green" @checked(old('chk_chapter', true))> Chapter</label>
                        <label class="inline-flex items-center gap-2"><input type="checkbox" name="chk_topic" value="1" class="rounded border-slate-300 text-brand-green" @checked(old('chk_topic', true))> Topic</label>
                    </div>
                    <div class="border-t border-slate-200 pt-3 grid sm:grid-cols-2 gap-2 text-sm">
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
                    </div>
                    @error('chk_complete')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
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
    </div>
</x-teacher-layout>
