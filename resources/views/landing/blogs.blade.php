@extends('landing.layout')

@section('title', 'Artera - Marketing Blog')

@section('extra_css')
<style>
    .blog-header {
        background: linear-gradient(135deg, #E0E7FF 0%, #FFFFFF 100%);
        padding: 80px 0 60px;
        text-align: center;
        border-bottom: 1px solid #e2e8f0;
    }
    .blog-header h1 {
        font-size: 42px;
        font-weight: 800;
        color: var(--primary);
        margin-bottom: 20px;
    }
    .blog-header p {
        font-size: 18px;
        color: var(--text-gray);
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }
    
    .blog-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 30px;
        margin-top: 50px;
        margin-bottom: 50px;
    }
    
    .blog-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow-md);
        transition: var(--transition);
        display: flex;
        flex-direction: column;
        border: 1px solid #f1f5f9;
        text-decoration: none;
        color: inherit;
    }
    .blog-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }
    
    .blog-img-placeholder {
        width: 100%;
        height: 200px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 32px;
        position: relative;
        overflow: hidden;
    }
    .blog-img-placeholder::after {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4xKSIvPjwvc3ZnPg==');
        opacity: 0.5;
    }
    
    .blog-content {
        padding: 24px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    
    .blog-meta {
        font-size: 14px;
        color: var(--text-gray);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .blog-meta i { color: var(--primary-light); }
    
    .blog-title {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 15px;
        line-height: 1.4;
        color: var(--text-dark);
    }
    
    .blog-excerpt {
        font-size: 15px;
        color: var(--text-gray);
        line-height: 1.6;
        margin-bottom: 20px;
        flex-grow: 1;
    }
    
    .blog-read-more {
        font-weight: 600;
        color: var(--primary-light);
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: auto;
    }
    .blog-card:hover .blog-read-more {
        color: var(--primary);
    }
    .blog-card:hover .blog-read-more i {
        transform: translateX(4px);
    }
    .blog-read-more i {
        transition: var(--transition);
    }

    .empty-state {
        text-align: center;
        padding: 80px 0;
        color: var(--text-gray);
    }
    .empty-state i {
        font-size: 48px;
        color: #cbd5e1;
        margin-bottom: 20px;
    }
</style>
@endsection

@section('content')
<section class="blog-header">
    <div class="container" data-aos="fade-up">
        <h1>Marketing Insights & Updates</h1>
        <p>Discover the latest tips, tricks, and feature updates to grow your brand and automate your social media marketing.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        @if($blogs->count() > 0)
            <div class="blog-grid">
                @foreach($blogs as $blog)
                <a href="{{ route('landing.blog_details', $blog->slug) }}" class="blog-card" data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                    <div class="blog-img-placeholder">
                        <i class="fa-solid fa-pen-nib"></i>
                    </div>
                    <div class="blog-content">
                        <div class="blog-meta">
                            <span><i class="fa-regular fa-calendar"></i> {{ $blog->created_at->format('M d, Y') }}</span>
                            <span><i class="fa-regular fa-clock"></i> 5 min read</span>
                        </div>
                        <h2 class="blog-title">{{ $blog->title }}</h2>
                        <p class="blog-excerpt">
                            {{ Str::limit(strip_tags($blog->content), 120) }}
                        </p>
                        <div class="blog-read-more">
                            Read Article <i class="fa-solid fa-arrow-right"></i>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>

            <!-- Pagination -->
            <div style="display: flex; justify-content: center; margin-top: 40px;">
                {{ $blogs->links('pagination::bootstrap-4') }}
            </div>
        @else
            <div class="empty-state">
                <i class="fa-solid fa-newspaper"></i>
                <h2>No Posts Yet</h2>
                <p>We are brewing some amazing content. Check back soon!</p>
            </div>
        @endif
    </div>
</section>
@endsection
