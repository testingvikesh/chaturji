<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header :title="$prompt['label']" subtitle="Set the prompt used for this AI task">
            <x-slot name="actions">
                <a href="{{ route('admin.prompts.index') }}" class="admin-btn-secondary">Back</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page space-y-4">
        @include('admin.partials.alert')

        <div class="admin-form-card">
            <div class="admin-card-top"></div>
            <form method="POST" action="{{ route('admin.prompts.update', $prompt['key']) }}" class="admin-card-body">
                @csrf
                @method('PUT')
                <input type="hidden" name="key" value="{{ $prompt['key'] }}">

                <p class="text-sm text-slate-500">{{ $prompt['hint'] }}</p>
                @if ($prompt['custom'])
                    <p class="text-xs text-brand-green font-semibold">This prompt is customized.</p>
                @endif

                <div>
                    <label for="body" class="admin-label">Prompt text</label>
                    @php
                        $isExamPaperPrompt = ($prompt['key'] ?? '') === 'exam_paper_generator';
                    @endphp
                    <textarea id="body" name="body" rows="{{ $isExamPaperPrompt ? 28 : 16 }}" required class="admin-input font-mono text-sm">{{ old('body', $prompt['value']) }}</textarea>
                    @if ($isExamPaperPrompt)
                        <p class="text-xs text-slate-500 mt-2">
                            Dynamic placeholders:
                            <code class="text-[11px]">@{{ total_marks }}</code>,
                            <code class="text-[11px]">@{{ standard }}</code>,
                            <code class="text-[11px]">@{{ subject }}</code>,
                            <code class="text-[11px]">@{{ exam_date }}</code>,
                            <code class="text-[11px]">@{{ syllabus_outline }}</code>,
                            <code class="text-[11px]">@{{ chapter_weightage }}</code>,
                            <code class="text-[11px]">@{{ available_questions_json }}</code>
                        </p>
                    @endif
                    @error('body')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="admin-btn-primary">Save Prompt</button>
                </div>
            </form>
        </div>

        <form method="POST" action="{{ route('admin.prompts.reset', $prompt['key']) }}" onsubmit="return confirm('Reset this prompt to the default text?')">
            @csrf
            <button type="submit" class="admin-btn-secondary">Reset to default</button>
        </form>
    </div>
</x-app-layout>
