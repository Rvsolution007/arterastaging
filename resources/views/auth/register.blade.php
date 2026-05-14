<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css">
    <title>Register - {{App\Models\AppSetting::getAppSetting('app_title')}}</title>
    <link rel="icon" href="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/' . App\Models\AppSetting::getAppSetting('admin_favicon'))}} @else {{asset('uploads/' . App\Models\AppSetting::getAppSetting('admin_favicon'))}} @endif">
    <style>
        a:hover
        {
            text-decoration: none;
        }
    </style>
</head>
<body>
    <section class="vh-100" style="background-image: url('{{ asset('assets/images/web_bg.png') }}'); background-size: cover; background-position: center;">
        <div class="container py-5 h-100">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                    <div class="card shadow-lg" style="border-radius: 1rem;">
                        <div class="card-body p-5 text-center">
                            @if(App\Models\AppSetting::getAppSetting('app_logo'))
                                <img src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/' . App\Models\AppSetting::getAppSetting('app_logo'))}} @else {{asset('uploads/' . App\Models\AppSetting::getAppSetting('app_logo'))}} @endif" width="80px" height="80px" class="mb-4">
                            @endif
                            
                            <h3 class="mb-4" style="color:#f77b0b; font-weight: 700;">Register</h3>

                            @if($errors->any())
                                <div class="alert alert-danger text-left">
                                    <ul class="mb-0">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('register') }}">
                                @csrf
                                <div class="form-group text-left">
                                    <label class="font-weight-bold">Full Name</label>
                                    <input type="text" class="form-control form-control-lg" name="name" value="{{ old('name') }}" required autofocus>
                                </div>

                                <div class="form-group text-left">
                                    <label class="font-weight-bold">Email Address</label>
                                    <input type="email" class="form-control form-control-lg" name="email" value="{{ old('email') }}" required>
                                </div>

                                <div class="form-group text-left">
                                    <label class="font-weight-bold">Password</label>
                                    <input type="password" class="form-control form-control-lg" name="password" required>
                                </div>

                                <div class="form-group text-left">
                                    <label class="font-weight-bold">Confirm Password</label>
                                    <input type="password" class="form-control form-control-lg" name="password_confirmation" required>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg btn-block mt-4" style="background-color:#f77b0b; border-color:#f77b0b; font-weight: 700;">
                                    Sign Up
                                </button>
                            </form>

                            <hr class="my-4">
                            
                            <p class="mb-0">Already have an account? <a href="{{ route('login') }}" style="color:#f77b0b; font-weight: 700;">Login</a></p>
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
