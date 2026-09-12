<x-teacher-layout>
    <x-slot name="header">
        <h2 class="admin-page-title">Create Exam</h2>
    </x-slot>

    <form method="POST" action="{{ route('teacher.exams.preview') }}" class="admin-page max-w-4xl space-y-6">
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
                <label class="block text-sm font-medium text-slate-700 mb-1">Additional Exam Instructions</label>
                @if (filled(\App\Models\Setting::get('exam_default_instructions')))
                    <div class="mb-2 rounded-xl border border-amber-100 bg-amber-50/70 px-3 py-2.5 text-xs text-slate-600">
                        <p class="font-semibold text-slate-700 mb-1">Admin default (shown on every paper)</p>
                        <p class="whitespace-pre-line">{{ \App\Models\Setting::get('exam_default_instructions') }}</p>
                    </div>
                @endif
                <textarea name="instructions" rows="3" placeholder="One instruction per line (optional)" class="w-full rounded-xl border-slate-200">{{ old('instructions') }}</textarea>
            </div>

            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Duration (minutes)</label>
                    <input type="number" name="duration_minutes" value="{{ old('duration_minutes') }}" min="1" class="w-full rounded-xl border-slate-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Starts At</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" class="w-full rounded-xl border-slate-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ends At</label>
                    <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" class="w-full rounded-xl border-slate-200">
                </div>
            </div>
        </div>

        <div class="admin-card p-6">
            @include('teacher.partials.paper-type-builder', ['paperTypes' => $paperTypes, 'showMarks' => true])
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-xl bg-brand-green text-white px-6 py-2.5 text-sm font-semibold hover:bg-brand-green-dark transition">Preview Paper</button>
            <a href="{{ route('teacher.exams.index') }}" class="rounded-xl border border-slate-200 px-6 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">Cancel</a>
        </div>
        @include('teacher.partials.paper-builder-alpine-close')
    </form>
</x-teacher-layout>
