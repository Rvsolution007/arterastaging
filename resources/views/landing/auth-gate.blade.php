@extends('landing.layout')

@section('title', 'Artera - Register Business')

@section('extra_css')
<style>
    .auth-container { max-width: 700px; margin: 50px auto; background: white; border-radius: 20px; box-shadow: var(--shadow-lg); overflow: hidden; position: relative;}
    .auth-split { padding: 50px; display: flex; flex-direction: column; justify-content: center; }
    
    .auth-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #e2e8f0; padding-bottom: 20px;}
    
    .auth-title { font-size: 32px; font-weight: 800; color: var(--primary); margin-bottom: 5px; }
    .auth-subtitle { color: var(--text-gray); font-size: 15px; }

    .form-group { margin-bottom: 20px; }
    .form-label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: var(--text-dark); }
    .form-control { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; transition: var(--transition); font-size: 15px; font-family: 'Inter', sans-serif;}
    .form-control:focus { outline: none; border-color: var(--primary-light); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
    
    .btn-auth { width: 100%; padding: 14px; background: #10B981; color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 16px; cursor: pointer; transition: var(--transition); margin-top: 10px;}
    .btn-auth:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16,185,129,0.3); }

    .btn-login-redirect { padding: 10px 20px; background: white; color: var(--primary); border: 2px solid var(--primary); border-radius: 8px; font-weight: 600; text-decoration: none; transition: var(--transition); font-size: 14px;}
    .btn-login-redirect:hover { background: var(--primary); color: white; }

    .btn-google { width: 100%; padding: 14px; background: white; color: #333; border: 1px solid #dadce0; border-radius: 8px; font-weight: 600; font-size: 16px; cursor: pointer; transition: var(--transition); display: flex; align-items: center; justify-content: center; gap: 12px; text-decoration: none; }
    .btn-google:hover { background: #f8f9fa; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transform: translateY(-1px); }
    .btn-google img { width: 22px; height: 22px; }
    .divider-or { display: flex; align-items: center; text-align: center; margin: 25px 0; color: var(--text-gray); font-size: 13px; text-transform: uppercase; letter-spacing: 1px; }
    .divider-or::before, .divider-or::after { content: ''; flex: 1; border-bottom: 1px solid #e2e8f0; }
    .divider-or:not(:empty)::before { margin-right: 15px; }
    .divider-or:not(:empty)::after { margin-left: 15px; }

    .error-msg { color: #Ef4444; font-size: 13px; margin-top: 5px; }

    @media (max-width: 768px) {
        .auth-split { padding: 30px 20px; }
        .auth-header { flex-direction: column; align-items: flex-start; gap: 15px; }
    }
</style>
@endsection

@section('content')
<section class="section section-alt" style="padding: 40px 0;">
    <div class="container">
        <div class="text-center" data-aos="fade-up" style="margin-bottom: 20px;">
            <h2 class="section-title">Join <span class="text-gradient">Artera</span></h2>
        </div>

        <div class="auth-container" data-aos="fade-up" data-aos-delay="100">
            <div class="auth-split">
                
                <div class="auth-header">
                    <div>
                        <h3 class="auth-title">Create Profile</h3>
                        <p class="auth-subtitle">Register your business in 60 seconds.</p>
                    </div>
                    <div>
                        <a href="{{ route('landing.app_gateway') }}" class="btn-login-redirect">Already Registered? Login</a>
                    </div>
                </div>

                {{-- Google Sign-In Button --}}
                @if($errors->has('google'))
                    <div style="background: #FEF2F2; color: #EF4444; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 14px; border: 1px solid #FCA5A5;">
                        {{ $errors->first('google') }}
                    </div>
                @endif

                <a href="{{ route('auth.google') }}" class="btn-google">
                    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google">
                    Continue with Google
                </a>

                <div class="divider-or">or register manually</div>
                
                @if($errors->any() && !old('login_form') && !$errors->has('google'))
                    <div style="background: #FEF2F2; color: #EF4444; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; border: 1px solid #FCA5A5;">
                        Please fix the errors below to continue.
                    </div>
                @endif

                <form action="{{ route('client.register.post') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="from_landing" value="1">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label class="form-label">Business Name <span style="color:red">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="Artera Store" required>
                            @error('name')<div class="error-msg">{{ $message }}</div>@enderror
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Mobile Number <span style="color:red">*</span></label>
                            <input type="text" name="mobile_no" value="{{ old('mobile_no') }}" class="form-control" placeholder="+91 XXXXX XXXXX" required>
                            @error('mobile_no')<div class="error-msg">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address <span style="color:red">*</span></label>
                        <input type="email" name="email" value="{{ !old('login_form') ? old('email') : '' }}" class="form-control" placeholder="you@example.com" required>
                        @error('email')<div class="error-msg">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password <span style="color:red">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required minlength="8">
                        @error('password')<div class="error-msg">{{ $message }}</div>@enderror
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label class="form-label">Business Category <span style="color:red">*</span></label>
                            <select name="business_category_id" class="form-control" required style="background: white;">
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('business_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('business_category_id')<div class="error-msg">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Business Logo <span style="color:red">*</span></label>
                            <input type="file" name="logo" class="form-control" accept="image/*" required style="padding: 9px;">
                            @error('logo')<div class="error-msg">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-auth">Register Business & Continue</button>
                    <p style="font-size: 12px; color: var(--text-gray); text-align: center; margin-top: 15px;">By registering, you agree to our Terms of Service & Privacy Policy.</p>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
