@extends('landing.layout')

@section('title', 'Artera - Home')

@section('extra_css')
<style>
    .hero {
        padding-top: 50px;
        min-height: calc(100vh - 80px);
        display: flex;
        align-items: center;
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #E0E7FF 0%, #FFFFFF 100%);
    }

    .hero::before {
        content: ''; position: absolute; top: -10%; right: -5%; width: 500px; height: 500px; border-radius: 50%;
        background: radial-gradient(circle, rgba(59,130,246,0.1) 0%, rgba(255,255,255,0) 70%); z-index: 0;
    }
    .hero::after {
        content: ''; position: absolute; bottom: -10%; left: -5%; width: 400px; height: 400px; border-radius: 50%;
        background: radial-gradient(circle, rgba(30,58,138,0.1) 0%, rgba(255,255,255,0) 70%); z-index: 0;
    }

    .hero-slider { width: 100%; position: relative; z-index: 1; }
    .slide { display: none; animation: fadeIn 0.8s ease-in-out forwards; }
    .slide.active { display: block; }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    .hero-content { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center; }
    .hero-text h1 { font-size: 48px; font-weight: 800; line-height: 1.2; margin-bottom: 20px; color: var(--primary); }
    .hero-text p { font-size: 18px; color: var(--text-gray); margin-bottom: 30px; line-height: 1.6; }

    .hero-actions { display: flex; gap: 15px; }
    .play-store-btn {
        display: flex; align-items: center; gap: 10px; background: #111; color: white; padding: 10px 20px;
        border-radius: 8px; text-decoration: none; transition: var(--transition);
    }
    .play-store-btn:hover { background: #000; transform: translateY(-2px); }
    .play-store-btn i { font-size: 24px; }
    .play-store-text span { display: block; font-size: 10px; text-transform: uppercase; }
    .play-store-text strong { font-size: 16px; font-weight: 600; }

    .hero-image { position: relative; text-align: center; }
    .hero-img-element { max-width: 100%; border-radius: 20px; box-shadow: var(--shadow-lg); animation: float 6s ease-in-out infinite; }

    @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-20px); } 100% { transform: translateY(0px); } }

    .slider-dots { display: flex; justify-content: center; gap: 10px; margin-top: 40px; position: relative; z-index: 2;}
    .dot { width: 12px; height: 12px; border-radius: 50%; background: #cbd5e1; cursor: pointer; transition: var(--transition); }
    .dot.active { background: var(--primary); width: 24px; border-radius: 6px; }

    @media (max-width: 768px) {
        .hero-content { grid-template-columns: 1fr; text-align: center; }
        .hero-actions { justify-content: center; }
        .hero-text h1 { font-size: 36px; }
    }
</style>
@endsection

@section('content')
<section class="hero">
    <div class="container">
        <div class="hero-slider" id="heroSlider">
            
            <!-- Slide 1 -->
            <div class="slide active">
                <div class="hero-content">
                    <div class="hero-text" data-aos="fade-right">
                        <h1>Create Stunning Marketing Posts in Seconds</h1>
                        <p>Artera uses advanced AI to instantly generate professional festival posters, business templates, and custom social media content for your brand.</p>
                        <div class="hero-actions">
                            <a href="#" class="play-store-btn">
                                <i class="fa-brands fa-google-play"></i>
                                <div class="play-store-text">
                                    <span>GET IT ON</span>
                                    <strong>Google Play</strong>
                                </div>
                            </a>
                            <a href="{{ route('landing.features') }}" class="btn btn-outline" style="display:flex; align-items:center;">Explore Features</a>
                        </div>
                    </div>
                    <div class="hero-image" data-aos="fade-left">
                        <img src="{{ asset('landing/images/hero-phone.png') }}" alt="Artera App Interface" class="hero-img-element" onerror="this.src='https://placehold.co/400x800/1E3A8A/FFFFFF?text=App+Mockup'">
                    </div>
                </div>
            </div>

            <!-- Slide 2 -->
            <div class="slide">
                <div class="hero-content">
                    <div class="hero-text">
                        <h1>Automate Your Daily WhatsApp Marketing</h1>
                        <p>Stop worrying about what to post. Our Daily Drip engine automatically creates and suggests a fresh, branded product post for your WhatsApp status every morning.</p>
                        <div class="hero-actions">
                            <a href="#" class="play-store-btn">
                                <i class="fa-brands fa-google-play"></i>
                                <div class="play-store-text">
                                    <span>GET IT ON</span>
                                    <strong>Google Play</strong>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="hero-image">
                        <img src="{{ asset('landing/images/hero-phone.png') }}" alt="Marketing Automation" class="hero-img-element" style="animation-delay: -3s;" onerror="this.src='https://placehold.co/400x800/3B82F6/FFFFFF?text=Automation'">
                    </div>
                </div>
            </div>

            <!-- Slide 3 -->
            <div class="slide">
                <div class="hero-content">
                    <div class="hero-text">
                        <h1>AI Magic Cloner for Your Products</h1>
                        <p>Upload a photo of a competitor's ad, and our AI instantly analyzes the layout, colors, and fonts to recreate a matching template with YOUR products.</p>
                        <div class="hero-actions">
                            <a href="#" class="play-store-btn">
                                <i class="fa-brands fa-google-play"></i>
                                <div class="play-store-text">
                                    <span>GET IT ON</span>
                                    <strong>Google Play</strong>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="hero-image">
                        <img src="{{ asset('landing/images/app-screens.png') }}" alt="AI Magic Cloner" class="hero-img-element" style="border-radius: 0; box-shadow: none;" onerror="this.src='https://placehold.co/600x500/1E3A8A/FFFFFF?text=AI+Cloner'">
                    </div>
                </div>
            </div>

        </div>
        
        <div class="slider-dots" id="sliderDots">
            <div class="dot active" onclick="setSlide(0)"></div>
            <div class="dot" onclick="setSlide(1)"></div>
            <div class="dot" onclick="setSlide(2)"></div>
        </div>
    </div>
</section>
@endsection

@section('extra_js')
<script>
    let currentSlide = 0;
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    let slideInterval;

    function setSlide(index) {
        slides[currentSlide].classList.remove('active');
        dots[currentSlide].classList.remove('active');
        
        currentSlide = index;
        
        slides[currentSlide].classList.add('active');
        dots[currentSlide].classList.add('active');
        
        clearInterval(slideInterval);
        startSlider();
    }

    function nextSlide() {
        let next = (currentSlide + 1) % slides.length;
        setSlide(next);
    }

    function startSlider() {
        slideInterval = setInterval(nextSlide, 5000);
    }

    if(slides.length > 0) {
        startSlider();
    }
</script>
@endsection
