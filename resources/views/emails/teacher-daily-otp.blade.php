<x-email-layout title="Today's login OTP" eyebrow="Teacher login OTP">
    <p style="margin:0 0 12px;font-size:16px;">Hello {{ $teacher->name }},</p>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#334155;">
        Use this 4-digit code instead of your password to login today ({{ $otpDate }}).
    </p>
    <p style="margin:0 0 16px;padding:16px;background:#ecfdf5;border:1px solid #bbf7d0;border-radius:12px;font-size:32px;font-weight:bold;letter-spacing:0.25em;color:#166534;text-align:center;">
        {{ $otp->otp }}
    </p>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#334155;">
        This code is valid only for today. You can still login with your regular password.
    </p>
    <p style="margin:0;">
        <a href="{{ $loginUrl }}" style="display:inline-block;background:#166534;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:10px;font-size:14px;font-weight:bold;">Teacher login</a>
    </p>
</x-email-layout>
