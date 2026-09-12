<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public TicketReply $reply,
        public bool $toAdmin = false,
    ) {}

    public function envelope(): Envelope
    {
        $who = $this->toAdmin ? 'User replied' : 'Admin replied';

        return new Envelope(
            subject: $who.' on '.$this->ticket->ticket_no,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-reply',
            with: [
                'ticket' => $this->ticket,
                'reply' => $this->reply,
                'toAdmin' => $this->toAdmin,
                'ticketUrl' => $this->ticketUrl(),
            ],
        );
    }

    private function ticketUrl(): string
    {
        if ($this->toAdmin) {
            return route('admin.tickets.show', $this->ticket);
        }

        $name = $this->ticket->role === 'teacher' ? 'teacher.tickets.show' : 'student.tickets.show';

        return route($name, $this->ticket);
    }
}
