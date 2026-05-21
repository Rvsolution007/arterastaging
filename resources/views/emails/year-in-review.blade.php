<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your Year in Review</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-top: 40px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #4338ca; font-size: 28px; margin-bottom: 10px; }
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .stat-card { background-color: #f1f5f9; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-value { font-size: 24px; font-weight: bold; color: #059669; }
        .stat-label { font-size: 14px; color: #64748b; margin-top: 5px; }
        .footer { text-align: center; font-size: 12px; color: #94a3b8; margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Happy Anniversary, {{ $user->name }}! 🎉</h1>
            <p>It's been exactly one year since you joined Artera. Here's what you achieved.</p>
        </div>

        <div style="display: flex; justify-content: space-between; margin-bottom: 30px;">
            <div style="background-color: #f1f5f9; padding: 20px; border-radius: 8px; text-align: center; width: 45%;">
                <div style="font-size: 24px; font-weight: bold; color: #059669;">{{ $stats['total_posts'] }}</div>
                <div style="font-size: 14px; color: #64748b; margin-top: 5px;">Total Designs Created</div>
            </div>
            <div style="background-color: #f1f5f9; padding: 20px; border-radius: 8px; text-align: center; width: 45%;">
                <div style="font-size: 24px; font-weight: bold; color: #4338ca;">{{ $stats['max_streak'] }} Days</div>
                <div style="font-size: 14px; color: #64748b; margin-top: 5px;">Longest Creation Streak</div>
            </div>
        </div>

        <div style="text-align: center; margin-bottom: 30px;">
            <div style="background-color: #fef3c7; padding: 20px; border-radius: 8px;">
                <div style="font-size: 24px; font-weight: bold; color: #d97706;">{{ $stats['badges_earned'] }}</div>
                <div style="font-size: 14px; color: #92400e; margin-top: 5px;">Badges Earned</div>
            </div>
        </div>

        <p style="text-align: center; font-size: 16px;">We can't wait to see what you create in year two. Keep building amazing things!</p>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="{{ url('/dashboard') }}" style="background-color: #4f46e5; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">Go to Dashboard</a>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Artera. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
