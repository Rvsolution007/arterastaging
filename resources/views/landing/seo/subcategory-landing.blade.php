@extends('landing.layout')
@section('title', $seo['title'])
@section('seo')
    @include('components.seo-head', ['seo' => $seo])
@endsection

@section('content')
<div class="header-spacer"></div>

{{-- Hero --}}
<section style="padding: 80px 0 40px; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: #fff;">
    <div class="container-wide">
        <nav style="margin-bottom: 32px; font-size: 14px;">
            @foreach($seo['breadcrumbs'] as $crumb)
                @if(!$loop->last)
                    <a href="{{ url($crumb['url'] ?? '/') }}" style="color: rgba(255,255,255,0.7); text-decoration: none;">{{ $crumb['name'] }}</a>
                    <span style="color: rgba(255,255,255,0.4); margin: 0 8px;">›</span>
                @else
                    <span style="color: #fff;">{{ $crumb['name'] }}</span>
                @endif
            @endforeach
        </nav>
        <h1 class="heading-lg" style="margin-bottom: 16px;">
            {{ $entity->name }} <span style="color: #93c5fd;">Poster Maker</span>
        </h1>
        <p class="speakable-intro" style="font-size: 1.15rem; color: rgba(255,255,255,0.8); max-width: 650px; line-height: 1.7; margin-bottom: 32px;">
            Create professional {{ strtolower($entity->name) }} marketing posters and social media content.
            Part of the {{ $category->name }} poster maker collection. AI-powered — no design skills needed.
        </p>
        <a href="{{ config('seo.app_links.android') }}" class="btn-sharp btn-sharp-white" style="border-radius: 0;">
            <i class="fab fa-google-play"></i> Create {{ $entity->name }} Poster Free
        </a>
    </div>
</section>

{{-- Child Types (if sub-category) --}}
@if($isSubCategory && $childTypes->count() > 0)
<section style="padding: 48px 0; background: #f9fafb;">
    <div class="container-wide">
        <h2 style="font-size: 22px; font-weight: 700; margin-bottom: 20px;">Business Types in {{ $entity->name }}</h2>
        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
            @foreach($childTypes as $ct)
                @php $ctSlug = $ct->slug ?: \Illuminate\Support\Str::slug($ct->name); @endphp
                <a href="{{ url('/poster-maker/' . ($category->slug ?: \Illuminate\Support\Str::slug($category->name)) . '/' . $ctSlug) }}"
                   style="padding: 10px 18px; background: #fff; border: 1px solid rgba(0,0,0,0.08); text-decoration: none; color: var(--text-dark); font-size: 14px; font-weight: 500; transition: border-color 0.2s;"
                   onmouseover="this.style.borderColor='var(--blue)'" onmouseout="this.style.borderColor='rgba(0,0,0,0.08)'">
                    {{ $ct->name }}
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Products --}}
@if($products->count() > 0)
<section style="padding: 48px 0;">
    <div class="container-wide">
        <h2 style="font-size: 22px; font-weight: 700; margin-bottom: 20px;">{{ $entity->name }} Products & Services</h2>
        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
            @foreach($products as $prod)
                <span style="padding: 8px 16px; background: rgba(59,130,246,0.08); color: var(--blue); font-size: 13px; font-weight: 500;">
                    {{ $prod->name }}
                </span>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Templates --}}
<section style="padding: 60px 0; background: {{ $products->count() > 0 ? '#f9fafb' : '#fff' }};" id="templates">
    <div class="container-wide">
        <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 32px;">{{ $entity->name }} Poster Templates</h2>
        @if($templates->count() > 0)
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px;">
                @foreach($templates as $tmpl)
                    @if($tmpl->frame_image)
                        <a href="{{ url('/template/c' . $tmpl->id . '/' . \Illuminate\Support\Str::slug($entity->name . ' poster template')) }}"
                           style="display: block; overflow: hidden; border-radius: 8px; border: 1px solid rgba(0,0,0,0.06); transition: transform 0.3s;"
                           onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                            <img src="{{ $tmpl->seo_image }}" alt="{{ $entity->name }} Poster Template"
                                 style="width: 100%; height: auto; display: block;" loading="lazy"
                                 width="{{ $tmpl->width ?? 400 }}" height="{{ $tmpl->height ?? 400 }}">
                        </a>
                    @endif
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 50px; background: #fff; border-radius: 8px;">
                <i class="fas fa-palette" style="font-size: 40px; color: var(--blue); margin-bottom: 12px;"></i>
                <p style="color: var(--text-gray);">Templates available in the app. Download to browse all {{ strtolower($entity->name) }} templates.</p>
                <a href="{{ config('seo.app_links.android') }}" class="btn-sharp btn-sharp-primary" style="border-radius: 0; margin-top: 16px; font-size: 13px; padding: 12px 24px;">
                    <i class="fab fa-google-play"></i> Open in App
                </a>
            </div>
        @endif
    </div>
</section>

{{-- Related Categories --}}
<section style="padding: 48px 0;">
    <div class="container-wide">
        <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">More Business Categories</h2>
        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
            @foreach($relatedCategories as $rc)
                <a href="{{ url('/poster-maker/' . ($rc->slug ?: \Illuminate\Support\Str::slug($rc->name))) }}"
                   style="padding: 8px 16px; border: 1px solid rgba(0,0,0,0.08); text-decoration: none; color: var(--text-dark); font-size: 13px; transition: border-color 0.2s;"
                   onmouseover="this.style.borderColor='var(--blue)'" onmouseout="this.style.borderColor='rgba(0,0,0,0.08)'">
                    {{ $rc->name }}
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- FAQ --}}
@if(!empty($seo['faq']))
<section style="padding: 60px 0; background: #f9fafb;">
    <div class="container-wide">
        <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 24px; text-align: center;">FAQ</h2>
        <div class="speakable-faq" style="max-width: 750px; margin: 0 auto;">
            @foreach($seo['faq'] as $item)
                <details style="margin-bottom: 10px; border: 1px solid rgba(0,0,0,0.08); background: #fff;">
                    <summary style="padding: 16px 20px; font-weight: 600; font-size: 15px; cursor: pointer; list-style: none;">{{ $item['question'] }}</summary>
                    <div style="padding: 0 20px 16px; color: var(--text-gray); line-height: 1.7; font-size: 14px;">{{ $item['answer'] }}</div>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- CTA --}}
<section style="padding: 60px 0; background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; text-align: center;">
    <div class="container-wide">
        <h2 style="font-size: 28px; font-weight: 800; margin-bottom: 16px;">Create {{ $entity->name }} Posters Now</h2>
        <p style="color: rgba(255,255,255,0.8); margin-bottom: 28px;">Free to start. No design skills needed.</p>
        <a href="{{ config('seo.app_links.android') }}" class="btn-sharp btn-sharp-white" style="border-radius: 0;">
            <i class="fab fa-google-play"></i> Download Free App
        </a>
    </div>
</section>
@endsection
