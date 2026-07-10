@extends('landing.layout')

@section('title', 'Artera - Marketing Blog')

@section('extra_css')
<style>
    /* ============================================
       BLOG PAGE — 8x.social Design System
       ============================================ */

    /* ---- Hero Header ---- */
    .blog-hero {
        padding: 100px 0 80px;
        text-align: center;
        background: linear-gradient(135deg, var(--blue) 0%, #4338ca 100%);
        position: relative;
    }
    .blog-hero .eyebrow {
        margin-bottom: 28px;
        color: rgba(255, 255, 255, 0.9);
        background: rgba(255, 255, 255, 0.1);
    }
    .blog-hero .heading-lg {
        color: #ffffff;
        margin-bottom: 24px;
    }
    .blog-hero-subtitle {
        font-size: clamp(1rem, 1.5vw, 1.25rem);
        color: rgba(255, 255, 255, 0.8);
        max-width: 560px;
        margin: 0 auto;
        line-height: 1.7;
    }
    .blog-hero-line {
        width: 60px;
        height: 3px;
        background: #ffffff;
        margin: 40px auto 0;
    }

    /* ---- Blog Grid ---- */
    .blog-section {
        padding: 80px 0 100px;
        background: var(--bg-white);
    }
    .blog-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 32px;
    }
    @media (max-width: 1024px) {
        .blog-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 640px) {
        .blog-grid {
            grid-template-columns: 1fr;
        }
    }

    /* ---- Blog Card ---- */
    .blog-card {
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 2px solid rgba(26, 26, 26, 0.08);
        text-decoration: none;
        color: inherit;
        overflow: hidden;
        transition: border-color 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                    transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .blog-card:hover {
        border-color: var(--blue);
        transform: translateY(-4px);
    }

    /* Card Image Placeholder */
    .blog-card-visual {
        width: 100%;
        height: 200px;
        background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }
    .blog-card-visual .noise-overlay {
        opacity: 0.05;
    }
    .blog-card-icon {
        position: relative;
        z-index: 1;
        width: 48px;
        height: 48px;
        color: rgba(255, 255, 255, 0.85);
    }

    /* Card Body */
    .blog-card-body {
        padding: 28px 24px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    /* Meta Row */
    .blog-card-meta {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 16px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--text-muted);
    }
    .blog-card-meta span {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .blog-card-meta svg {
        width: 13px;
        height: 13px;
        stroke: var(--text-muted);
        flex-shrink: 0;
    }

    /* Card Title */
    .blog-card-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-dark);
        line-height: 1.45;
        margin-bottom: 12px;
        letter-spacing: -0.01em;
    }

    /* Card Excerpt */
    .blog-card-excerpt {
        font-size: 14px;
        color: var(--text-gray);
        line-height: 1.7;
        margin-bottom: 24px;
        flex-grow: 1;
    }

    /* Read More Link */
    .blog-card-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 700;
        color: var(--blue);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: auto;
        transition: gap 0.3s ease;
    }
    .blog-card-link svg {
        width: 16px;
        height: 16px;
        transition: transform 0.3s ease;
    }
    .blog-card:hover .blog-card-link svg {
        transform: translateX(4px);
    }

    /* ---- Pagination ---- */
    .blog-pagination {
        display: flex;
        justify-content: center;
        margin-top: 64px;
    }
    .blog-pagination .pagination {
        display: flex;
        align-items: center;
        gap: 4px;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .blog-pagination .page-item .page-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 44px;
        height: 44px;
        padding: 0 14px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-dark);
        background: transparent;
        border: 2px solid rgba(26, 26, 26, 0.1);
        text-decoration: none;
        transition: var(--transition);
    }
    .blog-pagination .page-item .page-link:hover {
        border-color: var(--blue);
        color: var(--blue);
    }
    .blog-pagination .page-item.active .page-link {
        background: var(--blue);
        border-color: var(--blue);
        color: #fff;
    }
    .blog-pagination .page-item.disabled .page-link {
        color: var(--text-muted);
        border-color: rgba(26, 26, 26, 0.06);
        pointer-events: none;
    }

    /* ---- Empty State ---- */
    .blog-empty {
        text-align: center;
        padding: 120px 0;
        position: relative;
    }
    .blog-empty-icon {
        width: 64px;
        height: 64px;
        color: var(--text-dark);
        margin-bottom: 32px;
        opacity: 0.2;
    }
    .blog-empty h2 {
        font-size: 28px;
        font-weight: 900;
        color: var(--text-dark);
        margin-bottom: 12px;
        letter-spacing: -0.02em;
    }
    .blog-empty p {
        font-size: 16px;
        color: var(--text-gray);
        line-height: 1.6;
    }
</style>
@endsection

@section('content')
{{-- ===================== HERO HEADER ===================== --}}
<section class="blog-hero">
    <div class="container-full">
        <div class="reveal">
            <span class="eyebrow"><span class="typewriter">ARTERA BLOG</span></span>
        </div>
        <h1 class="heading-lg split-text reveal-delay-1">Marketing Insights<br>&amp; Updates</h1>
        <p class="blog-hero-subtitle stagger-words reveal-delay-2">
            Discover the latest tips, tricks, and feature updates to grow your brand and automate your social media marketing.
        </p>
        <div class="blog-hero-line reveal reveal-delay-3"></div>
    </div>
</section>

{{-- ===================== BLOG GRID ===================== --}}
<section class="blog-section">
    <div class="container-full">
        @if($blogs->count() > 0)
            <div class="blog-grid">
                @foreach($blogs as $blog)
                <a href="{{ route('landing.blog_details', $blog->slug) }}" class="blog-card reveal-scale reveal-delay-{{ ($loop->index % 3) + 1 }}">
                    {{-- Card Visual --}}
                    <div class="blog-card-visual" @if($blog->og_image) style="background-image: url('{{ asset($blog->og_image) }}'); background-size: cover; background-position: center;" @endif>
                        <div class="noise-overlay"></div>
                        @if(!$blog->og_image)
                        <svg class="blog-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9"/>
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                        </svg>
                        @endif
                    </div>

                    {{-- Card Body --}}
                    <div class="blog-card-body">
                        <div class="blog-card-meta">
                            <span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                {{ $blog->created_at->format('M d, Y') }}
                            </span>
                            <span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                </svg>
                                5 MIN READ
                            </span>
                        </div>

                        <h2 class="blog-card-title">{{ $blog->title }}</h2>
                        <p class="blog-card-excerpt">{{ Str::limit(strip_tags($blog->content), 120) }}</p>

                        <div class="blog-card-link">
                            Read Article
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="blog-pagination reveal">
                {{ $blogs->links('pagination::bootstrap-4') }}
            </div>
        @else
            {{-- Empty State --}}
            <div class="blog-empty reveal-blur">
                <svg class="blog-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/>
                    <path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6z"/>
                </svg>
                <h2>No Posts Yet</h2>
                <p>We are brewing some amazing content. Check back soon!</p>
            </div>
        @endif
    </div>
</section>
@endsection
