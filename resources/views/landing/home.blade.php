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

    /* Add new styles for features */
    .section-title { text-align: center; margin-bottom: 40px; }
    .section-title h2 { font-size: 36px; font-weight: 700; color: var(--primary); }
    .section-title p { font-size: 18px; color: var(--text-gray); }

    /* 3-Step Workflow */
    .workflow-section { padding: 80px 0; background: #fff; }
    .workflow-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; text-align: center; }
    .workflow-card { padding: 30px; border-radius: 15px; background: #f8fafc; transition: 0.3s; }
    .workflow-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    .workflow-icon { width: 80px; height: 80px; margin: 0 auto 20px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; }
    
    /* Category Carousel */
    .category-section { padding: 80px 0; background: #f1f5f9; }
    .category-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 20px; }
    .category-card { display: block; text-align: center; padding: 20px; background: #fff; border-radius: 12px; text-decoration: none; color: inherit; transition: 0.3s; border: 1px solid #e2e8f0; }
    .category-card:hover { transform: scale(1.05); box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-color: var(--primary); }
    .category-icon { font-size: 36px; color: var(--primary); margin-bottom: 10px; }
    
    /* Multilingual Banner */
    .language-section { padding: 60px 0; background: linear-gradient(135deg, var(--primary) 0%, #1E3A8A 100%); color: white; text-align: center; }
    .language-section h2 { color: white; }
    .lang-cloud { display: flex; justify-content: center; flex-wrap: wrap; gap: 15px; margin-top: 30px; }
    .lang-badge { padding: 10px 25px; border: 1px solid rgba(255,255,255,0.3); border-radius: 30px; font-weight: 600; background: rgba(255,255,255,0.1); }
    
    @media (max-width: 768px) {
        .workflow-grid { grid-template-columns: 1fr; }
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



        </div>
        
        <div class="slider-dots" id="sliderDots">
            <div class="dot active" onclick="setSlide(0)"></div>
            <div class="dot" onclick="setSlide(1)"></div>
        </div>
    </div>
</section>

<!-- 3-Step Workflow -->
<section class="workflow-section">
    <div class="container">
        <div class="section-title">
            <h2>Create & Share in 3 Easy Steps</h2>
            <p>Grow your business with professional marketing posts</p>
        </div>
        <div class="workflow-grid">
            <div class="workflow-card">
                <div class="workflow-icon"><i class="fa-solid fa-hand-pointer"></i></div>
                <h3>1. Select Template</h3>
                <p>Choose from 100,000+ ready-made templates for festivals and business needs.</p>
            </div>
            <div class="workflow-card">
                <div class="workflow-icon"><i class="fa-solid fa-pen-nib"></i></div>
                <h3>2. Customize</h3>
                <p>Add your logo, business details, and personalize the design in seconds.</p>
            </div>
            <div class="workflow-card">
                <div class="workflow-icon"><i class="fa-solid fa-share-nodes"></i></div>
                <h3>3. Download & Share</h3>
                <p>Instantly download high-quality images and share directly to your social media.</p>
            </div>
        </div>
    </div>
</section>

<!-- Category Section -->
<section class="category-section">
    <div class="container">
        <div class="section-title">
            <h2>Explore Business Categories</h2>
            <p>Find the perfect templates for your specific industry</p>
        </div>
        <div class="category-grid">
            <!-- Mock Categories -->
            <a href="{{ route('landing.category', 'real-estate') }}" class="category-card">
                <div class="category-icon"><i class="fa-solid fa-building"></i></div>
                <h4>Real Estate</h4>
            </a>
            <a href="{{ route('landing.category', 'doctors') }}" class="category-card">
                <div class="category-icon"><i class="fa-solid fa-user-doctor"></i></div>
                <h4>Doctors</h4>
            </a>
            <a href="{{ route('landing.category', 'politicians') }}" class="category-card">
                <div class="category-icon"><i class="fa-solid fa-bullhorn"></i></div>
                <h4>Politicians</h4>
            </a>
            <a href="{{ route('landing.category', 'education') }}" class="category-card">
                <div class="category-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                <h4>Education</h4>
            </a>
            <a href="{{ route('landing.category', 'restaurants') }}" class="category-card">
                <div class="category-icon"><i class="fa-solid fa-utensils"></i></div>
                <h4>Restaurants</h4>
            </a>
            <a href="{{ route('landing.category', 'jewellery') }}" class="category-card">
                <div class="category-icon"><i class="fa-solid fa-gem"></i></div>
                <h4>Jewellery</h4>
            </a>
        </div>
        <div style="text-align: center; margin-top: 40px;">
            <a href="{{ route('landing.templates') }}" class="btn btn-primary" style="background: var(--primary); color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: bold;">View All Templates</a>
        </div>
    </div>
</section>

<!-- Multilingual Banner -->
<section class="language-section">
    <div class="container">
        <h2>Your Content, Your Language</h2>
        <p style="color: rgba(255,255,255,0.8); font-size: 18px; margin-top:10px;">Connect with your local audience in their native language</p>
        <div class="lang-cloud">
            <span class="lang-badge">English</span>
            <span class="lang-badge">हिंदी (Hindi)</span>
            <span class="lang-badge">मराठी (Marathi)</span>
            <span class="lang-badge">ગુજરાતી (Gujarati)</span>
            <span class="lang-badge">தமிழ் (Tamil)</span>
            <span class="lang-badge">తెలుగు (Telugu)</span>
            <span class="lang-badge">ಕನ್ನಡ (Kannada)</span>
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
