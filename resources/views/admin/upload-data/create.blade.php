<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Curriculum</span>
            <h2 class="admin-page-title">Upload Chapter Data</h2>
            <p class="admin-page-subtitle">Select standard → subject → chapter and upload material JSON file</p>
        </div>
    </x-slot>

    @include('admin.partials.alert')

    <div class="mb-4">
        <a href="{{ route('admin.upload-data.index') }}" class="text-sm text-brand-green hover:underline">&larr; Back to Upload List</a>
    </div>

    <form method="POST" action="{{ route('admin.upload-data.store') }}" enctype="multipart/form-data"
          x-data="uploadDataForm()"
          class="admin-card max-w-3xl">
        <div class="admin-card-top"></div>
        <div class="admin-card-header">
            <h3 class="font-bold text-slate-900">Upload Form</h3>
        </div>
        <div class="p-6 space-y-5">
            @csrf

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Standard <span class="text-red-500">*</span></label>
                    <select name="standard_id" x-model="standardId" @change="loadSubjects()" required class="admin-select">
                        <option value="">Select Standard</option>
                        @foreach ($standards as $standard)
                            <option value="{{ $standard->id }}" @selected(old('standard_id') == $standard->id)>{{ $standard->name }}</option>
                        @endforeach
                    </select>
                    @error('standard_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Subject <span class="text-red-500">*</span></label>
                    <select name="subject_id" x-model="subjectId" @change="loadChapters()" required class="admin-select" :disabled="!subjects.length">
                        <option value="">Select Subject</option>
                        <template x-for="subject in subjects" :key="subject.id">
                            <option :value="subject.id" x-text="subject.name"></option>
                        </template>
                    </select>
                    @error('subject_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Chapter <span class="text-red-500">*</span></label>
                <div class="flex flex-wrap gap-4 mb-3">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="radio" name="chapter_mode" value="existing" x-model="chapterMode" class="text-brand-green focus:ring-brand-green">
                        Existing Chapter
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="radio" name="chapter_mode" value="new" x-model="chapterMode" class="text-brand-green focus:ring-brand-green">
                        Create New Chapter
                    </label>
                </div>

                <div x-show="chapterMode === 'existing'" x-cloak>
                    <select name="chapter_id" x-model="chapterId" @change="onChapterChange()" class="admin-select" :disabled="!chapters.length" :required="chapterMode === 'existing'">
                        <option value="">Select Chapter</option>
                        <template x-for="chapter in chapters" :key="chapter.id">
                            <option :value="chapter.id" x-text="chapter.name + (chapter.content_exists ? ' (has data — will replace)' : '')"></option>
                        </template>
                    </select>
                    @error('chapter_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror

                    <div x-show="selectedChapterTitle" x-cloak class="mt-3 rounded-xl border border-brand-green-100 bg-brand-green-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-brand-green">Selected Chapter Title</p>
                        <p class="mt-1 text-base font-bold text-slate-900" x-text="selectedChapterTitle"></p>
                    </div>
                </div>

                <div x-show="chapterMode === 'new'" x-cloak>
                    <input type="text" name="chapter_name" x-model="chapterName" @input="onChapterNameInput()" placeholder="e.g. અમારી કામધેનુ" class="admin-input" :required="chapterMode === 'new'">
                    @error('chapter_name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Topic Name <span class="text-red-500">*</span></label>
                <input type="text" name="topic_name" x-model="topicName" required
                       placeholder="Topic name for this chapter"
                       class="admin-input">
                <p class="text-xs text-slate-500 mt-2">Auto-filled from chapter title or JSON file. This topic will be added under the selected chapter.</p>
                @error('topic_name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Content Language <span class="text-red-500">*</span></label>
                <select name="language" required class="admin-select">
                    <option value="">Select Language</option>
                    @foreach ($languages as $value => $label)
                        <option value="{{ $value }}" @selected(old('language', 'gujarati') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500 mt-2">Used when JSON meta.language is missing. JSON meta (gu/hi/en) overrides this.</p>
                @error('language')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Material JSON File <span class="text-red-500">*</span></label>
                <input type="file" name="data_file" accept=".json,application/json" required @change="onJsonFileChange($event)"
                       class="block w-full text-sm text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-brand-green-50 file:text-brand-green file:font-semibold hover:file:bg-brand-green-100">
                <p class="text-xs text-slate-500 mt-2">Upload <code>*-material-ai.json</code> file generated from material pipeline. Max 10MB.</p>
                @error('data_file')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Original PDF File <span class="text-slate-400 font-normal">(optional)</span></label>
                <input type="file" name="original_pdf" accept="application/pdf,.pdf"
                       class="block w-full text-sm text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-amber-50 file:text-amber-800 file:font-semibold hover:file:bg-amber-100">
                <p class="text-xs text-slate-500 mt-2">Students can open this PDF with a <strong>Practice PDF</strong> button (modal). Max 50MB.</p>
                @error('original_pdf')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="rounded-xl bg-brand-green-50 border border-brand-green-100 p-4 text-sm text-slate-600">
                <p class="font-semibold text-brand-green mb-2">What gets saved:</p>
                <ul class="list-disc list-inside space-y-1 text-xs">
                    <li>Topic name saved under chapter → <strong>topics</strong> table</li>
                    <li>Introduction, trailer, what I like → <strong>chapter_content_sections</strong></li>
                    <li>ગુણ, કળા, સંસ્કાર → sections table (gun / kala / sankar)</li>
                    <li>One word, knowledge ladder, MCQ, fill blank, T/F, match, short/long answers → <strong>chapter_questions</strong></li>
                    <li>Textbook exercises & chapter assessment metadata → sections</li>
                    <li>Original JSON stored securely for reference</li>
                    <li>Original PDF (if uploaded) shown to students for practice</li>
                </ul>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="admin-btn-primary">Upload & Import JSON</button>
                <a href="{{ route('admin.upload-data.index') }}" class="admin-btn-secondary">Cancel</a>
            </div>
        </div>
    </form>

    <script>
        function uploadDataForm() {
            return {
                standardId: @json(old('standard_id', '')),
                subjectId: @json(old('subject_id', '')),
                chapterId: @json(old('chapter_id', '')),
                chapterMode: @json(old('chapter_mode', 'existing')),
                chapterName: @json(old('chapter_name', '')),
                topicName: @json(old('topic_name', '')),
                subjects: [],
                chapters: [],
                get selectedChapterTitle() {
                    if (this.chapterMode === 'new') {
                        return this.chapterName || '';
                    }
                    const chapter = this.chapters.find((item) => String(item.id) === String(this.chapterId));
                    return chapter ? chapter.name : '';
                },
                async init() {
                    if (this.standardId) await this.loadSubjects(false);
                    if (this.subjectId) await this.loadChapters(false);
                    if (!this.topicName) {
                        this.syncTopicName();
                    }
                },
                onChapterChange() {
                    this.syncTopicName();
                },
                onChapterNameInput() {
                    if (this.chapterMode === 'new') {
                        this.syncTopicName();
                    }
                },
                syncTopicName() {
                    const title = this.selectedChapterTitle;
                    if (title) {
                        this.topicName = title;
                    }
                },
                onJsonFileChange(event) {
                    const file = event.target.files?.[0];
                    if (!file) return;

                    const reader = new FileReader();
                    reader.onload = () => {
                        try {
                            const json = JSON.parse(reader.result);
                            const title = json.meta?.title
                                || json.sections?.[0]?.title_gu
                                || json.sections?.[0]?.title;

                            if (title) {
                                this.topicName = title;
                            }
                        } catch (error) {
                            // Ignore invalid JSON preview; server validation will handle it.
                        }
                    };
                    reader.readAsText(file);
                },
                async loadSubjects(reset = true) {
                    if (reset) {
                        this.subjectId = '';
                        this.chapterId = '';
                        this.subjects = [];
                        this.chapters = [];
                    }
                    if (!this.standardId) return;
                    const res = await fetch(`{{ route('admin.upload-data.subjects') }}?standard_id=${this.standardId}`);
                    this.subjects = await res.json();
                },
                async loadChapters(reset = true) {
                    if (reset) {
                        this.chapterId = '';
                        this.chapters = [];
                    }
                    if (!this.subjectId) return;
                    const res = await fetch(`{{ route('admin.upload-data.chapters') }}?subject_id=${this.subjectId}`);
                    this.chapters = await res.json();
                }
            }
        }
    </script>
</x-app-layout>
