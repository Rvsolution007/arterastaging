@component('mail::message')

<h1><div style="color: #1a8aa1;">Hello {{$name}}</div></h1>
<h2>Forgot Password </h2>

<p style="font-size:20px;">Your New Password: <b style="font-size:30px;">{{$newPassword}}</b></p>

Thank You.<br>
<span style="color: #185869;"><strong>{{ App\Models\AppSetting::getAppSetting('app_title') }}</strong></span>
@endcomponent
