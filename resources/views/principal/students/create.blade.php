<x-principal-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Students</span>
            <h2 class="admin-page-title">Add Student</h2>
            <p class="admin-page-subtitle">Create a student in one of your allotted standards</p>
        </div>
    </x-slot>

    <div class="admin-page">
        @if (session('error'))
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <div class="mb-4">
            <a href="{{ route('principal.reports.allotted-students') }}" class="text-sm text-brand-green hover:underline font-medium">&larr; Back to Students</a>
        </div>

        <div class="admin-form-card">
            <div class="admin-card-top"></div>
            <form method="POST" action="{{ route('principal.students.store') }}" class="admin-card-body grid sm:grid-cols-2 gap-4">
                @csrf

                <div>
                    <label class="admin-label">Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="admin-input">
                    @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="admin-label">Mobile *</label>
                    <input type="text" name="mobile" value="{{ old('mobile') }}" required class="admin-input" placeholder="Login username">
                    @error('mobile')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="admin-label">Email (optional)</label>
                    <input type="text" name="email" value="{{ old('email') }}" class="admin-input" placeholder="Needed only to send login mail">
                    @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="admin-label">Password *</label>
                    <input type="text" name="password" value="{{ old('password', $defaultPassword) }}" required minlength="8" class="admin-input">
                    @error('password')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="admin-label">Standard *</label>
                    <select name="standard" required class="admin-select">
                        <option value="">Select standard</option>
                        @foreach ($standards as $standard)
                            <option value="{{ $standard->slug }}" @selected(old('standard') === $standard->slug)>{{ $standard->name }}</option>
                        @endforeach
                    </select>
                    @error('standard')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="admin-label">Medium *</label>
                    <select name="medium" required class="admin-select">
                        @foreach ($mediums as $key => $label)
                            <option value="{{ $key }}" @selected(old('medium', 'gujarati') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('medium')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2 space-y-2">
                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="hidden" name="approve" value="0">
                        <input type="checkbox" name="approve" value="1" class="rounded border-slate-300 text-brand-green" checked>
                        Approve now (can login immediately)
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="hidden" name="send_mail" value="0">
                        <input type="checkbox" name="send_mail" value="1" class="rounded border-slate-300 text-brand-green">
                        Send login mail (requires email)
                    </label>
                    <p class="text-xs text-slate-500">Login is always mobile + password. Standard must be one allotted to you.</p>
                </div>

                <div class="sm:col-span-2 flex items-center gap-3 pt-2">
                    <button type="submit" class="admin-btn-primary">Create Student</button>
                    <a href="{{ route('principal.reports.allotted-students') }}" class="admin-btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-principal-layout>
