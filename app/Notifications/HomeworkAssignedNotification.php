<?php

namespace App\Notifications;

use App\Models\Homework;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class HomeworkAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public Homework $homework, public string $action = 'created') {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'homework',
            'action' => $this->action,
            'homework_id' => $this->homework->id,
            'title' => $this->homework->title,
            'message' => $this->action === 'created'
                ? "New homework assigned: {$this->homework->title}"
                : "Homework updated: {$this->homework->title}",
            'url' => route('student.homework.show', $this->homework),
        ];
    }
}
