@extends('landing.layout')

@section('title', 'Registration Successful - ' . App\Models\AppSetting::getAppSetting('app_title'))

@section('extra_css')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    .success-section {
        padding: 120px 0 80px;
        background: #fafafa;
        min-height: calc(100vh - 80px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Poppins', sans-serif !important;
    }

    .success-wrapper {
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 24px 48px -12px rgba(0, 0, 0, 0.1);
        padding: 64px 40px;
        text-align: center;
        border: 1px solid rgba(0,0,0,0.05);
        max-width: 600px;
        width: 100%;
        margin: 0 auto;
    }

    .success-icon {
        font-size: 80px;
        color: #10b981;
        margin-bottom: 24px;
        animation: scaleUp 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    }

    @keyframes scaleUp {
        0% { transform: scale(0); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }

    .success-wrapper h2 {
        font-family: 'Poppins', sans-serif !important;
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 16px;
        color: #111827;
        letter-spacing: -0.02em;
    }

    .success-wrapper p {
        font-size: 18px;
        color: #4b5563;
        margin-bottom: 40px;
        line-height: 1.6;
    }

    .btn-app {
        background-color: #111827;
        color: white;
        border-radius: 12px;
        padding: 12px 24px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        text-decoration: none !important;
        transition: all 0.3s;
        min-width: 200px;
        justify-content: center;
    }
    .btn-app:hover {
        background-color: var(--blue);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -10px var(--blue);
    }
    .btn-app i {
        font-size: 24px;
    }
    .btn-app .text-left {
        text-align: left;
    }
    .btn-app small {
        font-size: 10px;
        display: block;
        line-height: 1;
        opacity: 0.8;
        margin-bottom: 4px;
        text-transform: uppercase;
    }
    .btn-app span {
        font-size: 16px;
        line-height: 1;
        display: block;
    }

    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 16px;
        align-items: center;
    }

    @media(min-width: 640px) {
        .action-buttons {
            flex-direction: row;
            justify-content: center;
        }
    }
</style>
@endsection

@section('content')
<section class="success-section">
    <div class="container-full">
        <div class="success-wrapper">
            <i class="fa-solid fa-circle-check success-icon"></i>
            <h2>Registration Successful!</h2>
            <p>
                Your business profile has been securely created. Download our app now and login with your new credentials to access thousands of stunning AI templates.
            </p>
            
            <div class="action-buttons">
                <a href="{{ App\Models\AppSetting::getAppSetting('play_store_url') ?: 'https://play.google.com/store/apps/details?id=com.arterapixel.pro&hl=en_IN' }}" class="btn-app" target="_blank">
                    <i class="fa-brands fa-google-play"></i>
                    <div class="text-left">
                        <small>GET IT ON</small>
                        <span>Google Play</span>
                    </div>
                </a>
                
                @if(App\Models\AppSetting::getAppSetting('app_store_url'))
                <a href="{{ App\Models\AppSetting::getAppSetting('app_store_url') }}" class="btn-app" target="_blank">
                    <i class="fa-brands fa-apple"></i>
                    <div class="text-left">
                        <small>Download on the</small>
                        <span>App Store</span>
                    </div>
                </a>
                @endif
                
                <a href="{{ url('/') }}" class="btn-app" style="background: var(--blue);">
                    <i class="fa-solid fa-house"></i>
                    <span>Return to Home</span>
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
