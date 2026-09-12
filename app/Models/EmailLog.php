<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    public const STATUSES = [
        'sent' => 'Sent',
        'failed' => 'Failed',
        'sending' => 'Sending',
    ];

    protected $fillable = [
        'to_email',
        'cc_email',
        'bcc_email',
        'from_email',
        'subject',
        'mailable',
        'mailer',
        'status',
        'body_html',
        'body_text',
        'error',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function typeLabel(): string
    {
        $class = class_basename((string) $this->mailable);

        return match ($class) {
            'TicketGeneratedMail' => 'Ticket generated',
            'TicketReplyMail' => 'Ticket reply',
            'TicketAdminAlertMail' => 'New ticket alert',
            'MailSetupTestMail' => 'Test mail',
            'ResetPassword' => 'Password reset',
            '' => 'Email',
            default => str_replace(['Mail', 'Notification'], '', $class) ?: 'Email',
        };
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }
}
