<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Hostinger: afterResponse jobs often die; cron finishes pending sheet checks.
        $schedule->command('homework:process-pending-sheets --limit=8')
            ->everyMinute()
            ->withoutOverlapping(5);

        // Daily teacher OTP → in-app Notifications at 1:00 AM India time (no email).
        $schedule->command('teachers:generate-daily-otp --force')
            ->dailyAt('01:00')
            ->timezone('Asia/Kolkata')
            ->withoutOverlapping(30)
            ->appendOutputTo(storage_path('logs/teacher-daily-otp.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
