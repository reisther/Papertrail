<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;background:#f3f4f6;font-family:Arial,sans-serif;color:#111827;">
    <div style="max-width:640px;margin:0 auto;padding:24px;">
        <div style="border:1px solid #e5e7eb;background:#ffffff;border-radius:12px;overflow:hidden;">
            <div style="background:#1f2937;color:#ffffff;padding:18px 22px;">
                <h1 style="font-size:20px;line-height:1.3;margin:0;">{{ $title }}</h1>
                <p style="font-size:13px;margin:6px 0 0;">PaperTrail academic update</p>
            </div>
            <div style="padding:22px;">
                <p style="font-size:15px;line-height:1.6;margin:0 0 16px;">{{ $intro }}</p>
                <div style="border-left:4px solid #2563eb;background:#f9fafb;padding:14px 16px;white-space:pre-line;font-size:14px;line-height:1.6;">{{ $body }}</div>
                <p style="font-size:12px;color:#6b7280;margin:18px 0 0;">{{ $reason }}</p>
                <p style="font-size:12px;color:#6b7280;margin:8px 0 0;">Please sign in to PaperTrail to review the details in your dashboard.</p>
            </div>
        </div>
    </div>
</body>
</html>
