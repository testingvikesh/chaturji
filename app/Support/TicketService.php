<?php

namespace App\Support;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Notifications\TicketRepliedNotification;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function __construct(private TicketMailer $mailer) {}

    public function create(User $user, array $data): Ticket
    {
        $ticket = DB::transaction(function () use ($user, $data) {
            return Ticket::query()->create([
                'ticket_no' => Ticket::nextNumber(),
                'user_id' => $user->id,
                'role' => in_array($user->role, ['student', 'teacher'], true) ? $user->role : 'student',
                'category' => $data['category'],
                'subject' => $data['subject'],
                'message' => $data['message'],
                'status' => 'open',
            ]);
        });

        $this->mailer->created($ticket->load('user'));

        return $ticket;
    }

    public function reply(Ticket $ticket, User $user, string $message, bool $asAdmin = false): TicketReply
    {
        $reply = DB::transaction(function () use ($ticket, $user, $message, $asAdmin) {
            $reply = TicketReply::query()->create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'is_admin' => $asAdmin,
                'message' => $message,
            ]);

            $ticket->update([
                'status' => $asAdmin ? 'answered' : 'open',
                'last_replied_at' => now(),
            ]);

            if ($asAdmin && $ticket->user && (int) $ticket->user_id !== (int) $user->id) {
                $ticket->user->notify(new TicketRepliedNotification($ticket->fresh()));
            }

            return $reply;
        });

        $this->mailer->replied($ticket->fresh('user'), $reply);

        return $reply;
    }

    public function close(Ticket $ticket): void
    {
        $ticket->update(['status' => 'closed']);
    }

    public function reopen(Ticket $ticket): void
    {
        $ticket->update(['status' => 'open']);
    }
}
