<?php

namespace App\Console\Commands;

use App\Support\TeacherOtpService;
use Illuminate\Console\Command;

class GenerateTeacherDailyOtpCommand extends Command
{
    protected $signature = 'teachers:generate-daily-otp {--force : Replace today\'s OTP for every approved teacher} {--email : Also email OTPs (off by default)}';

    protected $description = 'Create a 4-digit login OTP for each approved teacher for today and send it to Notifications';

    public function handle(): int
    {
        $count = TeacherOtpService::generateForApprovedTeachers(null, (bool) $this->option('force'));

        $this->info('Teacher login OTPs ready for '.now()->toDateString().' ('.$count.' teacher'.($count === 1 ? '' : 's').').');

        $notify = TeacherOtpService::notifyTodayOtpsToApprovedTeachers();
        $this->info('OTP notifications sent: '.$notify['notified'].' · skipped: '.$notify['skipped']);

        if ($this->option('email')) {
            $mail = TeacherOtpService::emailTodayOtpsToApprovedTeachers();
            $this->info('OTP emails sent: '.$mail['sent'].' · skipped (no email): '.$mail['skipped'].' · failed: '.$mail['failed']);
        } else {
            $this->info('OTP email skipped (use --email to send mail).');
        }

        return self::SUCCESS;
    }
}
