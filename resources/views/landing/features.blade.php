@extends('landing.layout')

@section('title', $seo['title'])

@section('seo')
    @include('components.seo-head', ['seo' => $seo])
@endsection

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
        background: var(--bg-white, #ffffff);
        padding: 120px 0;
        text-align: center;
        overflow: hidden;
    }
    .cta-blob {
        position: absolute;
        filter: blur(80px);
        z-index: 1;
        opacity: 0.6;
        animation: float-blob 15s infinite alternate ease-in-out;
    }
    .cta-blob-1 {
        width: 400px;
        height: 400px;
        background: #dbeafe;
        top: -100px;
        left: -100px;
        border-radius: 50%;
    }
    .cta-blob-2 {
        width: 300px;
        height: 300px;
        background: #e0e7ff;
        bottom: -50px;
        right: -50px;
        border-radius: 50%;
        animation-duration: 20s;
        animation-direction: alternate-reverse;
    }
    @keyframes float-blob {
        0% { transform: translate(0, 0) scale(1); }
        100% { transform: translate(80px, 40px) scale(1.1); }
    }
    .features-cta-inner {
        position: relative;
        z-index: 2;
        max-width: 700px;
        margin: 0 auto;
    }
    .features-cta .heading-lg {
        color: var(--text-dark, #111);
        margin-bottom: 24px;
    }
    .features-cta-desc {
        font-size: 1.1rem;
        color: var(--text-gray, #666);
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
<section class="feature-section feature-section-light">
    <div class="container-full">
        <div class="feature-grid reverse">
            <div class="feature-text reveal">
                  <div class="feature-icon-wrap">
                      <i class="fa-solid fa-briefcase"></i>
                  </div>
                  <span class="eyebrow-plain"><span class="typewriter">Business Growth</span></span>
                  <h2 class="heading-md draw-underline">Category & Business Posts</h2>
                  <p class="feature-desc">Promote your business every day with stunning, category-specific posts designed for your niche and customized for your brand.</p>
                  <ul class="feature-checklist stagger-list">
                      <li class="stagger-item">
                          <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                          Ready-made templates tailored to your specific business category.
                      </li>
                      <li class="stagger-item">
                          <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                          Fully customizable business posts to highlight your products and offers.
                      </li>
                      <li class="stagger-item">
                          <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                          Automatically branded with your logo, contact details, and brand colors.
                      </li>
                  </ul>
              </div>
              <div class="feature-visual reveal-scale reveal-delay-1" style="text-align: center;">
                <img src="{{ asset('landing/images/feature1_mobile.png') }}" onerror="this.src='https://placehold.co/400x600/1E3A8A/FFFFFF?text=Business+Posts'" alt="Business Category Posts" style="width:100%; max-width:400px; filter: drop-shadow(0 20px 30px rgba(0,0,0,0.15));">
            </div>
        </div>
    </div>
</section>

{{-- ===== FEATURE 2: Festival & Event Calendar (Blue) ===== --}}
<section class="feature-section feature-section-dark" style="background-color: #1d4ed8;">
    <div class="noise-overlay"></div>
    <div class="container-full">
        <div class="feature-grid">
            <div class="feature-visual reveal-scale" style="text-align: center; position: relative; z-index: 1;">
                <div style="position: absolute; width: 280px; height: 350px; background: #ffffff; filter: blur(70px); opacity: 0.15; border-radius: 50%; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: -1;"></div>
                <img src="{{ asset('landing/images/feature2_new_festival.png') }}" onerror="this.src='https://placehold.co/600x400/F0F7FF/1E3A8A?text=Festival+Templates'" alt="Festival Templates" style="width:100%; max-width:400px; filter: drop-shadow(0 20px 30px rgba(0,0,0,0.3));">
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

{{-- 
===== FEATURE 3: Custom Post Studio (Dark, Reversed) ===== 
(Hidden as requested for future use)
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
            <div class="feature-visual reveal-scale reveal-delay-1" style="text-align: center;">
                <img src="{{ asset('landing/images/feature3.png') }}" onerror="this.src='https://placehold.co/400x600/F0F7FF/1E3A8A?text=Post+Studio'" alt="Custom Post Studio" style="width:100%; max-width:400px; filter: drop-shadow(0 20px 30px rgba(0,0,0,0.15));">
            </div>
        </div>
    </div>
</section>
--}}

{{-- ===== BOTTOM CTA ===== --}}
<section class="features-cta">
    <div class="cta-blob cta-blob-1"></div>
    <div class="cta-blob cta-blob-2"></div>
    <div class="container-full">
        <div class="features-cta-inner">
            <div class="reveal">
                <span class="eyebrow font-mono"><span class="typewriter">Get Started</span></span>
            </div>
            <h2 class="heading-lg reveal reveal-delay-1">Ready to automate your marketing?</h2>
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
