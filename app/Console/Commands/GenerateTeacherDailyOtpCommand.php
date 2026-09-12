<?php

namespace App\Console\Commands;

use App\Support\TeacherOtpService;
use Illuminate\Console\Command;

class GenerateTeacherDailyOtpCommand extends Command
{
    protected $signature = 'teachers:generate-daily-otp {--force : Replace today\'s OTP for every approved teacher} {--no-email : Generate OTP without sending emails}';

    protected $description = 'Create a 4-digit login OTP for each approved teacher for today and email it';

    public function handle(): int
    {
        $count = TeacherOtpService::generateForApprovedTeachers(null, (bool) $this->option('force'));

        $this->info('Teacher login OTPs ready for '.now()->toDateString().' ('.$count.' teacher'.($count === 1 ? '' : 's').').');

        if ($this->option('no-email')) {
            return self::SUCCESS;
        }

        $mail = TeacherOtpService::emailTodayOtpsToApprovedTeachers();

        $this->info('OTP emails sent: '.$mail['sent'].' · skipped (no email): '.$mail['skipped'].' · failed: '.$mail['failed']);

        return self::SUCCESS;
    }
}
