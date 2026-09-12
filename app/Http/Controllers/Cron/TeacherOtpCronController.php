<?php

namespace App\Http\Controllers\Cron;

use App\Http\Controllers\Controller;
use App\Support\TeacherOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherOtpCronController extends Controller
{
    public function __invoke(Request $request, string $token): JsonResponse
    {
        abort_unless(hash_equals(TeacherOtpService::cronToken(), $token), 404);

        $force = $request->boolean('force');
        $count = TeacherOtpService::generateForApprovedTeachers(null, $force);
        $mail = $request->boolean('no_email')
            ? ['sent' => 0, 'skipped' => 0, 'failed' => 0]
            : TeacherOtpService::emailTodayOtpsToApprovedTeachers();

        return response()->json([
            'ok' => true,
            'date' => now()->toDateString(),
            'teachers' => $count,
            'force' => $force,
            'emails_sent' => $mail['sent'],
            'emails_skipped' => $mail['skipped'],
            'emails_failed' => $mail['failed'],
            'message' => $force
                ? 'New 4-digit OTPs generated and emailed for today.'
                : 'Today\'s 4-digit OTPs are ready and emailed.',
        ]);
    }
}