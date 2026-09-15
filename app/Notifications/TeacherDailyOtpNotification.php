<?php

namespace App\Notifications;

use App\Models\TeacherDailyOtp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TeacherDailyOtpNotification extends Notification
{
    use Queueable;

    public function __construct(public TeacherDailyOtp $otp) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $date = optional($this->otp->otp_date)->format('d M Y') ?: now()->format('d M Y');

        return [
            'type' => 'otp',
            'otp' => $this->otp->otp,
            'otp_date' => optional($this->otp->otp_date)?->toDateString() ?: now()->toDateString(),
            'title' => "Today's login OTP",
            'message' => 'Your 4-digit login OTP for '.$date.' is '.$this->otp->otp.'. Use it instead of your password today.',
            'url' => route('teacher.notifications.index'),
        ];
    }
}
