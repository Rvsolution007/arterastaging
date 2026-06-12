@extends('landing.layout')

@section('title', 'Artera - Templates Gallery')

@section('extra_css')
<style>
    /* ============================================
       TEMPLATES GALLERY — 8x.social Design
       ============================================ */

    /* ---- Hero Header ---- */
    .tpl-hero {
        position: relative;
        background: var(--bg-dark);
        padding: 100px 0 80px;
        overflow: hidden;
    }
    .tpl-hero-inner {
        position: relative;
        z-index: 2;
        text-align: center;
    }
    .tpl-hero .heading-lg {
        color: #fff;
        margin-bottom: 20px;
    }
    .tpl-hero-sub {
        font-size: clamp(1rem, 2vw, 1.25rem);
        color: rgba(255, 255, 255, 0.6);
        max-width: 560px;
        margin: 0 auto;
        line-height: 1.6;
    }
    .tpl-hero-count {
        margin-top: 40px;
        display: flex;
        justify-content: center;
        gap: 48px;
    }
    .tpl-hero-stat {
        text-align: center;
    }
    .tpl-hero-stat-num {
        font-size: clamp(1.5rem, 3vw, 2.25rem);
        font-weight: 900;
        color: var(--blue);
        letter-spacing: -0.02em;
    }
    .tpl-hero-stat-label {
        font-family: 'JetBrains Mono', monospace;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: rgba(255, 255, 255, 0.4);
        margin-top: 4px;
    }

    /* ---- Template Section ---- */
    .tpl-section {
        padding: 80px 0;
    }
    .tpl-section:nth-child(even) {
        background: #fafafa;
    }
    .tpl-section-header {
        margin-bottom: 48px;
    }
    .tpl-section-title {
        font-size: clamp(1.5rem, 3vw, 2.25rem);
        font-weight: 900;
        letter-spacing: -0.02em;
        color: var(--text-dark);
        margin-top: 12px;
    }

    /* ---- Template Grid ---- */
    .tpl-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    @media (min-width: 600px) {
        .tpl-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (min-width: 900px) {
        .tpl-grid { grid-template-columns: repeat(4, 1fr); gap: 24px; }
    }
    @media (min-width: 1280px) {
        .tpl-grid { grid-template-columns: repeat(5, 1fr); }
    }

    /* ---- Template Card ---- */
    .tpl-card {
        position: relative;
        border: 1px solid rgba(26, 26, 26, 0.08);
        background: #fff;
        overflow: hidden;
        transition: border-color 0.3s ease, transform 0.3s ease;
    }
    .tpl-card:hover {
        border-color: var(--blue);
        transform: translateY(-4px);
    }

    .tpl-card-img-wrap {
        position: relative;
        padding-top: 100%;
        background: #f1f5f9;
        overflow: hidden;
    }
    .tpl-card-img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .tpl-card:hover .tpl-card-img {
        transform: scale(1.05);
    }

    /* ---- Overlay ---- */
    .tpl-card-overlay {
        position: absolute;
        inset: 0;
        background: rgba(26, 26, 26, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.35s ease;
    }
    .tpl-card:hover .tpl-card-overlay {
        opacity: 1;
    }
    .tpl-overlay-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 14px 28px;
        background: var(--blue);
        color: #fff;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        text-decoration: none;
        transition: background 0.3s ease;
    }
    .tpl-overlay-btn:hover {
        background: #2563eb;
    }
    .tpl-overlay-btn svg {
        width: 16px;
        height: 16px;
    }

    /* ---- Card Info ---- */
    .tpl-card-info {
        padding: 14px 16px;
    }
    .tpl-card-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ---- Empty State ---- */
    .tpl-empty {
        text-align: center;
        padding: 80px 20px;
    }
    .tpl-empty-text {
        font-size: 16px;
        color: var(--text-gray);
    }

    /* ---- Divider line between sections ---- */
    .tpl-divider {
        width: 60px;
        height: 3px;
        background: var(--blue);
        margin-top: 16px;
    }
</style>
@endsection

@section('content')

{{-- ========== HERO HEADER ========== --}}
<section class="tpl-hero">
    <div class="noise-overlay"></div>
    <div class="container-full tpl-hero-inner">
        <div class="reveal">
            <span class="eyebrow"><span class="typewriter">BROWSE & CUSTOMIZE</span></span>
        </div>
        <h1 class="heading-lg split-text reveal-delay-1">Templates Gallery</h1>
        <p class="tpl-hero-sub stagger-words reveal-delay-2">
            Browse thousands of ready-made templates crafted for festivals, business branding, and custom social media content.
        </p>
        <div class="tpl-hero-count reveal reveal-delay-3">
            @if(isset($festivals))
            <div class="tpl-hero-stat">
                <div class="tpl-hero-stat-num">{{ count($festivals) }}</div>
                <div class="tpl-hero-stat-label">Festival</div>
            </div>
            @endif
            @if(isset($businessPosts))
            <div class="tpl-hero-stat">
                <div class="tpl-hero-stat-num">{{ count($businessPosts) }}</div>
                <div class="tpl-hero-stat-label">Business</div>
            </div>
            @endif
            @if(isset($customPosts))
            <div class="tpl-hero-stat">
                <div class="tpl-hero-stat-num">{{ count($customPosts) }}</div>
                <div class="tpl-hero-stat-label">Custom</div>
            </div>
            @endif
        </div>
    </div>
</section>

{{-- ========== FESTIVAL TEMPLATES ========== --}}
@if(isset($festivals) && count($festivals) > 0)
<section class="tpl-section">
    <div class="container-full">
        <div class="tpl-section-header reveal">
            <span class="eyebrow-plain reveal-left">COLLECTION 01</span>
            <h2 class="tpl-section-title draw-underline">Festival Templates</h2>
            <div class="tpl-divider"></div>
        </div>
        <div class="tpl-grid">
            @foreach($festivals as $post)
            <div class="tpl-card reveal-scale">
                <div class="tpl-card-img-wrap">
                    <img src="{{ $post->frame_image ? asset('uploads/'.$post->frame_image) : asset('assets/images/placeholder.png') }}" class="tpl-card-img" alt="{{ $post->festivals->title ?? 'Festival' }}" loading="lazy">
                    <div class="tpl-card-overlay">
                        <a href="{{ route('client.login') }}" class="tpl-overlay-btn">
                            Login to Customize
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
                <div class="tpl-card-info">
                    <h4 class="tpl-card-title">{{ $post->festivals->title ?? 'Festival Post' }}</h4>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ========== BUSINESS TEMPLATES ========== --}}
