@extends('landing.layout')

@section('title', 'Search Results - Artera')

@section('extra_css')
<style>
    .search-hero {
        padding: 64px 0 48px;
        background: #f8f9fa;
        text-align: center;
    }
    .search-container {
        max-width: 800px;
        margin: 0 auto;
        position: relative;
    }
    /* ---- Template Card (tpl-card) ---- */
    .tpl-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    @media (min-width: 640px) {
        .tpl-grid { grid-template-columns: repeat(3, 1fr); gap: 20px; }
    }
    @media (min-width: 1024px) {
        .tpl-grid { grid-template-columns: repeat(4, 1fr); gap: 24px; }
    }
    @media (min-width: 1280px) {
        .tpl-grid { grid-template-columns: repeat(5, 1fr); }
    }
    
    .tpl-card {
        position: relative;
        border: 1px solid rgba(26, 26, 26, 0.08);
        background: #fff;
        overflow: hidden;
        transition: border-color 0.3s ease, transform 0.3s ease;
        border-radius: 12px;
        text-decoration: none;
        display: block;
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
        background: rgba(17, 24, 39, 0.4);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .tpl-card:hover .tpl-card-overlay {
        opacity: 1;
    }
    .tpl-overlay-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 85%;
        padding: 12px 16px;
        background: #ffffff;
        color: #1e3a8a;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        font-weight: 700;
        font-size: 12px;
        text-decoration: none;
        border-radius: 50px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        text-align: center;
        line-height: 1.3;
    }
    .tpl-overlay-btn:hover {
        transform: translateY(-4px) scale(1.02);
        background: linear-gradient(135deg, #2563eb, #3b82f6);
        color: #ffffff;
        box-shadow: 0 15px 35px rgba(37, 99, 235, 0.4);
    }
    .tpl-overlay-btn i {
        font-size: 14px;
    }
    
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
</style>
@endsection

@section('content')
<section class="search-hero">
    <div class="container-full">
        <h1 class="heading-lg" style="margin-bottom: 32px;">Search Templates</h1>
        
        <div class="search-container">
            <form action="{{ route('landing.search') }}" method="GET">
                <div class="search-box" style="position: relative; z-index: 100;">
                    <input type="text" id="searchInput" name="q" value="{{ $query }}" placeholder="Search over 7,00,000 templates..." style="width: 100%; padding: 18px 24px 18px 56px; border-radius: 50px; border: 1px solid #ddd; font-size: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: all 0.3s;" autocomplete="off" required>
                    <i class="fas fa-search" style="position: absolute; left: 24px; top: 50%; transform: translateY(-50%); color: #888; font-size: 18px;"></i>
                    <button type="button" id="searchBtn" class="btn-sharp blue" style="position: absolute; right: 8px; top: 8px; padding: 10px 24px; border-radius: 40px; border: none; font-weight: 600;">Search</button>

                    <div id="searchResults" style="display: none; position: absolute; top: calc(100% + 12px); left: 0; right: 0; background: #fff; border-radius: 16px; box-shadow: 0 12px 32px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05); overflow: hidden; z-index: 100; text-align: left;">
                        <div id="searchResultsContent" style="padding: 8px;"></div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<section style="padding: 64px 0;">
    <div class="container-full">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
            <h2 class="heading-md">Results for "{{ $query }}"</h2>
            <div style="color: #666;">Found {{ $resultsCollection->count() }} results</div>
        </div>

        @if($resultsCollection->isEmpty())
            <div style="text-align: center; padding: 64px 0;">
                <i class="fas fa-search" style="font-size: 48px; color: #ddd; margin-bottom: 24px;"></i>
                <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 8px;">No templates found</h3>
                <p style="color: #666;">Try adjusting your search query or browse our categories.</p>
                <a href="{{ route('seo.festival_hub') }}" class="btn-sharp blue" style="display: inline-flex; margin-top: 24px;">Browse Templates</a>
            </div>
        @else
            <div class="tpl-grid">
                @foreach($resultsCollection as $item)
                    <div class="tpl-card">
                        <div class="tpl-card-img-wrap">
                            @if($item->image)
                                <img src="{{ $item->image }}" class="tpl-card-img" alt="{{ $item->title }}" loading="lazy">
                            @else
                                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #ccc; font-size: 48px;">
                                    <i class="fas {{ $item->type === 'festival' ? 'fa-calendar-alt' : 'fa-briefcase' }}"></i>
                                </div>
                            @endif
                            <div class="tpl-card-overlay">
                                <a href="{{ config('seo.app_links.android') }}" target="_blank" class="tpl-overlay-btn">
                                    <i class="fab fa-google-play"></i> Download App for Customization
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection

@section('extra_js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const searchResults = document.getElementById('searchResults');
    const searchResultsContent = document.getElementById('searchResultsContent');
    let searchTimeout;

    function performSearch() {
        const query = searchInput.value.trim();
        if (query.length < 1) {
            searchResults.style.display = 'none';
            return;
        }

        fetch(`{{ route('landing.ajax_search') }}?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                searchResultsContent.innerHTML = '';
                if (data.length === 0) {
                    searchResultsContent.innerHTML = '<div style="padding: 12px; color: #888; text-align: center;">No results found.</div>';
                } else {
                    data.forEach(item => {
                        const icon = item.type === 'festival' ? 'fa-calendar-alt' : 'fa-briefcase';
                        const el = document.createElement('a');
                        el.href = item.url;
                        el.style = 'display: flex; align-items: center; gap: 16px; padding: 12px; text-decoration: none; border-radius: 8px; transition: background 0.2s; color: #333;';
                        el.onmouseover = () => el.style.background = '#f5f5f5';
                        el.onmouseout = () => el.style.background = 'transparent';
                        
                        let imgHtml = item.image 
                            ? `<img src="${item.image}" style="width: 48px; height: 48px; border-radius: 8px; object-fit: cover;">`
                            : `<div style="width: 48px; height: 48px; border-radius: 8px; background: #eee; display: flex; align-items: center; justify-content: center; color: #888;"><i class="fas ${icon}"></i></div>`;

                        el.innerHTML = `
                            ${imgHtml}
                            <div>
                                <div style="font-weight: 600; font-size: 15px;">${item.title}</div>
                                <div style="font-size: 12px; color: #888; text-transform: capitalize;">${item.type}</div>
                            </div>
                        `;
                        searchResultsContent.appendChild(el);
                    });
                    
                    const viewMoreEl = document.createElement('a');
                    viewMoreEl.href = `{{ route('landing.search') }}?q=${encodeURIComponent(query)}`;
                    viewMoreEl.style = 'display: block; text-align: center; padding: 12px; margin-top: 8px; text-decoration: none; border-radius: 8px; background: #f8f9fa; color: var(--blue); font-weight: 600; border: 1px solid #eee;';
                    viewMoreEl.innerHTML = `View more results for "${query}"`;
                    searchResultsContent.appendChild(viewMoreEl);
                }
                searchResults.style.display = 'block';
            })
            .catch(err => console.error('Search error:', err));
    }

    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 300);
    });

    searchBtn.addEventListener('click', () => {
        // Find form and submit if search btn clicked explicitly, or just submit the form naturally.
        searchInput.closest('form').submit();
    });
    
    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
});
</script>
@endsection
