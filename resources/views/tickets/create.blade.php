<x-dynamic-component :component="$layout">
    <x-slot name="header">
        <div>
            <span class="admin-section-label">{{ $label }}</span>
            <h2 class="admin-page-title">Generate Ticket</h2>
            <p class="admin-page-subtitle">Describe your issue, or use Missing Chapter Upload to send standard, subject, chapter and files.</p>
        </div>
    </x-slot>

    <div class="admin-page">
        @include('admin.partials.alert')

        <div class="mb-4">
            <a href="{{ route($routePrefix.'.index') }}" class="text-sm text-brand-green hover:underline font-medium">&larr; Back to Tickets</a>
        </div>

        <div class="admin-form-card">
            <div class="admin-card-top"></div>
            <form method="POST"
                  action="{{ route($routePrefix.'.store') }}"
                  enctype="multipart/form-data"
                  class="admin-card-body"
                  x-data="{
                      sending: false,
                      files: [],
                      chapterFiles: [],
                      medium: @js(old('medium', $initialMedium ?: '')),
                      standardId: @js((string) old('standard_id', '')),
                      subjectId: @js((string) old('subject_id', '')),
                      chapterId: @js((string) old('chapter_id', '')),
                      chapterName: @js(old('chapter_name', '')),
                      subjects: [],
                      chapters: [],
                      loadingSubjects: false,
                      loadingChapters: false,
                      subjectsUrl: @js($subjectsUrl),
                      chaptersUrl: @js($chaptersUrl),
                      standards: @js($standards->map(fn ($s) => ['id' => (string) $s->id, 'name' => $s->name])->values()),
                      mediums: @js($mediums),
                      onPick(event, target) {
                          const picked = Array.from(event.target.files || []);
                          const limited = picked.slice(0, 5).map(file => ({
                              name: file.name,
                              size: file.size,
                              type: file.type || ''
                          }));
                          if (target === 'chapter') {
                              this.chapterFiles = limited;
                          } else {
                              this.files = limited;
                          }
                          if (picked.length > 5) {
                              alert('You can upload up to 5 files. Extra files were ignored.');
                              const dt = new DataTransfer();
                              picked.slice(0, 5).forEach(file => dt.items.add(file));
                              event.target.files = dt.files;
                          }
                      },
                      clearFiles(target) {
                          if (target === 'chapter') {
                              this.chapterFiles = [];
                              this.$refs.chapterFileInput.value = '';
                          } else {
                              this.files = [];
                              this.$refs.fileInput.value = '';
                          }
                      },
                      prettySize(bytes) {
                          if (bytes < 1024) return bytes + ' B';
                          if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                          return (bytes / 1048576).toFixed(1) + ' MB';
                      },
                      async loadSubjects() {
                          this.subjects = [];
                          this.chapters = [];
                          this.subjectId = '';
                          this.chapterId = '';
                          this.chapterName = '';
                          if (!this.medium || !this.standardId) return;
                          this.loadingSubjects = true;
                          try {
                              const url = this.subjectsUrl + '?medium=' + encodeURIComponent(this.medium) + '&standard_id=' + encodeURIComponent(this.standardId);
                              const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                              this.subjects = res.ok ? await res.json() : [];
                          } catch (e) {
                              this.subjects = [];
                          } finally {
                              this.loadingSubjects = false;
                          }
                      },
                      async loadChapters() {
                          this.chapters = [];
                          this.chapterId = '';
                          this.chapterName = '';
                          if (!this.medium || !this.subjectId) return;
                          this.loadingChapters = true;
                          try {
                              const url = this.chaptersUrl + '?medium=' + encodeURIComponent(this.medium) + '&subject_id=' + encodeURIComponent(this.subjectId);
                              const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                              this.chapters = res.ok ? await res.json() : [];
                          } catch (e) {
                              this.chapters = [];
                          } finally {
                              this.loadingChapters = false;
                          }
                      },
                      chooseChapter(id) {
                          this.chapterId = String(id || '');
                          const found = this.chapters.find(c => String(c.id) === String(id));
                          this.chapterName = found ? found.name : this.chapterName;
                      },
                      init() {
                          if (this.medium && this.standardId) {
                              this.loadSubjects().then(() => {
                                  if (this.subjectId) {
                                      const keepSubject = this.subjectId;
                                      const keepChapter = this.chapterId;
                                      const keepName = this.chapterName;
                                      this.subjectId = keepSubject;
                                      return this.loadChapters().then(() => {
                                          this.chapterId = keepChapter;
                                          if (keepName) this.chapterName = keepName;
                                      });
                                  }
                              });
                          }
                      }
                  }"
                  @submit="if (sending) { $event.preventDefault(); return; } sending = true">
                @csrf

                <div>
                    <label class="admin-label">Category</label>
                    <select name="category" required class="admin-select">
                        @foreach ($categories as $key => $name)
                            <option value="{{ $key }}" @selected(old('category', 'other') === $key)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('category')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-label">Subject</label>
                    <input type="text" name="subject" value="{{ old('subject') }}" required maxlength="160" class="admin-input" placeholder="Short title of your issue">
                    @error('subject')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-label">Message</label>
                    <textarea name="message" rows="6" required maxlength="4000" class="admin-input" placeholder="Describe the problem">{{ old('message') }}</textarea>
                    @error('message')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-label">Attachments <span class="font-normal text-slate-400">(optional)</span></label>
                    <p class="text-xs text-slate-500 mb-2">General screenshots/files · up to 5 · max 10 MB each</p>
                    <input type="file"
                           name="attachments[]"
                           x-ref="fileInput"
                           multiple
                           accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.txt,.zip,image/jpeg,image/png,image/webp,application/pdf"
                           class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-green file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-green-dark"
                           @change="onPick($event, 'general')">
                    @error('attachments')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    @error('attachments.*')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    <div x-show="files.length" x-cloak class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold text-slate-600">Selected · <span x-text="files.length"></span></p>
                            <button type="button" class="text-xs font-semibold text-red-600 hover:underline" @click="clearFiles('general')">Clear all</button>
                        </div>
                        <ul class="space-y-1.5">
                            <template x-for="(file, index) in files" :key="'g-'+index">
                                <li class="flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2 text-xs text-slate-700 border border-slate-100">
                                    <span class="min-w-0 truncate font-semibold" x-text="file.name"></span>
                                    <span class="shrink-0 text-slate-400" x-text="prettySize(file.size)"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4 space-y-4">
                    <div>
                        <h3 class="text-sm font-bold text-amber-900">Missing Chapter Upload</h3>
                        <p class="text-xs text-amber-800/80 mt-0.5">Select medium, standard, subject and chapter, then upload the missing chapter files. Leave blank if not needed.</p>
                    </div>

                    <input type="hidden" name="medium" :value="medium">
                    <input type="hidden" name="standard_id" :value="standardId">
                    <input type="hidden" name="subject_id" :value="subjectId">
                    <input type="hidden" name="chapter_id" :value="chapterId">

                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <label class="admin-label">Medium</label>
                            <select class="admin-select" x-model="medium" @change="loadSubjects()">
                                <option value="">Choose medium</option>
                                <template x-for="(label, value) in mediums" :key="value">
                                    <option :value="value" x-text="label"></option>
                                </template>
                            </select>
                            @error('medium')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="admin-label">Standard</label>
                            <select class="admin-select" x-model="standardId" :disabled="!medium" @change="loadSubjects()">
                                <option value="" x-text="medium ? 'Choose standard' : 'Select medium first'"></option>
                                <template x-for="std in standards" :key="std.id">
                                    <option :value="std.id" x-text="std.name"></option>
                                </template>
                            </select>
                            @error('standard_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="admin-label">Subject</label>
                            <select class="admin-select" x-model="subjectId" :disabled="!medium || !standardId || loadingSubjects" @change="loadChapters()">
                                <option value="" x-text="loadingSubjects ? 'Loading…' : (!medium || !standardId ? 'Select medium & standard first' : 'Choose subject')"></option>
                                <template x-for="item in subjects" :key="item.id">
                                    <option :value="String(item.id)" x-text="item.name"></option>
                                </template>
                            </select>
                            @error('subject_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="admin-label">Chapter</label>
                            <select class="admin-select" x-model="chapterId" :disabled="!subjectId || loadingChapters" @change="chooseChapter(chapterId)">
                                <option value="" x-text="loadingChapters ? 'Loading…' : (!subjectId ? 'Select subject first' : 'Choose chapter (or type below)')"></option>
                                <template x-for="item in chapters" :key="item.id">
                                    <option :value="String(item.id)" x-text="item.name"></option>
                                </template>
                            </select>
                            @error('chapter_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="admin-label">Chapter name</label>
                        <input type="text" name="chapter_name" x-model="chapterName" maxlength="255" class="admin-input" placeholder="Chapter name (required for missing chapter upload)">
                        @error('chapter_name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="admin-label">Chapter files</label>
                        <p class="text-xs text-slate-500 mb-2">Upload missing chapter PDF/images · up to 5 · max 10 MB each</p>
                        <input type="file"
                               name="chapter_files[]"
                               x-ref="chapterFileInput"
                               multiple
                               accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.txt,.zip,image/jpeg,image/png,image/webp,application/pdf"
                               class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-amber-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-amber-700"
                               @change="onPick($event, 'chapter')">
                        @error('chapter_files')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        @error('chapter_files.*')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        <div x-show="chapterFiles.length" x-cloak class="mt-3 rounded-xl border border-amber-200 bg-white p-3">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-amber-900">Selected · <span x-text="chapterFiles.length"></span></p>
                                <button type="button" class="text-xs font-semibold text-red-600 hover:underline" @click="clearFiles('chapter')">Clear all</button>
                            </div>
                            <ul class="space-y-1.5">
                                <template x-for="(file, index) in chapterFiles" :key="'c-'+index">
                                    <li class="flex items-center justify-between gap-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-slate-700 border border-amber-100">
                                        <span class="min-w-0 truncate font-semibold" x-text="file.name"></span>
                                        <span class="shrink-0 text-slate-400" x-text="prettySize(file.size)"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="admin-btn-primary"
                            :disabled="sending"
                            :class="{ 'opacity-60 cursor-not-allowed': sending }">
                        <span x-text="sending ? 'Submitting…' : 'Generate Ticket'">Generate Ticket</span>
                    </button>
                    <a href="{{ route($routePrefix.'.index') }}" class="admin-btn-ghost" @click="if (sending) $event.preventDefault()">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-dynamic-component>
