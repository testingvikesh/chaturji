@props(['title' => config('app.name'), 'eyebrow' => 'Support Ticket'])

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 12px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="background:#166534;padding:18px 24px;color:#ffffff;">
                            <p style="margin:0;font-size:18px;font-weight:bold;">Gses Chaturji</p>
                            <p style="margin:4px 0 0;font-size:12px;color:#d1fae5;">{{ $eyebrow }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 24px;background:#f8fafc;color:#64748b;font-size:12px;">
                            This is an automated mail from Gses Chaturji. Please do not reply to this email.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
