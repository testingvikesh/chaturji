{{-- Shared Self Exam / Self Homework create: subject → material chapter → material topic → marks --}}
@php
    $formAction = $formAction ?? '#';
    $countsUrl = $countsUrl ?? '#';
    $previewUrl = $previewUrl ?? '#';
    $cancelUrl = $cancelUrl ?? url('/student');
    $generateLabel = $generateLabel ?? 'Generate';
    $markOptions = $markOptions ?? [20, 30, 50, 70, 100];
    $alpineSubjects = $alpineSubjects ?? \App\Support\MaterialPaperBank::alpineSubjects($subjects);
@endphp

<div class="admin-page max-w-3xl"
     x-data="selfPaperBuilder({
        subjects: @js($alpineSubjects),
        markOptions: @js($markOptions),
        countsUrl: @js($countsUrl),
        previewUrl: @js($previewUrl),
        oldSubject: @js(old('subject_id')),
        oldChapters: @js(array_values(array_map('strval', (array) (old('chapter_ids') ?: (old('chapter_id') ? [old('chapter_id')] : []))))),
        oldTopics: @js(array_values(array_map('strval', (array) old('topic_ids', [])))),
        oldMarks: @js((int) old('target_marks', 20)),
     })">
    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-disc pl-4 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
    @endif

    @if (! $standard || $subjects->isEmpty())
        <div class="admin-card p-8 text-center text-slate-600">
            No material subjects found for {{ $standardName }}. Upload materials for this standard and medium first.
        </div>
    @else
        <form method="POST" action="{{ $formAction }}" class="space-y-6"
              @submit="if (!canGenerate) { $event.preventDefault(); planError = planError || 'Please Preview first and fix any availability errors.'; }">
            @csrf

            <div class="admin-card p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Subject *</label>
                    <select name="subject_id" x-model="subjectId" @change="onSubjectChange()" required class="w-full rounded-xl border-slate-200 h-11">
                        <option value="">Select subject</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected((string) old('subject_id') === (string) $subject->id)>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <div class="mb-1 flex items-center justify-between gap-2">
                        <label class="block text-sm font-medium text-slate-700">Chapter *</label>
                        <div class="flex items-center gap-2 text-xs" x-show="chapters.length">
                            <button type="button" class="font-semibold text-brand-green hover:underline" @click="selectAllChapters()" :disabled="!subjectId">Select all</button>
                            <span class="text-slate-300">|</span>
                            <button type="button" class="font-semibold text-slate-500 hover:underline" @click="clearChapters()" :disabled="!subjectId">Clear</button>
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-3 max-h-52 overflow-y-auto space-y-2"
                         :class="!subjectId || !chapters.length ? 'opacity-50 pointer-events-none' : ''">
                        <p class="text-xs text-slate-500" x-show="!chapters.length">Select a subject to load chapters.</p>
                        <template x-for="ch in chapters" :key="ch.id">
                            <label class="flex items-start gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox"
                                       class="mt-0.5 rounded border-slate-300 text-brand-green focus:ring-brand-green"
                                       :value="String(ch.id)"
                                       x-model="chapterIds"
                                       @change="onChaptersChange()">
                                <span class="text-sm text-slate-700" x-text="ch.name"></span>
                            </label>
                        </template>
                        <template x-for="id in chapterIds" :key="'chapter-hidden-'+id">
                            <input type="hidden" name="chapter_ids[]" :value="id">
                        </template>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Select one or more chapters.
                        <span x-show="chapterIds.length" x-text="chapterIds.length + ' chapter(s) selected'"></span>
                    </p>
                </div>

                <div>
                    <div class="mb-1 flex items-center justify-between gap-2">
                        <label class="block text-sm font-medium text-slate-700">Topic *</label>
                        <div class="flex items-center gap-2 text-xs" x-show="topics.length">
                            <button type="button" class="font-semibold text-brand-green hover:underline" @click="selectAllTopics()" :disabled="!chapterIds.length">Select all</button>
                            <span class="text-slate-300">|</span>
                            <button type="button" class="font-semibold text-slate-500 hover:underline" @click="clearTopics()" :disabled="!chapterIds.length">Clear</button>
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-3 max-h-52 overflow-y-auto space-y-2"
                         :class="!chapterIds.length ? 'opacity-50 pointer-events-none' : ''">
                        <p class="text-xs text-slate-500" x-show="!topics.length">Select chapter(s) to load topics.</p>
                        <template x-for="tp in topics" :key="tp.id">
                            <label class="flex items-start gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox"
                                       class="mt-0.5 rounded border-slate-300 text-brand-green focus:ring-brand-green"
                                       :value="String(tp.id)"
                                       x-model="topicIds"
                                       @change="invalidatePreview(); refreshCounts()">
                                <span class="text-sm text-slate-700" x-text="tp.name"></span>
                            </label>
                        </template>
                        <template x-for="id in topicIds" :key="'hidden-'+id">
                            <input type="hidden" name="topic_ids[]" :value="id">
                        </template>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Select one or more topics.
                        <span x-show="!topicIds.length">None selected = all topics in selected chapters.</span>
                        <span x-show="topicIds.length" x-text="topicIds.length + ' topic(s) selected'"></span>
                    </p>
                </div>

                <div>
                    <p class="block text-sm font-medium text-slate-700 mb-2">Total marks *</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="marks in markOptions" :key="marks">
                            <label class="cursor-pointer">
                                <input type="radio" name="target_marks" :value="marks" x-model.number="targetMarks" @change="invalidatePreview(); refreshCounts()" class="peer sr-only">
                                <span class="inline-flex h-10 min-w-[3.25rem] items-center justify-center rounded-lg border px-3 text-sm font-semibold leading-none border-slate-200 bg-white text-slate-700 peer-checked:border-brand-green peer-checked:bg-emerald-50 peer-checked:text-brand-green peer-checked:ring-1 peer-checked:ring-brand-green"
                                      x-text="marks"></span>
                            </label>
                        </template>
                    </div>
                </div>
            </div>

            <div class="admin-card p-6 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-slate-900">Questions available (by type)</h3>
                        <p class="text-xs text-slate-500">Preview validates each type against available questions, then you can generate.</p>
                    </div>
                    <button type="button" @click="refreshCounts()" class="text-xs font-semibold text-brand-green hover:underline" :disabled="loading">
                        <span x-show="!loading">Refresh</span>
                        <span x-show="loading">Loading…</span>
                    </button>
                </div>

                <div x-show="planError" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800" x-text="planError"></div>
                <div x-show="previewOk && !planError" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800">
                    Preview validated. You can generate the paper now.
                </div>

                <div class="overflow-x-auto">
                    <table class="admin-table w-full text-sm">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Available</th>
                                <th>In this paper</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in typeRows" :key="row.type">
                                <tr>
                                    <td x-text="row.label"></td>
                                    <td x-text="row.available"></td>
                                    <td class="font-semibold">
                                        <input type="number"
                                               min="0"
                                               :max="row.available"
                                               x-model.number="row.planned"
                                               @input="onPlannedInput(row)"
                                               class="w-20 rounded-lg border-slate-200 text-sm">
                                        <input type="hidden" :name="`type_counts[${row.type}]`" :value="row.planned ?? 0">
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="typeRows.length === 0">
                                <td colspan="3" class="text-center text-slate-500 py-6">Select chapter and marks to see availability.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3 text-sm text-slate-700" x-show="typeRows.length">
                    <span class="font-semibold">Paper plan:</span>
                    <span x-text="plannedQuestions"></span> questions ·
                    <span x-text="plannedMarks"></span> marks
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="button"
                        @click="runPreview()"
                        class="inline-flex h-10 items-center justify-center rounded-lg border border-brand-green px-5 text-sm font-semibold leading-none text-brand-green hover:bg-brand-green-50 transition disabled:opacity-50"
                        :disabled="!canPreview || loading">
                    Preview
                </button>
                <button type="submit"
                        class="inline-flex h-10 items-center justify-center rounded-lg bg-brand-green px-5 text-sm font-semibold leading-none text-white hover:bg-brand-green-dark transition disabled:opacity-50"
                        :disabled="!canGenerate || loading">
                    {{ $generateLabel }}
                </button>
                <a href="{{ $cancelUrl }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 px-5 text-sm font-semibold leading-none text-slate-600 hover:bg-slate-50 transition">Cancel</a>
            </div>
        </form>
    @endif

    {{-- Teleport to body so fixed positioning is never clipped by layout parents --}}
    <template x-teleport="body">
        <div x-show="previewOpen"
             x-cloak
             class="paper-preview-modal"
             style="display: none; position: fixed; inset: 0; z-index: 99999;"
             @keydown.escape.window="if (previewOpen) closePreviewUi()">
            <div class="paper-preview-modal__backdrop" style="position:absolute;inset:0;background:rgb(15 23 42 / 0.65);" @click="closePreviewUi()"></div>

            <div class="paper-preview-modal__frame" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:0.5rem;pointer-events:none;">
                <div class="paper-preview-modal__panel"
                     style="pointer-events:auto;display:flex;flex-direction:column;width:100%;max-width:64rem;height:min(92vh,920px);max-height:92vh;overflow:hidden;border-radius:0.75rem;background:#fff;border:1px solid #e2e8f0;box-shadow:0 25px 50px -12px rgb(15 23 42 / 0.35);"
                     @click.stop
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="paper-preview-title">
                    <div class="paper-preview-modal__header" style="flex:0 0 auto;display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem;padding:0.625rem 0.875rem;border-bottom:1px solid #e2e8f0;background:#fff;">
                        <div class="min-w-0 flex-1">
                            <h3 id="paper-preview-title" class="text-sm sm:text-base font-bold text-slate-900">Exam Paper Preview</h3>
                            <p class="mt-0.5 text-xs text-slate-500 leading-snug truncate">
                                Same layout as the original paper — type sections, options &amp; marks
                            </p>
                        </div>
                        <button type="button"
                                @click="closePreviewUi()"
                                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 text-lg leading-none"
                                aria-label="Close preview">
                            &times;
                        </button>
                    </div>

                    <div class="paper-preview-modal__body" style="flex:1 1 auto;min-height:0;overflow-y:auto;overscroll-behavior:contain;padding:0.625rem 0.75rem;background:#f8fafc;display:flex;flex-direction:column;gap:0.625rem;">
                        <template x-if="!previewSections.length">
                            <div class="rounded-xl border border-dashed border-slate-200 bg-white px-3 py-8 text-center text-sm text-slate-500">
                                No questions in this preview.
                            </div>
                        </template>

                        <template x-if="previewSections.length">
                            <div class="space-y-2.5">
                                {{-- Paper header (matches exam-paper-header) --}}
                                <div class="admin-card overflow-hidden">
                                    <div class="admin-card-top"></div>
                                    <div class="relative overflow-hidden bg-gradient-to-br from-brand-green via-brand-green to-brand-green-dark px-3 py-2 sm:px-4 sm:py-2 text-white">
                                        <div class="relative flex flex-col sm:flex-row sm:items-center gap-2.5">
                                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl overflow-hidden">
                                                <img :src="previewMeta.logo_url"
                                                     :alt="previewMeta.school_name || 'Gses Chaturji'"
                                                     class="h-full w-full object-contain"
                                                     x-show="previewMeta.logo_url">
                                                <span class="text-lg font-bold text-brand-gold"
                                                      x-show="!previewMeta.logo_url"
                                                      x-text="previewMeta.logo_letter || 'G'"></span>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-white/70">Question Paper</p>
                                                <h2 class="mt-0.5 text-base sm:text-lg font-bold leading-tight" x-text="previewMeta.school_name || 'Gses Chaturji'"></h2>
                                                <p class="mt-0.5 text-xs sm:text-sm text-white/90" x-text="previewMeta.paper_title || 'Paper Preview'"></p>
                                            </div>
                                            <span class="inline-flex items-center gap-1 rounded-lg bg-brand-gold/90 px-2.5 py-1.5 text-xs font-bold text-brand-green-dark self-start">
                                                <span x-text="previewMeta.total_marks || 0"></span> Marks
                                            </span>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 p-2.5 sm:p-3 bg-gradient-to-b from-slate-50/80 to-white">
                                        <div class="flex items-start gap-2 rounded-lg border border-slate-100 bg-white px-2.5 py-2 shadow-sm min-w-0">
                                            <div class="min-w-0 flex-1">
                                                <p class="text-[11px] font-medium text-slate-500">Class</p>
                                                <p class="mt-0.5 text-sm font-semibold text-slate-900 break-words leading-snug" x-text="previewMeta.class_name || '—'"></p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-2 rounded-lg border border-slate-100 bg-white px-2.5 py-2 shadow-sm min-w-0">
                                            <div class="min-w-0 flex-1">
                                                <p class="text-[11px] font-medium text-slate-500">Subject</p>
                                                <p class="mt-0.5 text-sm font-semibold text-slate-900 break-words leading-snug" x-text="previewMeta.subject_name || '—'"></p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-2 rounded-lg border border-slate-100 bg-white px-2.5 py-2 shadow-sm min-w-0">
                                            <div class="min-w-0 flex-1">
                                                <p class="text-[11px] font-medium text-slate-500">Chapter</p>
                                                <p class="mt-0.5 text-sm font-semibold text-slate-900 break-words leading-snug" x-text="previewMeta.chapter_name || '—'"></p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-2 rounded-lg border border-slate-100 bg-white px-2.5 py-2 shadow-sm min-w-0">
                                            <div class="min-w-0 flex-1">
                                                <p class="text-[11px] font-medium text-slate-500">Topic</p>
                                                <p class="mt-0.5 text-sm font-semibold text-slate-900 break-words leading-snug" x-text="previewMeta.topic_label || '—'"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Type breakdown --}}
                                <div class="admin-card overflow-hidden" x-show="previewBreakdown.length">
                                    <div class="admin-card-top"></div>
                                    <div class="admin-card-header flex flex-wrap items-center justify-between gap-2">
                                        <h3 class="text-sm font-bold text-slate-900">Paper Type Breakdown</h3>
                                        <div class="flex flex-wrap gap-1.5 text-xs">
                                            <span class="rounded-md bg-slate-100 px-2 py-1 font-semibold">
                                                <span x-text="previewMeta.total_questions || 0"></span> Questions
                                            </span>
                                            <span class="rounded-md bg-brand-gold/20 px-2 py-1 font-semibold text-brand-green-darker">
                                                <span x-text="previewMeta.total_marks || 0"></span> Marks
                                            </span>
                                        </div>
                                    </div>
                                    <div class="p-2.5 sm:p-3 grid sm:grid-cols-2 gap-2">
                                        <template x-for="row in previewBreakdown" :key="row.type">
                                            <div class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 bg-slate-50 px-2.5 py-2">
                                                <div class="flex items-center gap-1.5 min-w-0">
                                                    <span class="shrink-0 text-sm" x-text="row.icon || ''"></span>
                                                    <span class="text-sm font-medium text-slate-800 truncate" x-text="row.label"></span>
                                                </div>
                                                <div class="shrink-0 text-right">
                                                    <span class="text-sm font-bold text-brand-green"><span x-text="row.count"></span> Q</span>
                                                    <span class="block text-[11px] font-semibold text-amber-700" x-show="(row.subtotal || 0) > 0">
                                                        <span x-text="row.marks"></span> × <span x-text="row.count"></span> = <span x-text="row.subtotal"></span> marks
                                                    </span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                {{-- Type sections (original paper view) --}}
                                <template x-for="section in previewSections" :key="section.type">
                                    <div class="admin-card overflow-hidden">
                                        <div class="admin-card-top"></div>
                                        <div class="material-question-group-header">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <span class="text-base shrink-0" x-text="section.icon || ''"></span>
                                                <h3 class="text-sm font-bold text-slate-900 truncate" x-text="section.label"></h3>
                                            </div>
                                            <div class="flex shrink-0 items-center gap-1.5">
                                                <span class="admin-badge-slate text-xs"><span x-text="section.count"></span> Q</span>
                                                <span class="admin-badge-gold text-xs" x-show="(section.subtotal || 0) > 0">
                                                    <span x-text="section.subtotal"></span> marks
                                                </span>
                                            </div>
                                        </div>
                                        <div class="divide-y divide-slate-100">
                                            <template x-for="q in section.questions" :key="section.type + '-' + q.no">
                                                <div class="material-question p-3">
                                                    <div class="flex items-start gap-2.5">
                                                        <span class="material-question-no" x-text="q.no"></span>
                                                        <div class="min-w-0 flex-1 space-y-2">
                                                            <div class="flex items-start justify-between gap-2">
                                                                <p class="material-question-text flex-1 whitespace-pre-wrap" x-text="q.text"></p>
                                                                <span class="admin-badge-gold text-xs whitespace-nowrap shrink-0"
                                                                      x-show="(q.marks || 0) > 0"
                                                                      x-text="(q.marks || 1) + ' mark' + ((q.marks || 1) > 1 ? 's' : '')"></span>
                                                            </div>

                                                            <template x-if="q.match">
                                                                <div class="material-match-grid gap-2">
                                                                    <div>
                                                                        <p class="material-options-label">Column A</p>
                                                                        <ul class="material-options-list">
                                                                            <template x-for="(item, idx) in (q.match.column_a || [])" :key="'a-' + q.no + '-' + idx">
                                                                                <li x-text="item"></li>
                                                                            </template>
                                                                        </ul>
                                                                    </div>
                                                                    <div>
                                                                        <p class="material-options-label">Column B</p>
                                                                        <ul class="material-options-list">
                                                                            <template x-for="(item, idx) in (q.match.column_b || [])" :key="'b-' + q.no + '-' + idx">
                                                                                <li x-text="item"></li>
                                                                            </template>
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                            </template>

                                                            <template x-if="!q.match && (q.options || []).length">
                                                                <div :class="(q.type === 'mcq' || q.type === 'true_false' || (q.options || []).length >= 2)
                                                                    ? 'grid grid-cols-1 sm:grid-cols-2 gap-1.5'
                                                                    : 'space-y-1.5'">
                                                                    <template x-for="opt in q.options" :key="q.no + '-' + opt.key">
                                                                        <div class="flex w-full items-center gap-2 rounded-lg border border-slate-100 bg-slate-50/80 px-2 py-1.5 text-sm">
                                                                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-brand-green/30 bg-white text-xs font-bold text-brand-green"
                                                                                  x-text="String(opt.key || '').toUpperCase()"></span>
                                                                            <span class="min-w-0 flex-1 leading-snug text-slate-700" x-text="opt.text"></span>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <div class="paper-preview-modal__footer" style="flex:0 0 auto;display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-end;gap:0.5rem;padding:0.625rem 0.875rem;border-top:1px solid #e2e8f0;background:#fff;">
                        <p class="text-xs text-slate-500 text-center sm:text-left sm:mr-auto w-full sm:w-auto">
                            Preview validated. Generate to create the paper.
                        </p>
                        <div class="flex flex-wrap gap-2 justify-end w-full sm:w-auto">
                            <button type="button"
                                    class="inline-flex h-9 flex-1 sm:flex-none items-center justify-center rounded-lg border border-slate-200 px-3.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                                    @click="closePreviewUi()">
                                Close
                            </button>
                            <button type="button"
                                    class="inline-flex h-9 flex-1 sm:flex-none items-center justify-center rounded-lg bg-brand-green px-3.5 text-sm font-semibold text-white hover:bg-brand-green-dark disabled:opacity-50"
                                    :disabled="!canGenerate"
                                    @click="submitAfterPreview()">
                                {{ $generateLabel }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function selfPaperBuilder(cfg) {
    return {
        subjects: cfg.subjects || [],
        markOptions: cfg.markOptions || [20, 30, 50, 70, 100],
        countsUrl: cfg.countsUrl,
        previewUrl: cfg.previewUrl,
        subjectId: cfg.oldSubject ? String(cfg.oldSubject) : '',
        chapterIds: Array.isArray(cfg.oldChapters) ? cfg.oldChapters.map(String) : [],
        topicIds: Array.isArray(cfg.oldTopics) ? cfg.oldTopics.map(String) : [],
        targetMarks: cfg.oldMarks || 20,
        chapters: [],
        topics: [],
        typeRows: [],
        plan: null,
        planError: '',
        loading: false,
        previewOk: false,
        previewOpen: false,
        previewQuestions: [],
        previewSections: [],
        previewBreakdown: [],
        previewMeta: {},

        get canPreview() {
            return !!(this.subjectId && this.chapterIds.length && this.targetMarks && this.plannedQuestions > 0);
        },

        get canGenerate() {
            return !!(this.previewOk && this.subjectId && this.chapterIds.length && this.targetMarks && this.plannedQuestions > 0 && !this.planError);
        },

        get plannedQuestions() {
            return this.typeRows.reduce((sum, row) => sum + Math.max(0, Number(row.planned || 0)), 0);
        },

        get plannedMarks() {
            return this.typeRows.reduce((sum, row) => (
                sum + (Math.max(0, Number(row.planned || 0)) * Math.max(1, Number(row.marks || 1)))
            ), 0);
        },

        init() {
            this.onSubjectChange(false);
            if (this.chapterIds.length) this.refreshTopicsFromChapters(false);
            if (this.chapterIds.length) this.refreshCounts();
        },

        invalidatePreview() {
            this.previewOk = false;
            this.previewOpen = false;
            this.previewQuestions = [];
            this.previewSections = [];
            this.previewBreakdown = [];
            this.previewMeta = {};
            document.body.classList.remove('overflow-hidden');
        },

        closePreviewUi() {
            this.previewOpen = false;
            document.body.classList.remove('overflow-hidden');
        },

        onSubjectChange(reset = true) {
            this.invalidatePreview();
            const subject = this.subjects.find(s => String(s.id) === String(this.subjectId));
            this.chapters = subject ? subject.chapters : [];
            if (reset) {
                this.chapterIds = [];
                this.topicIds = [];
                this.topics = [];
                this.typeRows = [];
                this.plan = null;
                this.planError = '';
            } else {
                this.chapterIds = this.chapterIds.filter(id => this.chapters.some(ch => String(ch.id) === String(id)));
                this.refreshTopicsFromChapters(false);
            }
        },

        onChaptersChange() {
            this.invalidatePreview();
            this.refreshTopicsFromChapters(true);
            this.refreshCounts();
        },

        refreshTopicsFromChapters(resetTopics = true) {
            const selected = this.chapters.filter(ch => this.chapterIds.includes(String(ch.id)));
            const map = new Map();
            selected.forEach(ch => {
                (ch.topics || []).forEach(tp => map.set(String(tp.id), tp));
            });
            this.topics = Array.from(map.values());
            if (resetTopics) {
                this.topicIds = [];
            } else {
                this.topicIds = this.topicIds.filter(id => this.topics.some(tp => String(tp.id) === String(id)));
            }
        },

        selectAllChapters() {
            this.invalidatePreview();
            this.chapterIds = this.chapters.map(ch => String(ch.id));
            this.refreshTopicsFromChapters(true);
            this.refreshCounts();
        },

        clearChapters() {
            this.invalidatePreview();
            this.chapterIds = [];
            this.topics = [];
            this.topicIds = [];
            this.typeRows = [];
            this.planError = '';
        },

        selectAllTopics() {
            this.invalidatePreview();
            this.topicIds = this.topics.map(tp => String(tp.id));
            this.refreshCounts();
        },

        clearTopics() {
            this.invalidatePreview();
            this.topicIds = [];
            this.refreshCounts();
        },

        onPlannedInput(row) {
            this.invalidatePreview();
            const available = Math.max(0, Number(row.available || 0));
            const value = Math.max(0, Number(row.planned || 0));
            row.planned = Math.min(value, available);
            this.planError = '';
            if (this.plannedQuestions === 0 && this.typeRows.length) {
                this.planError = 'Set at least one question in "In this paper".';
            }
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || document.querySelector('input[name="_token"]')?.value
                || '';
        },

        typeCountsPayload() {
            const counts = {};
            this.typeRows.forEach(row => {
                counts[row.type] = Math.max(0, Number(row.planned || 0));
            });
            return counts;
        },

        async refreshCounts() {
            this.invalidatePreview();
            this.plan = null;
            this.planError = '';
            if (!this.subjectId || !this.chapterIds.length || !this.targetMarks) {
                this.typeRows = [];
                return;
            }
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.set('subject_id', this.subjectId);
                params.set('target_marks', this.targetMarks);
                (this.chapterIds || []).forEach(id => {
                    if (id) params.append('chapter_ids[]', id);
                });
                (this.topicIds || []).forEach(id => {
                    if (id) params.append('topic_ids[]', id);
                });

                const res = await fetch(this.countsUrl + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                const planned = data.plan?.type_counts || {};
                this.typeRows = (data.types || [])
                    .filter(t => Number(t.available || 0) > 0)
                    .map(t => ({
                    ...t,
                    planned: planned[t.type] ?? 0,
                }));
                this.plan = data.plan || null;
                this.planError = data.error || '';
                if (!this.plan && !this.planError && (data.total === 0)) {
                    this.planError = 'No questions available for these chapters/topics.';
                }
                if (!this.planError && this.plannedQuestions === 0 && data.total > 0) {
                    this.planError = 'Set at least one question in "In this paper".';
                }
            } catch (e) {
                this.planError = 'Could not load question counts.';
            } finally {
                this.loading = false;
            }
        },

        async runPreview() {
            this.planError = '';
            this.invalidatePreview();

            if (!this.canPreview) {
                this.planError = 'Select subject, chapter(s), marks and set question counts first.';
                return;
            }

            const over = this.typeRows.filter(row => Number(row.planned || 0) > Number(row.available || 0));
            if (over.length) {
                this.planError = 'Not enough questions available: ' + over.map(r => `${r.label} (need ${r.planned}, available ${r.available})`).join('; ');
                return;
            }
            if (this.plannedQuestions <= 0) {
                this.planError = 'Set at least one question in "In this paper" before preview.';
                return;
            }

            this.loading = true;
            try {
                const body = {
                    subject_id: Number(this.subjectId),
                    chapter_ids: (this.chapterIds || []).map(Number).filter(Boolean),
                    topic_ids: (this.topicIds || []).map(Number).filter(Boolean),
                    target_marks: Number(this.targetMarks),
                    type_counts: this.typeCountsPayload(),
                };

                const res = await fetch(this.previewUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();

                if (!data.ok) {
                    this.planError = data.error || 'Preview failed. Check available questions.';
                    this.previewOk = false;
                    return;
                }

                this.previewOk = true;
                this.previewQuestions = data.questions || [];
                this.previewSections = data.sections || [];
                this.previewBreakdown = data.breakdown || [];
                this.previewMeta = {
                    chapter_name: data.chapter_name || '',
                    topic_label: data.topic_label || '',
                    total_questions: data.total_questions || 0,
                    total_marks: data.total_marks || 0,
                    subject_name: data.subject_name || '',
                    class_name: data.class_name || '',
                    school_name: data.school_name || 'Gses Chaturji',
                    logo_letter: data.logo_letter || 'G',
                    logo_url: data.logo_url || '',
                    paper_title: data.paper_title || 'Paper Preview',
                };
                this.previewOpen = true;
                this.planError = '';
                document.body.classList.add('overflow-hidden');
            } catch (e) {
                this.planError = 'Could not build preview. Please try again.';
                this.previewOk = false;
            } finally {
                this.loading = false;
            }
        },

        submitAfterPreview() {
            if (!this.canGenerate) {
                this.planError = 'Please Preview first and fix any availability errors.';
                return;
            }
            this.closePreviewUi();
            this.$root.querySelector('form')?.requestSubmit();
        },
    }
}
</script>