@if(isset($businessPosts) && count($businessPosts) > 0)
<section class="tpl-section">
    <div class="container-full">
        <div class="tpl-section-header reveal">
            <span class="eyebrow-plain reveal-left">COLLECTION 02</span>
            <h2 class="tpl-section-title draw-underline">Business Templates</h2>
            <div class="tpl-divider"></div>
        </div>
        <div class="tpl-grid">
            @foreach($businessPosts as $post)
            <div class="tpl-card reveal-scale">
                <div class="tpl-card-img-wrap">
                    <img src="{{ $post->frame_image ? asset('uploads/'.$post->frame_image) : asset('assets/images/placeholder.png') }}" class="tpl-card-img" alt="{{ $post->category->name ?? 'Business Post' }}" loading="lazy">
                    <div class="tpl-card-overlay">
                        <a href="{{ route('client.login') }}" class="tpl-overlay-btn">
                            Login to Customize
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
                <div class="tpl-card-info">
                    <h4 class="tpl-card-title">{{ $post->category->name ?? 'Business Template' }}</h4>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ========== CUSTOM TEMPLATES ========== --}}
@if(isset($customPosts) && count($customPosts) > 0)
<section class="tpl-section">
    <div class="container-full">
        <div class="tpl-section-header reveal">
            <span class="eyebrow-plain reveal-left">COLLECTION 03</span>
            <h2 class="tpl-section-title draw-underline">Custom Templates</h2>
            <div class="tpl-divider"></div>
        </div>
        <div class="tpl-grid">
            @foreach($customPosts as $post)
            <div class="tpl-card reveal-scale">
                <div class="tpl-card-img-wrap">
                    <img src="{{ $post->frame_image ? asset('uploads/'.$post->frame_image) : asset('assets/images/placeholder.png') }}" class="tpl-card-img" alt="{{ $post->custom_post->name ?? 'Custom' }}" loading="lazy">
                    <div class="tpl-card-overlay">
                        <a href="{{ route('client.login') }}" class="tpl-overlay-btn">
                            Login to Customize
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
                <div class="tpl-card-info">
                    <h4 class="tpl-card-title">{{ $post->custom_post->name ?? 'Custom Post' }}</h4>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection
