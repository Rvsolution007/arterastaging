<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailSubject }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #a78bfa, #8b5cf6); padding: 30px 20px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; font-weight: 600; letter-spacing: 1px;}
        .content { padding: 40px 30px; color: #333333; line-height: 1.6; font-size: 15px;}
        .content p { margin-bottom: 15px; }
        .btn { display: inline-block; background: linear-gradient(135deg, #6366f1, #4f46e5); color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; margin-top: 20px; text-align: center; }
        .footer { background-color: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Artera</h1>
        </div>
        <div class="content">
            {!! nl2br(e($emailBody)) !!}

            <div style="text-align: center; margin-top: 30px;">
                <a href="{{ url('/') }}" class="btn">Open Artera App</a>
            </div>
            
            <p style="margin-top: 40px; font-size: 13px; color: #64748b;">
                Best regards,<br>
                The Artera Team
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Artera. All rights reserved.<br>
            You are receiving this automated but personalized email based on your app activity.
        </div>
    </div>
</body>
</html>
