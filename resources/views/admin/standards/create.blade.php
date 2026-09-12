<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold text-slate-900">Add Standard</h2></x-slot>
    @include('admin.partials.alert')
    <form method="POST" action="{{ route('admin.standards.store') }}" class="max-w-xl bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
        @csrf
        @include('admin.partials.curriculum-fields')
        <div>
            <label class="admin-label">Medium</label>
            <select name="medium" required class="admin-select">
                <option value="">Select medium</option>
                @foreach ($mediums as $value => $label)
                    <option value="{{ $value }}" @selected(old('medium', 'english') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('medium')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <x-primary-button>Create Standard</x-primary-button>
    </form>
</x-app-layout>
