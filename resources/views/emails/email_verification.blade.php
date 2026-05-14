@component('mail::message')

<h1><div style="color: #1a8aa1;">Hello {{$name}}</div></h1>
<h2>Email Verification Code</h2>

<p style="font-size:20px;">Your Verification Code: <b style="font-size:30px;">{{$code}}</b></p>

Thank You.<br>
<span style="color: #185869;"><strong>{{ App\Models\AppSetting::getAppSetting('app_title') }}</strong></span>
@endcomponent
