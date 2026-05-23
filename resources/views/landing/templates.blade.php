@extends('landing.layout')

@section('title', 'Artera - Templates Gallery')

@section('extra_css')
<style>
    .gallery-header { padding: 60px 0 40px; text-align: center; background: #f8fafc; }
    .gallery-header h1 { font-size: 36px; font-weight: 700; color: var(--primary); margin-bottom: 15px; }
    .gallery-header p { font-size: 18px; color: var(--text-gray); }

    .template-section { padding: 40px 0; }
    .section-title { font-size: 24px; font-weight: 600; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }

    .template-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
    .template-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: 0.3s; position: relative; }
    .template-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px rgba(0,0,0,0.1); }
    .template-img-wrapper { position: relative; padding-top: 100%; background: #f1f5f9; }
    .template-img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
    
    .template-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; opacity: 0; transition: 0.3s; }
    .template-card:hover .template-overlay { opacity: 1; }
    .btn-customize { background: var(--primary); color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; }
    .btn-customize:hover { background: #1e3a8a; color: white; }

    .template-info { padding: 15px; text-align: center; }
    .template-info h4 { font-size: 16px; margin: 0; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
</style>
@endsection

@section('content')
<div class="gallery-header">
    <div class="container">
        <h1>Templates Gallery</h1>
        <p>Browse thousands of ready-made templates for your business</p>
    </div>
</div>

<div class="template-section">
    <div class="container">
        
        @if(isset($festivals) && count($festivals) > 0)
        <h2 class="section-title">Festival Templates</h2>
        <div class="template-grid">
            @foreach($festivals as $post)
            <div class="template-card">
                <div class="template-img-wrapper">
                    <img src="{{ $post->frame_image ? asset('uploads/'.$post->frame_image) : asset('assets/images/placeholder.png') }}" class="template-img" alt="{{ $post->festivals->title ?? 'Festival' }}">
                    <div class="template-overlay">
                        <a href="{{ route('client.login') }}" class="btn-customize">Login to Customize</a>
                    </div>
                </div>
                <div class="template-info">
                    <h4>{{ $post->festivals->title ?? 'Festival Post' }}</h4>
                </div>
            </div>
            @endforeach
        </div>
        <br><br>
        @endif

        @if(isset($businessPosts) && count($businessPosts) > 0)
        <h2 class="section-title">Business Templates</h2>
        <div class="template-grid">
            @foreach($businessPosts as $post)
            <div class="template-card">
                <div class="template-img-wrapper">
                    <img src="{{ $post->frame_image ? asset('uploads/'.$post->frame_image) : asset('assets/images/placeholder.png') }}" class="template-img" alt="{{ $post->category->name ?? 'Business Post' }}">
                    <div class="template-overlay">
                        <a href="{{ route('client.login') }}" class="btn-customize">Login to Customize</a>
                    </div>
                </div>
                <div class="template-info">
                    <h4>{{ $post->category->name ?? 'Business Template' }}</h4>
                </div>
            </div>
            @endforeach
        </div>
        <br><br>
        @endif

        @if(isset($customPosts) && count($customPosts) > 0)
        <h2 class="section-title">Custom Templates</h2>
        <div class="template-grid">
            @foreach($customPosts as $post)
            <div class="template-card">
                <div class="template-img-wrapper">
                    <img src="{{ $post->frame_image ? asset('uploads/'.$post->frame_image) : asset('assets/images/placeholder.png') }}" class="template-img" alt="{{ $post->custom_post->name ?? 'Custom' }}">
                    <div class="template-overlay">
                        <a href="{{ route('client.login') }}" class="btn-customize">Login to Customize</a>
                    </div>
                </div>
                <div class="template-info">
                    <h4>{{ $post->custom_post->name ?? 'Custom Post' }}</h4>
                </div>
            </div>
            @endforeach
        </div>
        @endif

    </div>
</div>
@endsection
