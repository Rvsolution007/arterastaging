@extends('landing.layout')

@section('title', 'Artera - Video Post Maker (Coming Soon)')

@section('extra_css')
<style>
    .coming-soon-section {
        padding: 120px 0;
        text-align: center;
        background: #ffffff;
        min-height: 70vh;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }
    .coming-soon-section::before {
        content: '';
        position: absolute;
        width: 600px;
        height: 600px;
        background: rgba(59, 130, 246, 0.05);
        border-radius: 50%;
        top: -200px;
        left: -150px;
        filter: blur(60px);
    }
    .coming-soon-section::after {
        content: '';
        position: absolute;
        width: 400px;
        height: 400px;
        background: rgba(99, 102, 241, 0.05);
        border-radius: 50%;
        bottom: -150px;
        right: -100px;
        filter: blur(60px);
    }
    .coming-soon-content {
        max-width: 650px;
        margin: 0 auto;
        position: relative;
        z-index: 2;
    }
    .coming-soon-icon {
        font-size: 80px;
        color: #3b82f6;
        margin-bottom: 24px;
        filter: drop-shadow(0 10px 15px rgba(59, 130, 246, 0.2));
    }
    .coming-soon-content h1 {
        font-size: 56px;
        font-weight: 900;
        color: #0f172a;
        margin-bottom: 20px;
        letter-spacing: -0.02em;
        line-height: 1.1;
    }
    .coming-soon-content p {
        font-size: 20px;
        color: #64748b;
        margin-bottom: 40px;
        line-height: 1.6;
    }
    @media (max-width: 768px) {
        .coming-soon-content h1 { font-size: 36px; }
        .coming-soon-content p { font-size: 16px; }
    }
    .badge-soon {
        background: rgba(59, 130, 246, 0.1);
        color: #2563eb;
        padding: 8px 20px;
        border-radius: 30px;
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-bottom: 30px;
        display: inline-block;
        border: 1px solid rgba(59, 130, 246, 0.2);
    }
    .btn-back-home {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: #3b82f6;
        color: #ffffff;
        padding: 16px 36px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 800;
        font-size: 16px;
        transition: all 0.3s ease;
        box-shadow: 0 10px 25px rgba(59, 130, 246, 0.25);
    }
    .btn-back-home:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(59, 130, 246, 0.35);
        color: #ffffff;
    }
</style>
@endsection

@section('content')
<section class="coming-soon-section">
    <div class="container">
        <div class="coming-soon-content">
            <span class="badge-soon">Coming Soon</span>
            <div class="coming-soon-icon">
                <i class="fa-solid fa-video"></i>
            </div>
            <h1>Video Post Maker</h1>
            <p>Turn static images into engaging animated videos. Create stunning intro videos and dynamic social media posts effortlessly.</p>
            <a href="{{ route('landing.home') }}" class="btn-back-home">
                Back to Home
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
@endsection
