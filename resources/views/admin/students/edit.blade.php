<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header :title="'Edit: ' . $student->name" subtitle="Update student account details" />
    </x-slot>

    <div class="admin-page">
        @include('admin.partials.alert')

        <div class="admin-form-card">
            <div class="admin-card-top"></div>
            <form method="POST" action="{{ route('admin.students.update', $student) }}" class="admin-card-body">
                @csrf @method('PUT')

                <div>
                    <label class="admin-label">Name</label>
                    <input type="text" name="name" value="{{ old('name', $student->name) }}" required class="admin-input">
                    @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">Mobile</label>
                        <input type="text" name="mobile" value="{{ old('mobile', $student->mobile) }}" required class="admin-input">
                        @error('mobile')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Email (Optional)</label>
                        <input type="email" name="email" value="{{ old('email', $student->email) }}" class="admin-input">
                        @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">Standard</label>
                        <select name="standard" required class="admin-select">
                            @foreach ($standards as $slug => $name)
                                <option value="{{ $slug }}" @selected(old('standard', $student->standard) === $slug)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label">Medium</label>
                        <select name="medium" required class="admin-select">
                            @foreach ($mediums as $value => $label)
                                <option value="{{ $value }}" @selected(old('medium', $student->medium) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">New Password</label>
                        <x-password-input name="password" class="admin-input" placeholder="Leave blank to keep current" />
                    </div>
                    <div>
                        <label class="admin-label">Confirm Password</label>
                        <x-password-input name="password_confirmation" class="admin-input" />
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="admin-btn-primary">Update Student</button>
                    <a href="{{ route('admin.students.index') }}" class="admin-btn-ghost">Cancel</a>
                </div>
            </form>
        </div>

        <div class="mt-6">
            <x-admin.action-delete :action="route('admin.students.destroy', $student)" label="Delete student" confirm="Delete this student?" :compact="false" />
        </div>
    </div>
</x-app-layout>
