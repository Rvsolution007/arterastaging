@component('mail::message')

<h1><div style="color: #1E3A8A;">Hello {{$name}}</div></h1>
<h2>Password Reset OTP</h2>

<p style="font-size:16px;">Use the following code to reset your password:</p>

<p style="font-size:40px; font-weight: bold; letter-spacing: 8px; text-align: center; color: #1E3A8A; background: #EBF0FF; padding: 20px; border-radius: 12px;">{{$otp}}</p>

<p style="font-size:14px; color: #64748b;">This code will expire in 10 minutes. If you did not request a password reset, please ignore this email.</p>

Thank You.<br>
<span style="color: #1E3A8A;"><strong>{{ App\Models\AppSetting::getAppSetting('app_title') }}</strong></span>
@endcomponent
