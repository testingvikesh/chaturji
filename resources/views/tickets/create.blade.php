<x-dynamic-component :component="$layout">
    <x-slot name="header">
        <div>
            <span class="admin-section-label">{{ $label }}</span>
            <h2 class="admin-page-title">Generate Ticket</h2>
            <p class="admin-page-subtitle">Write your issue and attach screenshots or files if needed. Admin will see it in the ticket report.</p>
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
                      onPick(event) {
                          const picked = Array.from(event.target.files || []);
                          this.files = picked.slice(0, 5).map(file => ({
                              name: file.name,
                              size: file.size,
                              type: file.type || ''
                          }));
                          if (picked.length > 5) {
                              alert('You can upload up to 5 files. Extra files were ignored.');
                              const dt = new DataTransfer();
                              picked.slice(0, 5).forEach(file => dt.items.add(file));
                              event.target.files = dt.files;
                          }
                      },
                      clearFiles() {
                          this.files = [];
                          this.$refs.fileInput.value = '';
                      },
                      prettySize(bytes) {
                          if (bytes < 1024) return bytes + ' B';
                          if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                          return (bytes / 1048576).toFixed(1) + ' MB';
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
                    <p class="text-xs text-slate-500 mb-2">Upload up to 5 files · max 5 MB each · JPG, PNG, WEBP, PDF, DOC, DOCX, TXT, ZIP</p>
                    <input type="file"
                           name="attachments[]"
                           x-ref="fileInput"
                           multiple
                           accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.txt,.zip,image/jpeg,image/png,image/webp,application/pdf"
                           class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-green file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-green-dark"
                           @change="onPick($event)">
                    @error('attachments')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    @error('attachments.*')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror

                    <div x-show="files.length" x-cloak class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold text-slate-600">
                                Selected · <span x-text="files.length"></span>
                            </p>
                            <button type="button" class="text-xs font-semibold text-red-600 hover:underline" @click="clearFiles()">Clear all</button>
                        </div>
                        <ul class="space-y-1.5">
                            <template x-for="(file, index) in files" :key="index">
                                <li class="flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2 text-xs text-slate-700 border border-slate-100">
                                    <span class="min-w-0 truncate font-semibold" x-text="file.name"></span>
                                    <span class="shrink-0 text-slate-400" x-text="prettySize(file.size)"></span>
                                </li>
                            </template>
                        </ul>
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
