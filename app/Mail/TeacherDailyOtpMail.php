<?php

namespace App\Mail;

use App\Models\TeacherDailyOtp;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeacherDailyOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $teacher,
        public TeacherDailyOtp $otp,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Today\'s login OTP — '.$this->otpDateLabel(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.teacher-daily-otp',
            with: [
                'teacher' => $this->teacher,
                'otp' => $this->otp,
                'otpDate' => $this->otpDateLabel(),
                'loginUrl' => route('teacher.login'),
            ],
        );
    }

    private function otpDateLabel(): string
    {
        return $this->otp->otp_date?->format('d M Y') ?: now()->format('d M Y');
    }
}
