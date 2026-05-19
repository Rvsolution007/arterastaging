@extends('landing.layout')

@section('title', 'Artera - Features')

@section('extra_css')
<style>
    .feature-row { display: flex; align-items: center; gap: 60px; margin-bottom: 100px; }
    .feature-row.reverse { flex-direction: row-reverse; }
    .feature-img-box { flex: 1; text-align: center; }
    .feature-img-box img { max-width: 100%; border-radius: 20px; box-shadow: var(--shadow-lg); transition: var(--transition); }
    .feature-img-box img:hover { transform: scale(1.02); }
    .feature-text-box { flex: 1; }
    
    .f-icon { font-size: 40px; color: var(--primary-light); margin-bottom: 20px; }
    .feature-text-box h2 { font-size: 32px; color: var(--text-dark); margin-bottom: 20px; }
    .feature-text-box p { font-size: 16px; color: var(--text-gray); line-height: 1.8; margin-bottom: 20px; }
    .feature-list { list-style: none; margin-bottom: 30px; }
    .feature-list li { margin-bottom: 15px; display: flex; align-items: start; gap: 10px; font-size: 15px; color: var(--text-dark); }
    .feature-list li i { color: #10B981; margin-top: 4px; }

    @media (max-width: 768px) {
        .feature-row, .feature-row.reverse { flex-direction: column; text-align: left; gap: 30px; }
    }
</style>
@endsection

@section('content')
<section class="section section-alt">
    <div class="container">
        <div class="text-center" data-aos="fade-up" style="margin-bottom: 80px;">
            <div style="color: var(--primary-light); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-size: 14px; margin-bottom: 10px;">Deep Dive</div>
            <h2 class="section-title">Powerful Features <span class="text-gradient">Explained</span></h2>
            <p class="section-desc">Discover how each component of Artera works together to amplify your digital presence.</p>
        </div>

        <!-- Feature 1: AI Magic Cloner -->
        <div class="feature-row">
            <div class="feature-img-box" data-aos="fade-right">
                <img src="{{ asset('landing/images/app-screens.png') }}" onerror="this.src='https://placehold.co/600x400/F0F7FF/1E3A8A?text=AI+Magic+Cloner'" alt="AI Magic Cloner">
            </div>
            <div class="feature-text-box" data-aos="fade-left">
                <i class="fa-solid fa-clone f-icon"></i>
                <h2>AI Magic Cloner</h2>
                <p>Have you ever seen a competitor's social media post and wished you could create something just like it? Our AI Magic Cloner does exactly that.</p>
                <ul class="feature-list">
                    <li><i class="fa-solid fa-circle-check"></i> Upload any inspiration image.</li>
                    <li><i class="fa-solid fa-circle-check"></i> The AI automatically extracts the layout, color palette, and font vibe.</li>
                    <li><i class="fa-solid fa-circle-check"></i> It instantly recreates a matching template using your own business products.</li>
                </ul>
            </div>
        </div>

        <!-- Feature 2: Daily Drip Automation -->
        <div class="feature-row reverse">
            <div class="feature-img-box" data-aos="fade-left">
                <img src="{{ asset('landing/images/hero-phone.png') }}" onerror="this.src='https://placehold.co/400x600/1E3A8A/FFFFFF?text=Daily+Drip'" alt="Daily Automation">
            </div>
            <div class="feature-text-box" data-aos="fade-right">
                <i class="fa-brands fa-whatsapp f-icon"></i>
                <h2>Automated Daily Drip</h2>
                <p>Consistency is key to marketing, but finding time to post every day is hard. The Daily Drip engine acts as your automated marketing assistant.</p>
                <ul class="feature-list">
                    <li><i class="fa-solid fa-circle-check"></i> Automatically picks a product from your catalog every morning.</li>
                    <li><i class="fa-solid fa-circle-check"></i> Wraps it in a beautiful, trending template with your branding.</li>
                    <li><i class="fa-solid fa-circle-check"></i> Sends a push notification reminding you to share it to your WhatsApp Status.</li>
                </ul>
            </div>
        </div>

        <!-- Feature 3: Festival Templates -->
        <div class="feature-row">
            <div class="feature-img-box" data-aos="fade-right">
                <img src="{{ asset('landing/images/app-screens.png') }}" onerror="this.src='https://placehold.co/600x400/F0F7FF/1E3A8A?text=Festival+Templates'" alt="Festival Templates">
            </div>
            <div class="feature-text-box" data-aos="fade-left">
                <i class="fa-regular fa-calendar-check f-icon"></i>
                <h2>Festival & Event Calendar</h2>
                <p>Never miss a chance to connect with your audience. We provide thousands of templates for every regional and national festival.</p>
                <ul class="feature-list">
                    <li><i class="fa-solid fa-circle-check"></i> Pre-designed, high-quality greetings for all holidays.</li>
                    <li><i class="fa-solid fa-circle-check"></i> Automatic integration of your logo and business details.</li>
                    <li><i class="fa-solid fa-circle-check"></i> Auto-scheduled posting reminders so you stay ahead of the calendar.</li>
                </ul>
            </div>
        </div>

        <!-- Feature 4: Custom Post Studio -->
        <div class="feature-row reverse">
            <div class="feature-img-box" data-aos="fade-left">
                <img src="{{ asset('landing/images/hero-phone.png') }}" onerror="this.src='https://placehold.co/400x600/F0F7FF/1E3A8A?text=Post+Studio'" alt="Custom Post Studio">
            </div>
            <div class="feature-text-box" data-aos="fade-right">
                <i class="fa-solid fa-palette f-icon"></i>
                <h2>Custom Post Studio</h2>
                <p>Need full creative control? Our mobile-first editor gives you Canva-like flexibility right on your phone.</p>
                <ul class="feature-list">
                    <li><i class="fa-solid fa-circle-check"></i> Add text, multiple images, and stickers to any post.</li>
                    <li><i class="fa-solid fa-circle-check"></i> Access a vast library of shapes, overlays, and typography.</li>
                    <li><i class="fa-solid fa-circle-check"></i> Save your custom drafts to edit later.</li>
                </ul>
            </div>
        </div>

    </div>
</section>
@endsection
