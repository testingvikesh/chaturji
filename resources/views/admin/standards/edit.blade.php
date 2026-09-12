<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold text-slate-900">Edit Standard</h2></x-slot>
    @include('admin.partials.alert')
    <form method="POST" action="{{ route('admin.standards.update', $standard) }}" class="max-w-xl bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
        @csrf @method('PUT')
        @include('admin.partials.curriculum-fields', ['item' => $standard])
        <div>
            <label class="admin-label">Medium</label>
            <select name="medium" required class="admin-select">
                <option value="">Select medium</option>
                @foreach ($mediums as $value => $label)
                    <option value="{{ $value }}" @selected(old('medium', $standard->medium) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('medium')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-3"><x-primary-button>Update</x-primary-button><a href="{{ route('admin.standards.index') }}" class="text-sm text-slate-500 self-center">Cancel</a></div>
    </form>
    <div class="mt-4">
        <x-admin.action-delete :action="route('admin.standards.destroy', $standard)" label="Delete standard" confirm="Delete standard and all related content?" :compact="false" />
    </div>
</x-app-layout>
