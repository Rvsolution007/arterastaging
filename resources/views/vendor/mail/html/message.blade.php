@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
@if(App\Models\AppSetting::getAppSetting('app_logo'))<img type="image" src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.App\Models\AppSetting::getAppSetting('app_logo'))}}@else{{asset('uploads/'.App\Models\AppSetting::getAppSetting('app_logo'))}}@endif" alt="Image" style="width: 100px;height: 100px">@else{{App\Models\AppSetting::getAppSetting('app_title')}}@endif
@endcomponent
@endslot

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
© {{ date('Y') }} {{ App\Models\AppSetting::getAppSetting('app_title') }}. @lang('All rights reserved.')
@endcomponent
@endslot
@endcomponent
