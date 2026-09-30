<x-principal-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Students</span>
            <h2 class="admin-page-title">Edit: {{ $student->name }}</h2>
            <p class="admin-page-subtitle">Update student name and account details</p>
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
            <form method="POST" action="{{ route('principal.students.update', $student) }}" class="admin-card-body space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="admin-label">Name *</label>
                    <input type="text" name="name" value="{{ old('name', $student->name) }}" required class="admin-input">
                    @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">Mobile *</label>
                        <input type="text" name="mobile" value="{{ old('mobile', $student->mobile) }}" required class="admin-input">
                        @error('mobile')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Email (optional)</label>
                        <input type="text" name="email" value="{{ old('email', $student->email) }}" class="admin-input" placeholder="Optional — login uses mobile">
                        @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">Standard *</label>
                        <select name="standard" required class="admin-select">
                            @foreach ($standards as $standard)
                                <option value="{{ $standard->slug }}" @selected(old('standard', $student->standard) === $standard->slug)>{{ $standard->name }}</option>
                            @endforeach
                        </select>
                        @error('standard')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Medium *</label>
                        <select name="medium" required class="admin-select">
                            @foreach ($mediums as $value => $label)
                                <option value="{{ $value }}" @selected(old('medium', $student->medium) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('medium')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">New Password</label>
                        <x-password-input name="password" class="admin-input" placeholder="Leave blank to keep current" />
                        @error('password')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Confirm Password</label>
                        <x-password-input name="password_confirmation" class="admin-input" />
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="admin-btn-primary">Update Student</button>
                    <a href="{{ route('principal.reports.allotted-students') }}" class="admin-btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-principal-layout>
