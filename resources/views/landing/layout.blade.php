<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Artera — AI-Powered Business Poster Maker App')</title>

    {{-- SEO Head Component — Dynamic meta, OG, schema --}}
    @hasSection('seo')
        @yield('seo')
    @else
        <meta name="description" content="Artera uses advanced AI to instantly generate professional festival posters, business templates, and custom social media content for your brand.">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta property="og:title" content="Artera — AI-Powered Business Poster Maker App">
        <meta property="og:description" content="Create stunning marketing posters, festival greetings, and social media content in seconds with Artera.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:site_name" content="Artera">
        <meta name="twitter:card" content="summary_large_image">
    @endif

    <!-- Google Fonts — loaded after first paint to eliminate render-blocking -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Font Awesome — deferred with font-display fix -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    <style>
        /* Force font-display:swap on FontAwesome webfonts to eliminate FOIT */
        @font-face { font-family: 'Font Awesome 6 Free'; font-display: swap; }
        @font-face { font-family: 'Font Awesome 6 Brands'; font-display: swap; }
    </style>
    <script>
        // Load Google Fonts AFTER first paint — non-blocking
        (function(){
            if('requestIdleCallback' in window){
                requestIdleCallback(function(){loadGF();});
            } else {
                window.addEventListener('load',loadGF);
            }
            function loadGF(){
                var l=document.createElement('link');
                l.rel='stylesheet';
                l.href='https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=JetBrains+Mono:wght@400;600&display=swap';
                document.head.appendChild(l);
            }
        })();
    </script>

    <style>
        /* ============================================
           DESIGN SYSTEM — 8x.social Inspired
           ============================================ */
        :root {
            --primary: #1E3A8A;
            --primary-light: #3B82F6;
            --accent: #60A5FA;
            --bg-white: #FFFFFF;
            --bg-dark: #1a1a1a;
            --bg-black: #000000;
            --text-dark: #1a1a1a;
            --text-gray: #555555;
            --text-muted: #999999;
            --blue: #3b82f6;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-dark);
            overflow-x: hidden;
            background-color: var(--bg-white);
            line-height: 1.5;
        }

        /* ---- Typography ---- */
        .font-mono { font-family: 'JetBrains Mono', 'SF Mono', 'Fira Code', monospace; }
        .font-black { font-weight: 900; }
        .font-bold { font-weight: 700; }
        .font-semibold { font-weight: 600; }
        .font-medium { font-weight: 500; }
        .tracking-tight { letter-spacing: -0.02em; }
        .tracking-wider { letter-spacing: 0.05em; }
        .tracking-widest { letter-spacing: 0.2em; }
        .uppercase { text-transform: uppercase; }
        .leading-tight { line-height: 0.95; }
        .leading-relaxed { line-height: 1.65; }

        /* ---- Layout ---- */
        .container-full { width: 100%; padding: 0 24px; }
        .container-wide { width: 100%; max-width: 1400px; margin: 0 auto; padding: 0 24px; }
        @media (min-width: 1024px) { .container-full { padding: 0 48px; } .container-wide { padding: 0 48px; } }
        @media (min-width: 1280px) { .container-full { padding: 0 80px; } .container-wide { padding: 0 80px; } }

        /* ---- Eyebrow Badge ---- */
        .eyebrow {
            display: inline-block;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--blue);
            padding: 8px 16px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 50px;
            margin-bottom: 32px;
        }
        .eyebrow-plain {
            display: inline-block;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--blue);
        }

        /* ---- Section Headings ---- */
        .heading-xl {
            font-size: clamp(2.5rem, 7vw, 6rem);
            font-weight: 900;
            line-height: 0.95;
            letter-spacing: -0.02em;
        }
        .heading-lg {
            font-size: clamp(2.5rem, 6vw, 5rem);
            font-weight: 900;
            line-height: 0.95;
            letter-spacing: -0.02em;
        }
        .heading-md {
            font-size: clamp(2rem, 5vw, 4rem);
            font-weight: 900;
            line-height: 0.95;
            letter-spacing: -0.01em;
        }
        .text-xl { font-size: clamp(1.125rem, 2vw, 1.5rem); }
        .text-lg { font-size: 1.125rem; }
        .text-sm { font-size: 0.875rem; }
        .text-xs { font-size: 0.75rem; }

        /* ---- Buttons — Sharp Geometric (no border-radius) ---- */
        .btn-sharp {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 20px 40px;
            font-weight: 700;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
        }
        .btn-sharp-primary {
            background-color: #2563eb;
            color: #fff;
        }
        .btn-sharp-primary:hover { background-color: #2563eb; transform: translateY(-2px); }
        .btn-sharp-outline {
            background: transparent;
            border: 2px solid var(--blue);
            color: var(--blue);
        }
        .btn-sharp-outline:hover { background: rgba(59, 130, 246, 0.05); }
        .btn-sharp-white {
            background: #fff;
            color: var(--blue);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .btn-sharp-white:hover { transform: translateY(-2px); box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.3); }

        .btn-arrow {
            width: 20px; height: 20px;
            transition: transform 0.3s ease;
        }
        .btn-sharp:hover .btn-arrow { transform: translateX(4px); }

        /* ---- Noise Texture ---- */
        .noise-overlay {
            position: absolute; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
            opacity: 0.03;
            pointer-events: none;
        }

        /* ============================================
           ANIMATION SYSTEM — Advanced Text Animations
           ============================================ */

        /* ---- Base Scroll Reveal ---- */
        .reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .reveal.revealed {
            opacity: 1;
            transform: translateY(0);
        }
        .reveal-delay-1 { transition-delay: 0.1s; }
        .reveal-delay-2 { transition-delay: 0.2s; }
        .reveal-delay-3 { transition-delay: 0.3s; }
        .reveal-delay-4 { transition-delay: 0.4s; }
        .reveal-delay-5 { transition-delay: 0.5s; }
        .reveal-delay-6 { transition-delay: 0.6s; }

        /* ---- Slide from Left / Right ---- */
        .reveal-left {
            opacity: 0;
            transform: translateX(-60px);
            transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .reveal-left.revealed { opacity: 1; transform: translateX(0); }

        .reveal-right {
            opacity: 0;
            transform: translateX(60px);
            transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .reveal-right.revealed { opacity: 1; transform: translateX(0); }

        /* ---- Scale Up Reveal ---- */
        .reveal-scale {
            opacity: 0;
            transform: scale(0.85);
            transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .reveal-scale.revealed { opacity: 1; transform: scale(1); }

        /* ---- Blur In Reveal ---- */
        .reveal-blur {
            opacity: 0;
            filter: blur(12px);
            transform: translateY(20px);
            transition: opacity 0.9s cubic-bezier(0.16, 1, 0.3, 1), filter 0.9s cubic-bezier(0.16, 1, 0.3, 1), transform 0.9s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .reveal-blur.revealed { opacity: 1; filter: blur(0); transform: translateY(0); }

        /* ---- Split Text — Line by Line Mask Reveal ---- */
        .split-text .split-line {
            display: block;
            overflow: hidden;
        }
        .split-text .split-line-inner {
            display: block;
            transform: translateY(110%);
            transition: transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .split-text.revealed .split-line-inner {
            transform: translateY(0);
        }
        .split-text .split-line:nth-child(2) .split-line-inner { transition-delay: 0.08s; }
        .split-text .split-line:nth-child(3) .split-line-inner { transition-delay: 0.16s; }
        .split-text .split-line:nth-child(4) .split-line-inner { transition-delay: 0.24s; }
        .split-text .split-line:nth-child(5) .split-line-inner { transition-delay: 0.32s; }

        /* ---- Stagger Words ---- */
        .stagger-words .stagger-word {
            display: inline-block;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s cubic-bezier(0.16, 1, 0.3, 1), transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .stagger-words.revealed .stagger-word {
            opacity: 1;
            transform: translateY(0);
        }

        /* ---- Typewriter Effect ---- */
        .typewriter {
            display: inline-flex;
            position: relative;
            white-space: nowrap;
            clip-path: inset(0 100% 0 0);
        }
        .typewriter::after {
            content: '';
            position: absolute;
            right: 0;
            top: 10%;
            height: 80%;
            width: 2px;
            background-color: var(--blue);
            animation: typewriter-blink 0.8s step-end infinite;
        }
        .typewriter.revealed {
            animation: typewriter-expand 1.5s steps(30, end) forwards;
        }
        @keyframes typewriter-expand {
            from { clip-path: inset(0 100% 0 0); }
            to { clip-path: inset(0 0 0 0); }
        }
        @keyframes typewriter-blink {
            0%, 100% { opacity: 0; }
            50% { opacity: 1; }
        }

        /* ---- Counter Animated Numbers ---- */
        .counter-up {
            display: inline-block;
        }

        /* ---- Text Shimmer / Gradient Sweep (composited via transform) ---- */
        .text-shimmer, .text-shimmer-white {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        .text-shimmer-white { color: #fff; }
        .text-shimmer { color: var(--text-dark); }
        .text-shimmer::after, .text-shimmer-white::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent 0%, rgba(59,130,246,0.35) 50%, transparent 100%);
            animation: shimmer-sweep 3s ease-in-out infinite;
            mix-blend-mode: overlay;
            will-change: transform;
        }
        @keyframes shimmer-sweep {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* ---- Floating / Pulsing glow effect for CTAs ---- */
        .btn-glow {
            position: relative;
            overflow: visible;
        }
        .btn-glow::after {
            content: '';
            position: absolute;
            inset: -2px;
            background: var(--blue);
            filter: blur(16px);
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: -1;
        }
        .btn-glow:hover::after {
            opacity: 0.4;
        }

        /* ---- Underline draw animation ---- */
        .draw-underline {
            position: relative;
            display: inline-block;
        }
        .draw-underline::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 3px;
            background: var(--blue);
            transition: width 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .draw-underline.revealed::after {
            width: 100%;
        }

        /* ---- Fade up stagger for list items ---- */
        .stagger-list .stagger-item {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity 0.5s cubic-bezier(0.16, 1, 0.3, 1), transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .stagger-list.revealed .stagger-item { opacity: 1; transform: translateY(0); }

        /* ============================================
           NAVBAR — 8x.social style
           ============================================ */
        .site-header {
            position: fixed;
            inset: 0 0 auto 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            transition: var(--transition);
        }
        .site-header.scrolled {
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.08);
        }
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
        }

        /* Logo */
        .site-logo {
            font-size: 22px;
            font-weight: 900;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.02em;
        }
        .site-logo i { color: var(--blue); font-size: 20px; }

        /* Nav links */
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 32px;
            list-style: none;
        }
        .nav-menu a {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-dark);
            text-decoration: none;
            transition: var(--transition);
            position: relative;
        }
        .nav-menu a:hover,
        .nav-menu a.active { color: var(--blue); opacity: 0.85; }

        /* Nav dropdown */
        .nav-dropdown { position: relative; }
        .nav-dropdown-content {
            display: none;
            position: absolute;
            top: calc(100% + 12px);
            left: -12px;
            background: #fff;
            border: 1px solid rgba(0,0,0,0.08);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
            min-width: 200px;
            padding: 8px 0;
            z-index: 1001;
        }
        .nav-dropdown:hover .nav-dropdown-content { display: block; }
        .nav-dropdown-content a {
            display: block;
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 500;
            text-transform: none;
            letter-spacing: 0;
            color: var(--text-dark);
        }
        .nav-dropdown-content a:hover {
            background: rgba(59, 130, 246, 0.06);
            color: var(--blue);
        }

        /* Nav CTA — fixed dimensions prevent CLS */
        .nav-cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            background: #1d4ed8;
            color: #fff !important;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-decoration: none;
            transition: var(--transition);
            min-height: 42px;
            min-width: 160px;
            justify-content: center;
        }
        .nav-cta:hover { background: #1e40af; }

        /* Mobile toggle */
        .mobile-toggle {
            display: none;
            font-size: 22px;
            color: var(--text-dark);
            cursor: pointer;
            background: none;
            border: none;
            padding: 8px;
        }

        @media (max-width: 1024px) {
            .nav-menu, .nav-actions { display: none; }
            .mobile-toggle { display: block; }
            .nav-menu.open {
                display: flex;
                flex-direction: column;
                position: fixed;
                top: 64px; left: 0; right: 0;
                background: #fff;
                padding: 24px;
                gap: 20px;
                border-bottom: 1px solid rgba(0,0,0,0.08);
                box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            }
            .nav-menu.open .nav-dropdown-content {
                position: static;
                box-shadow: none;
                border: none;
                padding-left: 16px;
                display: block;
            }
        }

        /* ============================================
           FOOTER — Full black, 8x.social style
           ============================================ */
        .site-footer {
            width: 100%;
            background: #000;
            color: #fff;
            padding: 60px 0 0;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 40px;
            padding-bottom: 48px;
        }
        .footer-col-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 20px;
        }
        .footer-links { list-style: none; }
        .footer-links li { margin-bottom: 10px; }
        .footer-links a {
            color: rgba(255, 255, 255, 0.65);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s ease;
        }
        .footer-links a:hover { color: #fff; }

        .footer-bottom {
            padding: 24px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .footer-bottom-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: 13px;
        }
        .footer-socials { display: flex; gap: 16px; }
        .footer-socials a {
            color: rgba(255, 255, 255, 0.4);
            font-size: 16px;
            transition: color 0.2s ease;
        }
        .footer-socials a:hover { color: #fff; }

        @media (max-width: 1024px) {
            .footer-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .footer-grid { grid-template-columns: repeat(2, 1fr); }
            .footer-bottom-inner { flex-direction: column; gap: 12px; text-align: center; }
        }
        @media (max-width: 480px) {
            .footer-grid { grid-template-columns: 1fr; }
        }

        /* ---- Utility spacer ---- */
        .header-spacer { height: 64px; }

        /* ---- Hero LCP Fix: make hero heading visible instantly (no animation delay) ---- */
        .hero-section .split-text {
            opacity: 1 !important;
            transform: none !important;
        }
        .hero-section .split-text .split-line-inner,
        .hero-section .split-text .split-line {
            transform: none !important;
            opacity: 1 !important;
        }
        .hero-section > .container-full .reveal:first-child {
            opacity: 1 !important;
            transform: none !important;
        }
        .hero-section .typewriter {
            clip-path: none !important;
        }
    </style>
    @yield('extra_css')
</head>
<body>

    <!-- Navigation -->
    <header class="site-header" id="siteHeader">
        <div class="container-full">
            <div class="header-inner">
                <a href="{{ route('landing.home') }}" class="site-logo">
                    <i class="fa-solid fa-layer-group"></i> Artera
                </a>

                <ul class="nav-menu" id="navMenu">
                    <li><a href="{{ route('landing.home') }}" class="{{ request()->routeIs('landing.home') ? 'active' : '' }}">Home</a></li>
                    <li><a href="{{ route('landing.templates') }}" class="{{ request()->routeIs('landing.templates') ? 'active' : '' }}">Templates</a></li>
                    <li class="nav-dropdown">
                        <a href="#">Tools <i class="fa-solid fa-chevron-down" style="font-size:10px; margin-left:4px;"></i></a>
                        <div class="nav-dropdown-content">
                            <a href="{{ route('landing.logo_maker') }}">Logo Maker</a>
                            <a href="{{ route('landing.digital_business_cards') }}">Digital Business Cards</a>
                            <a href="{{ route('landing.video_maker') }}">Video Maker</a>
                        </div>
                    </li>
                    <li><a href="{{ route('landing.features') }}" class="{{ request()->routeIs('landing.features') ? 'active' : '' }}">Features</a></li>
                    <li><a href="{{ route('landing.packages') }}" class="{{ request()->routeIs('landing.packages') ? 'active' : '' }}">Packages</a></li>
                    <li><a href="{{ route('landing.blogs') }}" class="{{ request()->routeIs('landing.blogs', 'landing.blog_details') ? 'active' : '' }}">Blog</a></li>
                    <li><a href="{{ route('landing.contact') }}" class="{{ request()->routeIs('landing.contact') ? 'active' : '' }}">Contact</a></li>
                </ul>

                <div class="nav-actions" style="display:flex; align-items:center; gap:12px;">
                    <a href="#" class="nav-cta">
                        <i class="fa-brands fa-google-play"></i> Download App
                    </a>
                </div>

                <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <div class="header-spacer"></div>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container-full">
            <div class="footer-grid">
                <div>
                    <h3 class="footer-col-title">Company</h3>
                    <ul class="footer-links">
                        <li><a href="{{ route('landing.home') }}">Home</a></li>
                        <li><a href="{{ route('landing.features') }}">Features</a></li>
                        <li><a href="{{ route('landing.blogs') }}">Blog</a></li>
                        <li><a href="{{ route('landing.contact') }}">Contact</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="footer-col-title">Templates</h3>
                    <ul class="footer-links">
                        <li><a href="{{ route('landing.category', 'real-estate') }}">Real Estate</a></li>
                        <li><a href="{{ route('landing.category', 'doctors') }}">Doctors</a></li>
                        <li><a href="{{ route('landing.category', 'politicians') }}">Politicians</a></li>
                        <li><a href="{{ route('landing.category', 'education') }}">Education</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="footer-col-title">Tools</h3>
                    <ul class="footer-links">
                        <li><a href="{{ route('landing.logo_maker') }}">Logo Maker</a></li>
                        <li><a href="{{ route('landing.digital_business_cards') }}">Business Cards</a></li>
                        <li><a href="{{ route('landing.video_maker') }}">Video Maker</a></li>
                        <li><a href="{{ route('landing.templates') }}">All Templates</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="footer-col-title">Legal</h3>
                    <ul class="footer-links">
                        <li><a href="{{ url('/privacy-policy') }}">Privacy Policy</a></li>
                        <li><a href="{{ url('/terms-condition') }}">Terms & Conditions</a></li>
                        <li><a href="{{ url('/refund-policy') }}">Refund Policy</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="footer-col-title">Download</h3>
                    <ul class="footer-links">
                        <li><a href="#">Get the App</a></li>
                        <li><a href="{{ route('landing.packages') }}">View Plans</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="container-full" style="padding:0;">
                    <div class="footer-bottom-inner">
                        <div>&copy; {{ date('Y') }} Artera. All rights reserved.</div>
                        <div class="footer-socials">
                            <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                            <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                            <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                            <a href="#" aria-label="Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // ============================================
        // NAVBAR SCROLL + MOBILE TOGGLE (lightweight, run immediately)
        // ============================================
        (function(){
            var header = document.getElementById('siteHeader');
            if(header){
                window.addEventListener('scroll', function(){
                    if(window.scrollY > 30){ header.classList.add('scrolled'); }
                    else { header.classList.remove('scrolled'); }
                }, {passive:true});
            }
            document.addEventListener('DOMContentLoaded', function(){
                var mt = document.getElementById('mobileToggle');
                var nm = document.getElementById('navMenu');
                if(mt && nm){
                    mt.addEventListener('click', function(){
                        nm.classList.toggle('open');
                        var icon = mt.querySelector('i');
                        if(icon){ icon.classList.toggle('fa-bars'); icon.classList.toggle('fa-xmark'); }
                    });
                }
            });
        })();
    </script>
    <script>
        // ============================================
        // ANIMATIONS — Deferred to after first paint
        // ============================================
        function _initAnimations(){
            // 1. SPLIT TEXT — skip hero for LCP
            document.querySelectorAll('.split-text').forEach(function(el){
                if(el.closest('.hero-section')) return;
                var lines = el.querySelectorAll('span[style*="display:block"], span[style*="display: block"]');
                if(lines.length > 0){
                    lines.forEach(function(line){
                        var inner = document.createElement('span');
                        inner.className = 'split-line-inner';
                        inner.innerHTML = line.innerHTML;
                        line.innerHTML = '';
                        line.classList.add('split-line');
                        line.appendChild(inner);
                    });
                } else {
                    var html = el.innerHTML;
                    var parts = html.split(/<br\s*\/?>/i);
                    if(parts.length > 1){
                        el.innerHTML = parts.map(function(p){
                            return '<span class="split-line"><span class="split-line-inner">'+p.trim()+'</span></span>';
                        }).join('');
                    } else {
                        el.innerHTML = '<span class="split-line"><span class="split-line-inner">'+html+'</span></span>';
                    }
                }
            });

            // 2. STAGGER WORDS
            document.querySelectorAll('.stagger-words').forEach(function(el){
                var text = el.textContent.trim();
                var words = text.split(/\s+/);
                el.innerHTML = words.map(function(w, i){
                    return '<span class="stagger-word" style="transition-delay:'+i*0.04+'s">'+w+'</span>';
                }).join(' ');
            });

            // 3. STAGGER LIST
            document.querySelectorAll('.stagger-list').forEach(function(list){
                var items = list.querySelectorAll('.stagger-item');
                items.forEach(function(item, i){ item.style.transitionDelay = i*0.08+'s'; });
            });

            // 4. INTERSECTION OBSERVER
            var sel = '.reveal, .reveal-left, .reveal-right, .reveal-scale, .reveal-blur, .split-text:not(.hero-section .split-text), .stagger-words, .stagger-list, .typewriter:not(.hero-section .typewriter), .draw-underline';
            var els = document.querySelectorAll(sel);
            if('IntersectionObserver' in window){
                var obs = new IntersectionObserver(function(entries){
                    for(var i=0;i<entries.length;i++){
                        if(entries[i].isIntersecting){
                            entries[i].target.classList.add('revealed');
                            obs.unobserve(entries[i].target);
                        }
                    }
                }, {threshold:0.1, rootMargin:'0px 0px -60px 0px'});
                els.forEach(function(el){ obs.observe(el); });
            } else {
                els.forEach(function(el){ el.classList.add('revealed'); });
            }

            // 5. COUNTER UP
            var counterEls = document.querySelectorAll('.counter-up');
            if(counterEls.length > 0){
                var cObs = new IntersectionObserver(function(entries){
                    entries.forEach(function(entry){
                        if(entry.isIntersecting){
                            var el = entry.target;
                            var target = parseInt(el.getAttribute('data-target'))||0;
                            var suffix = el.getAttribute('data-suffix')||'';
                            var duration = 1500, start = performance.now();
                            function upd(now){
                                var p = Math.min((now-start)/duration,1);
                                var e = 1-Math.pow(1-p,3);
                                el.textContent = Math.floor(e*target).toLocaleString()+suffix;
                                if(p<1) requestAnimationFrame(upd);
                            }
                            requestAnimationFrame(upd);
                            cObs.unobserve(el);
                        }
                    });
                }, {threshold:0.3});
                counterEls.forEach(function(el){ cObs.observe(el); });
            }
        }

        // Run animations after first paint to avoid forced reflow
        if('requestIdleCallback' in window){
            requestIdleCallback(_initAnimations);
        } else {
            window.addEventListener('load', function(){ setTimeout(_initAnimations, 0); });
        }
    </script>
    @yield('extra_js')
</body>
</html>
