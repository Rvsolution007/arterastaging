@extends('layouts.client')

@section('title', 'Reset Password')

@section('styles')
    <style>
        nav, #fab-container, #fab-backdrop { display: none !important; }
        #main-content { padding-bottom: 0 !important; }

        .reset-container {
            height: 100vh; height: 100dvh;
            display: flex; flex-direction: column; justify-content: center;
            padding: 0 24px;
            background: linear-gradient(180deg, #EBF0FF 0%, #FFFFFF 50%);
        }

        .brand-section { text-align: center; margin-bottom: 36px; }

        .logo-box {
            width: 70px; height: 70px;
            background: linear-gradient(135deg, #1E3A8A, #3B82F6);
            border-radius: 18px; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px; color: white;
            box-shadow: 0 8px 20px rgba(30, 58, 138, 0.25);
        }

        .reset-title { font-size: 22px; font-weight: 800; color: #1E3A8A; }
        .reset-subtitle { font-size: 13px; color: #64748b; font-weight: 500; margin-top: 4px; }

        .input-group { margin-bottom: 16px; }

        .input-field {
            width: 100%; background-color: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 12px; padding: 14px 16px; font-size: 15px;
            font-weight: 600; color: #1e293b; outline: none; transition: all 0.2s;
        }
        .input-field:focus { border-color: #3B82F6; background: white; box-shadow: 0 0 0 4px rgba(59,130,246,0.1); }

        .submit-btn {
            background: linear-gradient(135deg, #1E3A8A, #3B82F6); color: white;
            width: 100%; padding: 14px; border: none; border-radius: 12px;
            font-size: 16px; font-weight: 700; cursor: pointer;
            box-shadow: 0 4px 12px rgba(30,58,138,0.25); transition: all 0.2s;
        }
        .submit-btn:active { transform: scale(0.98); }

        .back-link {
            text-align: center; margin-top: 24px; font-size: 14px; color: #64748b; font-weight: 500;
        }
        .back-link a { color: #3B82F6; font-weight: 700; text-decoration: none; }

        .error-msg { color: #ef4444; font-size: 12px; font-weight: 600; margin-top: 4px; text-align: center; }
        .success-msg {
            background: #F0FDF4; color: #16A34A; padding: 10px; border-radius: 8px;
            margin-bottom: 16px; font-size: 14px; border: 1px solid #BBF7D0; text-align: center;
        }
        .error-box {
            background: #FEF2F2; color: #EF4444; padding: 10px; border-radius: 8px;
            margin-bottom: 16px; font-size: 14px; border: 1px solid #FCA5A5; text-align: center;
        }

        /* OTP Input Styling */
        .otp-container { display: flex; gap: 8px; justify-content: center; margin-bottom: 20px; }
        .otp-input {
            width: 48px; height: 56px; text-align: center; font-size: 22px; font-weight: 800;
            border: 2px solid #e2e8f0; border-radius: 12px; color: #1E3A8A; outline: none;
            transition: all 0.2s; background: #f8fafc;
        }
        .otp-input:focus { border-color: #3B82F6; background: white; box-shadow: 0 0 0 4px rgba(59,130,246,0.1); }

        .resend-link { text-align: center; font-size: 13px; color: #64748b; margin-bottom: 20px; }
        .resend-link a { color: #3B82F6; font-weight: 700; text-decoration: none; }
    </style>
@endsection

@section('content')
<div class="reset-container">
    <div class="brand-section">
        <div class="logo-box">
            <i data-lucide="key-round" class="w-8 h-8" style="color:white;"></i>
        </div>

        @if($step == 'email')
            <h1 class="reset-title">Forgot Password</h1>
            <p class="reset-subtitle">Enter your registered email address</p>
        @elseif($step == 'otp')
            <h1 class="reset-title">Verify OTP</h1>
            <p class="reset-subtitle">Enter the 6-digit code sent to {{ session('reset_email') }}</p>
        @elseif($step == 'reset')
            <h1 class="reset-title">New Password</h1>
            <p class="reset-subtitle">Create a strong new password</p>
        @endif
    </div>

    @if(session('success'))
        <div class="success-msg">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="error-box">{{ session('error') }}</div>
    @endif

    {{-- STEP 1: Enter Email --}}
    @if($step == 'email')
        <form action="{{ route('password.send-otp') }}" method="POST">
            @csrf
            <div class="input-group">
                <input type="email" name="email" class="input-field" placeholder="Enter your email address"
                    value="{{ old('email') }}" required autofocus>
                @error('email') <p class="error-msg">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="submit-btn">Send OTP Code</button>
        </form>

    {{-- STEP 2: Enter OTP --}}
    @elseif($step == 'otp')
        <form action="{{ route('password.verify-otp') }}" method="POST">
            @csrf
            <div class="otp-container">
                <input type="text" name="d1" class="otp-input" maxlength="1" inputmode="numeric" autofocus required>
                <input type="text" name="d2" class="otp-input" maxlength="1" inputmode="numeric" required>
                <input type="text" name="d3" class="otp-input" maxlength="1" inputmode="numeric" required>
                <input type="text" name="d4" class="otp-input" maxlength="1" inputmode="numeric" required>
                <input type="text" name="d5" class="otp-input" maxlength="1" inputmode="numeric" required>
                <input type="text" name="d6" class="otp-input" maxlength="1" inputmode="numeric" required>
            </div>
            @error('otp') <p class="error-msg" style="margin-bottom:16px;">{{ $message }}</p> @enderror
            <button type="submit" class="submit-btn">Verify Code</button>
        </form>

        <div class="resend-link" style="margin-top: 20px;">
            Didn't receive it?
            <form action="{{ route('password.send-otp') }}" method="POST" style="display:inline;">
                @csrf
                <input type="hidden" name="email" value="{{ session('reset_email') }}">
                <button type="submit" style="background:none;border:none;color:#3B82F6;font-weight:700;cursor:pointer;font-size:13px;">Resend OTP</button>
            </form>
        </div>

    {{-- STEP 3: Set New Password --}}
    @elseif($step == 'reset')
        <form action="{{ route('password.update') }}" method="POST">
            @csrf
            <div class="input-group">
                <input type="password" name="password" class="input-field" placeholder="New Password" required minlength="8">
                @error('password') <p class="error-msg">{{ $message }}</p> @enderror
            </div>
            <div class="input-group">
                <input type="password" name="password_confirmation" class="input-field" placeholder="Confirm New Password" required minlength="8">
            </div>
            <button type="submit" class="submit-btn">Update Password</button>
        </form>
    @endif

    <p class="back-link">
        <a href="{{ route('client.login') }}">← Back to Sign In</a>
    </p>
</div>
@endsection

@section('scripts')
<script>
    // Auto-advance OTP inputs
    document.querySelectorAll('.otp-input').forEach((input, index, inputs) => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && index > 0) {
                inputs[index - 1].focus();
            }
        });
        // Handle paste
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const data = e.clipboardData.getData('text').replace(/[^0-9]/g, '').substring(0, 6);
            data.split('').forEach((char, i) => {
                if (inputs[index + i]) {
                    inputs[index + i].value = char;
                }
            });
            if (inputs[Math.min(index + data.length, inputs.length - 1)]) {
                inputs[Math.min(index + data.length, inputs.length - 1)].focus();
            }
        });
    });
</script>
@endsection
