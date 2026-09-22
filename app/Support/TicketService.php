<?php

namespace App\Support;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketReply;
use App\Models\User;
use App\Notifications\TicketRepliedNotification;
use App\Support\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function __construct(private TicketMailer $mailer) {}

    /**
     * @param  list<UploadedFile>  $files
     */
    public function create(User $user, array $data, array $files = []): Ticket
    {
        $ticket = DB::transaction(function () use ($user, $data, $files) {
            $ticket = Ticket::query()->create([
                'ticket_no' => Ticket::nextNumber(),
                'user_id' => $user->id,
                'role' => in_array($user->role, ['student', 'teacher'], true) ? $user->role : 'student',
                'category' => $data['category'],
                'medium' => $data['medium'] ?? null,
                'standard_id' => $data['standard_id'] ?? null,
                'subject_id' => $data['subject_id'] ?? null,
                'chapter_id' => $data['chapter_id'] ?? null,
                'chapter_name' => $data['chapter_name'] ?? null,
                'chapter_no' => $data['chapter_no'] ?? null,
                'subject' => $data['subject'],
                'message' => $data['message'],
                'status' => 'open',
            ]);

            $this->storeAttachments($ticket, $user, $files);

            return $ticket;
        });

        $this->mailer->created($ticket->load(['user', 'attachments', 'standard', 'curriculumSubject']));

        ActivityLogger::log(
            'ticket.create',
            'Created ticket '.$ticket->ticket_no,
            $ticket,
            [
                'ticket_no' => $ticket->ticket_no,
                'category' => $ticket->category,
                'subject' => $ticket->subject,
            ],
            $user
        );

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

        ActivityLogger::log(
            'ticket.reply',
            ($asAdmin ? 'Admin' : 'User').' replied to ticket '.$ticket->ticket_no,
            $ticket,
            [
                'ticket_no' => $ticket->ticket_no,
                'as_admin' => $asAdmin,
            ],
            $user
        );

        return $reply;
    }

    public function close(Ticket $ticket): void
    {
        $ticket->update(['status' => 'closed']);

        ActivityLogger::log(
            'ticket.status',
            'Closed ticket '.$ticket->ticket_no,
            $ticket,
            [
                'ticket_no' => $ticket->ticket_no,
                'status' => 'closed',
            ]
        );
    }

    public function reopen(Ticket $ticket): void
    {
        $ticket->update(['status' => 'open']);

        ActivityLogger::log(
            'ticket.status',
            'Reopened ticket '.$ticket->ticket_no,
            $ticket,
            [
                'ticket_no' => $ticket->ticket_no,
                'status' => 'open',
            ]
        );
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    private function storeAttachments(Ticket $ticket, User $user, array $files): void
    {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = $file->store('tickets/'.$ticket->id, 'public');

            if (! $path) {
                continue;
            }

            TicketAttachment::query()->create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType() ?: $file->getMimeType(),
                'size' => (int) $file->getSize(),
            ]);
        }
    }
}
