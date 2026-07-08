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
        height: auto;
        display: block;
        pointer-events: none;
        user-select: none;
        -webkit-user-drag: none;
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
        background: #1d4ed8;
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
        background: rgba(255, 255, 255, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
        border-radius: 12px;
    }
    .why-card-icon i { font-size: 20px; color: #fff; }
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
    .category-hidden { display: none !important; }
    .categories-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    @media (min-width: 576px) { .categories-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (min-width: 992px) { .categories-grid { grid-template-columns: repeat(4, 1fr); } }
    @media (min-width: 1200px) { .categories-grid { grid-template-columns: repeat(6, 1fr); gap: 24px; } }

    .category-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 24px 12px;
        border: 1px solid rgba(0, 0, 0, 0.04);
        border-radius: 16px;
        text-decoration: none;
        color: var(--text-dark);
        background: #fff;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    }
    .category-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        border-color: transparent;
    }
    .category-card .icon-wrapper {
        width: 60px;
        height: 60px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
        font-size: 28px;
        color: #fff;
        transition: transform 0.4s;
    }
    .category-card:hover .icon-wrapper {
        transform: scale(1.1) rotate(5deg);
    }
    .category-card h4 {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin: 0;
    }

    /* ============================================
       LANGUAGE — Gradient section
       ============================================ */
    .lang-section {
        padding: 96px 0;
        background: #1d4ed8;
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
        background: #fff;
        color: #1a1a1a;
        position: relative;
        overflow: hidden;
    }
    .cta-glow-1 {
        position: absolute;
        top: 0; right: 0;
        width: 384px; height: 384px;
        border-radius: 50%;
        background: rgba(59, 130, 246, 0.05);
        filter: blur(80px);
    }
    .cta-glow-2 {
        position: absolute;
        bottom: 0; left: 0;
        width: 384px; height: 384px;
        border-radius: 50%;
        background: rgba(59, 130, 246, 0.05);
        filter: blur(80px);
    }
    .cta-inner {
        text-align: center;
        max-width: 800px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
    }
    .cta-inner .heading-xl { margin-bottom: 32px; color: #1a1a1a; }
    .cta-inner .cta-desc {
        font-size: clamp(1.125rem, 2vw, 1.5rem);
        color: #4b5563;
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
                <div class="marquee-columns" oncontextmenu="return false;">
                    {{-- Column 1 — Up --}}
                    <div class="marquee-col">
                        <div class="marquee-track marquee-track-up">
                            @if(isset($homeBanners[1]) && $homeBanners[1]->count() > 0)
                                @foreach($homeBanners[1] as $banner)
                                    <div class="marquee-card"><img src="{{ asset($banner->image_path) }}" loading="lazy" alt="Banner"></div>
                                @endforeach
                                {{-- Duplicate for seamless loop --}}
                                @foreach($homeBanners[1] as $banner)
                                    <div class="marquee-card"><img src="{{ asset($banner->image_path) }}" loading="lazy" alt="Banner"></div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    {{-- Column 2 — Down --}}
                    <div class="marquee-col">
                        <div class="marquee-track marquee-track-down">
                            @if(isset($homeBanners[2]) && $homeBanners[2]->count() > 0)
                                @foreach($homeBanners[2] as $banner)
                                    <div class="marquee-card"><img src="{{ asset($banner->image_path) }}" loading="lazy" alt="Banner"></div>
                                @endforeach
                                {{-- Duplicate --}}
                                @foreach($homeBanners[2] as $banner)
                                    <div class="marquee-card"><img src="{{ asset($banner->image_path) }}" loading="lazy" alt="Banner"></div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    {{-- Column 3 — Up --}}
                    <div class="marquee-col">
                        <div class="marquee-track marquee-track-up" style="animation-duration:30s;">
                            @if(isset($homeBanners[3]) && $homeBanners[3]->count() > 0)
                                @foreach($homeBanners[3] as $banner)
                                    <div class="marquee-card"><img src="{{ asset($banner->image_path) }}" loading="lazy" alt="Banner"></div>
                                @endforeach
                                {{-- Duplicate --}}
                                @foreach($homeBanners[3] as $banner)
                                    <div class="marquee-card"><img src="{{ asset($banner->image_path) }}" loading="lazy" alt="Banner"></div>
                                @endforeach
                            @endif
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
</section>

{{-- ============================================
    FESTIVALS & TEMPLATE SEARCH
    ============================================ --}}
<section class="search-festivals-section" style="padding: 60px 0; background: #fff;">
    <div class="container-full">
        <div class="search-header" style="text-align: center; margin-bottom: 40px;">
            <h2 class="heading-lg" style="font-size: 2.5rem; color: #1e293b; margin-bottom: 16px;">Find Your Perfect Template</h2>
            <p style="color: #64748b; font-size: 1.1rem; max-width: 600px; margin: 0 auto 30px;">Search for any upcoming festival or business category to instantly discover thousands of beautiful, AI-generated marketing posts.</p>
            
            <div class="search-container" style="max-width: 700px; margin: 0 auto; position: relative;">
                <form action="{{ route('landing.search') }}" method="GET" id="mainSearchForm" class="search-form" style="position: relative; display: flex; align-items: center; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 50px; padding: 5px 5px 5px 24px; transition: all 0.3s ease;">
                    <i class="fa-solid fa-magnifying-glass" style="color: #94a3b8; font-size: 1.2rem;"></i>
                    <input type="text" name="q" id="ajaxSearchInput" placeholder="Search for festivals, categories, quotes..." autocomplete="off" style="flex: 1; border: none; background: transparent; padding: 15px; font-size: 1.1rem; color: #334155; outline: none; box-shadow: none;">
                    <button type="submit" class="btn-sharp btn-sharp-primary" style="border-radius: 50px; padding: 12px 30px; font-weight: 600;">Search</button>
                </form>
                
                {{-- AJAX Results Dropdown --}}
                <div id="ajaxSearchResults" class="ajax-results-dropdown" style="display: none; position: absolute; top: 110%; left: 0; right: 0; background: #fff; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; z-index: 100; overflow: hidden; text-align: left;">
                    <div class="results-list" id="ajaxResultsList" style="max-height: 400px; overflow-y: auto;">
                        <!-- Results injected via JS -->
                    </div>
                    <div class="view-more-container" style="padding: 12px; text-align: center; border-top: 1px solid #f1f5f9; background: #f8fafc;">
                        <a href="#" id="viewMoreBtn" style="color: var(--blue); font-weight: 600; text-decoration: none; font-size: 0.95rem;">View all results <i class="fa-solid fa-arrow-right" style="margin-left: 5px;"></i></a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Upcoming Festivals Strip --}}
        <div class="upcoming-festivals" style="margin-top: 60px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                <h3 style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0;">Upcoming Festivals</h3>
            </div>
            
            <div class="festivals-scroll-container" style="display: flex; gap: 20px; overflow-x: auto; padding-bottom: 20px; scrollbar-width: none;">
                @foreach($festivalsByDate as $dateKey => $data)
                    <div class="festival-date-group" style="min-width: 300px; background: #f8fafc; border-radius: 20px; padding: 20px; border: 1px solid #e2e8f0;">
                        <div class="date-badge" style="display: inline-block; background: #e0e7ff; color: var(--blue); padding: 6px 14px; border-radius: 50px; font-weight: 700; font-size: 0.9rem; margin-bottom: 16px;">
                            <i class="fa-regular fa-calendar" style="margin-right: 6px;"></i> {{ $data['date_string'] }}
                        </div>
                        
                        <div class="festivals-list" style="display: flex; flex-direction: column; gap: 12px;">
                            @foreach($data['festivals'] as $festival)
                                <a href="{{ route('seo.festival', ['festivalSlug' => \Illuminate\Support\Str::slug($festival->title)]) }}" class="festival-card-mini" style="display: flex; align-items: center; gap: 12px; background: #fff; padding: 10px; border-radius: 12px; text-decoration: none; transition: all 0.2s ease; border: 1px solid #f1f5f9; position: relative; overflow: hidden;">
                                    <div class="fest-img" style="width: 50px; height: 50px; border-radius: 8px; overflow: hidden; background: #f1f5f9; flex-shrink: 0;">
                                        @if($festival->image)
                                            <img src="{{ asset('uploads/' . $festival->image) }}" alt="{{ $festival->title }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #94a3b8;"><i class="fa-solid fa-image"></i></div>
                                        @endif
                                    </div>
                                    <div class="fest-info" style="flex: 1; min-width: 0;">
                                        <h4 style="font-size: 1rem; font-weight: 600; color: #1e293b; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $festival->title }}</h4>
                                        <span style="font-size: 0.8rem; color: #64748b;">Explore Templates</span>
                                    </div>
                                    <div class="fest-arrow" style="color: #cbd5e1;">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
<style>
    .search-form:focus-within {
        border-color: var(--blue) !important;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1) !important;
    }
    .ajax-results-dropdown .result-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px 20px;
        text-decoration: none;
        transition: background 0.2s;
        border-bottom: 1px solid #f1f5f9;
    }
    .ajax-results-dropdown .result-item:hover {
        background: #f8fafc;
    }
    .ajax-results-dropdown .result-item:last-child {
        border-bottom: none;
    }
    .festival-card-mini:hover {
        border-color: #cbd5e1 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .festival-card-mini:hover .fest-arrow {
        color: var(--blue) !important;
    }
    .festivals-scroll-container::-webkit-scrollbar {
        display: none;
    }
