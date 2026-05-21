<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { background-color: #ff416c; padding: 30px 20px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 40px 30px; color: #333333; line-height: 1.6; }
        .content h2 { margin-top: 0; font-size: 20px; color: #1e293b; }
        .alert-box { background-color: #fff1f2; border-left: 4px solid #e11d48; padding: 15px; border-radius: 4px; margin: 25px 0; color: #be123c; }
        .btn { display: inline-block; background: linear-gradient(135deg, #6366f1, #4f46e5); color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; margin-top: 20px; text-align: center; }
        .footer { background-color: #f8fafc; padding: 20px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Artera Subscription Update</h1>
        </div>
        <div class="content">
            <h2>Hi {{ $user->name }},</h2>
            
            <div class="alert-box">
                {!! nl2br(e($emailBody)) !!}
            </div>

            <p>You can easily update your payment method or try again by clicking the button below:</p>
            
            <div style="text-align: center;">
                <a href="{{ url('/') }}" class="btn">Update Payment Details</a>
            </div>
            
            <p style="margin-top: 30px;">If you have already updated your payment method or believe this is an error, please ignore this email or reply to contact our support team.</p>
            
            <p>Best Regards,<br>The Artera Team</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Artera SaaS. All rights reserved.<br>
            You are receiving this email because you are subscribed to Artera.
        </div>
    </div>
</body>
</html>
