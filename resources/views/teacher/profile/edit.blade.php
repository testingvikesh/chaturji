<x-teacher-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Teacher</span>
            <h2 class="admin-page-title">Edit Profile</h2>
            <p class="admin-page-subtitle">Update your name, mobile and email</p>
        </div>
    </x-slot>

    <div class="admin-page max-w-2xl">
        @include('admin.partials.alert')

        <div class="admin-form-card">
            <div class="admin-card-top"></div>
            <form method="POST" action="{{ route('teacher.profile.update') }}" class="admin-card-body space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="admin-label">Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="admin-input" autocomplete="name">
                    @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">Mobile No.</label>
                        <input type="text" name="mobile" value="{{ old('mobile', $user->mobile) }}" required class="admin-input" autocomplete="tel">
                        @error('mobile')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Email <span class="text-slate-400 font-normal">(Optional)</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="admin-input" autocomplete="email">
                        @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <button type="submit" class="admin-btn-primary">Save Profile</button>
                    <a href="{{ route('teacher.change-password') }}" class="admin-btn-secondary">Change Password</a>
                    <a href="{{ route('teacher.dashboard') }}" class="admin-btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-teacher-layout>