</style>

{{-- ============================================
    WHY ARTERA — Dark section
    ============================================ --}}
<section class="why-section">
    <div class="top-line"></div>
    <div class="noise-overlay"></div>
    <div class="container-full" style="position:relative; z-index:1;">
        <div class="why-header">
            <span class="eyebrow-plain reveal" style="color:#bfdbfe;">WHY ARTERA IS DIFFERENT</span>
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
                <div class="why-card-icon"><i class="fa-solid fa-calendar-day"></i></div>
                <h3>Festival & Quote Posts</h3>
                <p>Never miss an opportunity to connect. Generate high-quality festival greetings, daily quotes, and custom business posts instantly with your branding.</p>
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
<style>
/* Modern Pricing Cards */
.pricing-section {
    padding: 100px 0;
    background: #f8fafc;
    position: relative;
    overflow: hidden;
}
.pricing-header {
    text-align: center;
    margin-bottom: 60px;
}
.pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 32px;
    max-width: 1200px;
    margin: 0 auto;
}
.pricing-card {
    background: #fff;
    border-radius: 24px;
    padding: 40px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.04);
    border: 1px solid rgba(0,0,0,0.05);
    transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.4s ease;
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
}
.pricing-card:hover {
    transform: translateY(-12px);
    box-shadow: 0 20px 50px rgba(99, 102, 241, 0.15);
    border-color: rgba(99, 102, 241, 0.3);
}
.pricing-card.popular {
    border: 2px solid #6366f1;
    box-shadow: 0 20px 50px rgba(99, 102, 241, 0.2);
}
.pricing-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    padding: 6px 16px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.plan-name {
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 12px;
}
.plan-price-wrap {
    margin-bottom: 24px;
    display: flex;
    align-items: baseline;
    gap: 8px;
}
.plan-price {
    font-size: 48px;
    font-weight: 900;
    color: #0f172a;
    line-height: 1;
}
.plan-currency {
    font-size: 24px;
    font-weight: 700;
    color: #64748b;
}
.plan-duration {
    font-size: 16px;
    color: #64748b;
    font-weight: 500;
}
.plan-discount {
    font-size: 16px;
    color: #ef4444;
    text-decoration: line-through;
    font-weight: 600;
    margin-left: 8px;
}
.plan-features {
    list-style: none;
    padding: 0;
    margin: 0 0 32px 0;
    flex-grow: 1;
}
.plan-features li {
    font-size: 16px;
    color: #475569;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.plan-features li i.fa-check-circle {
    color: #10b981;
    font-size: 18px;
}
.plan-features li i.fa-times-circle {
    color: #cbd5e1;
    font-size: 18px;
}
.plan-btn {
    display: block;
    width: 100%;
    text-align: center;
    padding: 16px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 16px;
    text-decoration: none;
    transition: all 0.3s ease;
}
.pricing-card.popular .plan-btn {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: #fff;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
}
.pricing-card.popular .plan-btn:hover {
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.6);
    transform: scale(1.02);
}
.pricing-card:not(.popular) .plan-btn {
    background: #f1f5f9;
    color: #0f172a;
}
.pricing-card:not(.popular) .plan-btn:hover {
    background: #e2e8f0;
    transform: scale(1.02);
}

