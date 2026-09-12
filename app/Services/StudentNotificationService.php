<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ExamPublishedNotification;
use App\Notifications\HomeworkAssignedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

class StudentNotificationService
{
    public static function notifyStandard(string $standard, Notification $notification): void
    {
        User::query()
            ->students()
            ->approved()
            ->where('standard', $standard)
            ->each(fn (User $student) => $student->notify($notification));
    }

    public static function examPublished(Model $exam, string $action = 'created'): void
    {
        self::notifyStandard($exam->standard, new ExamPublishedNotification($exam, $action));
    }

    public static function homeworkAssigned(Model $homework, string $action = 'created'): void
    {
        self::notifyStandard($homework->standard, new HomeworkAssignedNotification($homework, $action));
    }
}
