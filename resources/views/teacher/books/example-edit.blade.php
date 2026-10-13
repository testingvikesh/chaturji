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
                    <x-math-editor name="question_text" :rows="4" :value="old('question_text', $item['question_text'] ?? '')" placeholder="Question" />
                    @error('question_text')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="admin-label">Find</label>
                        <x-math-editor name="find" :rows="3" :value="old('find', $item['find'] ?? '')" />
                        @error('find')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Given</label>
                        <x-math-editor name="given" :rows="3" :value="old('given', $item['given'] ?? '')" />
                        @error('given')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="admin-label">Solution overview</label>
                    <x-math-editor name="solution_overview" :rows="3" :value="old('solution_overview', $item['solution_overview'] ?? '')" />
                    @error('solution_overview')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="admin-label">Step-by-step <span class="font-normal text-slate-400">(one step per line; use <code>equation :: note</code>)</span></label>
                    <x-math-editor name="solution_text" :rows="8" :value="old('solution_text', $solutionText)" placeholder="One step per line. equation :: note" />
                    @error('solution_text')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="admin-label">Final answer</label>
                    <x-math-editor name="final_answer" :rows="3" :value="old('final_answer', $item['final_answer'] ?? '')" />
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
