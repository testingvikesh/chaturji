<p style="font-family: Arial, sans-serif; font-size: 14px; color: #0f172a;">
    <strong>Teacher logout work report</strong>
</p>

<table style="font-family: Arial, sans-serif; font-size: 13px; border-collapse: collapse; width: 100%; max-width: 560px;">
    <tr><td style="padding: 6px 0; color: #64748b;">Teacher</td><td style="padding: 6px 0;">{{ $report->teacher?->name }} ({{ $report->employee_code }})</td></tr>
    <tr><td style="padding: 6px 0; color: #64748b;">Date</td><td style="padding: 6px 0;">{{ $report->report_date?->format('d M Y') }}</td></tr>
    <tr><td style="padding: 6px 0; color: #64748b;">Medium</td><td style="padding: 6px 0;">{{ $report->medium ?: '—' }}</td></tr>
    <tr><td style="padding: 6px 0; color: #64748b;">Standard</td><td style="padding: 6px 0;">{{ $report->standard ?: '—' }}</td></tr>
    <tr><td style="padding: 6px 0; color: #64748b;">Subject</td><td style="padding: 6px 0;">{{ $report->subject_name ?: '—' }}</td></tr>
    <tr><td style="padding: 6px 0; color: #64748b;">Chapter</td><td style="padding: 6px 0;">{{ $report->chapter_name ?: '—' }}</td></tr>
    <tr><td style="padding: 6px 0; color: #64748b;">Topic</td><td style="padding: 6px 0;">{{ $report->topic_name ?: '—' }}</td></tr>
    <tr><td style="padding: 6px 0; color: #64748b;">Checked</td><td style="padding: 6px 0;">{{ implode(', ', $report->checkedLabels()) ?: '—' }}</td></tr>
    <tr><td style="padding: 6px 0; color: #64748b;">Status</td><td style="padding: 6px 0;">{{ $report->statusLabel() }}</td></tr>
    @if ($report->notes)
        <tr><td style="padding: 6px 0; color: #64748b;">Notes</td><td style="padding: 6px 0;">{{ $report->notes }}</td></tr>
    @endif
</table>

<p style="font-family: Arial, sans-serif; font-size: 13px; margin-top: 16px;">
    <a href="{{ $adminUrl }}">View logout reports in admin</a>
</p>
