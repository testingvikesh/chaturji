<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketAdminAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New '.$this->ticket->role.' ticket '.$this->ticket->ticket_no,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-admin-alert',
            with: [
                'ticket' => $this->ticket,
                'ticketUrl' => route('admin.tickets.show', $this->ticket),
            ],
        );
    }
}
