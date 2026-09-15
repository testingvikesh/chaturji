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
        $notify = TeacherOtpService::notifyTodayOtpsToApprovedTeachers();

        // Email is opt-in only: ?email=1
        $mail = $request->boolean('email')
            ? TeacherOtpService::emailTodayOtpsToApprovedTeachers()
            : ['sent' => 0, 'skipped' => 0, 'failed' => 0];

        return response()->json([
            'ok' => true,
            'date' => now()->toDateString(),
            'teachers' => $count,
            'force' => $force,
            'notifications_sent' => $notify['notified'],
            'emails_sent' => $mail['sent'],
            'emails_skipped' => $mail['skipped'],
            'emails_failed' => $mail['failed'],
            'message' => $force
                ? 'New 4-digit OTPs generated and sent to Notifications (email off by default).'
                : 'Today\'s 4-digit OTPs are ready in Notifications (email off by default).',
        ]);
    }
}
