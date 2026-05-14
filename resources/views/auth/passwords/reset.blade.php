<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css">
    <title>Reset Password</title>
    <link rel="icon" href="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.App\Models\AppSetting::getAppSetting('admin_favicon'))}} @else {{asset('uploads/'.App\Models\AppSetting::getAppSetting('admin_favicon'))}} @endif">
    <style>
        a:hover
        {
            text-decoration: none;
        }
    </style>
</head>
<body>
    <section class="vh-100" style="background-image: url('{{ asset('assets/images/web_bg.png') }}');">
        <div class="container py-5 h-100">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                    <div class="card">
                        <div class="card-body text-center">
                            @if(App\Models\AppSetting::getAppSetting('app_logo'))<img src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.App\Models\AppSetting::getAppSetting('app_logo'))}} @else {{asset('uploads/'.App\Models\AppSetting::getAppSetting('app_logo'))}} @endif" width="70px" height="70px">@endif 
                            <h3 class="mt-4 mb-4" style="color:#f77b0b">Reset Password</h3>

                            @error('password')
                                <div class="alert alert-success" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror

                            <form method="POST" action="{{ route('password.update') }}">
                                @csrf

                                <input type="hidden" name="token" value="{{ $token }}">

                                <div class="row">
                                    <div class="col-12 mt-3">
                                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus readonly>
                                    </div>

                                    <div class="col-12 mt-3">
                                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" placeholder="Enter New Password" name="password" required autocomplete="new-password">
                                    </div>

                                    <div class="col-12 mt-3">
                                        <input id="password-confirm" type="password" class="form-control" name="password_confirmation" placeholder="Confirm New Password" required autocomplete="new-password">
                                    </div>
                                </div>

                                <div class="row mb-0 mt-3">
                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary btn-block btn-flat" style="font-size:17px;background-color:#f77b0b;border-color:#f77b0b">
                                            {{ __('Reset Password') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
