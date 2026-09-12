<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketRepliedNotification extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $route = ($notifiable->role ?? '') === 'teacher'
            ? 'teacher.tickets.show'
            : 'student.tickets.show';

        return [
            'type' => 'ticket',
            'ticket_id' => $this->ticket->id,
            'title' => $this->ticket->ticket_no,
            'message' => 'Admin replied to your ticket '.$this->ticket->ticket_no,
            'url' => route($route, $this->ticket),
        ];
    }
}
