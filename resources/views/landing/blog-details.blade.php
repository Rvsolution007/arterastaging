@extends('landing.layout')

@section('title', $seo['title'])

@section('seo')
    @include('components.seo-head', ['seo' => $seo])
@endsection

@section('extra_css')
<style>
    /* ---- Reading Progress Bar ---- */
    .progress-container {
        width: 100%;
        height: 3px;
        background: transparent;
        position: fixed;
        top: 64px;
        left: 0;
        z-index: 999;
    }
    .progress-bar {
        height: 3px;
        background: linear-gradient(90deg, var(--blue), #60a5fa);
        width: 0%;
    }

    /* ---- Article Header ---- */
    .article-header-section {
        background: var(--bg-white);
        padding: 80px 0 60px;
    }
    .article-header-inner {
        max-width: 800px;
        margin: 0 auto;
        text-align: center;
    }
    .article-title {
        color: var(--text-dark);
        margin-bottom: 32px;
    }
    .article-meta-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 24px;
        flex-wrap: wrap;
    }
    .article-meta-item {
        font-family: 'JetBrains Mono', monospace;
        font-size: 13px;
        font-weight: 500;
        color: var(--text-gray);
        text-transform: uppercase;
        letter-spacing: 0.08em;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .article-meta-divider {
        width: 4px;
        height: 4px;
        background: var(--text-muted);
    }

    /* ---- Hero Image ---- */
    .article-hero {
        width: 100%;
        height: 420px;
        background: linear-gradient(135deg, var(--bg-dark) 0%, #2d2d2d 50%, var(--blue) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.15);
        font-size: 72px;
        position: relative;
        overflow: hidden;
    }
    .article-hero .hero-icon {
        position: relative;
        z-index: 2;
    }

    /* ---- Article Layout ---- */
    .article-body-section {
        background: var(--bg-white);
        padding: 80px 0;
    }
    .article-layout {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 60px;
        max-width: 1100px;
        margin: 0 auto;
    }

    /* ---- Article Content ---- */
    .article-content {
        font-size: 18px;
        line-height: 1.8;
        color: #334155;
    }
    .article-content h2 {
        font-size: 28px;
        font-weight: 900;
        color: var(--text-dark);
        margin: 48px 0 20px;
        letter-spacing: -0.02em;
    }
    .article-content h3 {
        font-size: 22px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 40px 0 16px;
    }
    .article-content p {
        margin-bottom: 24px;
    }
    .article-content img {
        max-width: 100%;
        margin: 24px 0;
    }
    .article-content ul,
    .article-content ol {
        margin-bottom: 24px;
        padding-left: 24px;
    }
    .article-content li {
        margin-bottom: 10px;
    }
    .article-content blockquote {
        border-left: 4px solid var(--blue);
        padding: 24px 24px 24px 28px;
        margin: 36px 0;
        background: #f8fafc;
        font-style: italic;
        color: var(--text-gray);
        line-height: 1.7;
    }
    .article-content a {
        color: var(--blue);
        text-decoration: underline;
        text-underline-offset: 3px;
    }
    .article-content a:hover {
        color: #2563eb;
    }

    /* ---- Sidebar ---- */
    .sidebar {
        position: sticky;
        top: 100px;
        align-self: start;
        display: flex;
        flex-direction: column;
        gap: 28px;
    }

    /* Share Box */
    .share-box {
        border: 1px solid rgba(0, 0, 0, 0.1);
        padding: 28px;
        text-align: center;
    }
    .share-box-title {
        font-family: 'JetBrains Mono', monospace;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: var(--text-gray);
        margin-bottom: 20px;
    }
    .social-icons {
        display: flex;
        justify-content: center;
        gap: 12px;
    }
    .social-icons a {
        width: 44px;
        height: 44px;
        border: 1px solid rgba(0, 0, 0, 0.1);
        color: var(--text-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 15px;
        transition: var(--transition);
    }
    .social-icons a:hover {
        background: var(--blue);
        border-color: var(--blue);
        color: #fff;
    }

    /* CTA Box */
    .cta-box {
        background: var(--bg-dark);
        padding: 36px 28px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .cta-box-heading {
        font-size: 20px;
        font-weight: 900;
        color: #fff;
        margin-bottom: 12px;
        letter-spacing: -0.02em;
        position: relative;
        z-index: 2;
    }
    .cta-box-text {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.6;
        margin-bottom: 24px;
        position: relative;
        z-index: 2;
    }
    .cta-box .btn-sharp {
        padding: 14px 28px;
        font-size: 13px;
        position: relative;
        z-index: 2;
        width: 100%;
        justify-content: center;
    }
    .cta-box .btn-arrow {
        width: 16px;
        height: 16px;
    }

    /* ---- Related Posts Section ---- */
    .related-section {
        background: var(--bg-dark);
        padding: 100px 0 120px;
        position: relative;
        overflow: hidden;
    }
    .related-section-header {
        text-align: center;
        margin-bottom: 60px;
    }
    .related-section-title {
        color: #fff;
    }
    .related-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 32px;
        max-width: 1100px;
        margin: 0 auto;
    }
    .related-card {
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: transparent;
        text-decoration: none;
        color: #fff;
        display: flex;
        flex-direction: column;
        transition: var(--transition);
    }
    .related-card:hover {
        border-color: var(--blue);
    }
    .related-card-img {
        width: 100%;
        height: 180px;
        background: linear-gradient(135deg, #222 0%, #333 50%, rgba(59, 130, 246, 0.3) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.12);
        font-size: 28px;
        position: relative;
        overflow: hidden;
    }
    .related-card-img .noise-overlay {
        opacity: 0.05;
    }
    .related-card-body {
        padding: 28px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .related-card-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 12px;
        line-height: 1.3;
        letter-spacing: -0.01em;
    }
    .related-card-excerpt {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 20px;
        flex-grow: 1;
        line-height: 1.6;
    }
    .related-card-link {
        font-family: 'JetBrains Mono', monospace;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--blue);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .related-card-link svg {
        width: 14px;
        height: 14px;
        transition: transform 0.3s ease;
    }
    .related-card:hover .related-card-link svg {
        transform: translateX(4px);
    }

    /* ---- Responsive ---- */
    @media (max-width: 992px) {
        .article-layout {
            grid-template-columns: 1fr;
            gap: 40px;
        }
        .sidebar {
            position: static;
            flex-direction: row;
        }
        .sidebar > * {
            flex: 1;
        }
        .article-header-section {
            padding: 60px 0 40px;
        }
    }
    @media (max-width: 768px) {
        .sidebar {
            flex-direction: column;
        }
        .article-hero {
            height: 260px;
            font-size: 48px;
        }
        .article-meta-row {
            flex-direction: column;
            gap: 12px;
        }
        .article-meta-divider {
            display: none;
        }
        .related-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<!-- Progress Bar -->
<div class="progress-container">
    <div class="progress-bar" id="myBar"></div>
</div>

<!-- Article Header -->
<section class="article-header-section">
    <div class="container-full">
        <div class="article-header-inner reveal">
            <div class="eyebrow"><span class="typewriter">ARTERA UPDATES</span></div>
            <h1 class="heading-lg article-title split-text">{{ $blog->title }}</h1>
            <div class="article-meta-row">
                <span class="article-meta-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="0"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                    {{ $blog->created_at->format('F d, Y') }}
                </span>
                <span class="article-meta-divider"></span>
                <span class="article-meta-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    5 MIN READ
                </span>
            </div>
        </div>
    </div>
</section>

<!-- Hero Image -->
<div class="article-hero reveal-scale" @if($blog->og_image) style="background-image: url('{{ asset($blog->og_image) }}'); background-size: cover; background-position: center;" @endif>
    <div class="noise-overlay"></div>
    @if(!$blog->og_image)
    <span class="hero-icon">
        <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19 7-7 3 3-7 7-3-3z"/><path d="m18 13-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="m2 2 7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg>
    </span>
    @endif
</div>

<!-- Article Body -->
<section class="article-body-section">
    <div class="container-full">
        <div class="article-layout">
            <!-- Main Content -->
            <div class="article-content reveal-left">
                {!! $blog->content !!}
            </div>

            <!-- Sidebar -->
            <div class="sidebar reveal-right reveal-delay-2">
                <div class="share-box">
                    <div class="share-box-title">Share This Article</div>
                    <div class="social-icons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(Request::url()) }}" target="_blank" aria-label="Share on Facebook">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <a href="https://www.instagram.com/" target="_blank" aria-label="Share on Instagram">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(Request::url()) }}&title={{ urlencode($blog->title) }}" target="_blank" aria-label="Share on LinkedIn">
                            <i class="fa-brands fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>

                <div class="cta-box">
                    <div class="noise-overlay"></div>
                    <h3 class="cta-box-heading text-shimmer-white">Ready to scale?</h3>
                    <p class="cta-box-text">Automate your business marketing today with Artera's AI tools.</p>
                    <a href="{{ route('landing.packages') }}" class="btn-sharp btn-sharp-white btn-glow">
                        VIEW PLANS
                        <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Related Posts -->
@if($relatedBlogs->count() > 0)
<section class="related-section">
    <div class="noise-overlay"></div>
    <div class="container-full">
        <div class="related-section-header reveal">
            <div class="eyebrow" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa;"><span class="typewriter">MORE FROM ARTERA</span></div>
            <h2 class="heading-md related-section-title split-text">Read Next</h2>
        </div>

        <div class="related-grid">
            @foreach($relatedBlogs as $rb)
            <a href="{{ route('landing.blog_details', $rb->slug) }}" class="related-card reveal-scale reveal-delay-{{ $loop->index < 4 ? $loop->index + 1 : 4 }}">
                <div class="related-card-img">
                    <div class="noise-overlay"></div>
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19 7-7 3 3-7 7-3-3z"/><path d="m18 13-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="m2 2 7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg>
                </div>
                <div class="related-card-body">
                    <h3 class="related-card-title">{{ $rb->title }}</h3>
                    <p class="related-card-excerpt">{{ Str::limit(strip_tags($rb->content), 80) }}</p>
                    <div class="related-card-link">
                        READ
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection

@section('extra_js')
<script>
    // Reading Progress Indicator
    window.onscroll = function() {
        var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        var scrolled = (winScroll / height) * 100;
        document.getElementById("myBar").style.width = scrolled + "%";
    };
</script>
@endsection
