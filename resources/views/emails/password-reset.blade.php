<x-email-layout title="Reset your password" eyebrow="Password reset">
    <p style="margin:0 0 12px;font-size:16px;">Hello {{ $user->name ?? 'there' }},</p>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#334155;">
        We received a request to reset your Gses Chaturji password. Click the button below to choose a new one. This link expires in 60 minutes.
    </p>
    <p style="margin:0 0 16px;">
        <a href="{{ $url }}" style="display:inline-block;background:#166534;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:10px;font-size:14px;font-weight:bold;">Reset password</a>
    </p>
    <p style="margin:0;font-size:12px;line-height:1.6;color:#64748b;">
        If you did not ask for this, you can ignore this email. Your password will stay the same.
    </p>
</x-email-layout>
