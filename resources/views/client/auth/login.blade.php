@extends('layouts.client')

@section('title', 'Sign In')

@section('styles')
    <style>
        /* Hide nav and fab */
        nav,
        #fab-container,
        #fab-backdrop {
            display: none !important;
        }

        #main-content {
            padding-bottom: 0 !important;
        }

        .login-container {
            height: 100vh;
            height: 100dvh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 24px;
            background: linear-gradient(180deg, #EBF0FF 0%, #FFFFFF 50%);
        }

        .brand-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-box {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #1E3A8A, #3B82F6);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: white;
            box-shadow: 0 8px 20px rgba(30, 58, 138, 0.25);
        }

        .login-title {
            font-size: 24px;
            font-weight: 800;
            color: #1E3A8A;
            letter-spacing: -0.02em;
        }

        .login-subtitle {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
            margin-top: 4px;
        }

        .input-group {
            margin-bottom: 16px;
        }

        .input-field {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
            outline: none;
            transition: all 0.2s;
        }

        .input-field:focus {
            border-color: #3B82F6;
            background-color: white;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .remember-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .sign-in-btn {
            background: linear-gradient(135deg, #1E3A8A, #3B82F6);
            color: white;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.25);
            transition: all 0.2s;
            cursor: pointer;
        }

        .sign-in-btn:active {
            transform: scale(0.98);
        }

        .register-link {
            text-align: center;
            margin-top: 28px;
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }

        .register-link a {
            color: #3B82F6;
            font-weight: 700;
            text-decoration: none;
        }

        .error-msg {
            color: #ef4444;
            font-size: 12px;
            font-weight: 600;
            margin-top: 4px;
            text-align: center;
        }

        /* Google Sign-In Button */
        .btn-google-login {
            width: 100%;
            padding: 14px;
            background: white;
            color: #333;
            border: 1px solid #dadce0;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-decoration: none;
        }
        .btn-google-login:hover, .btn-google-login:active {
            background: #f8f9fa;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .btn-google-login img {
            width: 20px;
            height: 20px;
        }

        .divider-or {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            color: #94a3b8;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        .divider-or::before, .divider-or::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }
        .divider-or:not(:empty)::before { margin-right: 12px; }
        .divider-or:not(:empty)::after { margin-left: 12px; }
    </style>
@endsection

@section('content')
    <div class="login-container">
        <div class="brand-section">
            <div class="logo-box">
                <i data-lucide="layers" class="w-10 h-10 fill-white"></i>
            </div>
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-subtitle">Sign in to continue your designs</p>
        </div>

        {{-- Google Sign-In --}}
        <a href="{{ route('auth.google') }}" class="btn-google-login">
            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google">
            Sign in with Google
        </a>

        <div class="divider-or">or sign in with email</div>

        @if(session('status'))
            <div style="background:#F0FDF4;color:#16A34A;padding:10px;border-radius:8px;margin-bottom:16px;font-size:14px;border:1px solid #BBF7D0;text-align:center;font-weight:600;">
                {{ session('status') }}
            </div>
        @endif

        <form action="{{ route('client.login.post') }}" method="POST">
            @csrf
            <div class="input-group">
                <input type="email" name="email" class="input-field" placeholder="Email Address" value="{{ old('email') }}"
                    required autofocus>
                @error('email') <p class="error-msg">{{ $message }}</p> @enderror
            </div>

            <div class="input-group">
                <input type="password" name="password" class="input-field" placeholder="Password" required>
                @error('password') <p class="error-msg">{{ $message }}</p> @enderror
            </div>

            <div class="remember-row">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="remember" style="width: 16px; height: 16px; accent-color: #3B82F6;">
                    <span style="font-size: 14px; font-weight: 600; color: #64748b;">Remember Me</span>
                </label>
                <a href="{{ route('password.forgot') }}" style="font-size: 14px; font-weight: 700; color: #3B82F6; text-decoration: none;">Forgot?</a>
            </div>

            <button type="submit" class="sign-in-btn">Sign In</button>

            <p class="register-link">
                Don't have an account? <a href="{{ route('client.register') }}">Create Account</a>
            </p>
        </form>
    </div>
@endsection