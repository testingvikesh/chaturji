<?php

namespace App\Support;

use App\Mail\TicketAdminAlertMail;
use App\Mail\TicketGeneratedMail;
use App\Mail\TicketReplyMail;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TicketMailer
{
    public function created(Ticket $ticket): void
    {
        $ticket->loadMissing('user');

        $userEmail = $this->validEmail($ticket->user?->email);
        if ($userEmail) {
            $this->send($userEmail, new TicketGeneratedMail($ticket));
        }

        $adminEmail = MailConfig::adminEmail();
        if ($adminEmail) {
            $this->send($adminEmail, new TicketAdminAlertMail($ticket));
        }
    }

    public function replied(Ticket $ticket, TicketReply $reply): void
    {
        $ticket->loadMissing('user');

        if ($reply->is_admin) {
            $userEmail = $this->validEmail($ticket->user?->email);
            if ($userEmail) {
                $this->send($userEmail, new TicketReplyMail($ticket, $reply, false));
            }

            return;
        }

        $adminEmail = MailConfig::adminEmail();
        if ($adminEmail) {
            $this->send($adminEmail, new TicketReplyMail($ticket, $reply, true));
        }
    }

    private function send(string $email, object $mailable): void
    {
        try {
            MailConfig::apply();
            Mail::to($email)->send($mailable);
        } catch (Throwable $e) {
            EmailLogRecorder::markLastFailed($e, $email, null, $mailable::class);
            Log::warning('Ticket mail failed', [
                'to' => $email,
                'mailable' => $mailable::class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function validEmail(?string $email): ?string
    {
        $email = trim((string) $email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}
