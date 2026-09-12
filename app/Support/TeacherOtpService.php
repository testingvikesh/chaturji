<?php

namespace App\Support;

use App\Mail\TeacherDailyOtpMail;
use App\Models\TeacherDailyOtp;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TeacherOtpService
{
    public static function today(): Carbon
    {
        return now()->startOfDay();
    }

    public static function generateCode(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    public static function ensureForTeacher(User $teacher, ?CarbonInterface $date = null, bool $force = false): TeacherDailyOtp
    {
        $otpDate = ($date ?? self::today())->toDateString();

        $existing = TeacherDailyOtp::query()
            ->where('user_id', $teacher->id)
            ->whereDate('otp_date', $otpDate)
            ->first();

        if ($existing && ! $force) {
            return $existing;
        }

        if ($existing && $force) {
            $existing->update(['otp' => self::generateCode()]);

            return $existing->fresh();
        }

        return TeacherDailyOtp::query()->create([
            'user_id' => $teacher->id,
            'otp' => self::generateCode(),
            'otp_date' => $otpDate,
        ]);
    }

    public static function generateForApprovedTeachers(?CarbonInterface $date = null, bool $force = false): int
    {
        $count = 0;

        User::teachers()
            ->approved()
            ->orderBy('id')
            ->each(function (User $teacher) use ($date, $force, &$count) {
                self::ensureForTeacher($teacher, $date, $force);
                $count++;
            });

        return $count;
    }

    /**
     * @return array{sent:int, skipped:int, failed:int}
     */
    public static function emailTodayOtpsToApprovedTeachers(): array
    {
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        User::teachers()
            ->approved()
            ->orderBy('id')
            ->each(function (User $teacher) use (&$sent, &$skipped, &$failed) {
                $result = self::emailTodayOtpToTeacher($teacher);

                if ($result === 'sent') {
                    $sent++;
                } elseif ($result === 'failed') {
                    $failed++;
                } else {
                    $skipped++;
                }
            });

        return compact('sent', 'skipped', 'failed');
    }

    public static function emailTodayOtpToTeacher(User $teacher): string
    {
        $email = trim((string) $teacher->email);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'skipped';
        }

        $otp = self::ensureForTeacher($teacher);

        try {
            MailConfig::apply();
            Mail::to($email)->send(new TeacherDailyOtpMail($teacher, $otp));

            return 'sent';
        } catch (Throwable $e) {
            EmailLogRecorder::markLastFailed($e, $email, 'Today\'s login OTP', TeacherDailyOtpMail::class);
            Log::warning('Teacher OTP mail failed', [
                'to' => $email,
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage(),
            ]);

            return 'failed';
        }
    }

    public static function todayFor(User $teacher): ?TeacherDailyOtp
    {
        if ($teacher->role !== 'teacher') {
            return null;
        }

        return TeacherDailyOtp::query()
            ->where('user_id', $teacher->id)
            ->whereDate('otp_date', self::today())
            ->first();
    }

    public static function matches(User $teacher, string $code): bool
    {
        if ($teacher->role !== 'teacher' || ! $teacher->isApproved()) {
            return false;
        }

        $code = trim($code);

        if (! preg_match('/^\d{4}$/', $code)) {
            return false;
        }

        $row = self::todayFor($teacher);

        return $row !== null && hash_equals($row->otp, $code);
    }

    public static function cronToken(): string
    {
        return substr(hash_hmac('sha256', 'teacher-daily-otp', (string) config('app.key')), 0, 24);
    }

    public static function cronUrl(): string
    {
        return url('/cron/teacher-otp/'.self::cronToken());
    }
}
