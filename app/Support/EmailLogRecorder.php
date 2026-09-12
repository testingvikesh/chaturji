<?php

namespace App\Support;

use App\Models\EmailLog;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

class EmailLogRecorder
{
    private static ?int $lastId = null;

    public function sending(MessageSending $event): void
    {
        try {
            $log = EmailLog::query()->create($this->payload($event->message, $event->data, 'sending'));
            self::$lastId = $log->id;
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function sent(MessageSent $event): void
    {
        try {
            $payload = $this->payload($event->message, $event->data, 'sent');
            $payload['sent_at'] = now();
            $payload['error'] = null;

            if (self::$lastId) {
                EmailLog::query()->whereKey(self::$lastId)->update($payload);
                self::$lastId = null;

                return;
            }

            EmailLog::query()->create($payload);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public static function markLastFailed(Throwable $exception, ?string $to = null, ?string $subject = null, ?string $mailable = null): void
    {
        try {
            if (self::$lastId) {
                EmailLog::query()->whereKey(self::$lastId)->update([
                    'status' => 'failed',
                    'error' => mb_substr($exception->getMessage(), 0, 2000),
                    'to_email' => $to ?: EmailLog::query()->whereKey(self::$lastId)->value('to_email'),
                    'subject' => $subject ?: EmailLog::query()->whereKey(self::$lastId)->value('subject'),
                    'mailable' => $mailable ?: EmailLog::query()->whereKey(self::$lastId)->value('mailable'),
                ]);
                self::$lastId = null;

                return;
            }

            EmailLog::query()->create([
                'to_email' => $to,
                'subject' => $subject,
                'mailable' => $mailable,
                'mailer' => config('mail.default'),
                'status' => 'failed',
                'error' => mb_substr($exception->getMessage(), 0, 2000),
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(Email $message, array $data, string $status): array
    {
        return [
            'to_email' => $this->addresses($message->getTo()),
            'cc_email' => $this->addresses($message->getCc()) ?: null,
            'bcc_email' => $this->addresses($message->getBcc()) ?: null,
            'from_email' => $this->addresses($message->getFrom()) ?: null,
            'subject' => mb_substr((string) $message->getSubject(), 0, 255) ?: null,
            'mailable' => $this->mailableName($data, (string) $message->getSubject()),
            'mailer' => config('mail.default'),
            'status' => $status,
            'body_html' => $this->clip((string) ($message->getHtmlBody() ?? '')),
            'body_text' => $this->clip((string) ($message->getTextBody() ?? '')),
        ];
    }

    /**
     * @param  array<int, Address>  $addresses
     */
    private function addresses(array $addresses): string
    {
        return collect($addresses)
            ->map(fn (Address $address) => $address->getAddress())
            ->filter()
            ->implode(', ');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function mailableName(array $data, string $subject = ''): ?string
    {
        foreach (['__laravel_notification', '__laravel_mailable'] as $key) {
            if (! empty($data[$key]) && is_string($data[$key])) {
                return $data[$key];
            }
        }

        $subject = $subject !== '' ? $subject : (string) ($data['subject'] ?? '');

        return match (true) {
            str_contains($subject, 'Ticket generated') => 'App\\Mail\\TicketGeneratedMail',
            str_contains($subject, 'Admin replied') || str_contains($subject, 'User replied') => 'App\\Mail\\TicketReplyMail',
            str_contains($subject, 'New ') && str_contains($subject, 'ticket') => 'App\\Mail\\TicketAdminAlertMail',
            str_contains($subject, 'mail setup test') => 'App\\Mail\\MailSetupTestMail',
            str_contains($subject, 'Reset your') => 'Illuminate\\Auth\\Notifications\\ResetPassword',
            default => null,
        };
    }

    private function clip(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, 20000);
    }
}
