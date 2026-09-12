<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketGeneratedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticket generated: '.$this->ticket->ticket_no,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-generated',
            with: [
                'ticket' => $this->ticket,
                'ticketUrl' => $this->ticketUrl(),
            ],
        );
    }

    private function ticketUrl(): string
    {
        $name = $this->ticket->role === 'teacher' ? 'teacher.tickets.show' : 'student.tickets.show';

        return route($name, $this->ticket);
    }
}