/* Toggle Styles */
.pricing-toggle-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-bottom: 40px;
}
.toggle-label {
    font-size: 16px;
    font-weight: 600;
    color: #64748b;
    transition: color 0.3s;
    cursor: pointer;
}
.toggle-label.active {
    color: #0f172a;
}
.switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}
.switch input { 
    opacity: 0;
    width: 0;
    height: 0;
}
.slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #cbd5e1;
    transition: .4s;
}
.slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
input:checked + .slider {
    background-color: #6366f1;
}
input:checked + .slider:before {
    transform: translateX(26px);
}
.slider.round {
    border-radius: 34px;
}
.badge-save {
    background-color: #10b981;
    color: white;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 12px;
    margin-left: 8px;
    vertical-align: middle;
}
</style>

<section class="pricing-section" id="pricing">
    <div class="container-full">
        <div class="pricing-header">
            <span class="eyebrow-plain reveal">PRICING PLANS</span>
            <h2 class="heading-md split-text" style="margin-top:16px;">Choose the right plan for your business</h2>
            <p class="stagger-words" style="font-size:clamp(1rem,1.5vw,1.25rem); color:var(--text-gray); margin-top:24px; max-width:640px; margin-left:auto; margin-right:auto; line-height:1.65;">Start for free, upgrade when you need more features. No hidden fees.</p>
        </div>

        @php
            $plans = \App\Models\Subscription::where('status', 1)->orderBy('plan_price', 'asc')->get();
        @endphp

        <div class="pricing-toggle-wrap reveal">
            <span class="toggle-label" id="label-monthly" onclick="document.getElementById('billing-toggle').click()">Monthly</span>
            <label class="switch">
                <input type="checkbox" id="billing-toggle" checked>
                <span class="slider round"></span>
            </label>
            <span class="toggle-label active" id="label-yearly" onclick="document.getElementById('billing-toggle').click()">Yearly <span class="badge-save">Save 20%</span></span>
        </div>

        <div class="pricing-grid reveal-scale reveal-delay-1">
            @foreach($plans as $plan)
                @php
                    $isPopular = $plan->plan_name == 'Basic' || $plan->yearly_price > 0 && $plan->yearly_price < 2000;
                    $monthlyFinal = $plan->monthly_discount_price > 0 ? $plan->monthly_discount_price : $plan->monthly_price;
                    $yearlyFinal = $plan->yearly_discount_price > 0 ? $plan->yearly_discount_price : $plan->yearly_price;
                    if ($yearlyFinal == 0 && $plan->plan_price > 0) {
                        $yearlyFinal = $plan->discount_price > 0 ? $plan->discount_price : $plan->plan_price;
                    }
                    $isFree = $monthlyFinal == 0 && $yearlyFinal == 0 && $plan->plan_price == 0;
                    $finalPrice = $yearlyFinal; 
                @endphp
                <div class="pricing-card {{ $isPopular ? 'popular' : '' }}" data-is-free="{{ $isFree ? 'true' : 'false' }}">
                    @if($isPopular)
                        <div class="pricing-badge">Most Popular</div>
                    @endif
                    <h3 class="plan-name">{{ $plan->plan_name }}</h3>
                    
                    @if($isFree)
                        <div class="plan-price-wrap">
                            <span class="plan-currency">₹</span>
                            <span class="plan-price">0</span>
                        </div>
                    @else
                        <!-- Monthly Price -->
                        <div class="plan-price-wrap price-monthly" style="display: none;">
                            <span class="plan-currency">₹</span>
                            <span class="plan-price">{{ round($monthlyFinal) }}</span>
                            @if($plan->monthly_discount_price > 0 && $plan->monthly_price > $plan->monthly_discount_price)
                                <span class="plan-discount">₹{{ round($plan->monthly_price) }}</span>
                            @endif
                            @if($monthlyFinal > 0)
                                <span class="plan-duration">/ month</span>
                            @endif
                        </div>
                        <!-- Yearly Price -->
                        <div class="plan-price-wrap price-yearly">
                            <span class="plan-currency">₹</span>
                            <span class="plan-price">{{ round($yearlyFinal) }}</span>
                            @if($plan->yearly_discount_price > 0 && $plan->yearly_price > $plan->yearly_discount_price)
                                <span class="plan-discount">₹{{ round($plan->yearly_price) }}</span>
                            @endif
                            @if($yearlyFinal > 0)
                                <span class="plan-duration">/ year</span>
                            @endif
                        </div>
                    @endif
                    
                    <ul class="plan-features">
                        @if($finalPrice == 0)
                            <li>
                                <i class="fas fa-check-circle"></i>
                                Unlimited Free Templates (Ad-Supported)
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                Festival & Quote Posts (Watch Ad)
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                {{ $plan->business_limit }} Business {{ Str::plural('Profile', $plan->business_limit) }}
                            </li>
                            <li>
                                <i class="fas fa-times-circle"></i>
                                Ad-Free Experience
                            </li>
                        @else
                            <li>
                                <i class="{{ $plan->festival_post_limit > 0 ? 'fas fa-check-circle' : 'fas fa-times-circle' }}"></i>
                                {{ $plan->festival_post_limit > 0 ? $plan->festival_post_limit . ' Ad-Free Festival Posts' : 'No Festival Posts' }}
                            </li>
                            <li>
                                <i class="{{ $plan->custom_post_edit_limit > 0 ? 'fas fa-check-circle' : 'fas fa-times-circle' }}"></i>
                                {{ $plan->custom_post_edit_limit > 0 ? $plan->custom_post_edit_limit . ' Ad-Free Custom Posts' : 'No Custom Posts' }}
                            </li>
                            <li>
                                <i class="{{ $plan->category_post_limit > 0 ? 'fas fa-check-circle' : 'fas fa-times-circle' }}"></i>
                                {{ $plan->category_post_limit > 0 ? $plan->category_post_limit . ' Ad-Free Category Posts' : 'No Category Posts' }}
                            </li>
                            <li>
                                <i class="{{ $plan->daily_drip_limit > 0 ? 'fas fa-check-circle' : 'fas fa-times-circle' }}"></i>
                                {{ $plan->daily_drip_limit > 0 ? $plan->daily_drip_limit . ' Daily Drip Automations' : 'No Daily Drip' }}
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                100% Ad-Free Experience
                            </li>
                        @endif
                    </ul>
                    
                    <a href="{{ config('seo.app_links.android', '#') }}" class="plan-btn">
                        {{ $finalPrice == 0 ? 'Get Started Free' : 'Choose ' . $plan->plan_name }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const toggle = document.getElementById('billing-toggle');
        const cards = document.querySelectorAll('.pricing-card');
        
        function updatePricingDisplay() {
            const isYearly = toggle.checked;
            document.getElementById('label-yearly').classList.toggle('active', isYearly);
            document.getElementById('label-monthly').classList.toggle('active', !isYearly);
            
            cards.forEach(card => {
                const isFree = card.getAttribute('data-is-free') === 'true';
                
                if (!isFree) {
                    const monthlyEl = card.querySelector('.price-monthly');
                    const yearlyEl = card.querySelector('.price-yearly');
                    if (monthlyEl && yearlyEl) {
                        monthlyEl.style.display = isYearly ? 'none' : 'flex';
                        yearlyEl.style.display = isYearly ? 'flex' : 'none';
                    }
                }
            });
        }
        
        if(toggle) {
            toggle.addEventListener('change', updatePricingDisplay);
            // Run on load
            updatePricingDisplay();
        }
    });
