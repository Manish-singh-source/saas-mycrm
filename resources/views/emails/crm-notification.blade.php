<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0; padding:0; background:#f3f4f6; font-family:Arial, sans-serif; color:#111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
                    <tr>
                        <td style="padding:24px 28px; background:#111827; color:#ffffff;">
                            <div style="font-size:13px; letter-spacing:.04em; text-transform:uppercase; color:#cbd5e1;">{{ config('app.name', 'SaaS CRM') }}</div>
                            <h1 style="margin:8px 0 0; font-size:22px; line-height:1.3;">{{ $heading }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 18px; font-size:15px; line-height:1.6; color:#374151;">{{ $intro }}</p>

                            @if (! empty($rows))
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse; margin:18px 0; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
                                    @foreach ($rows as $label => $value)
                                        <tr>
                                            <td style="padding:12px 14px; background:#f9fafb; border-bottom:1px solid #e5e7eb; width:38%; font-size:13px; color:#6b7280;">{{ $label }}</td>
                                            <td style="padding:12px 14px; border-bottom:1px solid #e5e7eb; font-size:14px; color:#111827; font-weight:600;">{{ $value ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif

                            @if ($actionText && $actionUrl)
                                <p style="margin:24px 0;">
                                    <a href="{{ $actionUrl }}" style="display:inline-block; padding:11px 16px; background:#2563eb; color:#ffffff; text-decoration:none; border-radius:8px; font-weight:700;">{{ $actionText }}</a>
                                </p>
                                <p style="margin:0 0 18px; font-size:12px; color:#6b7280; word-break:break-all;">{{ $actionUrl }}</p>
                            @endif

                            @if ($outro)
                                <p style="margin:18px 0 0; font-size:14px; line-height:1.6; color:#4b5563;">{{ $outro }}</p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px; background:#f9fafb; color:#6b7280; font-size:12px; line-height:1.5;">
                            This is an automated message from {{ config('app.name', 'SaaS CRM') }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
