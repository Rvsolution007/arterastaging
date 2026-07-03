@extends('landing.layout')

@section('title', 'Artera - Create Stunning Marketing Posts in Seconds')

@section('extra_css')
<style>
    /* ============================================
       HERO SECTION — Marquee Columns + Bold Text
       ============================================ */
    .hero-section {
        position: relative;
        padding: 96px 0 64px;
        display: flex;
        align-items: center;
        overflow: hidden;
        min-height: calc(100vh - 64px);
        background: #fff;
    }

    .hero-grid {
        display: flex;
        flex-direction: column-reverse;
        gap: 40px;
    }
    @media (min-width: 1024px) {
        .hero-grid {
            flex-direction: row;
            align-items: center;
            gap: 64px;
        }
    }

    .hero-text-col { flex: 1; min-width: 0; }
    .hero-text-col .heading-xl span.blue { color: var(--blue); }

    .hero-subtitle {
        font-size: clamp(1.125rem, 2vw, 1.5rem);
        color: var(--text-gray);
        font-weight: 500;
        line-height: 1.6;
        margin-bottom: 40px;
        max-width: 640px;
    }
    .hero-buttons { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; }

    /* Marquee image columns */
    .hero-marquee-col {
        position: relative;
        width: 100%;
        max-width: 500px;
        height: 400px;
        flex-shrink: 0;
        overflow: hidden;
        margin: 0 auto;
    }
    @media (min-width: 1024px) {
        .hero-marquee-col {
            width: 520px;
            height: calc(100vh - 128px);
            max-width: none;
        }
    }
    @media (min-width: 1280px) {
        .hero-marquee-col { width: 620px; }
    }

    .marquee-fade-top,
    .marquee-fade-bottom {
        position: absolute;
        left: 0; right: 0;
        height: 96px;
        z-index: 10;
        pointer-events: none;
    }
    .marquee-fade-top { top: 0; background: linear-gradient(to bottom, #fff, transparent); }
    .marquee-fade-bottom { bottom: 0; background: linear-gradient(to top, #fff, transparent); }

    .marquee-columns {
        display: flex;
        gap: 12px;
        height: 100%;
    }
    .marquee-col {
        flex: 1;
        overflow: hidden;
    }
    .marquee-track {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .marquee-track-up {
        animation: marqueeUp 40s linear infinite;
    }
    .marquee-track-down {
        animation: marqueeDown 40s linear infinite;
    }

    @keyframes marqueeUp {
        0% { transform: translateY(0); }
        100% { transform: translateY(-50%); }
    }
    @keyframes marqueeDown {
        0% { transform: translateY(-50%); }
        100% { transform: translateY(0); }
    }

    .marquee-card {
        width: 100%;
        border-radius: 16px;
        overflow: hidden;
        background: linear-gradient(135deg, #e8edf5, #d4dbe8);
        position: relative;
    }
    .marquee-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    /* ============================================
       TRUSTED BY — Blue bar
       ============================================ */
    .trusted-section {
        padding: 48px 0;
        background: #1d4ed8;
    }
    .trusted-label {
        text-align: center;
        font-family: 'JetBrains Mono', monospace;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.2em;
        color: rgba(255,255,255,0.9);
        margin-bottom: 32px;
    }
    .trusted-logos {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        align-items: center;
        gap: 24px;
    }
    .trusted-logo-item {
        height: 56px;
        padding: 0 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: opacity 0.3s ease;
    }
    .trusted-logo-item:hover { opacity: 0.85; }
    .trusted-logo-item i {
        font-size: 28px;
        color: #fff;
    }
    .trusted-logo-item span {
        font-size: 18px;
        font-weight: 700;
        color: #fff;
        letter-spacing: 0.02em;
    }

    /* ============================================
       WHY DIFFERENT — Dark section
       ============================================ */
    .why-section {
        padding: 128px 0;
        background: var(--bg-dark);
        color: #fff;
        position: relative;
    }
    .why-section .top-line {
        position: absolute;
        top: 0; left: 0; width: 100%;
        height: 1px;
        background: linear-gradient(to right, transparent, rgba(255,255,255,0.2), transparent);
    }
    .why-header { margin-bottom: 80px; }
    .why-header .heading-lg { margin-top: 16px; max-width: 800px; white-space: pre-line; }
    .why-header .why-desc {
        font-size: clamp(1rem, 1.5vw, 1.25rem);
        color: rgba(255,255,255,0.5);
        margin-top: 24px;
        max-width: 640px;
        line-height: 1.6;
    }
    .why-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 32px;
        max-width: 1400px;
        margin: 0 auto;
    }
    @media (min-width: 768px) { .why-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1280px) { .why-grid { grid-template-columns: repeat(4, 1fr); } }

    .why-card {
        padding: 32px;
        border: 1px solid rgba(255,255,255,0.1);
        transition: border-color 0.3s ease;
    }
    .why-card:hover { border-color: rgba(59, 130, 246, 0.5); }
    .why-card-icon {
        width: 48px; height: 48px;
        background: rgba(59, 130, 246, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
    }
    .why-card-icon i { font-size: 20px; color: var(--blue); }
    .why-card h3 { font-size: 18px; font-weight: 700; margin-bottom: 12px; }
    .why-card p { color: rgba(255,255,255,0.5); line-height: 1.65; font-size: 15px; }

    /* ============================================
       HOW IT WORKS — Numbered steps
       ============================================ */
    .howit-section {
        padding: 128px 0;
        background: linear-gradient(135deg, #2563eb, #3b82f6);
        color: #fff;
    }
    .howit-header { margin-bottom: 64px; }
    .howit-header .eyebrow-light {
        display: inline-block;
        font-family: 'JetBrains Mono', monospace;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: #fecaca;
    }
    .howit-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 32px;
    }
    @media (min-width: 1024px) { .howit-grid { grid-template-columns: repeat(3, 1fr); } }

    .howit-step { position: relative; }
    .howit-step-number {
        font-size: 8rem;
        font-weight: 900;
        color: rgba(255,255,255,0.1);
        position: absolute;
        top: -32px; left: -16px;
        line-height: 1;
        pointer-events: none;
    }
    .howit-step-body { position: relative; padding-top: 64px; }
    .howit-step h3 { font-size: 24px; font-weight: 900; margin-bottom: 16px; }
    .howit-step p { font-size: 18px; color: rgba(255,255,255,0.8); line-height: 1.65; }

    /* ============================================
       CATEGORIES — White section
       ============================================ */
    .categories-section {
        padding: 128px 0;
        background: #fff;
    }
    .categories-header { margin-bottom: 64px; }
    .categories-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
    }
    @media (min-width: 768px) { .categories-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (min-width: 1024px) { .categories-grid { grid-template-columns: repeat(6, 1fr); } }

    .category-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 32px 16px;
        border: 2px solid rgba(26, 26, 26, 0.08);
        text-decoration: none;
        color: var(--text-dark);
        transition: var(--transition);
        text-align: center;
    }
    .category-card:hover {
        border-color: var(--blue);
        transform: translateY(-4px);
        box-shadow: 0 20px 40px rgba(59, 130, 246, 0.1);
    }
    .category-card i {
        font-size: 32px;
        color: var(--blue);
        margin-bottom: 16px;
    }
    .category-card h4 {
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    /* ============================================
       LANGUAGE — Gradient section
       ============================================ */
    .lang-section {
        padding: 96px 0;
        background: linear-gradient(135deg, var(--primary), #1E3A8A);
        color: #fff;
        text-align: center;
        position: relative;
    }
    .lang-cloud {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 40px;
    }
    .lang-badge {
        padding: 12px 28px;
        border: 2px solid rgba(255,255,255,0.25);
        font-weight: 600;
        font-size: 14px;
        background: rgba(255,255,255,0.08);
        backdrop-filter: blur(4px);
        transition: var(--transition);
    }
    .lang-badge:hover {
        background: rgba(255,255,255,0.2);
        border-color: rgba(255,255,255,0.5);
        transform: translateY(-2px);
    }

    /* ============================================
       COMPARISON TABLE — 8x-style
       ============================================ */
    .compare-section {
        padding: 128px 0;
        background: #fff;
    }
    .compare-header { margin-bottom: 64px; }
    .compare-table-wrap { overflow-x: auto; }
    .compare-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 600px;
    }
    .compare-table thead th {
        text-align: center;
        padding: 16px 24px;
        font-size: 13px;
    }
    .compare-table thead th:first-child {
        text-align: left;
        width: 35%;
    }
    .compare-table .col-highlight {
        background: var(--blue);
        color: #fff;
    }
    .compare-table .col-highlight-top {
        border-radius: 12px 12px 0 0;
    }
    .compare-table .col-highlight-bottom {
        border-radius: 0 0 12px 12px;
        height: 8px;
    }
    .compare-table .highlight-name {
        font-size: 16px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .compare-table .highlight-sub {
        font-size: 11px;
        font-family: 'JetBrains Mono', monospace;
        opacity: 0.8;
        margin-top: 4px;
    }
    .compare-table tbody td {
        padding: 16px 24px;
        text-align: center;
        font-size: 14px;
        color: var(--text-gray);
        font-weight: 500;
        border-top: 1px solid rgba(26, 26, 26, 0.06);
        transition: background 0.2s ease;
    }
    .compare-table tbody td:first-child { text-align: left; }
    .compare-table tbody tr:hover td { background: #f8f9fa; }
    .compare-table .cell-highlight {
        background: rgba(59, 130, 246, 0.05);
        border-left: 1px solid rgba(59, 130, 246, 0.15);
        border-right: 1px solid rgba(59, 130, 246, 0.15);
        color: var(--blue);
        font-weight: 700;
    }
    .compare-check { color: var(--blue); font-size: 18px; }
    .compare-x { color: rgba(26,26,26,0.15); font-size: 18px; }

    /* ============================================
       FINAL CTA — Dark gradient
       ============================================ */
    .cta-section {
        padding: 128px 0;
        background: var(--bg-dark);
        color: #fff;
        position: relative;
        overflow: hidden;
    }
    .cta-glow-1 {
        position: absolute;
        top: 0; right: 0;
        width: 384px; height: 384px;
        border-radius: 50%;
        background: rgba(255,255,255,0.06);
        filter: blur(80px);
    }
    .cta-glow-2 {
        position: absolute;
        bottom: 0; left: 0;
        width: 384px; height: 384px;
        border-radius: 50%;
        background: rgba(59, 130, 246, 0.08);
        filter: blur(80px);
    }
    .cta-inner {
        text-align: center;
        max-width: 800px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
    }
    .cta-inner .heading-xl { margin-bottom: 32px; }
    .cta-inner .cta-desc {
        font-size: clamp(1.125rem, 2vw, 1.5rem);
        color: rgba(255,255,255,0.85);
        margin-bottom: 48px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    /* ---- Responsive tweaks ---- */
    @media (max-width: 768px) {
        .hero-section { padding: 48px 0 32px; min-height: auto; }
        .hero-marquee-col { height: 320px; }
        .hero-buttons { justify-content: center; }
        .hero-text-col { text-align: center; }
        .btn-sharp { padding: 16px 28px; font-size: 14px; justify-content: center; width: 100%; }
        .why-section, .howit-section, .categories-section, .compare-section, .cta-section { padding: 80px 0; }
        .why-header, .categories-header, .compare-header { margin-bottom: 48px; }
    }
</style>
@endsection

@section('content')

{{-- ============================================
    HERO SECTION
    ============================================ --}}
<section class="hero-section">
    <div class="noise-overlay"></div>
    <div class="container-full" style="position:relative; z-index:1;">
        <div class="hero-grid">
            {{-- Marquee Image Columns --}}
            <div class="hero-marquee-col reveal">
                <div class="marquee-fade-top"></div>
                <div class="marquee-fade-bottom"></div>
                <div class="marquee-columns">
                    {{-- Column 1 — Up --}}
                    <div class="marquee-col">
                        <div class="marquee-track marquee-track-up">
                            <div class="marquee-card" style="height:280px; background:linear-gradient(135deg,#667eea,#764ba2);"></div>
                            <div class="marquee-card" style="height:200px; background:linear-gradient(135deg,#f093fb,#f5576c);"></div>
                            <div class="marquee-card" style="height:240px; background:linear-gradient(135deg,#4facfe,#00f2fe);"></div>
                            <div class="marquee-card" style="height:220px; background:linear-gradient(135deg,#43e97b,#38f9d7);"></div>
                            <div class="marquee-card" style="height:260px; background:linear-gradient(135deg,#fa709a,#fee140);"></div>
                            <div class="marquee-card" style="height:200px; background:linear-gradient(135deg,#a18cd1,#fbc2eb);"></div>
                            {{-- Duplicate for seamless loop --}}
                            <div class="marquee-card" style="height:280px; background:linear-gradient(135deg,#667eea,#764ba2);"></div>
                            <div class="marquee-card" style="height:200px; background:linear-gradient(135deg,#f093fb,#f5576c);"></div>
                            <div class="marquee-card" style="height:240px; background:linear-gradient(135deg,#4facfe,#00f2fe);"></div>
                            <div class="marquee-card" style="height:220px; background:linear-gradient(135deg,#43e97b,#38f9d7);"></div>
                            <div class="marquee-card" style="height:260px; background:linear-gradient(135deg,#fa709a,#fee140);"></div>
                            <div class="marquee-card" style="height:200px; background:linear-gradient(135deg,#a18cd1,#fbc2eb);"></div>
                        </div>
                    </div>
                    {{-- Column 2 — Down --}}
                    <div class="marquee-col">
                        <div class="marquee-track marquee-track-down">
                            <div class="marquee-card" style="height:220px; background:linear-gradient(135deg,#ffecd2,#fcb69f);"></div>
                            <div class="marquee-card" style="height:280px; background:linear-gradient(135deg,#a1c4fd,#c2e9fb);"></div>
                            <div class="marquee-card" style="height:200px; background:linear-gradient(135deg,#d4fc79,#96e6a1);"></div>
                            <div class="marquee-card" style="height:260px; background:linear-gradient(135deg,#84fab0,#8fd3f4);"></div>
                            <div class="marquee-card" style="height:220px; background:linear-gradient(135deg,#fbc2eb,#a6c1ee);"></div>
                            <div class="marquee-card" style="height:240px; background:linear-gradient(135deg,#fdcbf1,#e6dee9);"></div>
                            {{-- Duplicate --}}
                            <div class="marquee-card" style="height:220px; background:linear-gradient(135deg,#ffecd2,#fcb69f);"></div>
                            <div class="marquee-card" style="height:280px; background:linear-gradient(135deg,#a1c4fd,#c2e9fb);"></div>
                            <div class="marquee-card" style="height:200px; background:linear-gradient(135deg,#d4fc79,#96e6a1);"></div>
                            <div class="marquee-card" style="height:260px; background:linear-gradient(135deg,#84fab0,#8fd3f4);"></div>
                            <div class="marquee-card" style="height:220px; background:linear-gradient(135deg,#fbc2eb,#a6c1ee);"></div>
                            <div class="marquee-card" style="height:240px; background:linear-gradient(135deg,#fdcbf1,#e6dee9);"></div>
                        </div>
                    </div>
                    {{-- Column 3 — Up --}}
                    <div class="marquee-col" style="display:none;">
                        <div class="marquee-track marquee-track-up" style="animation-duration:45s;">
                            <div class="marquee-card" style="height:200px; background:linear-gradient(135deg,#e0c3fc,#8ec5fc);"></div>
                            <div class="marquee-card" style="height:260px; background:linear-gradient(135deg,#f5576c,#ff6a00);"></div>
                            <div class="marquee-card" style="height:220px; background:linear-gradient(135deg,#667eea,#764ba2);"></div>
                            <div class="marquee-card" style="height:240px; background:linear-gradient(135deg,#ffecd2,#fcb69f);"></div>
                            <div class="marquee-card" style="height:200px; background:linear-gradient(135deg,#89f7fe,#66a6ff);"></div>
                            <div class="marquee-card" style="height:260px; background:linear-gradient(135deg,#fddb92,#d1fdff);"></div>
                            {{-- Duplicate --}}
                            <div class="marquee-card" style="height:200px; background:linear-gradient(135deg,#e0c3fc,#8ec5fc);"></div>
                            <div class="marquee-card" style="height:260px; background:linear-gradient(135deg,#f5576c,#ff6a00);"></div>
                            <div class="marquee-card" style="height:220px; background:linear-gradient(135deg,#667eea,#764ba2);"></div>
                            <div class="marquee-card" style="height:240px; background:linear-gradient(135deg,#ffecd2,#fcb69f);"></div>
                            <div class="marquee-card" style="height:200px; background:linear-gradient(135deg,#89f7fe,#66a6ff);"></div>
                            <div class="marquee-card" style="height:260px; background:linear-gradient(135deg,#fddb92,#d1fdff);"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Hero Text --}}
            <div class="hero-text-col">
                <div class="reveal">
                    <span class="eyebrow"><span class="typewriter">AI-POWERED MARKETING</span></span>
                </div>
                <h1 class="heading-xl split-text" style="margin-bottom:32px;">
                    <span style="display:block;">Create stunning</span>
                    <span style="display:block;" class="blue">marketing posts</span>
                    <span style="display:block;">in seconds.</span>
                </h1>
                <div class="reveal reveal-delay-2">
                    <p class="hero-subtitle stagger-words">
                        Artera uses advanced AI to instantly generate professional festival posters, business templates, and custom social media content for your brand.
                    </p>
                    <div class="hero-buttons">
                        <a href="#" class="btn-sharp btn-sharp-primary btn-glow">
                            <i class="fa-brands fa-google-play"></i>
                            Download App
                            <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                        <a href="{{ route('landing.features') }}" class="btn-sharp btn-sharp-outline">
                            Explore Features
                            <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================
    TRUSTED BY — Blue bar
    ============================================ --}}
<section class="trusted-section">
    <div class="container-full">
        <p class="trusted-label reveal">Trusted by growing businesses</p>
        <div class="trusted-logos">
            <div class="trusted-logo-item reveal reveal-delay-1"><span><i class="fa-solid fa-building-columns"></i>&nbsp; Real Estate</span></div>
            <div class="trusted-logo-item reveal reveal-delay-1"><span><i class="fa-solid fa-user-doctor"></i>&nbsp; Healthcare</span></div>
            <div class="trusted-logo-item reveal reveal-delay-2"><span><i class="fa-solid fa-graduation-cap"></i>&nbsp; Education</span></div>
            <div class="trusted-logo-item reveal reveal-delay-2"><span><i class="fa-solid fa-utensils"></i>&nbsp; Restaurants</span></div>
            <div class="trusted-logo-item reveal reveal-delay-3"><span><i class="fa-solid fa-gem"></i>&nbsp; Jewellery</span></div>
            <div class="trusted-logo-item reveal reveal-delay-3"><span><i class="fa-solid fa-store"></i>&nbsp; Retail</span></div>
            <div class="trusted-logo-item reveal reveal-delay-4"><span><i class="fa-solid fa-car"></i>&nbsp; Automotive</span></div>
        </div>
    </div>
</section>

{{-- ============================================
    WHY ARTERA — Dark section
    ============================================ --}}
<section class="why-section">
    <div class="top-line"></div>
    <div class="noise-overlay"></div>
    <div class="container-full" style="position:relative; z-index:1;">
        <div class="why-header">
            <span class="eyebrow-plain reveal" style="color:var(--blue);">WHY ARTERA IS DIFFERENT</span>
            <h2 class="heading-lg split-text" style="margin-top:16px;">Not just templates.
An AI marketing machine.</h2>
            <p class="why-desc stagger-words">Most apps give you static templates. Artera generates personalized, branded content using AI — automatically, every single day, in your language.</p>
        </div>
        <div class="why-grid">
            <div class="why-card reveal-blur">
                <div class="why-card-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                <h3>AI-Powered Generation</h3>
                <p>Our AI engine creates unique posters and templates tailored to your business, brand colors, and industry — no design skills needed.</p>
            </div>
            <div class="why-card reveal-blur reveal-delay-1">
                <div class="why-card-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <h3>Daily Drip Marketing</h3>
                <p>Wake up to a fresh, branded marketing post every morning. Automatic content for your WhatsApp status and social media.</p>
            </div>
            <div class="why-card reveal-blur reveal-delay-2">
                <div class="why-card-icon"><i class="fa-solid fa-language"></i></div>
                <h3>Multilingual Content</h3>
                <p>Create posts in Hindi, Marathi, Gujarati, Tamil, Telugu, and more. Reach your local audience in their native language.</p>
            </div>
            <div class="why-card reveal-blur reveal-delay-3">
                <div class="why-card-icon"><i class="fa-solid fa-bolt"></i></div>
                <h3>Ready in Seconds</h3>
                <p>Select a template, add your business details, and download instantly. From idea to social media post in under 30 seconds.</p>
            </div>
        </div>
    </div>
</section>

{{-- ============================================
    COMPARISON TABLE
    ============================================ --}}
<section class="compare-section">
    <div class="container-full">
        <div class="compare-header">
            <span class="eyebrow-plain reveal">HOW WE COMPARE</span>
            <h2 class="heading-md split-text" style="margin-top:16px; max-width:600px; white-space:pre-line;">Artera vs. generic editors
vs. manual design</h2>
            <p class="stagger-words" style="font-size:clamp(1rem,1.5vw,1.25rem); color:var(--text-gray); margin-top:24px; max-width:640px; line-height:1.65;">AI-powered templates give you professional quality, local language support, and automation. Here's why businesses are switching.</p>
        </div>
        <div class="compare-table-wrap reveal-scale reveal-delay-1">
            <table class="compare-table">
                <thead>
                    <tr>
                        <th></th>
                        <th class="col-highlight col-highlight-top">
                            <div class="highlight-name">Artera</div>
                            <div class="highlight-sub">AI Marketing Platform</div>
                        </th>
                        <th><span style="font-weight:700; color:var(--text-dark);">Canva / Generic</span></th>
                        <th><span style="font-weight:700; color:var(--text-dark);">Manual Design</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>AI-generated content</td>
                        <td class="cell-highlight"><i class="fa-solid fa-check compare-check"></i></td>
                        <td><i class="fa-solid fa-xmark compare-x"></i></td>
                        <td><i class="fa-solid fa-xmark compare-x"></i></td>
                    </tr>
                    <tr>
                        <td>Auto daily posts</td>
                        <td class="cell-highlight"><i class="fa-solid fa-check compare-check"></i></td>
                        <td><i class="fa-solid fa-xmark compare-x"></i></td>
                        <td><i class="fa-solid fa-xmark compare-x"></i></td>
                    </tr>
                    <tr>
                        <td>Festival templates (auto)</td>
                        <td class="cell-highlight"><i class="fa-solid fa-check compare-check"></i></td>
                        <td><span style="color:var(--text-gray);">Manual search</span></td>
                        <td><i class="fa-solid fa-xmark compare-x"></i></td>
                    </tr>
                    <tr>
                        <td>Regional language support</td>
                        <td class="cell-highlight"><span style="font-weight:700; color:var(--blue);">7+ languages</span></td>
                        <td><span style="color:var(--text-gray);">Limited</span></td>
                        <td><span style="color:var(--text-gray);">Manual only</span></td>
                    </tr>
                    <tr>
                        <td>Brand kit integration</td>
                        <td class="cell-highlight"><i class="fa-solid fa-check compare-check"></i></td>
                        <td><i class="fa-solid fa-check compare-check"></i></td>
                        <td><i class="fa-solid fa-xmark compare-x"></i></td>
                    </tr>
                    <tr>
                        <td>Time to create a post</td>
                        <td class="cell-highlight"><span style="font-weight:700; color:var(--blue);">&lt; 30 sec</span></td>
                        <td><span style="color:var(--text-gray);">5–15 min</span></td>
                        <td><span style="color:var(--text-gray);">30+ min</span></td>
                    </tr>
                    <tr>
                        <td>Cost per month</td>
                        <td class="cell-highlight"><span style="font-weight:700; color:var(--blue);">₹99–499</span></td>
                        <td><span style="color:var(--text-gray);">₹500–3999</span></td>
                        <td><span style="color:var(--text-gray);">₹5000+</span></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td class="col-highlight col-highlight-bottom"></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</section>

{{-- ============================================
    HOW IT WORKS — 3 Steps
    ============================================ --}}
<section class="howit-section">
    <div class="noise-overlay" style="opacity:0.05;"></div>
    <div class="container-full" style="position:relative; z-index:1;">
        <div class="howit-header reveal">
            <span class="howit-header .eyebrow-light" style="color:#fecaca; font-family:'JetBrains Mono',monospace; font-size:12px; text-transform:uppercase; letter-spacing:0.15em; font-weight:600;"><span class="typewriter">HOW IT WORKS</span></span>
            <h2 class="heading-lg split-text" style="margin-top:16px; color:#fff;">Three simple steps</h2>
        </div>
        <div class="howit-grid">
            <div class="howit-step reveal">
                <span class="howit-step-number reveal-scale">01</span>
                <div class="howit-step-body">
                    <h3>Select a Template</h3>
                    <p>Choose from 100,000+ ready-made templates — festivals, business posts, offers, greetings, and more. Filtered for your industry.</p>
                </div>
            </div>
            <div class="howit-step reveal reveal-delay-1">
                <span class="howit-step-number reveal-scale">02</span>
                <div class="howit-step-body">
                    <h3>Customize with AI</h3>
                    <p>Add your logo, business name, contact info. Our AI auto-fills your brand kit. Change text, colors, and language in one tap.</p>
                </div>
            </div>
            <div class="howit-step reveal reveal-delay-2">
                <span class="howit-step-number reveal-scale">03</span>
                <div class="howit-step-body">
                    <h3>Download & Share</h3>
                    <p>Instantly download high-quality images. Share directly to WhatsApp, Instagram, Facebook, or any social media platform.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================
    CATEGORIES — Explore by Industry
    ============================================ --}}
<section class="categories-section">
    <div class="container-full">
        <div class="categories-header reveal">
            <span class="eyebrow-plain">EXPLORE BY INDUSTRY</span>
            <h2 class="heading-md" style="margin-top:16px; max-width:600px;">Templates for every business.</h2>
            <p style="font-size:clamp(1rem,1.5vw,1.25rem); color:var(--text-gray); margin-top:24px; max-width:640px; line-height:1.65;">Find professionally designed templates specifically curated for your industry.</p>
        </div>
        <div class="categories-grid">
            <a href="{{ route('landing.category', 'real-estate') }}" class="category-card reveal-scale">
                <i class="fa-solid fa-building"></i>
                <h4>Real Estate</h4>
            </a>
            <a href="{{ route('landing.category', 'doctors') }}" class="category-card reveal-scale reveal-delay-1">
                <i class="fa-solid fa-user-doctor"></i>
                <h4>Doctors</h4>
            </a>
            <a href="{{ route('landing.category', 'politicians') }}" class="category-card reveal-scale reveal-delay-1">
                <i class="fa-solid fa-bullhorn"></i>
                <h4>Politicians</h4>
            </a>
            <a href="{{ route('landing.category', 'education') }}" class="category-card reveal-scale reveal-delay-2">
                <i class="fa-solid fa-graduation-cap"></i>
                <h4>Education</h4>
            </a>
            <a href="{{ route('landing.category', 'restaurants') }}" class="category-card reveal-scale reveal-delay-2">
                <i class="fa-solid fa-utensils"></i>
                <h4>Restaurants</h4>
            </a>
            <a href="{{ route('landing.category', 'jewellery') }}" class="category-card reveal-scale reveal-delay-3">
                <i class="fa-solid fa-gem"></i>
                <h4>Jewellery</h4>
            </a>
        </div>
        <div style="text-align:center; margin-top:48px;" class="reveal">
            <a href="{{ route('landing.templates') }}" class="btn-sharp btn-sharp-outline">
                View All Templates
                <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</section>

{{-- ============================================
    MULTILINGUAL — Gradient section
    ============================================ --}}
<section class="lang-section">
    <div class="noise-overlay" style="opacity:0.06;"></div>
    <div class="container-full" style="position:relative; z-index:1;">
        <div class="reveal">
            <span style="color:#fecaca; font-family:'JetBrains Mono',monospace; font-size:12px; text-transform:uppercase; letter-spacing:0.15em; font-weight:600;">MULTILINGUAL SUPPORT</span>
            <h2 class="heading-md" style="margin-top:16px; color:#fff;">Your content, your language.</h2>
            <p style="color:rgba(255,255,255,0.8); font-size:clamp(1rem,1.5vw,1.25rem); margin-top:16px; line-height:1.6;">Connect with your local audience in their native language.</p>
        </div>
        <div class="lang-cloud">
            <span class="lang-badge reveal-blur">English</span>
            <span class="lang-badge reveal-blur reveal-delay-1">हिंदी (Hindi)</span>
            <span class="lang-badge reveal-blur reveal-delay-1">मराठी (Marathi)</span>
            <span class="lang-badge reveal-blur reveal-delay-2">ગુજરાતી (Gujarati)</span>
            <span class="lang-badge reveal-blur reveal-delay-2">தமிழ் (Tamil)</span>
            <span class="lang-badge reveal-blur reveal-delay-3">తెలుగు (Telugu)</span>
            <span class="lang-badge reveal-blur reveal-delay-3">ಕನ್ನಡ (Kannada)</span>
        </div>
    </div>
</section>

{{-- ============================================
    FINAL CTA — Dark section
    ============================================ --}}
<section class="cta-section">
    <div class="cta-glow-1"></div>
    <div class="cta-glow-2"></div>
    <div class="noise-overlay"></div>
    <div class="container-full">
        <div class="cta-inner reveal">
            <h2 class="heading-xl text-shimmer-white">Ready to grow<br>your business?</h2>
            <p class="cta-desc">Join thousands of businesses using Artera to create professional marketing content — automatically, every day.</p>
            <a href="#" class="btn-sharp btn-sharp-white btn-glow">
                <i class="fa-brands fa-google-play"></i>
                Download the App
                <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--blue);"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</section>

@endsection

@section('extra_js')
<script>
    // Show 3rd marquee column on desktop
    (function() {
        const cols = document.querySelectorAll('.marquee-col');
        function checkWidth() {
            if (cols.length >= 3) {
                cols[2].style.display = window.innerWidth >= 1024 ? 'block' : 'none';
            }
        }
        checkWidth();
        window.addEventListener('resize', checkWidth);
    })();
</script>
@endsection