</script>

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
        @php
            $categories = \App\Models\BusinessCategory::where('status', 1)->get();
            $iconMap = [
                'retail' => 'fa-shop',
                'manufacturing' => 'fa-industry',
                'wholesale' => 'fa-boxes-stacked',
                'local' => 'fa-wrench',
                'healthcare' => 'fa-heart-pulse',
                'education' => 'fa-graduation-cap',
                'food' => 'fa-utensils',
                'restaurant' => 'fa-utensils',
                'real estate' => 'fa-building',
                'finance' => 'fa-chart-pie',
                'insurance' => 'fa-shield-halved',
                'agriculture' => 'fa-seedling',
                'technology' => 'fa-laptop-code',
                'fashion' => 'fa-shirt',
                'apparel' => 'fa-shirt',
                'beauty' => 'fa-spa',
                'wellness' => 'fa-spa',
                'home' => 'fa-house-chimney',
                'travel' => 'fa-plane-up',
                'tourism' => 'fa-earth-americas',
                'software' => 'fa-code',
                'it &' => 'fa-computer',
                'entertainment' => 'fa-film',
                'media' => 'fa-photo-film',
                'consultant' => 'fa-user-tie',
                'advisor' => 'fa-user-tie',
                'industrial' => 'fa-industry',
                'engineering' => 'fa-gear',
                'logistic' => 'fa-truck-fast',
                'transport' => 'fa-truck',
                'commerce' => 'fa-cart-shopping',
                'online business' => 'fa-globe',
                'hospitality' => 'fa-bell-concierge',
                'sports' => 'fa-volleyball',
                'fitness' => 'fa-dumbbell',
                'electronic' => 'fa-plug',
                'electrical' => 'fa-bolt',
                'print' => 'fa-print',
                'packag' => 'fa-box-open',
                'advertis' => 'fa-bullhorn',
                'marketing' => 'fa-bullseye',
                'telecom' => 'fa-satellite-dish',
                'internet' => 'fa-wifi',
                'energy' => 'fa-solar-panel',
                'renewable' => 'fa-leaf',
                'ngo' => 'fa-hand-holding-heart',
                'non-profit' => 'fa-hand-holding-heart',
                'government' => 'fa-building-columns',
                'public sector' => 'fa-building-columns',
                'import' => 'fa-ship',
                'export' => 'fa-plane-departure',
                'pet' => 'fa-paw',
                'animal' => 'fa-paw',
                'event' => 'fa-calendar-check',
                'wedding' => 'fa-ring',
                'religious' => 'fa-hands-praying',
                'spiritual' => 'fa-hands-praying',
                'arts' => 'fa-palette',
                'crafts' => 'fa-palette',
                'gifts' => 'fa-gift',
                'books' => 'fa-book',
                'stationery' => 'fa-pen-ruler',
                'office' => 'fa-paperclip',
                'baby' => 'fa-baby',
                'kids' => 'fa-child',
                'security' => 'fa-shield-halved',
                'safety' => 'fa-shield',
                'automotive' => 'fa-car',
                'construction' => 'fa-trowel-bricks',
                'infrastructure' => 'fa-helmet-safety',
                'others' => 'fa-layer-group',
                'default' => 'fa-briefcase'
            ];
            
            function getIconClass($name, $map) {
                $lower = strtolower($name);
                foreach($map as $key => $iconClass) {
                    if (str_contains($lower, $key)) {
                        return $iconClass;
                    }
                }
                return $map['default'];
            }
        @endphp
        
        <div class="categories-grid" id="categoriesGrid">
            @foreach($categories as $index => $cat)
                @php
                    $iconClass = getIconClass($cat->name, $iconMap);
                    $bgStyle = "linear-gradient(135deg, var(--blue) 0%, #0ea5e9 50%, #1e3a8a 100%)";
                    $hideClass = $index >= 12 ? 'category-hidden' : '';
                @endphp
                <div class="category-card reveal-scale {{ $hideClass }}">
                    <div class="icon-wrapper" style="background: {{ $bgStyle }}">
                        @if($cat->icon)
                            <img src="{{ asset('storage/'.$cat->icon) }}" alt="{{ $cat->name }}" style="width:32px; height:32px; object-fit:contain; filter:brightness(0) invert(1);">
                        @else
                            <i class="fa-solid {{ $iconClass }}"></i>
                        @endif
                    </div>
                    <h4>{{ $cat->name }}</h4>
                </div>
            @endforeach
        </div>
        @if(count($categories) > 12)
            <div style="text-align:center; margin-top:48px;" class="reveal">
                <button id="viewAllCategoriesBtn" class="btn-sharp btn-sharp-outline">
                    Explore {{ count($categories) - 12 }} More Industries
                    <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
            </div>
            <script>
                document.getElementById('viewAllCategoriesBtn').addEventListener('click', function() {
                    const hiddenCards = document.querySelectorAll('#categoriesGrid .category-hidden');
                    if (hiddenCards.length > 0) {
                        hiddenCards.forEach(card => card.classList.remove('category-hidden'));
                        this.innerHTML = `Show Less <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="transform: rotate(180deg); margin-left: 8px;"><path d="m6 9 6 6 6-6"/></svg>`;
                    } else {
                        const allCards = document.querySelectorAll('#categoriesGrid .category-card');
                        allCards.forEach((card, index) => {
                            if (index >= 12) card.classList.add('category-hidden');
                        });
                        this.innerHTML = `Explore {{ count($categories) - 12 }} More Industries <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>`;
                    }
                });
            </script>
        @endif
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
            <h2 class="heading-xl">Ready to grow<br>your business?</h2>
            <p class="cta-desc">Join thousands of businesses using Artera to create professional marketing content — automatically, every day.</p>
            <a href="#" class="btn-sharp btn-sharp-primary btn-glow">
                <i class="fa-brands fa-google-play"></i>
                Download the App
                <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:#fff;"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</section>

