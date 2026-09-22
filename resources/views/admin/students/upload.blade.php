<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header
            title="Upload Students"
            subtitle="Upload by Medium · Standard — students can login and use all features">
            <x-slot name="actions">
                <a href="{{ route('admin.students.upload.template') }}" class="admin-btn-secondary">Download CSV template</a>
                <a href="{{ route('admin.students.index') }}" class="admin-btn-primary">All students</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page max-w-4xl space-y-6"
         x-data="{
            medium: '{{ old('medium', 'gujarati') }}',
            standard: '{{ old('standard', '') }}',
            standards: @js($standards->map(fn ($s) => ['slug' => $s->slug, 'name' => $s->name, 'medium' => \App\Models\Material::normalizeMedium($s->medium) ?: $s->medium])),
            syncStandards() {
                const sel = this.$refs.standardSelect;
                if (!sel) return;
                const keep = this.standard;
                while (sel.options.length > 1) sel.remove(1);
                this.standards.forEach(s => {
                    const o = document.createElement('option');
                    o.value = s.slug;
                    o.textContent = s.name;
                    sel.appendChild(o);
                });
                if (keep && [...sel.options].some(o => o.value === keep)) {
                    sel.value = keep;
                    this.standard = keep;
                } else {
                    sel.value = '';
                    this.standard = '';
                }
            }
         }"
         x-init="syncStandards()">
        @include('admin.partials.alert')

        @if (session('upload_errors'))
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-semibold mb-2">Skipped rows</p>
                <ul class="list-disc pl-5 space-y-1 max-h-48 overflow-y-auto">
                    @foreach (session('upload_errors') as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            <p class="font-semibold text-slate-800">How it works</p>
            <ol class="mt-2 list-decimal pl-5 space-y-1">
                <li>Select <strong>Medium</strong> and <strong>Standard</strong> (applied to every student in the file).</li>
                <li>Upload CSV with columns: <code class="text-xs bg-white px-1 rounded border">name, mobile, email, password</code></li>
                <li>Empty password → uses default password below.</li>
                <li>Approved students can login immediately with <strong>mobile + password</strong> and use exams, homework, books, etc.</li>
            </ol>
        </div>

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-header"><h3 class="font-bold text-slate-900">CSV upload</h3></div>
            <div class="admin-card-body">
                <form method="POST" action="{{ route('admin.students.upload.store') }}" enctype="multipart/form-data" class="grid sm:grid-cols-2 gap-4">
                    @csrf
                    <div>
                        <label class="admin-label">Medium *</label>
                        <select name="medium" x-model="medium" @change="syncStandards()" required class="admin-select">
                            @foreach ($mediums as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('medium')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Standard *</label>
                        <select name="standard" x-ref="standardSelect" x-model="standard" required class="admin-select">
                            <option value="">Select standard</option>
                        </select>
                        @error('standard')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Default password *</label>
                        <input type="text" name="default_password" value="{{ old('default_password', $defaultPassword) }}" required minlength="8" class="admin-input">
                        <p class="text-xs text-slate-500 mt-1">Used when CSV password cell is empty.</p>
                        @error('default_password')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">CSV file *</label>
                        <input type="file" name="file" accept=".csv,.txt,text/csv" required class="admin-input">
                        @error('file')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2 space-y-2">
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="hidden" name="approve" value="0">
                            <input type="checkbox" name="approve" value="1" class="rounded border-slate-300 text-brand-green" @checked(old('approve', true))>
                            Approve now (students can login &amp; use all features)
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="hidden" name="send_mail" value="0">
                            <input type="checkbox" name="send_mail" value="1" class="rounded border-slate-300 text-brand-green" @checked(old('send_mail', true))>
                            Send mail — app login link + username (mobile) + password
                        </label>
                        <p class="text-xs text-slate-500">When Send mail is on, each CSV row must include an email address.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="admin-btn-primary">Upload student list</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-header"><h3 class="font-bold text-slate-900">Add one student</h3></div>
            <div class="admin-card-body">
                <form method="POST" action="{{ route('admin.students.store-one') }}" class="grid sm:grid-cols-2 gap-4">
                    @csrf
                    <div>
                        <label class="admin-label">Name *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="admin-input">
                        @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Mobile *</label>
                        <input type="text" name="mobile" value="{{ old('mobile') }}" required class="admin-input">
                        @error('mobile')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="admin-input">
                        @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Password *</label>
                        <input type="text" name="password" value="{{ old('password', $defaultPassword) }}" required minlength="8" class="admin-input">
                        @error('password')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Medium *</label>
                        <select name="medium" required class="admin-select">
                            @foreach ($mediums as $key => $label)
                                <option value="{{ $key }}" @selected(old('medium', 'gujarati') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label">Standard *</label>
                        <select name="standard" required class="admin-select">
                            <option value="">Select standard</option>
                            @foreach ($standards as $s)
                                <option value="{{ $s->slug }}" @selected(old('standard') === $s->slug)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        @error('standard')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2 space-y-2">
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="hidden" name="approve" value="0">
                            <input type="checkbox" name="approve" value="1" class="rounded border-slate-300 text-brand-green" checked>
                            Approve now (can login immediately)
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="hidden" name="send_mail" value="0">
                            <input type="checkbox" name="send_mail" value="1" class="rounded border-slate-300 text-brand-green" checked>
                            Send mail — app login link + username + password
                        </label>
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="admin-btn-secondary">Create student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
