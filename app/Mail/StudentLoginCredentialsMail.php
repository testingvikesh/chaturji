<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentLoginCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $student,
        public string $plainPassword,
        public string $loginUrl,
        public string $appUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your '.config('app.name', 'Gses Chaturji').' student login details',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student-login-credentials',
            with: [
                'student' => $this->student,
                'plainPassword' => $this->plainPassword,
                'loginUrl' => $this->loginUrl,
                'appUrl' => $this->appUrl,
                'username' => $this->student->mobile ?: $this->student->email,
            ],
        );
    }
}
