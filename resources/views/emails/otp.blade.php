<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Victoria verification code</title>
</head>
<body style="margin:0;padding:28px;background:#f4f6fa;font-family:Arial,sans-serif;color:#20283a">
    <div style="max-width:520px;margin:0 auto;padding:30px;background:#fff;border:1px solid #e5e9f0;border-radius:12px">
        <p style="margin:0 0 8px;color:#637087;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase">Municipality of Victoria</p>
        <h1 style="margin:0 0 18px;font-size:23px">Your verification code</h1>
        <p style="line-height:1.6">Use this code to {{ $purpose === 'registration' ? 'verify your email and finish creating your account' : 'finish signing in to your account' }}.</p>
        <div style="margin:24px 0;padding:18px;background:#f3f4fb;border-radius:8px;text-align:center;font-size:32px;font-weight:bold;letter-spacing:8px">{{ $code }}</div>
        <p style="line-height:1.6">This code expires in {{ $minutes }} minutes and can only be used once.</p>
        <p style="color:#637087;font-size:13px;line-height:1.6">If you did not request this code, you can ignore this email. Do not share the code with anyone.</p>
        <p style="margin:24px 0 0;color:#637087;font-size:12px">Municipality of Victoria, Oriental Mindoro</p>
    </div>
</body>
</html>
