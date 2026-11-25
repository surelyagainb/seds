<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Victoria Citizen Account Temporary Password</title>
</head>
<body style="margin:0;padding:28px;background:#f4f6fa;font-family:Arial,sans-serif;color:#20283a">
    <div style="max-width:520px;margin:0 auto;padding:30px;background:#fff;border:1px solid #e5e9f0;border-radius:12px">
        <p style="margin:0 0 8px;color:#0f2f63;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase">Municipality of Victoria · Oriental Mindoro</p>
        <h1 style="margin:0 0 14px;font-size:22px;color:#0f2f63">Welcome, {{ $name }}</h1>
        <p style="line-height:1.6;margin:0 0 12px">
            Your citizen account has been created. Sign in on the shared sign-in page using your email address (<strong>{{ $email }}</strong>) and the temporary password below:
        </p>
        <div style="margin:22px 0;padding:18px;background:#eef2f7;border:1px solid #d5e0f0;border-radius:10px;text-align:center;font-size:22px;font-weight:bold;letter-spacing:2px;color:#0f2f63">{{ $temporaryPassword }}</div>
        <p style="line-height:1.6;margin:0 0 10px">After signing in, you will be required to set a new password of at least 8 characters before accessing the portal.</p>
        <p style="color:#637087;font-size:13px;line-height:1.6;margin:0">Do not share this temporary password with anyone.</p>
    </div>
</body>
</html>
