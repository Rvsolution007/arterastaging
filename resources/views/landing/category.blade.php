@extends('landing.layout')

@section('title', 'Artera - ' . ($category->name ?? 'Category'))

@section('extra_css')
<style>
    .category-header { padding: 80px 0 60px; text-align: center; background: linear-gradient(135deg, var(--primary) 0%, #1E3A8A 100%); color: white; }
    .category-header h1 { font-size: 42px; font-weight: 800; margin-bottom: 15px; color: white; }
    .category-header p { font-size: 20px; opacity: 0.9; max-width: 600px; margin: 0 auto; }

    .template-section { padding: 80px 0; background: #f8fafc; }
    
    .template-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 30px; }
    .template-card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.04); transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); position: relative; border: 1px solid rgba(0,0,0,0.03); }
    .template-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); border-color: rgba(37, 99, 235, 0.1); }
    .template-img-wrapper { position: relative; padding-top: 100%; background: #f1f5f9; overflow: hidden; }
    .template-img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
    .template-card:hover .template-img { transform: scale(1.05); }
    
    .template-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; opacity: 0; transition: all 0.4s ease; }
    .template-card:hover .template-overlay { opacity: 1; }
    .btn-customize { background: linear-gradient(135deg, var(--primary) 0%, #3b82f6 100%); color: white; padding: 12px 24px; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 14px; letter-spacing: 0.5px; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3); transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; transform: translateY(10px); }
    .template-card:hover .btn-customize { transform: translateY(0); }
    .btn-customize:hover { background: linear-gradient(135deg, #1e40af 0%, var(--primary) 100%); color: white; box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4); transform: translateY(-2px) !important; }

    .template-info { padding: 20px 15px; text-align: center; border-top: 1px solid rgba(0,0,0,0.03); }
    .template-info h4 { font-size: 17px; margin: 0; color: #1e293b; font-weight: 700; }

    .empty-state { text-align: center; padding: 80px 0; color: #64748b; background: white; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-top: 20px; }

    @media (max-width: 768px) {
        .template-grid { grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .template-section { padding: 40px 0; }
        .category-header { padding: 50px 0 30px; }
        .category-header h1 { font-size: 28px; }
        .template-card { border-radius: 12px; }
        .template-info { padding: 12px 8px; }
        .template-info h4 { font-size: 13px; }
        .btn-customize { padding: 8px 12px; font-size: 11px; flex-direction: column; gap: 4px; border-radius: 12px; text-align: center; }
        .btn-customize i { font-size: 16px; }
    }
</style>
@endsection

@section('content')
<div class="category-header">
    <div class="container">
        <h1>{{ $category->name ?? 'Business' }} Posters & Templates</h1>
        <p>Create stunning, professional marketing posts for your {{ $category->name ?? 'business' }} in seconds.</p>
    </div>
</div>

<div class="template-section">
    <div class="container">
        @if(isset($posts) && count($posts) > 0)
        <div class="template-grid">
            @foreach($posts as $post)
            <div class="template-card">
                <div class="template-img-wrapper">
                    <img src="{{ $post->frame_image ? asset('uploads/'.$post->frame_image) : asset('assets/images/placeholder.png') }}" class="template-img" alt="{{ $category->name }} Template">
                    <div class="template-overlay">
                        <a href="{{ config('seo.app_links.android') }}" target="_blank" class="btn-customize">
                            <i class="fa-brands fa-google-play"></i> Download App to Customize
                        </a>
                    </div>
                </div>
                <div class="template-info">
                    <h4>{{ $category->name }} Template</h4>
                </div>
            </div>
            @endforeach
        </div>
        
        <div style="margin-top: 40px; display: flex; justify-content: center;">
            {{ $posts->links('pagination::bootstrap-4') }}
        </div>
        @else
        <div class="empty-state">
            <i class="fa-solid fa-folder-open" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
            <h3>No templates found for this category yet.</h3>
            <p>Check back later or explore our other templates.</p>
            <a href="{{ route('landing.templates') }}" class="btn btn-primary" style="margin-top: 15px;">View All Templates</a>
        </div>
        @endif
    </div>
</div>
@endsection
