<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { background-color: #0f172a; padding: 30px 20px; text-align: center; color: #fff;}
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 40px 30px; color: #333333; line-height: 1.6; }
        .receipt-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e2e8f0; }
        .row:last-child { border-bottom: none; font-weight: bold; font-size: 18px; color: #0f172a; }
        .footer { background-color: #f8fafc; padding: 20px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #e2e8f0; }
        .btn { display: inline-block; background: #38bdf8; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Artera Receipt</h1>
        </div>
        <div class="content">
            <h2>Thank you for your payment, {{ $user->name }}!</h2>
            <p>Your subscription to the <strong>{{ $planName }}</strong> has been successfully renewed. You can save this email for your financial records.</p>
            
            <div class="receipt-box">
                <div class="row">
                    <span>Date Paid:</span>
                    <span>{{ $invoiceDate }}</span>
                </div>
                <div class="row">
                    <span>Description:</span>
                    <span>{{ $planName }} Subscription</span>
                </div>
                <div class="row">
                    <span>Total Paid:</span>
                    <span>${{ number_format($amount, 2) }}</span>
                </div>
            </div>

            <p style="text-align: center;">
                <a href="{{ url('/dashboard') }}" class="btn">Go to Dashboard</a>
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Artera. This is an automatically generated receipt.
        </div>
    </div>
</body>
</html>
