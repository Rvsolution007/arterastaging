@extends('landing.layout')

@section('title', 'Artera - Features')

@section('extra_css')
<style>
    /* ============================================
       FEATURES PAGE — 8x.social Design
       ============================================ */

    /* ---- Hero Section ---- */
    .features-hero {
        position: relative;
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%);
        padding: 140px 0 120px;
        overflow: hidden;
        text-align: center;
    }
    .features-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse at 30% 20%, rgba(96, 165, 250, 0.3) 0%, transparent 60%),
                    radial-gradient(ellipse at 70% 80%, rgba(30, 58, 138, 0.4) 0%, transparent 60%);
        pointer-events: none;
    }
    .features-hero-inner {
        position: relative;
        z-index: 2;
        max-width: 900px;
        margin: 0 auto;
    }
    .features-hero .eyebrow {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
    }
    .features-hero .heading-xl {
        color: #fff;
        margin-bottom: 24px;
    }
    .features-hero-subtitle {
        font-size: clamp(1.05rem, 2vw, 1.35rem);
        color: rgba(255, 255, 255, 0.8);
        line-height: 1.7;
        max-width: 640px;
        margin: 0 auto;
    }

    /* ---- Feature Section (shared) ---- */
    .feature-section {
        position: relative;
        padding: 120px 0;
        overflow: hidden;
    }
    .feature-section-dark {
        background: var(--bg-dark);
        color: #fff;
    }
    .feature-section-light {
        background: var(--bg-white);
        color: var(--text-dark);
    }
    .feature-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 80px;
        align-items: center;
    }
    .feature-grid.reverse .feature-text {
        order: 2;
    }
    .feature-grid.reverse .feature-visual {
        order: 1;
    }

    /* ---- Feature Text ---- */
    .feature-text .eyebrow-plain {
        margin-bottom: 20px;
    }
    .feature-section-dark .eyebrow-plain {
        color: var(--accent);
    }
    .feature-text .heading-md {
        margin-bottom: 24px;
    }
    .feature-section-dark .heading-md {
        color: #fff;
    }
    .feature-text .feature-desc {
        font-size: 1.05rem;
        line-height: 1.8;
        color: var(--text-gray);
        margin-bottom: 32px;
    }
    .feature-section-dark .feature-desc {
        color: rgba(255, 255, 255, 0.6);
    }

    /* ---- Checklist ---- */
    .feature-checklist {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .feature-checklist li {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        font-size: 15px;
        line-height: 1.7;
        margin-bottom: 18px;
    }
    .feature-section-light .feature-checklist li {
        color: var(--text-dark);
    }
    .feature-section-dark .feature-checklist li {
        color: rgba(255, 255, 255, 0.85);
    }
    .check-icon {
        flex-shrink: 0;
        width: 22px;
        height: 22px;
        margin-top: 2px;
        color: var(--blue);
    }
    .feature-section-dark .check-icon {
        color: var(--accent);
    }

    /* ---- Feature Visual ---- */
    .feature-visual {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .feature-visual img {
        max-width: 100%;
        height: auto;
        display: block;
        transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .feature-visual:hover img {
        transform: scale(1.03);
    }
    .feature-visual-frame {
        position: relative;
        padding: 24px;
    }
    .feature-visual-frame::before {
        content: '';
        position: absolute;
        inset: 0;
        border: 1px solid rgba(59, 130, 246, 0.15);
        pointer-events: none;
    }
    .feature-section-dark .feature-visual-frame::before {
        border-color: rgba(255, 255, 255, 0.08);
    }

    /* ---- Feature Icon ---- */
    .feature-icon-wrap {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--blue);
        margin-bottom: 24px;
        color: var(--blue);
        font-size: 24px;
    }
    .feature-section-dark .feature-icon-wrap {
        border-color: var(--accent);
        color: var(--accent);
    }

    /* ---- CTA Section ---- */
    .features-cta {
        position: relative;
        background: var(--bg-dark);
        padding: 120px 0;
        text-align: center;
        overflow: hidden;
    }
    .features-cta-inner {
        position: relative;
        z-index: 2;
        max-width: 700px;
        margin: 0 auto;
    }
    .features-cta .heading-lg {
        color: #fff;
        margin-bottom: 24px;
    }
    .features-cta-desc {
        font-size: 1.1rem;
        color: rgba(255, 255, 255, 0.55);
        line-height: 1.7;
        margin-bottom: 48px;
    }

    /* ---- Responsive ---- */
    @media (max-width: 900px) {
        .feature-grid {
            grid-template-columns: 1fr;
            gap: 48px;
        }
        .feature-grid.reverse .feature-text {
            order: 1;
        }
        .feature-grid.reverse .feature-visual {
            order: 2;
        }
        .feature-section {
            padding: 80px 0;
        }
        .features-hero {
            padding: 100px 0 80px;
        }
        .features-cta {
            padding: 80px 0;
        }
    }
</style>
@endsection

@section('content')

{{-- ===== HERO SECTION ===== --}}
<section class="features-hero">
    <div class="noise-overlay"></div>
    <div class="container-full">
        <div class="features-hero-inner">
            <div class="reveal">
                <span class="eyebrow font-mono"><span class="typewriter">Deep Dive</span></span>
            </div>
            <h1 class="heading-xl split-text reveal reveal-delay-1">Powerful features.<br>Built for growth.</h1>
            <p class="features-hero-subtitle stagger-words reveal reveal-delay-2">Discover how each component of Artera works together to amplify your digital presence and automate your marketing.</p>
        </div>
    </div>
</section>

{{-- ===== FEATURE 1: Daily Drip Automation (Dark, Reversed) ===== --}}
<section class="feature-section feature-section-dark">
    <div class="noise-overlay"></div>
    <div class="container-full">
        <div class="feature-grid reverse">
            <div class="feature-text reveal">
                <div class="feature-icon-wrap">
                    <i class="fa-brands fa-whatsapp"></i>
                </div>
                <span class="eyebrow-plain"><span class="typewriter">Automation</span></span>
                <h2 class="heading-md draw-underline">Automated Daily Drip</h2>
                <p class="feature-desc">Consistency is key to marketing, but finding time to post every day is hard. The Daily Drip engine acts as your automated marketing assistant.</p>
                <ul class="feature-checklist stagger-list">
                    <li class="stagger-item">
                        <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        Automatically picks a product from your catalog every morning.
                    </li>
                    <li class="stagger-item">
                        <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        Wraps it in a beautiful, trending template with your branding.
                    </li>
                    <li class="stagger-item">
                        <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        Sends a push notification reminding you to share it to your WhatsApp Status.
                    </li>
                </ul>
            </div>
            <div class="feature-visual reveal-scale reveal-delay-1">
                <div class="feature-visual-frame">
                    <img src="{{ asset('landing/images/hero-phone.png') }}" onerror="this.src='https://placehold.co/400x600/1E3A8A/FFFFFF?text=Daily+Drip'" alt="Daily Automation">
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===== FEATURE 2: Festival & Event Calendar (Light) ===== --}}
<section class="feature-section feature-section-light">
    <div class="container-full">
        <div class="feature-grid">
            <div class="feature-visual reveal-scale">
                <div class="feature-visual-frame">
                    <img src="{{ asset('landing/images/app-screens.png') }}" onerror="this.src='https://placehold.co/600x400/F0F7FF/1E3A8A?text=Festival+Templates'" alt="Festival Templates">
                </div>
            </div>
            <div class="feature-text reveal reveal-delay-1">
                <div class="feature-icon-wrap">
                    <i class="fa-regular fa-calendar-check"></i>
                </div>
                <span class="eyebrow-plain"><span class="typewriter">Calendar</span></span>
                <h2 class="heading-md draw-underline">Festival &amp; Event Calendar</h2>
                <p class="feature-desc">Never miss a chance to connect with your audience. We provide thousands of templates for every regional and national festival.</p>
                <ul class="feature-checklist stagger-list">
                    <li class="stagger-item">
                        <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        Pre-designed, high-quality greetings for all holidays.
                    </li>
                    <li class="stagger-item">
                        <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        Automatic integration of your logo and business details.
                    </li>
                    <li class="stagger-item">
                        <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        Auto-scheduled posting reminders so you stay ahead of the calendar.
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ===== FEATURE 3: Custom Post Studio (Dark, Reversed) ===== --}}
<section class="feature-section feature-section-dark">
    <div class="noise-overlay"></div>
    <div class="container-full">
        <div class="feature-grid reverse">
            <div class="feature-text reveal">
                <div class="feature-icon-wrap">
                    <i class="fa-solid fa-palette"></i>
                </div>
                <span class="eyebrow-plain"><span class="typewriter">Creative Tools</span></span>
                <h2 class="heading-md draw-underline">Custom Post Studio</h2>
                <p class="feature-desc">Need full creative control? Our mobile-first editor gives you Canva-like flexibility right on your phone.</p>
                <ul class="feature-checklist stagger-list">
                    <li class="stagger-item">
                        <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        Add text, multiple images, and stickers to any post.
                    </li>
                    <li class="stagger-item">
                        <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        Access a vast library of shapes, overlays, and typography.
                    </li>
                    <li class="stagger-item">
                        <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        Save your custom drafts to edit later.
                    </li>
                </ul>
            </div>
            <div class="feature-visual reveal-scale reveal-delay-1">
                <div class="feature-visual-frame">
                    <img src="{{ asset('landing/images/hero-phone.png') }}" onerror="this.src='https://placehold.co/400x600/F0F7FF/1E3A8A?text=Post+Studio'" alt="Custom Post Studio">
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===== BOTTOM CTA ===== --}}
<section class="features-cta">
    <div class="noise-overlay"></div>
    <div class="container-full">
        <div class="features-cta-inner">
            <div class="reveal">
                <span class="eyebrow font-mono"><span class="typewriter">Get Started</span></span>
            </div>
            <h2 class="heading-lg text-shimmer-white reveal reveal-delay-1">Ready to automate your marketing?</h2>
            <p class="features-cta-desc reveal reveal-delay-2">Join thousands of businesses using Artera to grow their brand on autopilot.</p>
            <div class="reveal reveal-delay-3">
                <a href="{{ route('landing.packages') }}" class="btn-sharp btn-sharp-primary btn-glow">
                    View Plans
                    <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
