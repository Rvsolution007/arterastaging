@extends('landing.layout')

@section('title', $blog->title . ' - Artera Blog')

@section('extra_css')
<style>
    /* Reading Progress Bar */
    .progress-container {
        width: 100%;
        height: 4px;
        background: transparent;
        position: fixed;
        top: 70px; /* Below navbar */
        left: 0;
        z-index: 999;
    }
    .progress-bar {
        height: 4px;
        background: linear-gradient(90deg, var(--primary-light), var(--accent));
        width: 0%;
        border-radius: 0 2px 2px 0;
    }

    .article-header {
        padding: 60px 0 40px;
        text-align: center;
        max-width: 800px;
        margin: 0 auto;
    }
    .article-meta {
        font-size: 14px;
        color: var(--primary-light);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 20px;
    }
    .article-title {
        font-size: 48px;
        font-weight: 800;
        color: var(--text-dark);
        line-height: 1.2;
        margin-bottom: 20px;
    }
    .article-author-date {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        color: var(--text-gray);
        font-size: 15px;
    }

    .article-hero-img {
        width: 100%;
        height: 400px;
        border-radius: 20px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 48px;
        margin-bottom: 50px;
        box-shadow: var(--shadow-lg);
        position: relative;
        overflow: hidden;
    }
    .article-hero-img::after {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4xKSIvPjwvc3ZnPg==');
        opacity: 0.5;
    }

    .article-layout {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 50px;
        max-width: 1100px;
        margin: 0 auto;
        padding-bottom: 80px;
    }

    .article-content {
        font-size: 18px;
        line-height: 1.8;
        color: #334155;
    }
    .article-content h2, .article-content h3 {
        color: var(--text-dark);
        margin: 40px 0 20px;
        font-weight: 700;
    }
    .article-content p { margin-bottom: 24px; }
    .article-content img { max-width: 100%; border-radius: 12px; margin: 20px 0; }
    .article-content ul, .article-content ol { margin-bottom: 24px; padding-left: 20px; }
    .article-content li { margin-bottom: 10px; }
    .article-content blockquote {
        border-left: 4px solid var(--primary-light);
        padding-left: 20px;
        font-style: italic;
        color: var(--text-gray);
        margin: 30px 0;
        background: #f8fafc;
        padding: 20px;
        border-radius: 0 12px 12px 0;
    }

    /* Sidebar */
    .sidebar {
        position: sticky;
        top: 100px;
        align-self: start;
    }
    .share-box {
        background: #f8fafc;
        padding: 24px;
        border-radius: 16px;
        text-align: center;
        border: 1px solid #e2e8f0;
    }
    .share-box h4 {
        margin-bottom: 15px;
        font-size: 16px;
        color: var(--text-gray);
    }
    .social-icons {
        display: flex;
        justify-content: center;
        gap: 15px;
    }
    .social-icons a {
        width: 40px; height: 40px;
        border-radius: 50%;
        background: white;
        color: var(--primary);
        display: flex; align-items: center; justify-content: center;
        text-decoration: none;
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
    }
    .social-icons a:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-3px);
    }

    .cta-box {
        margin-top: 30px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        padding: 30px 20px;
        border-radius: 16px;
        text-align: center;
        color: white;
    }
    .cta-box h3 { font-size: 20px; margin-bottom: 10px; }
    .cta-box p { font-size: 14px; margin-bottom: 20px; opacity: 0.9; line-height: 1.5; }
    .cta-box .btn { padding: 10px 20px; font-size: 14px; background: white; color: var(--primary); }

    /* Related Posts */
    .related-section {
        background: #f8fafc;
        padding: 80px 0;
        border-top: 1px solid #e2e8f0;
    }
    .related-title {
        text-align: center;
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 40px;
    }

    @media (max-width: 992px) {
        .article-layout { grid-template-columns: 1fr; }
        .sidebar { position: static; display: flex; gap: 20px; }
        .share-box, .cta-box { flex: 1; margin-top: 0; }
        .article-title { font-size: 36px; }
    }
    @media (max-width: 768px) {
        .sidebar { flex-direction: column; }
        .article-hero-img { height: 250px; }
    }
</style>
@endsection

@section('content')
<!-- Progress Bar -->
<div class="progress-container">
  <div class="progress-bar" id="myBar"></div>
</div>

<div class="container">
    <div class="article-header" data-aos="fade-up">
        <div class="article-meta">Artera Updates</div>
        <h1 class="article-title">{{ $blog->title }}</h1>
        <div class="article-author-date">
            <span><i class="fa-regular fa-calendar"></i> {{ $blog->created_at->format('F d, Y') }}</span>
            <span><i class="fa-regular fa-clock"></i> 5 min read</span>
        </div>
    </div>

    <div class="article-hero-img" data-aos="fade-up" data-aos-delay="100">
        <i class="fa-solid fa-pen-nib"></i>
    </div>

    <div class="article-layout">
        <!-- Main Content -->
        <div class="article-content" data-aos="fade-right">
            {!! $blog->content !!}
        </div>

        <!-- Sidebar -->
        <div class="sidebar" data-aos="fade-left">
            <div class="share-box">
                <h4>Share this article</h4>
                <div class="social-icons">
                    <a href="https://twitter.com/intent/tweet?text={{ urlencode($blog->title) }}&url={{ urlencode(Request::url()) }}" target="_blank"><i class="fa-brands fa-twitter"></i></a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(Request::url()) }}" target="_blank"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(Request::url()) }}&title={{ urlencode($blog->title) }}" target="_blank"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
            </div>

            <div class="cta-box">
                <h3>Ready to scale?</h3>
                <p>Automate your business marketing today with Artera's AI tools.</p>
                <a href="{{ route('landing.packages') }}" class="btn">View Plans</a>
            </div>
        </div>
    </div>
</div>

@if($relatedBlogs->count() > 0)
<section class="related-section">
    <div class="container">
        <h2 class="related-title">Read Next</h2>
        
        <!-- Reusing the grid from blogs.blade.php -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 30px;">
            @foreach($relatedBlogs as $rb)
            <a href="{{ route('landing.blog_details', $rb->slug) }}" style="background: white; border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-sm); text-decoration: none; color: inherit; display: flex; flex-direction: column; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='var(--shadow-sm)'">
                <div style="width: 100%; height: 160px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; position: relative; overflow: hidden;">
                    <i class="fa-solid fa-pen-nib"></i>
                    <div style="position: absolute; top:0; left:0; right:0; bottom:0; background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4xKSIvPjwvc3ZnPg=='); opacity: 0.5;"></div>
                </div>
                <div style="padding: 20px; flex-grow: 1; display: flex; flex-direction: column;">
                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 10px;">{{ $rb->title }}</h3>
                    <p style="font-size: 14px; color: var(--text-gray); margin-bottom: 15px; flex-grow: 1;">
                        {{ Str::limit(strip_tags($rb->content), 80) }}
                    </p>
                    <div style="font-size: 14px; font-weight: 600; color: var(--primary-light);">Read <i class="fa-solid fa-arrow-right"></i></div>
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
