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
                <textarea name="question_text" rows="4" required class="admin-input">{{ old('question_text', $question->question_text) }}</textarea>
                @error('question_text')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Options (one per line)
                    @if (in_array($question->question_type, ['mcq', 'fill_blank', 'one_word'], true))
                        <span class="font-normal text-slate-500">— add 4 choices for A/B/C/D</span>
                    @endif
                </label>
                <textarea name="options_text" rows="5" class="admin-input" placeholder="Option A&#10;Option B&#10;Option C&#10;Option D">{{ old('options_text', $question->options ? implode("\n", array_map(fn ($o) => is_array($o) ? json_encode($o, JSON_UNESCAPED_UNICODE) : $o, $question->options)) : '') }}</textarea>
                @error('options_text')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Answer</label>
                <textarea name="answer" rows="3" class="admin-input">{{ old('answer', $question->answer) }}</textarea>
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
