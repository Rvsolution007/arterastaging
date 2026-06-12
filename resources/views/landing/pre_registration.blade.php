@extends('landing.layout')

@section('title', 'Pre-Register for Artera - Get 50% Off')

@section('extra_css')
<style>
    @keyframes scrollBackground {
        0% { background-position: 0 0; }
        100% { background-position: 0 1000px; }
    }

    .pre-register-section {
        padding: 48px 16px;
        background: linear-gradient(rgba(240, 248, 255, 0.92), rgba(224, 242, 254, 0.94)), url('{{ asset('assets/images/posters_bg.png') }}') repeat;
        background-size: 500px auto;
        animation: scrollBackground 30s linear infinite;
        display: flex;
        align-items: center;
        position: relative;
    }
    @media (min-width: 992px) {
        .pre-register-section { padding: 96px 0; }
    }
    .pre-register-card {
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        overflow: hidden;
        max-width: 1200px;
        width: 100%;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        border: 1px solid rgba(26,26,26,0.05);
    }
    @media (min-width: 992px) {
        .pre-register-card {
            flex-direction: row;
        }
    }
    
    /* Left Side Slider */
    .pre-register-slider {
        background: linear-gradient(135deg, var(--blue), #1E3A8A);
        color: #fff;
        position: relative;
        overflow: hidden;
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 32px 24px;
    }
    @media (min-width: 992px) {
        .pre-register-slider { padding: 48px; }
    }
    .pre-register-slider::after {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: url('data:image/svg+xml;utf8,<svg width="20" height="20" xmlns="http://www.w3.org/2000/svg"><circle cx="2" cy="2" r="2" fill="rgba(255,255,255,0.05)"/></svg>');
        pointer-events: none;
    }
    
    .slide-track {
        position: relative;
        flex: 1;
        display: flex;
        align-items: center;
        min-height: 280px;
    }
    
    .slide-item {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        opacity: 0;
        transition: opacity 1s ease-in-out;
        z-index: 1;
    }
    
    /* CSS Animation for 3 slides */
    @keyframes fadeSlides {
        0%, 25% { opacity: 1; z-index: 2; }
        33%, 92% { opacity: 0; z-index: 1; }
        100% { opacity: 1; z-index: 2; }
    }
    .slide-item:nth-child(1) { animation: fadeSlides 12s infinite; animation-delay: 0s; }
    .slide-item:nth-child(2) { animation: fadeSlides 12s infinite; animation-delay: 4s; }
    .slide-item:nth-child(3) { animation: fadeSlides 12s infinite; animation-delay: 8s; }

    .slide-item h2 {
        font-size: clamp(2rem, 3vw, 2.8rem);
        font-weight: 900;
        line-height: 1.2;
        margin-bottom: 24px;
    }
    .slide-item p {
        font-size: 1.125rem;
        opacity: 0.9;
        line-height: 1.6;
        margin-bottom: 32px;
    }
    
    .discount-badge {
        display: inline-flex;
        align-items: center;
        background: #fef08a;
        color: #854d0e;
        padding: 8px 16px;
        border-radius: 100px;
        font-weight: 800;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 24px;
        align-self: flex-start;
        z-index: 10;
        position: relative;
    }

    /* Right Side Form */
    .pre-register-form-wrap {
        padding: 32px 24px;
        flex: 1;
        max-width: 600px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: #fff;
    }
    .form-row {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-bottom: 20px;
    }
    @media (min-width: 768px) {
        .form-row { flex-direction: row; }
    }
    .form-row .form-group {
        flex: 1;
        margin-bottom: 0;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 8px;
        color: var(--text-dark);
    }
    .form-group input, .form-group select {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid rgba(26,26,26,0.1);
        border-radius: 10px;
        font-size: 15px;
        font-family: inherit;
        transition: var(--transition);
        outline: none;
        background: #fafafa;
    }
    .form-group input:focus, .form-group select:focus {
        border-color: var(--blue);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
    }
    .alert-success {
        background: #dcfce7;
        color: #166534;
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 24px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 24px;
        font-weight: 600;
    }
    .btn-submit {
        width: 100%;
        padding: 16px;
        border-radius: 12px;
        background: var(--blue);
        color: #fff;
        font-size: 16px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin-top: 10px;
    }
    .btn-submit:hover {
        background: #2563eb;
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(59,130,246,0.2);
    }

    /* Sections Below */
    .why-section {
        padding: 128px 0;
        background: var(--bg-dark);
        color: #fff;
        position: relative;
    }
    .why-header { margin-bottom: 80px; text-align: center; }
    .why-header .heading-lg { margin-top: 16px; max-width: 800px; margin-left: auto; margin-right: auto;}
    .why-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 32px;
        max-width: 1200px;
        margin: 0 auto;
    }
    @media (min-width: 768px) { .why-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .why-grid { grid-template-columns: repeat(4, 1fr); } }

    .why-card {
        padding: 32px;
        border: 1px solid rgba(255,255,255,0.1);
        transition: border-color 0.3s ease;
        border-radius: 16px;
        background: rgba(255,255,255,0.02);
    }
    .why-card:hover { border-color: rgba(59, 130, 246, 0.5); background: rgba(255,255,255,0.05); }
    .why-card-icon {
        width: 48px; height: 48px;
        background: rgba(59, 130, 246, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
        border-radius: 12px;
    }
    .why-card-icon i { font-size: 20px; color: var(--blue); }
    .why-card h3 { font-size: 18px; font-weight: 700; margin-bottom: 12px; }
    .why-card p { color: rgba(255,255,255,0.6); line-height: 1.65; font-size: 15px; }

    .about-section {
        padding: 80px 16px;
        background: url('{{ asset('assets/images/about_bg.png') }}') center/cover no-repeat fixed;
        text-align: center;
        position: relative;
        display: flex;
        align-items: center;
    }
    @media (min-width: 992px) {
        .about-section { padding: 160px 0; min-height: 80vh; }
    }
    .about-section::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(14, 165, 233, 0.1);
    }
    .about-inner {
        max-width: 900px;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.95);
        padding: 40px 24px;
        border-radius: 24px;
        box-shadow: 0 40px 80px rgba(0,0,0,0.1);
        position: relative;
        z-index: 1;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.5);
    }
    @media (min-width: 992px) {
        .about-inner { padding: 80px 64px; border-radius: 32px; }
    }
    .about-inner h2 {
        font-size: clamp(2.5rem, 4vw, 3.5rem);
        font-weight: 900;
        margin-bottom: 32px;
        color: var(--text-dark);
        line-height: 1.2;
    }
    .about-inner p {
        font-size: 1.25rem;
        color: var(--text-gray);
        line-height: 1.8;
        margin-bottom: 24px;
    }
