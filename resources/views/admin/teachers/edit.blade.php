<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Edit Teacher</h2>
            <p class="text-sm text-slate-500 mt-1">{{ $teacher->name }}</p>
        </div>
    </x-slot>

    @include('admin.partials.alert')

    <form method="POST" action="{{ route('admin.teachers.update', $teacher) }}" class="max-w-3xl bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
            <input type="text" name="name" value="{{ old('name', $teacher->name) }}" required class="w-full rounded-xl border-slate-300 text-sm">
            @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Mobile</label>
                <input type="text" name="mobile" value="{{ old('mobile', $teacher->mobile) }}" required class="w-full rounded-xl border-slate-300 text-sm">
                @error('mobile')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $teacher->email) }}" required class="w-full rounded-xl border-slate-300 text-sm">
                @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
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
            <x-primary-button>Update Teacher</x-primary-button>
            <a href="{{ route('admin.teachers.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Cancel</a>
        </div>
    </form>

    <div class="max-w-3xl mt-6">
        <x-admin.action-delete :action="route('admin.teachers.destroy', $teacher)" label="Delete teacher" confirm="Delete this teacher?" :compact="false" />
    </div>
</x-app-layout>
