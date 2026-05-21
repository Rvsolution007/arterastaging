<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Downgraded</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { background-color: #64748b; padding: 30px 20px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 40px 30px; color: #333333; line-height: 1.6; }
        .content h2 { margin-top: 0; font-size: 20px; color: #1e293b; }
        .alert-box { background-color: #f8fafc; border-left: 4px solid #94a3b8; padding: 15px; border-radius: 4px; margin: 25px 0; color: #475569; }
        .btn { display: inline-block; background: linear-gradient(135deg, #38bdf8, #0ea5e9); color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; margin-top: 20px; text-align: center; }
        .footer { background-color: #f8fafc; padding: 20px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Artera</h1>
        </div>
        <div class="content">
            <h2>Hi {{ $user->name }},</h2>
            <p>We are writing to let you know that we were unable to process your subscription renewal after multiple attempts over the last 3 days.</p>
            
            <div class="alert-box">
                Your account has been moved to the <strong>Free Plan</strong>. You no longer have access to premium templates and advanced tools.
            </div>

            <p><strong>Sorry to see you go!</strong><br>
            If you decided to leave us intentionally, we would love to know how we can improve. Please click below to share a quick 1-minute feedback:</p>
            
            <div style="text-align: center;">
                <a href="{{ url('/') }}" class="btn">Share Feedback</a>
            </div>
            
            <p style="margin-top: 30px;">If this was a mistake and you'd like to reactivate your premium features, you can upgrade again at any time from your dashboard.</p>
            
            <p>Best Regards,<br>The Artera Team</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Artera SaaS. All rights reserved.
        </div>
    </div>
</body>
</html>