</style>
@endsection

@section('content')

{{-- Pre-Register Form & Slider --}}
<section class="pre-register-section">
    <div class="container-full">
        <div class="pre-register-card">
            
            {{-- Left Slider --}}
            <div class="pre-register-slider">
                <span class="discount-badge">🎁 50% Off First Use</span>
                
                <div class="slide-track">
                    <div class="slide-item">
                        <h2>Be the First to Experience Artera.</h2>
                        <p>Register before our official launch, and instantly unlock a <strong>50% discount</strong>. This exclusive offer is linked directly to your mobile number.</p>
                    </div>
                    <div class="slide-item">
                        <h2>AI-Powered Marketing Automations.</h2>
                        <p>Our intelligent system generates daily promotional content tailored perfectly to your brand colors and typography. Just approve and post.</p>
                    </div>
                    <div class="slide-item">
                        <h2>Grow Your Business Faster.</h2>
                        <p>Focus on what you do best while Artera handles your social media presence with stunning designs across all regional languages.</p>
                    </div>
                </div>

                <div style="margin-top: auto; font-size: 14px; opacity: 0.7; z-index: 10; position: relative;">
                    <i class="fa-solid fa-shield-halved"></i> 100% Secure. We respect your privacy.
                </div>
            </div>

            {{-- Right Form --}}
            <div class="pre-register-form-wrap">
                <h3 style="font-size: 28px; font-weight: 900; margin-bottom: 32px; color: var(--text-dark);">Claim Your Spot</h3>

                @if(session('success'))
                    <div class="alert-success">
                        <i class="fa-solid fa-circle-check" style="font-size: 20px;"></i>
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert-error">
                        <strong>Whoops! Something went wrong.</strong>
                        <ul style="margin: 8px 0 0 20px;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('landing.pre_register.store') }}" method="POST">
                    @csrf
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Your Name <span style="color:red;">*</span></label>
                            <input type="text" name="name" id="name" placeholder="John Doe" value="{{ old('name') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="business_name">Business Name <span style="color:red;">*</span></label>
                            <input type="text" name="business_name" id="business_name" placeholder="Your Brand Ltd." value="{{ old('business_name') }}" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="mobile">Mobile Number <span style="color:red;">*</span></label>
                            <input type="tel" name="mobile" id="mobile" placeholder="9876543210" value="{{ old('mobile') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address <span style="color:#888; font-weight:normal;">(Optional)</span></label>
                            <input type="email" name="email" id="email" placeholder="you@example.com" value="{{ old('email') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="category">Business Category <span style="color:red;">*</span></label>
                        <select name="category" id="category" required>
                            <option value="">Select Category</option>
                            <option value="Real Estate" {{ old('category') == 'Real Estate' ? 'selected' : '' }}>Real Estate</option>
                            <option value="Healthcare / Doctor" {{ old('category') == 'Healthcare / Doctor' ? 'selected' : '' }}>Healthcare / Doctor</option>
                            <option value="Education" {{ old('category') == 'Education' ? 'selected' : '' }}>Education</option>
                            <option value="Restaurant / Food" {{ old('category') == 'Restaurant / Food' ? 'selected' : '' }}>Restaurant / Food</option>
                            <option value="Retail / Shop" {{ old('category') == 'Retail / Shop' ? 'selected' : '' }}>Retail / Shop</option>
                            <option value="Jewellery" {{ old('category') == 'Jewellery' ? 'selected' : '' }}>Jewellery</option>
                            <option value="Automotive" {{ old('category') == 'Automotive' ? 'selected' : '' }}>Automotive</option>
                            <option value="IT / Software" {{ old('category') == 'IT / Software' ? 'selected' : '' }}>IT / Software</option>
                            <option value="Other" {{ old('category') == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-submit">
                        Secure My 50% Discount <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

{{-- Features Section (Similar to Home page) --}}
<section class="why-section">
    <div class="container-full">
        <div class="why-header">
            <span class="eyebrow-plain" style="color:var(--blue); font-weight:700; letter-spacing: 2px;">WHY ARTERA</span>
            <h2 class="heading-lg">An AI marketing machine for your business.</h2>
        </div>
        <div class="why-grid">
            <div class="why-card">
                <div class="why-card-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                <h3>AI-Powered Generation</h3>
                <p>Our AI engine creates unique posters and templates tailored to your business, brand colors, and industry.</p>
            </div>
            <div class="why-card">
                <div class="why-card-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <h3>Daily Drip Marketing</h3>
                <p>Wake up to a fresh, branded marketing post every morning. Automatic content for your WhatsApp status.</p>
            </div>
            <div class="why-card">
                <div class="why-card-icon"><i class="fa-solid fa-language"></i></div>
                <h3>Multilingual Content</h3>
                <p>Create posts in Hindi, Marathi, Gujarati, Tamil, Telugu, and more. Reach your local audience easily.</p>
            </div>
            <div class="why-card">
                <div class="why-card-icon"><i class="fa-solid fa-bolt"></i></div>
                <h3>Ready in Seconds</h3>
                <p>Select a template, add your details, and download instantly. From idea to social media post in under 30 seconds.</p>
            </div>
        </div>
    </div>
</section>

{{-- About Company Section --}}
<section class="about-section">
    <div class="container-full">
        <div class="about-inner">
            <span style="color:var(--blue); font-weight:700; letter-spacing: 2px; text-transform:uppercase; display:block; margin-bottom:16px;">The Artera Story</span>
            <h2>Empowering Local Businesses with Global Technology</h2>
            
            <div style="text-align: left; margin-top: 40px;">
                <p>At Artera, we believe that every business, regardless of size, deserves access to world-class marketing tools. We built our AI-powered platform to bridge the gap between complex design software and the everyday needs of business owners.</p>
                
                <p>Our journey started with a simple observation: local businesses struggle to maintain a consistent, high-quality presence on social media. Hiring a professional agency is often too expensive, and designing posts manually takes away precious time from running the business. Artera solves this by acting as your virtual digital marketing partner, fully automated by AI.</p>

                <h4 style="font-size: 22px; font-weight: 800; color: var(--text-dark); margin: 40px 0 16px;">Our Vision</h4>
                <p>We envision a world where any entrepreneur can launch a professional marketing campaign in under 30 seconds. By leveraging advanced generative AI, we are democratizing design and branding. We put agency-level marketing straight into the pockets of millions of business owners across India and beyond.</p>

                <div style="display: flex; flex-wrap: wrap; gap: 32px; margin-top: 48px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 32px;">
                    <div style="flex: 1; min-width: 250px;">
                        <h5 style="color: var(--blue); font-weight: 800; font-size: 18px; margin-bottom: 12px;"><i class="fa-solid fa-robot"></i> Intelligent Automation</h5>
                        <p style="font-size: 16px; line-height: 1.6; color: var(--text-gray);">Artera learns your brand kit. It auto-generates daily WhatsApp statuses and social posts tailored perfectly to your business typography and colors.</p>
                    </div>
                    <div style="flex: 1; min-width: 250px;">
                        <h5 style="color: var(--blue); font-weight: 800; font-size: 18px; margin-bottom: 12px;"><i class="fa-solid fa-earth-asia"></i> Regional Connection</h5>
                        <p style="font-size: 16px; line-height: 1.6; color: var(--text-gray);">Marketing is best when it's local. We provide high-quality automated content in 7+ regional languages to connect with your authentic audience.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
