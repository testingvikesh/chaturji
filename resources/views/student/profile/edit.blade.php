<x-student-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Student</span>
            <h2 class="admin-page-title">Edit Profile</h2>
            <p class="admin-page-subtitle">Update your personal information</p>
        </div>
    </x-slot>

    <div class="admin-page">
        @include('admin.partials.alert')

        <div class="admin-form-card">
            <div class="admin-card-top"></div>
            <form method="POST" action="{{ route('student.profile.update') }}" class="admin-card-body">
                @csrf
                @method('PUT')

                <div>
                    <label class="admin-label">Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="admin-input">
                    @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">Mobile No.</label>
                        <input type="text" name="mobile" value="{{ old('mobile', $user->mobile) }}" required class="admin-input">
                        @error('mobile')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Email <span class="text-slate-400 font-normal">(Optional)</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="admin-input">
                        @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">Standard</label>
                        <select name="standard" required class="admin-select">
                            @foreach ($standards as $slug => $name)
                                <option value="{{ $slug }}" @selected(old('standard', $user->standard) === $slug)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('standard')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Medium</label>
                        <select name="medium" required class="admin-select">
                            @foreach ($mediums as $value => $label)
                                <option value="{{ $value }}" @selected(old('medium', $user->medium) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('medium')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="admin-btn-primary">Save Profile</button>
                    <a href="{{ route('student.dashboard') }}" class="admin-btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-student-layout>
