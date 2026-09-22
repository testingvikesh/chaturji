<div class="admin-form-card mt-2">
    <div class="admin-card-top" style="background: linear-gradient(90deg, #f59e0b, #d97706);"></div>
    <form method="POST"
          action="{{ route($routePrefix.'.store') }}"
          enctype="multipart/form-data"
          class="admin-card-body"
          x-data="{
              sending: false,
              chapterFiles: [],
              medium: @js(old('medium', $initialMedium ?: '')),
              standardId: @js((string) old('standard_id', '')),
              subjectId: @js((string) old('subject_id', '')),
              chapterName: @js(old('chapter_name', '')),
              chapterNo: @js(old('chapter_no', '')),
              subjects: [],
              loadingSubjects: false,
              subjectsUrl: @js($subjectsUrl),
              standards: @js($standards->map(fn ($s) => ['id' => (string) $s->id, 'name' => $s->name])->values()),
              mediums: @js($mediums),
              onPick(event) {
                  const picked = Array.from(event.target.files || []);
                  this.chapterFiles = picked.slice(0, 5).map(file => ({ name: file.name, size: file.size }));
                  if (picked.length > 5) {
                      alert('You can upload up to 5 files. Extra files were ignored.');
                      const dt = new DataTransfer();
                      picked.slice(0, 5).forEach(file => dt.items.add(file));
                      event.target.files = dt.files;
                  }
              },
              clearFiles() {
                  this.chapterFiles = [];
                  this.$refs.chapterFileInput.value = '';
              },
              prettySize(bytes) {
                  if (bytes < 1024) return bytes + ' B';
                  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                  return (bytes / 1048576).toFixed(1) + ' MB';
              },
              async loadSubjects() {
                  this.subjects = [];
                  this.subjectId = '';
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
              init() {
                  if (this.medium && this.standardId) {
                      const keepSubject = this.subjectId;
                      this.loadSubjects().then(() => { this.subjectId = keepSubject; });
                  }
              }
          }"
          @submit="if (sending) { $event.preventDefault(); return; } sending = true">
        @csrf
        <input type="hidden" name="ticket_type" value="missing">
        <input type="hidden" name="medium" :value="medium">
        <input type="hidden" name="standard_id" :value="standardId">
        <input type="hidden" name="subject_id" :value="subjectId">

        <div class="mb-1">
            <h3 class="text-base font-bold text-amber-900">Missing File Upload</h3>
            <p class="text-xs text-amber-800/80 mt-0.5">Select medium, standard and subject, then enter chapter details and upload files.</p>
        </div>

        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="admin-label">Medium</label>
                <select class="admin-select" x-model="medium" @change="loadSubjects()" required>
                    <option value="">Choose medium</option>
                    <template x-for="(label, value) in mediums" :key="value">
                        <option :value="value" x-text="label"></option>
                    </template>
                </select>
                @error('medium')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="admin-label">Standard</label>
                <select class="admin-select" x-model="standardId" :disabled="!medium" @change="loadSubjects()" required>
                    <option value="" x-text="medium ? 'Choose standard' : 'Select medium first'"></option>
                    <template x-for="std in standards" :key="std.id">
                        <option :value="std.id" x-text="std.name"></option>
                    </template>
                </select>
                @error('standard_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="admin-label">Subject</label>
            <select class="admin-select" x-model="subjectId" :disabled="!medium || !standardId || loadingSubjects" required>
                <option value="" x-text="loadingSubjects ? 'Loading…' : (!medium || !standardId ? 'Select medium & standard first' : 'Choose subject')"></option>
                <template x-for="item in subjects" :key="item.id">
                    <option :value="String(item.id)" x-text="item.name"></option>
                </template>
            </select>
            @error('subject_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="admin-label">Chapter</label>
                <input type="text" name="chapter_name" x-model="chapterName" required maxlength="255" class="admin-input" placeholder="Enter chapter name">
                @error('chapter_name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="admin-label">Chapter No</label>
                <input type="text" name="chapter_no" x-model="chapterNo" required maxlength="64" class="admin-input" placeholder="Enter chapter no (e.g. 1, 2, 3)">
                @error('chapter_no')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="admin-label">Note <span class="font-normal text-slate-400">(optional)</span></label>
            <textarea name="message" rows="3" maxlength="4000" class="admin-input" placeholder="Any extra note for admin">{{ old('message') }}</textarea>
            @error('message')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="admin-label">Upload files</label>
            <p class="text-xs text-slate-500 mb-2">Required · up to 5 files · max 10 MB each</p>
            <input type="file"
                   name="chapter_files[]"
                   x-ref="chapterFileInput"
                   multiple
                   required
                   accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.txt,.zip,image/jpeg,image/png,image/webp,application/pdf"
                   class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-amber-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-amber-700"
                   @change="onPick($event)">
            @error('chapter_files')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            @error('chapter_files.*')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            <div x-show="chapterFiles.length" x-cloak class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold text-amber-900">Selected · <span x-text="chapterFiles.length"></span></p>
                    <button type="button" class="text-xs font-semibold text-red-600 hover:underline" @click="clearFiles()">Clear all</button>
                </div>
                <ul class="space-y-1.5">
                    <template x-for="(file, index) in chapterFiles" :key="'miss-'+index">
                        <li class="flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2 text-xs text-slate-700 border border-amber-100">
                            <span class="min-w-0 truncate font-semibold" x-text="file.name"></span>
                            <span class="shrink-0 text-slate-400" x-text="prettySize(file.size)"></span>
                        </li>
                    </template>
                </ul>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="admin-btn-primary" :disabled="sending" :class="{ 'opacity-60 cursor-not-allowed': sending }">
                <span x-text="sending ? 'Submitting…' : 'Submit Missing File'">Submit Missing File</span>
            </button>
            <button type="button" class="admin-btn-ghost" @click="$dispatch('ticket-mode', null)" :disabled="sending">Back</button>
        </div>
    </form>
</div>
