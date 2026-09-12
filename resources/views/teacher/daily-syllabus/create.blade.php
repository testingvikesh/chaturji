<x-teacher-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Daily Syllabus</span>
            <h2 class="admin-page-title">Daily Syllabus Update</h2>
            <p class="admin-page-subtitle">Auto-filled from login · select chapter/topic · mark status · submit</p>
        </div>
    </x-slot>

    @php
        $mediumLabel = $teacher->medium
            ? (\App\Models\Standard::MEDIUMS[$teacher->medium] ?? ucfirst($teacher->medium))
            : '—';
    @endphp

    <div class="admin-page max-w-5xl space-y-5"
         x-data="{
            standard: @js($prefill['standard'] ?? ''),
            subjectId: @js((string) ($prefill['subject_id'] ?? '')),
            chapterId: @js((string) ($prefill['chapter_id'] ?? '')),
            topicId: @js((string) ($prefill['topic_id'] ?? '')),
            subjects: [],
            chapters: [],
            topics: [],
            topicName: '',
            progress: @js($progress),
            status: @js(old('status', 'completed')),
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
                if (!this.chapterId) { this.topics = []; this.topicId = ''; this.topicName = ''; return; }
                const res = await fetch('{{ route('teacher.curriculum.topics') }}?chapter_id=' + this.chapterId);
                this.topics = await res.json();
                this.syncTopicName();
                this.refreshProgress();
            },
            syncTopicName() {
                const t = this.topics.find(x => String(x.id) === String(this.topicId));
                this.topicName = t ? t.name : '';
            },
            async refreshProgress() {
                if (!this.standard || !this.subjectId || !this.chapterId) return;
                const url = new URL('{{ route('teacher.daily-syllabus.progress') }}', window.location.origin);
                url.searchParams.set('standard', this.standard);
                url.searchParams.set('subject_id', this.subjectId);
                url.searchParams.set('chapter_id', this.chapterId);
                const res = await fetch(url);
                this.progress = await res.json();
            },
            setAction(name) {
                this.$refs.actionInput.value = name;
                if (name === 'mark_completed') this.status = 'completed';
            }
         }"
         x-init="
            if (standard) {
                loadSubjects().then(() => {
                    if (subjectId) return loadChapters();
                }).then(() => {
                    if (chapterId) return loadTopics();
                });
            }
         ">

        @include('admin.partials.alert')

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('teacher.daily-syllabus.index') }}" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">View Previous Updates</a>
            <a href="{{ route('teacher.daily-syllabus.pending') }}" class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100">View Pending Topics</a>
            <a href="{{ route('teacher.todays-teaching.index') }}" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">View Syllabus / Today's HW</a>
        </div>

        <form method="POST" action="{{ route('teacher.daily-syllabus.store') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="action" value="save" x-ref="actionInput">
            <input type="hidden" name="teaching_date" value="{{ $today->toDateString() }}">

            {{-- Teacher Information --}}
            <div class="admin-card overflow-hidden">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <h3 class="font-bold text-slate-900">👨‍🏫 Teacher Information</h3>
                    <p class="text-xs text-slate-500 mt-1">Auto from login</p>
                </div>
                <div class="p-5 grid sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Teacher Name</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $teacher->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Employee ID</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $employeeId }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Mobile Number</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $teacher->mobile ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Email ID</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $teacher->email ?: '—' }}</p>
                    </div>
                </div>
            </div>

            {{-- Academic + Class details --}}
            <div class="admin-card overflow-hidden">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <h3 class="font-bold text-slate-900">📚 Academic & Class Details</h3>
                </div>
                <div class="p-5 grid sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Academic Year</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $academicYear }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Medium</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $mediumLabel }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Date</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $today->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Day</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $dayName }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-400">Section (optional)</label>
                        <input type="text" name="section" value="{{ old('section') }}" placeholder="A / B / C" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-400">Period / Time</label>
                        <input type="text" name="period_label" value="{{ old('period_label') }}" placeholder="Auto from timetable soon · e.g. 2nd Period" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1">Class / Standard *</label>
                        <select name="standard" x-model="standard" required
                                @change="loadSubjects(); subjectId=''; chapterId=''; topicId=''; topics=[];"
                                class="w-full rounded-xl border-slate-200 text-sm">
                            <option value="">Select Class / Standard</option>
                            @foreach ($standards as $slug => $name)
                                <option value="{{ $slug }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1">Subject *</label>
                        <select name="subject_id" x-model="subjectId" required
                                @change="loadChapters(); chapterId=''; topicId=''; topics=[];"
                                class="w-full rounded-xl border-slate-200 text-sm">
                            <option value="">Select Subject</option>
                            <template x-for="s in subjects" :key="s.id">
                                <option :value="s.id" x-text="s.name" :selected="String(s.id) === String(subjectId)"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Syllabus details --}}
            <div class="admin-card overflow-hidden">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <h3 class="font-bold text-slate-900">📖 Syllabus Details</h3>
                    <p class="text-xs text-slate-500 mt-1">Select chapter & topic</p>
                </div>
                <div class="p-5 space-y-4">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Chapter *</label>
                            <select name="chapter_id" x-model="chapterId" required
                                    @change="loadTopics(); topicId='';"
                                    class="w-full rounded-xl border-slate-200 text-sm">
                                <option value="">Select Chapter</option>
                                <template x-for="c in chapters" :key="c.id">
                                    <option :value="c.id" x-text="c.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Topic *</label>
                            <select name="topic_id" x-model="topicId" required
                                    @change="syncTopicName()"
                                    class="w-full rounded-xl border-slate-200 text-sm">
                                <option value="">Select Topic</option>
                                <template x-for="t in topics" :key="t.id">
                                    <option :value="t.id" x-text="t.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-slate-700 mb-2">Today's Topic Covered</p>
                        <div class="rounded-xl border border-brand-green-100 bg-brand-green-50/60 px-4 py-3 text-sm font-semibold text-slate-800" x-text="topicName || 'Select a topic above'"></div>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-slate-700 mb-2">Actual Teaching Status *</p>
                        <div class="flex flex-wrap gap-4">
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="radio" name="status" value="completed" x-model="status" class="text-brand-green" required>
                                <span>Completed</span>
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="radio" name="status" value="partial" x-model="status" class="text-brand-green">
                                <span>Partially Completed</span>
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="radio" name="status" value="remaining" x-model="status" class="text-brand-green">
                                <span>Not Completed</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Teacher Remark (optional)</label>
                        <textarea name="notes" rows="3" class="w-full rounded-xl border-slate-200 text-sm" placeholder="Any remark for today…">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Optional learning update --}}
            <div class="admin-card overflow-hidden">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <h3 class="font-bold text-slate-900">🎯 Optional Learning Update</h3>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Concept Covered</label>
                        <input type="text" name="concept_covered" value="{{ old('concept_covered') }}" class="w-full rounded-xl border-slate-200 text-sm" placeholder="Key concept taught today">
                    </div>
                    <div class="grid sm:grid-cols-3 gap-4 text-sm">
                        <div>
                            <p class="font-medium text-slate-700 mb-2">Homework Given</p>
                            <label class="mr-3"><input type="radio" name="homework_given" value="1" @checked(old('homework_given') == '1') class="text-brand-green"> Yes</label>
                            <label><input type="radio" name="homework_given" value="0" @checked(old('homework_given', '0') == '0') class="text-brand-green"> No</label>
                        </div>
                        <div>
                            <p class="font-medium text-slate-700 mb-2">Study Material Shared</p>
                            <label class="mr-3"><input type="radio" name="material_shared" value="1" @checked(old('material_shared') == '1') class="text-brand-green"> Yes</label>
                            <label><input type="radio" name="material_shared" value="0" @checked(old('material_shared', '0') == '0') class="text-brand-green"> No</label>
                        </div>
                        <div>
                            <p class="font-medium text-slate-700 mb-2">Self-Test Given</p>
                            <label class="mr-3"><input type="radio" name="self_test_given" value="1" @checked(old('self_test_given') == '1') class="text-brand-green"> Yes</label>
                            <label><input type="radio" name="self_test_given" value="0" @checked(old('self_test_given', '0') == '0') class="text-brand-green"> No</label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Auto progress --}}
            <div class="admin-card overflow-hidden">
                <div class="admin-card-top"></div>
                <div class="admin-card-header flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900">📊 Auto-Generated Syllabus Status</h3>
                        <p class="text-xs text-slate-500 mt-1">Updates when chapter/subject is selected</p>
                    </div>
                    <button type="button" @click="refreshProgress()" class="text-xs font-semibold text-brand-green hover:underline">Refresh</button>
                </div>
                <div class="p-5 grid sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-500">Chapter Progress</p>
                        <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="(progress.chapter_percent || 0) + '%'"></p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-500">Subject Progress</p>
                        <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="(progress.subject_percent || 0) + '%'"></p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-500">Class Syllabus Progress</p>
                        <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="(progress.class_percent || 0) + '%'"></p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-500">Total Chapters</p>
                        <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="progress.total_chapters || 0"></p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-500">Completed Chapters</p>
                        <p class="mt-1 text-xl font-extrabold text-emerald-700" x-text="progress.completed_chapters || 0"></p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-500">Pending Chapters</p>
                        <p class="mt-1 text-xl font-extrabold text-amber-700" x-text="progress.pending_chapters || 0"></p>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="admin-card p-5">
                <div class="flex flex-wrap gap-2">
                    <button type="submit" @click="setAction('save')" class="rounded-xl bg-brand-green px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-green-dark">
                        Save Today's Update
                    </button>
                    <button type="submit" @click="setAction('save_next')" class="rounded-xl border border-brand-green text-brand-green bg-white px-4 py-2.5 text-sm font-semibold hover:bg-brand-green-50">
                        Save & Next
                    </button>
                    <button type="submit" @click="setAction('mark_completed')" class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 hover:bg-emerald-100">
                        Mark as Completed
                    </button>
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    Teacher enters only: Teaching Status · Remark (optional) · Homework / Material / Self-Test.
                    Name, date, day and progress are auto.
                </p>
            </div>
        </form>
    </div>
</x-teacher-layout>
