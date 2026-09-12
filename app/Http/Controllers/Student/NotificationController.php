<?php

namespace App\Http\Controllers\Student;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends BaseStudentController
{
    public function index(): View
    {
        $user = auth()->user();

        $notifications = $user->notifications()
            ->where(function ($q) {
                $q->where('data->type', 'exam')
                    ->orWhere('data->type', 'homework')
                    ->orWhere('data->type', 'ticket');
            })
            ->latest()
            ->paginate(20);

        return view('student.notifications.index', [
            'user' => $user,
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()
                ->where(function ($q) {
                    $q->where('data->type', 'exam')
                        ->orWhere('data->type', 'homework')
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

    private function destinationUrl(DatabaseNotification $notification): string
    {
        $data = is_array($notification->data) ? $notification->data : [];
        $type = (string) ($data['type'] ?? '');

        try {
            if ($type === 'ticket' && (int) ($data['ticket_id'] ?? 0) > 0) {
                return route('student.tickets.show', (int) $data['ticket_id']);
            }

            if ($type === 'exam' && (int) ($data['exam_id'] ?? 0) > 0) {
                return route('student.exams.show', (int) $data['exam_id']);
            }

            if ($type === 'homework' && (int) ($data['homework_id'] ?? 0) > 0) {
                return route('student.homework.show', (int) $data['homework_id']);
            }
        } catch (\Throwable) {
            // Fall through to type index.
        }

        return match ($type) {
            'ticket' => route('student.tickets.index'),
            'exam' => route('student.exams.index'),
            'homework' => route('student.homework.index'),
            default => route('student.notifications.index'),
        };
    }

    public function markAllRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications()
            ->where(function ($q) {
                $q->where('data->type', 'exam')
                    ->orWhere('data->type', 'homework')
                    ->orWhere('data->type', 'ticket');
            })
            ->update(['read_at' => now()]);

        return redirect()->route('student.notifications.index')
            ->with('success', 'All notifications marked as read.');
    }
}
