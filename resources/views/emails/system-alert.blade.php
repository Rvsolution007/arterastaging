<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>System Alert</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-top: 40px; border-top: 5px solid @if($severity == 'critical') #dc2626 @else #f59e0b @endif; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: @if($severity == 'critical') #dc2626 @else #d97706 @endif; font-size: 24px; margin-bottom: 10px; }
        .alert-box { background-color: #f1f5f9; padding: 20px; border-radius: 8px; margin-bottom: 30px; border-left: 4px solid @if($severity == 'critical') #dc2626 @else #f59e0b @endif; }
        .footer { text-align: center; font-size: 12px; color: #94a3b8; margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>System Alert: {{ ucfirst($alertType) }}</h1>
            <p>An automated health check or security scan triggered an alert on your system.</p>
        </div>

        <div class="alert-box">
            <strong>Severity:</strong> <span style="text-transform: uppercase;">{{ $severity }}</span><br><br>
            <strong>Message:</strong><br>
            {{ $alertMessage }}
        </div>

        <p style="text-align: center; font-size: 14px;">Please log in to the God View Admin Dashboard to investigate this issue.</p>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="{{ url('/admin/god-view') }}" style="background-color: #1e293b; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">Go to God View</a>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Artera Monitoring System.</p>
        </div>
    </div>
</body>
</html>
