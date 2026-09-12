<x-teacher-layout>
    <x-slot name="header">
        <h2 class="admin-page-title">Create Homework</h2>
    </x-slot>

    <form method="POST" action="{{ route('teacher.homework.preview') }}" class="admin-page max-w-4xl space-y-6">
        @csrf
        @include('teacher.partials.paper-builder-alpine-open', ['standards' => $standards])

        <div class="admin-card p-6 space-y-4">
            @include('teacher.partials.paper-curriculum-fields', ['standards' => $standards])

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" required class="w-full rounded-xl border-slate-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full rounded-xl border-slate-200">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Due Date</label>
                <input type="datetime-local" name="due_at" value="{{ old('due_at') }}" class="w-full rounded-xl border-slate-200">
            </div>
        </div>

        <div class="admin-card p-6">
            @include('teacher.partials.paper-type-builder', ['paperTypes' => $paperTypes, 'showMarks' => false])
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-xl bg-brand-green text-white px-6 py-2.5 text-sm font-semibold hover:bg-brand-green-dark transition">Preview Homework</button>
            <a href="{{ route('teacher.homework.index') }}" class="rounded-xl border border-slate-200 px-6 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">Cancel</a>
        </div>
        @include('teacher.partials.paper-builder-alpine-close')
    </form>
</x-teacher-layout>
