<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Curriculum</span>
            <h2 class="admin-page-title">Edit Question</h2>
            <p class="admin-page-subtitle">{{ $content->title }}</p>
        </div>
    </x-slot>

    @include('admin.partials.alert')

    <div class="mb-4">
        <a href="{{ route('admin.upload-data.show', $content) }}" class="text-sm text-brand-green hover:underline">&larr; Back to Upload Details</a>
    </div>

    <form method="POST" action="{{ route('admin.upload-data.questions.update', [$content, $question]) }}" class="admin-card max-w-3xl">
        <div class="admin-card-top"></div>
        <div class="admin-card-header">
            <h3 class="font-bold text-slate-900">{{ $question->typeLabel() }}</h3>
            <span class="admin-badge-gold">#{{ $question->sort_order + 1 }}</span>
        </div>
        <div class="p-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Question <span class="text-red-500">*</span></label>
                <x-math-editor name="question_text" :rows="4" :value="old('question_text', $question->question_text)" placeholder="Question text" />
                @error('question_text')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Options (one per line)
                    @if (in_array($question->question_type, ['mcq', 'fill_blank', 'one_word'], true))
                        <span class="font-normal text-slate-500">— add 4 choices for A/B/C/D</span>
                    @endif
                </label>
                <x-math-editor name="options_text" :rows="5" :value="old('options_text', $question->options ? implode("\n", array_map(fn ($o) => is_array($o) ? json_encode($o, JSON_UNESCAPED_UNICODE) : $o, $question->options)) : '')" placeholder="One option per line" />
                @error('options_text')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Answer</label>
                <x-math-editor name="answer" :rows="3" :value="old('answer', $question->answer)" placeholder="Answer" />
                @error('answer')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="admin-btn-primary">Update Question</button>
                <a href="{{ route('admin.upload-data.show', $content) }}" class="admin-btn-secondary">Cancel</a>
            </div>
        </div>
    </form>

    <div class="mt-6">
        <x-admin.action-delete :action="route('admin.upload-data.questions.destroy', [$content, $question])" label="Delete question" confirm="Delete this question?" :compact="false" />
    </div>
</x-app-layout>
