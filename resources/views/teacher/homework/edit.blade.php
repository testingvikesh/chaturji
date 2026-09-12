<x-teacher-layout>
    <x-slot name="header">
        <h2 class="admin-page-title">Edit Homework</h2>
    </x-slot>

    @php
        $config = $homework->generation_config ?? [];
        $selectedTypeCounts = $config['type_counts'] ?? [];
    @endphp

    <form method="POST" action="{{ route('teacher.homework.update', $homework) }}" class="admin-page max-w-4xl space-y-6">
        @csrf @method('PUT')
        @include('teacher.partials.paper-builder-alpine-open', [
            'standards' => $standards,
            'selectedStandard' => old('standard', $homework->standard),
            'selectedSubjectId' => old('subject_id', $homework->subject_id),
            'selectedChapterId' => old('chapter_id', $homework->chapter_id),
            'selectedTopicId' => old('topic_id', $homework->topic_id),
        ])

        <div class="admin-card p-6 space-y-4">
            @include('teacher.partials.paper-curriculum-fields', ['standards' => $standards, 'selectedStatus' => old('status', $homework->status)])

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Title *</label>
                <input type="text" name="title" value="{{ old('title', $homework->title) }}" required class="w-full rounded-xl border-slate-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full rounded-xl border-slate-200">{{ old('description', $homework->description) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Due Date</label>
                <input type="datetime-local" name="due_at" value="{{ old('due_at', $homework->due_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border-slate-200">
            </div>
        </div>

        <div class="admin-card p-6">
            <p class="text-sm text-amber-700 mb-4">Saving will regenerate questions randomly from the question bank using the quantities below.</p>
            @include('teacher.partials.paper-type-builder', [
                'paperTypes' => $paperTypes,
                'showMarks' => false,
                'selectedTypeCounts' => $selectedTypeCounts,
            ])
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-xl bg-brand-green text-white px-6 py-2.5 text-sm font-semibold hover:bg-brand-green-dark transition">Update &amp; Regenerate</button>
            <a href="{{ route('teacher.homework.show', $homework) }}" class="rounded-xl border border-slate-200 px-6 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">Cancel</a>
        </div>
        @include('teacher.partials.paper-builder-alpine-close')
    </form>
</x-teacher-layout>
