<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Password reset</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.5;">
    <h1 style="font-size: 20px;">Reset your password</h1>

    <p>We received a password reset request for {{ $surface }} account {{ $email }}.</p>

    @if ($tenant)
        <p>Tenant: {{ $tenant }}</p>
    @endif

    <p>
        <a href="{{ $resetUrl }}" style="display: inline-block; padding: 10px 14px; background: #2563eb; color: #ffffff; text-decoration: none; border-radius: 6px;">
            Reset password
        </a>
    </p>

    <p>If the button does not work, open this link:</p>
    <p><a href="{{ $resetUrl }}">{{ $resetUrl }}</a></p>

    <p>Reset token:</p>
    <p style="font-family: monospace; word-break: break-all;">{{ $token }}</p>

    <p>If you did not request this reset, you can ignore this email.</p>
</body>
</html>
