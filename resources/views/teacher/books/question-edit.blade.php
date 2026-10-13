<x-teacher-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Books</span>
            <h2 class="admin-page-title">Edit Question</h2>
            <p class="admin-page-subtitle">{{ $materialTopic->displayName() }} · {{ $question->typeLabel() }}</p>
        </div>
    </x-slot>

    <div class="admin-page max-w-3xl">
        @include('admin.partials.alert')

        <div class="mb-4">
            <a href="{{ $backUrl }}" class="text-sm text-brand-green hover:underline font-medium">&larr; Back to material</a>
        </div>

        <form method="POST"
              action="{{ route('teacher.books.questions.update', [$subject, $materialTopic, $questionKey]) }}"
              class="admin-form-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-body space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="medium" value="{{ $medium }}">

                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    Edit the question, options, and answer. Subscript and superscript stay when you paste maths.
                </div>

                <div>
                    <label class="admin-label">Question type</label>
                    <input type="text" class="admin-input bg-slate-50" value="{{ $question->typeLabel() }}" disabled>
                </div>

                <div>
                    <label class="admin-label">Question <span class="text-red-500">*</span></label>
                    <x-math-editor name="question_text" :rows="4" :value="old('question_text', $question->question_text)" placeholder="Question text" />
                    @error('question_text')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="admin-label">Options <span class="font-normal text-slate-400">(one per line, for MCQ / objective)</span></label>
                    <x-math-editor name="options_text" :rows="5" :value="old('options_text', $optionsText)" placeholder="One option per line" />
                    @error('options_text')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="admin-label">Answer</label>
                    <x-math-editor name="answer" :rows="4" :value="old('answer', is_array($question->answer) ? json_encode($question->answer, JSON_UNESCAPED_UNICODE) : $question->answer)" placeholder="Correct answer" />
                    @error('answer')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-wrap gap-3 pt-1">
                    <button type="submit" class="admin-btn-primary">Update question</button>
                    <a href="{{ $backUrl }}" class="admin-btn-ghost">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</x-teacher-layout>
