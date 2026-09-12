<?php

namespace App\Notifications;

use App\Models\Exam;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExamPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(public Exam $exam, public string $action = 'created') {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'exam',
            'action' => $this->action,
            'exam_id' => $this->exam->id,
            'title' => $this->exam->title,
            'message' => $this->action === 'created'
                ? "New exam published: {$this->exam->title}"
                : "Exam updated: {$this->exam->title}",
            'url' => route('student.exams.show', $this->exam),
        ];
    }
}
