<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css">
    <title>Login - {{App\Models\AppSetting::getAppSetting('app_title')}}</title>
    <link rel="icon"
        href="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/' . App\Models\AppSetting::getAppSetting('admin_favicon'))}} @else {{asset('uploads/' . App\Models\AppSetting::getAppSetting('admin_favicon'))}} @endif">
    <style>
        a:hover {
            text-decoration: none;
        }

        .login-box {
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>

<body>
    <section class="vh-100"
        style="background-image: url('{{ asset('assets/images/web_bg.png') }}'); background-size: cover; background-position: center;">
        <div class="container py-5 h-100">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                    <div class="card shadow-lg" style="border-radius: 1rem;">
                        <div class="card-body p-5 text-center">
                            @if(App\Models\AppSetting::getAppSetting('app_logo'))
                                <img src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/' . App\Models\AppSetting::getAppSetting('app_logo'))}} @else {{asset('uploads/' . App\Models\AppSetting::getAppSetting('app_logo'))}} @endif"
                                    width="80px" height="80px" class="mb-4">
                            @endif

                            <h3 class="mb-4" style="color:#f77b0b; font-weight: 700;">Login</h3>

                            @if($errors->any())
                                <div class="alert alert-danger text-left">
                                    <ul class="mb-0">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('login') }}">
                                @csrf
                                <div class="form-group text-left">
                                    <label class="font-weight-bold">Email Address</label>
                                    <input type="email" class="form-control form-control-lg" name="email"
                                        value="{{ old('email') }}" required autofocus>
                                </div>

                                <div class="form-group text-left">
                                    <div class="d-flex justify-content-between">
                                        <label class="font-weight-bold">Password</label>
                                        @if (Route::has('password.request'))
                                            <a href="{{ route('password.request') }}"
                                                style="color:#f77b0b; font-size: 0.9rem;">Forgot Password?</a>
                                        @endif
                                    </div>
                                    <input type="password" class="form-control form-control-lg" name="password"
                                        required>
                                </div>

                                <div class="form-group text-left">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="remember"
                                            name="remember">
                                        <label class="custom-control-label" for="remember">Remember Me</label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg btn-block"
                                    style="background-color:#f77b0b; border-color:#f77b0b; font-weight: 700;">
                                    Sign In
                                </button>
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