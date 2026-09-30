<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PrincipalLoginCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $principal,
        public string $plainPassword,
        public string $loginUrl,
        public string $appUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your '.config('app.name', 'Gses Chaturji').' principal login details',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.principal-login-credentials',
            with: [
                'principal' => $this->principal,
                'plainPassword' => $this->plainPassword,
                'loginUrl' => $this->loginUrl,
                'appUrl' => $this->appUrl,
            ],
        );
    }
}
