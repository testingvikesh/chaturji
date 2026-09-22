<x-email-layout title="Student login details" eyebrow="Welcome to {{ config('app.name', 'Gses Chaturji') }}">
    <p style="margin:0 0 12px;font-size:16px;">Hello {{ $student->name }},</p>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#334155;">
        Your student account is ready. Use the details below to open the app and login.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="padding:12px 14px;background:#f8fafc;font-size:12px;color:#64748b;font-weight:bold;text-transform:uppercase;letter-spacing:0.04em;">Application link</td>
        </tr>
        <tr>
            <td style="padding:12px 14px;font-size:14px;color:#0f172a;">
                <a href="{{ $loginUrl }}" style="color:#166534;font-weight:bold;word-break:break-all;">{{ $loginUrl }}</a>
            </td>
        </tr>
        <tr>
            <td style="padding:12px 14px;background:#f8fafc;font-size:12px;color:#64748b;font-weight:bold;text-transform:uppercase;letter-spacing:0.04em;border-top:1px solid #e2e8f0;">Username (Mobile)</td>
        </tr>
        <tr>
            <td style="padding:12px 14px;font-size:16px;font-weight:bold;color:#0f172a;">{{ $username }}</td>
        </tr>
        <tr>
            <td style="padding:12px 14px;background:#f8fafc;font-size:12px;color:#64748b;font-weight:bold;text-transform:uppercase;letter-spacing:0.04em;border-top:1px solid #e2e8f0;">Password</td>
        </tr>
        <tr>
            <td style="padding:12px 14px;font-size:16px;font-weight:bold;color:#0f172a;font-family:ui-monospace,Menlo,Consolas,monospace;">{{ $plainPassword }}</td>
        </tr>
        @if ($student->email)
            <tr>
                <td style="padding:12px 14px;background:#f8fafc;font-size:12px;color:#64748b;font-weight:bold;text-transform:uppercase;letter-spacing:0.04em;border-top:1px solid #e2e8f0;">Email</td>
            </tr>
            <tr>
                <td style="padding:12px 14px;font-size:14px;color:#0f172a;">{{ $student->email }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding:12px 14px;background:#f8fafc;font-size:12px;color:#64748b;font-weight:bold;text-transform:uppercase;letter-spacing:0.04em;border-top:1px solid #e2e8f0;">Medium · Standard</td>
        </tr>
        <tr>
            <td style="padding:12px 14px;font-size:14px;color:#0f172a;text-transform:capitalize;">
                {{ $student->medium ?: '—' }} · {{ $student->standardLabel() }}
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px;">
        <a href="{{ $loginUrl }}" style="display:inline-block;background:#166534;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:10px;font-size:14px;font-weight:bold;">Open student login</a>
    </p>

    <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">
        After login you can use books, exams, homework and all student features.
        App home: <a href="{{ $appUrl }}" style="color:#166534;">{{ $appUrl }}</a>
    </p>
</x-email-layout>
