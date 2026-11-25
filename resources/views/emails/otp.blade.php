<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Victoria verification code</title>
</head>
<body style="margin:0;padding:28px;background:#f4f6fa;font-family:Arial,sans-serif;color:#20283a">
    <div style="max-width:520px;margin:0 auto;padding:30px;background:#fff;border:1px solid #e5e9f0;border-radius:12px">
        <p style="margin:0 0 8px;color:#0b2a5b;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase">Municipality of Victoria · Oriental Mindoro</p>
        <h1 style="margin:0 0 14px;font-size:22px;color:#0b2a5b">Your verification code</h1>
        <p style="line-height:1.6;margin:0 0 12px">
            @if(($purpose ?? '') === 'password_reset')
                Use this 6-digit code to verify your identity and set a new password for your account.
            @else
                Use this 6-digit code to verify your email address for your Municipal Staff registration. Your account will need admin approval.
            @endif
        </p>
        <div style="margin:22px 0;padding:18px;background:#eef3fa;border:1px solid #d5e0f0;border-radius:10px;text-align:center;font-size:32px;font-weight:bold;letter-spacing:8px;color:#0b2a5b">{{ $code }}</div>
        <p style="line-height:1.6;margin:0 0 10px">This code expires in <strong>{{ $minutes }} minutes</strong> and can only be used once.</p>
        <p style="color:#637087;font-size:13px;line-height:1.6;margin:0">If you did not request this code, you can safely ignore this message. Never share your code with anyone.</p>
    </div>
</body>
</html>
