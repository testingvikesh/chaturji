<?php

namespace App\Support;

use App\Models\Setting;
use Throwable;

class MailConfig
{
    public static function apply(): void
    {
        try {
            $settings = Setting::allCached();
        } catch (Throwable) {
            return;
        }

        $mailer = trim((string) ($settings['mail_mailer'] ?? ''));
        $host = trim((string) ($settings['mail_host'] ?? ''));
        $from = trim((string) ($settings['mail_from_address'] ?? ''));

        if ($mailer === '' && $host === '' && $from === '') {
            return;
        }

        $port = (int) ($settings['mail_port'] ?? 587);
        $encryption = trim((string) ($settings['mail_encryption'] ?? 'tls'));

        config([
            'mail.default' => $mailer !== '' ? $mailer : config('mail.default'),
            'mail.mailers.smtp.host' => $host !== '' ? $host : config('mail.mailers.smtp.host'),
            'mail.mailers.smtp.port' => $port > 0 ? $port : config('mail.mailers.smtp.port'),
            'mail.mailers.smtp.encryption' => $encryption !== '' ? $encryption : null,
            'mail.mailers.smtp.username' => $settings['mail_username'] ?: config('mail.mailers.smtp.username'),
            'mail.mailers.smtp.password' => $settings['mail_password'] !== ''
                ? $settings['mail_password']
                : config('mail.mailers.smtp.password'),
            'mail.from.address' => $from !== '' ? $from : config('mail.from.address'),
            'mail.from.name' => trim((string) ($settings['mail_from_name'] ?? '')) ?: config('mail.from.name'),
        ]);
    }

    public static function adminEmail(): ?string
    {
        $settings = Setting::allCached();
        $email = trim((string) ($settings['mail_admin_email'] ?? $settings['contact_support_email'] ?? ''));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}