@endsection

@section('extra_js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // AJAX Search Logic
        const searchInput = document.getElementById('ajaxSearchInput');
        const searchResults = document.getElementById('ajaxSearchResults');
        const resultsList = document.getElementById('ajaxResultsList');
        const viewMoreBtn = document.getElementById('viewMoreBtn');
        const searchForm = document.getElementById('mainSearchForm');
        let searchTimeout = null;

        if(searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                if(searchTimeout) clearTimeout(searchTimeout);
                
                if(query.length < 1) {
                    searchResults.style.display = 'none';
                    return;
                }
                
                searchTimeout = setTimeout(() => {
                    fetch(`{{ route('landing.ajax_search') }}?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            resultsList.innerHTML = '';
                            if(data.length > 0) {
                                data.forEach(item => {
                                    const imgHtml = item.image ? `<img src="${item.image}" alt="${item.title}" style="width:40px; height:40px; border-radius:8px; object-fit:cover;">` : `<div style="width:40px; height:40px; border-radius:8px; background:#f1f5f9; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-image" style="color:#94a3b8;"></i></div>`;
                                    const itemHtml = `
                                        <a href="${item.url}" class="result-item">
                                            ${imgHtml}
                                            <div style="flex:1;">
                                                <h4 style="margin:0; font-size:1rem; color:#1e293b;">${item.title}</h4>
                                                <span style="font-size:0.75rem; color:#64748b; text-transform:uppercase; font-weight:600; letter-spacing:0.5px;">${item.type}</span>
                                            </div>
                                            <i class="fa-solid fa-chevron-right" style="color:#cbd5e1;"></i>
                                        </a>
                                    `;
                                    resultsList.insertAdjacentHTML('beforeend', itemHtml);
                                });
                                viewMoreBtn.href = `{{ route('landing.search') }}?q=${encodeURIComponent(query)}`;
                                searchResults.style.display = 'block';
                            } else {
                                resultsList.innerHTML = '<div style="padding: 20px; text-align: center; color: #64748b;">No results found. Try a different keyword.</div>';
                                viewMoreBtn.href = '#';
                                searchResults.style.display = 'block';
                            }
                        });
                }, 300);
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if(!searchForm.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.style.display = 'none';
                }
            });
            
            // Show dropdown when input is focused and has text
            searchInput.addEventListener('focus', function() {
                if(this.value.trim().length > 0) {
                    searchResults.style.display = 'block';
                }
            });
        }
    });
</script>
@endsection
