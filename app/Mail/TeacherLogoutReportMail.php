<?php

namespace App\Mail;

use App\Models\TeacherLogoutReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeacherLogoutReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TeacherLogoutReport $report) {}

    public function envelope(): Envelope
    {
        $teacher = $this->report->teacher?->name ?? 'Teacher';

        return new Envelope(
            subject: 'Teacher logout report · '.$teacher.' · '.$this->report->report_date?->format('d M Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.teacher-logout-report',
            with: [
                'report' => $this->report,
                'adminUrl' => route('admin.reports.logout-reports'),
            ],
        );
    }
}
