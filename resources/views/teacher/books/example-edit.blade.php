<x-teacher-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Books</span>
            <h2 class="admin-page-title">Edit Example</h2>
            <p class="admin-page-subtitle">{{ $example['label'] ?? 'Example' }} · {{ $material->displayChapterName() }}</p>
        </div>
    </x-slot>

    <div class="admin-page max-w-3xl">
        @include('admin.partials.alert')

        <div class="mb-4">
            <a href="{{ $backUrl }}" class="text-sm text-brand-green hover:underline font-medium">&larr; Back to examples</a>
        </div>

        <form method="POST"
              action="{{ route('teacher.books.examples.update', [$subject, $materialTopic, $exampleKey]) }}"
              class="admin-form-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-body space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="medium" value="{{ $medium }}">

                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    Edit this Maths / Accounts solved example. Changes update this material and create an admin log.
                </div>

                <div>
                    <label class="admin-label">Question <span class="text-red-500">*</span></label>
                    <textarea name="question_text" rows="4" required class="admin-input">{{ old('question_text', $item['question_text'] ?? '') }}</textarea>
                    @error('question_text')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="admin-label">Find</label>
                        <textarea name="find" rows="3" class="admin-input">{{ old('find', $item['find'] ?? '') }}</textarea>
                        @error('find')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Given</label>
                        <textarea name="given" rows="3" class="admin-input">{{ old('given', $item['given'] ?? '') }}</textarea>
                        @error('given')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="admin-label">Solution overview</label>
                    <textarea name="solution_overview" rows="3" class="admin-input">{{ old('solution_overview', $item['solution_overview'] ?? '') }}</textarea>
                    @error('solution_overview')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="admin-label">Step-by-step <span class="font-normal text-slate-400">(one step per line; use <code>equation :: note</code>)</span></label>
                    <textarea name="solution_text" rows="8" class="admin-input font-mono text-sm" placeholder="x = 5 :: Find the value&#10;Area = 25">{{ old('solution_text', $solutionText) }}</textarea>
                    @error('solution_text')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="admin-label">Final answer</label>
                    <textarea name="final_answer" rows="3" class="admin-input">{{ old('final_answer', $item['final_answer'] ?? '') }}</textarea>
                    @error('final_answer')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-wrap gap-3 pt-1">
                    <button type="submit" class="admin-btn-primary">Update example</button>
                    <a href="{{ $backUrl }}" class="admin-btn-ghost">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</x-teacher-layout>
