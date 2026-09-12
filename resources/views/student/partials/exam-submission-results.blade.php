@props(['submission', 'paperLabel' => 'exam'])

@php
    $evaluation = $submission?->evaluation ?? null;
    $summary = $evaluation['summary'] ?? null;
    $questions = $evaluation['questions'] ?? [];
    $paperTotal = $summary['paper_total_questions'] ?? $summary['exam_total_questions'] ?? null;
    $lang = \App\Support\PaperLanguage::normalize((string) ($summary['language'] ?? 'en'));
    $L = \App\Support\PaperLanguage::labels($lang);
    $emojiRaw = (string) ($summary['appreciation_emoji'] ?? '🎉');
    $emoji = match (strtolower(trim($emojiRaw))) {
        'trophy', 'award' => '🏆',
        'party', 'celebrate', 'celebration' => '🎉',
        'thumb', 'thumbs', 'thumbs_up' => '👍',
        'strong', 'muscle', 'flex' => '💪',
        default => $emojiRaw !== '' ? $emojiRaw : '🎉',
    };
    $checkedN = str_replace(':n', (string) ($summary['questions_checked'] ?? count($questions)), $L['ui_checked_n']);
    $paperTotalLine = ($paperTotal && $paperTotal > ($summary['questions_checked'] ?? 0))
        ? str_replace([':paper', ':total'], [$paperLabel, (string) $paperTotal], $L['ui_paper_total'])
        : '';
@endphp

@if ($submission && $submission->status === 'processing')
    <div class="admin-card border border-amber-200 bg-amber-50/70 p-5 sm:p-6" x-data x-init="setTimeout(() => window.location.reload(), 8000)">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 h-5 w-5 animate-spin rounded-full border-2 border-amber-400 border-t-transparent"></div>
            <div>
                <p class="font-semibold text-amber-900">Teacher check in progress…</p>
                <p class="mt-1 text-sm text-amber-800">
                    Your PDF was uploaded. AI is reading answers and preparing the checked sheet.
                    This page refreshes automatically in a few seconds.
                </p>
            </div>
        </div>
    </div>
@elseif ($submission && $submission->status === 'failed')
    <div class="admin-card border border-red-200 bg-red-50/60 p-5 sm:p-6">
        <p class="font-semibold text-red-700">{{ $L['ui_check_failed'] }}</p>
        <p class="mt-1 text-sm text-red-600">{{ $submission->error_message }}</p>
    </div>
@elseif ($submission && $submission->isCompleted() && $summary)
    <div class="space-y-5" lang="{{ $lang === 'gu' ? 'gu' : ($lang === 'hi' ? 'hi' : 'en') }}">
        <div class="admin-card overflow-hidden">
            <div class="admin-card-top"></div>
            <div class="bg-gradient-to-br from-amber-50 via-white to-brand-green-50 px-5 py-6 sm:px-8 text-center">
                <div class="text-5xl leading-none">{{ $emoji }}</div>
                <h3 class="mt-3 text-xl sm:text-2xl font-bold text-slate-900">{{ $summary['appreciation_message'] ?? $L['keep_good'] }}</h3>
                <p class="mt-2 text-sm text-slate-600">
                    {{ $checkedN }}
                    @if ($paperTotalLine !== '')
                        {{ $paperTotalLine }}
                    @endif
                </p>
                <div class="mt-5 inline-flex flex-wrap items-center justify-center gap-3">
                    <span class="rounded-full bg-brand-green px-4 py-2 text-sm font-bold text-white">
                        {{ $L['ui_score'] }}: {{ $summary['total_score'] ?? 0 }} / {{ $summary['max_score'] ?? 0 }}
                    </span>
                    <span class="rounded-full bg-brand-gold/20 px-4 py-2 text-sm font-bold text-brand-green-dark ring-1 ring-brand-gold/30">
                        {{ $summary['percentage'] ?? 0 }}%
                    </span>
                    @if (! empty($summary['grade']))
                        <span class="rounded-full bg-red-50 px-4 py-2 text-sm font-bold text-red-700 ring-1 ring-red-200">
                            {{ $L['grade'] }}: {{ $summary['grade'] }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Notebook red-pen checked paper --}}
        <div class="admin-card overflow-hidden border border-red-100">
            <div class="border-b border-red-100 bg-red-50/50 px-5 py-4 sm:px-6">
                <p class="text-xs font-semibold uppercase tracking-wide text-red-600">{{ $L['ui_facility'] }}</p>
                <h4 class="mt-1 text-lg font-bold text-slate-900">{{ $L['ui_sheet_title'] }}</h4>
                <p class="text-sm text-slate-600">{{ $L['ui_sheet_desc'] }}</p>
            </div>

            <div class="p-4 sm:p-5">
                @if ($submission->correctedSheetUrl())
                    <a href="{{ $submission->correctedSheetUrl() }}" target="_blank" class="block overflow-hidden rounded-xl border-2 border-red-200 bg-white shadow-sm hover:shadow-md transition">
                        <img
                            src="{{ $submission->correctedSheetUrl() }}"
                            alt="{{ $L['ui_sheet_title'] }}"
                            class="w-full max-h-[980px] object-contain bg-white"
                        >
                        <p class="border-t border-red-100 px-4 py-3 text-sm font-semibold text-red-700">
                            {{ $L['ui_open_sheet'] }}
                        </p>
                    </a>
                @elseif ($submission->uploadedSheetUrl())
                    <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-4">
                        <p class="text-sm font-semibold text-amber-800">{{ $L['ui_edited_missing'] }}</p>
                        <a href="{{ $submission->uploadedSheetUrl() }}" target="_blank" class="mt-3 inline-block text-sm font-semibold text-brand-green hover:underline">
                            {{ $L['ui_view_upload'] }}
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="admin-card overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h4 class="font-bold text-slate-900">{{ $L['ui_question_wise'] }}</h4>
            </div>
            <div class="space-y-3 px-5 py-5 sm:px-6">
                @foreach ($questions as $row)
                    @php $ok = (int) ($row['score_awarded'] ?? 0) > 0; @endphp
                    <div class="rounded-xl border px-4 py-4 {{ $ok ? 'border-brand-green-100 bg-brand-green-50/30' : 'border-red-100 bg-red-50/20' }}">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-semibold text-slate-900">
                                {{ \App\Support\PaperLanguage::answerHeading($lang, (int) ($row['question_number'] ?? 0)) }}
                                <span class="ml-2 text-sm {{ $ok ? 'text-brand-green' : 'text-red-600' }}">
                                    {{ $ok ? '✓' : 'X' }} {{ $row['score_awarded'] ?? 0 }}/{{ $row['max_score'] ?? 1 }}
                                </span>
                            </p>
                        </div>
                        <div class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                            <div class="rounded-lg bg-white/90 px-3 py-2">
                                <p class="text-xs text-slate-500">{{ $L['ui_your_answer'] }}</p>
                                <p class="text-slate-800">{{ $row['student_answer'] ?? '—' }}</p>
                            </div>
                            <div class="rounded-lg bg-white/90 px-3 py-2">
                                <p class="text-xs text-slate-500">{{ $L['correct_answer'] }}</p>
                                <p class="text-slate-800">{{ $row['correct_answer'] ?? '—' }}</p>
                            </div>
                        </div>
                        @if (! empty($row['teacher_comment']) || ! empty($row['feedback']))
                            <p class="mt-2 text-sm text-red-700">
                                <span class="font-semibold">{{ $L['teachers_comment'] }}:</span>
                                {{ $row['teacher_comment'] ?? $row['feedback'] }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
