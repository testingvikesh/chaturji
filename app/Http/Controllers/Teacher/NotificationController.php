<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Support\TeacherOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $todayOtp = TeacherOtpService::todayFor($user);

        $notifications = $user->notifications()
            ->where(function ($q) {
                $q->where('data->type', 'otp')
                    ->orWhere('data->type', 'ticket');
            })
            ->latest()
            ->paginate(500);

        return view('teacher.notifications.index', [
            'user' => $user,
            'todayOtp' => $todayOtp,
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()
                ->where(function ($q) {
                    $q->where('data->type', 'otp')
                        ->orWhere('data->type', 'ticket');
                })
                ->count(),
        ]);
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        $notification = auth()->user()->notifications()->where('id', $id)->firstOrFail();

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        return redirect()->to($this->destinationUrl($notification));
    }

    public function markAllRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications()
            ->where(function ($q) {
                $q->where('data->type', 'otp')
                    ->orWhere('data->type', 'ticket');
            })
            ->update(['read_at' => now()]);

        return redirect()->route('teacher.notifications.index')
            ->with('success', 'All notifications marked as read.');
    }

    private function destinationUrl(DatabaseNotification $notification): string
    {
        $data = is_array($notification->data) ? $notification->data : [];
        $type = (string) ($data['type'] ?? '');

        try {
            if ($type === 'ticket' && (int) ($data['ticket_id'] ?? 0) > 0) {
                return route('teacher.tickets.show', (int) $data['ticket_id']);
            }
        } catch (\Throwable) {
            // Fall through.
        }

        return match ($type) {
            'ticket' => route('teacher.tickets.index'),
            'otp' => route('teacher.notifications.index'),
            default => route('teacher.notifications.index'),
        };
    }
}
