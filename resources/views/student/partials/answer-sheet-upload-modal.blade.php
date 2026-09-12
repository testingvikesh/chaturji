@props([
    'modalName' => 'upload-answer-pdf',
    'action',
    'subjectiveStats' => null,
])

@php
    $numbers = $subjectiveStats['numbers'] ?? [];
    $questionHint = $numbers !== []
        ? 'Q'.implode(', Q', $numbers)
        : 'Q1, Q2, Q3';
@endphp

<x-modal :name="$modalName" maxWidth="lg">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="p-6">
        @csrf

        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Upload Written Answers</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Upload your answer sheet as <strong>PDF or photo</strong>.
                    AI teacher will check it and return a <strong>Teacher Checked Sheet</strong> with red-pen marks and comments.
                </p>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-600" x-on:click="$dispatch('close-modal', '{{ $modalName }}')">✕</button>
        </div>

        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-semibold">Format on your paper (subjective only)</p>
            <p class="mt-1">Write like this:</p>
            <p class="mt-1 font-mono text-xs leading-relaxed">
                Q10. question…?<br>
                Ans - your answer
            </p>
            @if (($subjectiveStats['count'] ?? 0) > 0)
                <p class="mt-2">Check these question numbers: <strong>{{ $questionHint }}</strong></p>
            @endif
            <p class="mt-1 text-xs text-amber-800/80">Objective MCQ / T-F are not checked from this upload.</p>
        </div>

        <div class="mt-5 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/80 p-6 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-green-50 text-brand-green">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>

            <label class="mt-4 inline-flex cursor-pointer items-center justify-center rounded-xl bg-brand-green px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-green-dark">
                Main Sheet (PDF / Photo)
                <input
                    type="file"
                    name="answer_pdf"
                    accept="application/pdf,.pdf,image/jpeg,.jpg,.jpeg,image/png,.png,image/webp,.webp"
                    class="hidden"
                    x-on:change="fileName = $event.target.files[0]?.name || ''"
                >
            </label>

            <label class="mt-3 inline-flex cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Add More Photos (merge)
                <input
                    type="file"
                    name="answer_images[]"
                    accept="image/jpeg,.jpg,.jpeg,image/png,.png,image/webp,.webp"
                    multiple
                    class="hidden"
                >
            </label>

            <p class="mt-3 text-sm font-medium text-slate-700" x-text="fileName || 'No main file selected yet'" x-cloak></p>
            <p class="mt-1 text-xs text-slate-500">Multi-page PDF is supported · Use dark pen · Good light · Result shows marks + teacher comments</p>
        </div>

        <div class="mt-6 flex flex-wrap justify-end gap-3">
            <button type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50" x-on:click="$dispatch('close-modal', '{{ $modalName }}')">
                Cancel
            </button>
            <button type="submit" class="rounded-xl bg-brand-green px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-green-dark">
                Upload &amp; Teacher Check
            </button>
        </div>
    </form>
</x-modal>
