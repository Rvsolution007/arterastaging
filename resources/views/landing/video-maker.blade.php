@extends('landing.layout')

@section('title', 'Artera - Video Post Maker (Coming Soon)')

@section('extra_css')
<style>
    .coming-soon-section { padding: 100px 0; text-align: center; background: #f8fafc; min-height: 60vh; display: flex; align-items: center; justify-content: center; }
    .coming-soon-content { max-width: 600px; margin: 0 auto; }
    .coming-soon-icon { font-size: 80px; color: var(--primary); margin-bottom: 20px; }
    .coming-soon-content h1 { font-size: 48px; font-weight: 800; color: #1e293b; margin-bottom: 15px; }
    .coming-soon-content p { font-size: 18px; color: #64748b; margin-bottom: 30px; }
    .badge-soon { background: #f59e0b; color: white; padding: 5px 15px; border-radius: 20px; font-size: 14px; font-weight: bold; text-transform: uppercase; margin-bottom: 20px; display: inline-block; }
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
            <a href="{{ route('landing.home') }}" class="btn btn-outline" style="border: 2px solid var(--primary); color: var(--primary); padding: 10px 25px; border-radius: 8px; text-decoration: none; font-weight: bold;">Back to Home</a>
        </div>
    </div>
</section>
@endsection
