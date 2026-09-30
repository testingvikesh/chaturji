<x-principal-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Reports</span>
            <h2 class="admin-page-title">Logout Report Detail</h2>
            <p class="admin-page-subtitle">{{ $report->teacher?->name }} · {{ $report->report_date?->format('d M Y') }}</p>
        </div>
    </x-slot>

    <div class="admin-page max-w-3xl space-y-4">
        @include('principal.partials.reports-nav')

        <div class="mb-2 report-print-hide">
            <a href="{{ route('principal.reports.logout-reports') }}" class="text-sm text-brand-green hover:underline font-medium">&larr; Back to Logout Reports</a>
        </div>

        <div class="admin-card p-5 space-y-3">
            <div class="admin-card-top"></div>
            <div class="grid sm:grid-cols-2 gap-3 text-sm">
                <p><span class="text-slate-500">Employee:</span> <span class="font-semibold">{{ $report->employee_code }} · {{ $report->teacher?->name }}</span></p>
                <p><span class="text-slate-500">Submitted:</span> <span class="font-semibold">{{ $report->submitted_at?->format('d M Y, h:i A') }}</span></p>
                <p><span class="text-slate-500">Medium:</span> <span class="font-semibold capitalize">{{ $report->medium ?: '—' }}</span></p>
                <p><span class="text-slate-500">Standard:</span> <span class="font-semibold">{{ $report->standard ?: '—' }}</span></p>
                <p><span class="text-slate-500">Subject:</span> <span class="font-semibold">{{ $report->subject_name ?: '—' }}</span></p>
                <p><span class="text-slate-500">Period:</span> <span class="font-semibold">{{ $report->period_label ?: '—' }}@if($report->section) · Sec {{ $report->section }}@endif</span></p>
                <p><span class="text-slate-500">Chapter:</span> <span class="font-semibold">{{ $report->chapter_name ?: '—' }}</span></p>
                <p class="sm:col-span-2"><span class="text-slate-500">Topic:</span> <span class="font-semibold">{{ $report->topic_names ?: ($report->topic_name ?: '—') }}</span></p>
                <p><span class="text-slate-500">Status:</span> <span class="font-semibold">{{ $report->statusLabel() }}</span></p>
                <p><span class="text-slate-500">Mail:</span> <span class="font-semibold">{{ $report->mail_sent ? 'Sent' : 'Not sent' }}</span></p>
                <p class="sm:col-span-2"><span class="text-slate-500">Checked:</span> <span class="font-semibold">{{ implode(' · ', $report->checkedLabels()) ?: '—' }}</span></p>
                @if ($report->notes)
                    <p class="sm:col-span-2"><span class="text-slate-500">Notes:</span> <span class="font-semibold whitespace-pre-line">{{ $report->notes }}</span></p>
                @endif
            </div>
        </div>
    </div>
</x-principal-layout>
