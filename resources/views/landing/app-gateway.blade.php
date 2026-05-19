@extends('landing.layout')

@section('title', 'Artera - Success')

@section('extra_css')
<style>
    .gateway-container {
        max-width: 800px;
        margin: 50px auto;
        background: white;
        border-radius: 20px;
        box-shadow: var(--shadow-lg);
        text-align: center;
        padding: 60px 40px;
        position: relative;
        overflow: hidden;
    }
    
    .gateway-container::before {
        content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 8px;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
    }

    .success-icon {
        width: 80px; height: 80px; background: #D1FAE5; color: #10B981; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; font-size: 36px;
        margin: 0 auto 30px; animation: bounceIn 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    @keyframes bounceIn {
        0% { transform: scale(0); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }

    .gateway-title { font-size: 32px; font-weight: 800; color: var(--text-dark); margin-bottom: 20px; }
    .gateway-desc { font-size: 18px; color: var(--text-gray); line-height: 1.6; margin-bottom: 40px; max-width: 600px; margin-left: auto; margin-right: auto;}

    .btn-intent {
        display: inline-flex; align-items: center; gap: 15px; padding: 18px 40px;
        background: var(--primary); color: white; border-radius: 12px; font-size: 20px; font-weight: 700;
        text-decoration: none; transition: var(--transition); box-shadow: 0 10px 20px rgba(30, 58, 138, 0.2);
    }

    .btn-intent:hover {
        background: var(--primary-light); transform: translateY(-3px); box-shadow: 0 15px 25px rgba(59, 130, 246, 0.3);
    }

    .btn-intent i { font-size: 28px; }

    .help-text { margin-top: 40px; font-size: 14px; color: var(--text-gray); border-top: 1px solid #e2e8f0; padding-top: 20px;}
    .help-text a { color: var(--primary-light); text-decoration: underline; }
</style>
@endsection

@section('content')
<section class="section section-alt" style="min-height: calc(100vh - 80px); display: flex; align-items: center;">
    <div class="container w-100">
        <div class="gateway-container" data-aos="fade-up">
            <div class="success-icon">
                <i class="fa-solid fa-check"></i>
            </div>
            
            <h1 class="gateway-title">Authentication Successful!</h1>
            <p class="gateway-desc">Your account is ready. Artera works best directly on your mobile device. Please open the Artera Mobile App and login with your credentials to start designing.</p>
            
            <a href="intent://#Intent;package=com.arterapixel.app;scheme=artera;end;" class="btn-intent" id="openAppBtn">
                <i class="fa-brands fa-google-play"></i> Open App to Continue
            </a>
            
            <div class="help-text">
                If the app doesn't open automatically, the button will take you to the Google Play Store to download it.<br><br>
                <a href="{{ url('/dashboard') }}">Continue to Web Dashboard instead (Limited Features)</a>
            </div>
        </div>
    </div>
</section>
@endsection

@section('extra_js')
<script>
    // Fallback logic for desktop users or missing intent support
    document.getElementById('openAppBtn').addEventListener('click', function(e) {
        let isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        
        // On desktop, the intent protocol usually fails or does nothing gracefully if no handler is installed.
        // We can set a fallback timeout to redirect to the play store directly if they are on mobile but the intent fails,
        // or just link to play store if they are on Desktop.
        if(!isMobile) {
            e.preventDefault();
            window.open('https://play.google.com/store/apps/details?id=com.arterapixel.app', '_blank');
        } else {
            // Intent links automatically failover to Play Store on modern Android devices if the package is specified.
            // No custom timeout fallback needed for Android Intent specification 'package=com.arterapixel.app'
        }
    });
</script>
@endsection
