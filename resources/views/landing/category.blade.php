@extends('landing.layout')

@section('title', 'Artera - ' . ($category->name ?? 'Category'))

@section('extra_css')
<style>
    .category-header { padding: 80px 0 60px; text-align: center; background: linear-gradient(135deg, var(--primary) 0%, #1E3A8A 100%); color: white; }
    .category-header h1 { font-size: 42px; font-weight: 800; margin-bottom: 15px; color: white; }
    .category-header p { font-size: 20px; opacity: 0.9; max-width: 600px; margin: 0 auto; }

    .template-section { padding: 60px 0; background: #f8fafc; }
    
    .template-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 25px; }
    .template-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: 0.3s; position: relative; }
    .template-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px rgba(0,0,0,0.1); }
    .template-img-wrapper { position: relative; padding-top: 100%; background: #f1f5f9; }
    .template-img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
    
    .template-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; opacity: 0; transition: 0.3s; }
    .template-card:hover .template-overlay { opacity: 1; }
    .btn-customize { background: var(--primary); color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; }
    .btn-customize:hover { background: #1e3a8a; color: white; }

    .template-info { padding: 15px; text-align: center; }
    .template-info h4 { font-size: 16px; margin: 0; color: #333; }

    .empty-state { text-align: center; padding: 50px 0; color: #64748b; }
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
                    <img src="{{ $post->frame_image ? asset('uploads/'.$post->frame_image) : asset('assets/images/placeholder.png') }}" class="template-img" alt="Template">
                    <div class="template-overlay">
                        <a href="{{ App\Models\AppUpdateSetting::getAppUpdateSetting('app_link') }}" target="_blank" class="btn-customize">DOWNLOAD APP TO CUSTOMIZE</a>
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
