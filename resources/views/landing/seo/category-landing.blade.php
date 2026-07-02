@extends('landing.layout')

@section('title', $seo['title'])

@section('seo')
    @include('components.seo-head', ['seo' => $seo])
@endsection

@section('content')
<div class="header-spacer"></div>

{{-- Hero with Category Info --}}
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

        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
            @if($category->icon)
                <div style="width: 72px; height: 72px; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; border-radius: 12px;">
                    <img src="{{ asset('uploads/' . $category->icon) }}" alt="{{ $category->name }} poster maker" style="width: 40px; height: 40px; object-fit: contain;">
                </div>
            @endif
            <div>
                <h1 class="heading-lg">{{ $category->name }} <span style="color: #93c5fd;">Poster Maker</span></h1>
            </div>
        </div>
        <p class="speakable-intro" style="font-size: 1.2rem; color: rgba(255,255,255,0.8); max-width: 700px; line-height: 1.7; margin-bottom: 32px;">
            Create professional {{ strtolower($category->name) }} marketing posters, banners, and social media content.
            Browse <strong>{{ $templateCount ?: '100' }}+ free templates</strong> designed specifically for
            {{ strtolower($category->name) }} businesses. AI-powered poster maker — no design skills needed.
        </p>
        <div style="display: flex; flex-wrap: wrap; gap: 16px;">
            <a href="{{ config('seo.app_links.android') }}" class="btn-sharp btn-sharp-white" style="border-radius: 0;">
                <i class="fab fa-google-play"></i> Create {{ $category->name }} Poster Free
            </a>
            <a href="#templates" class="btn-sharp btn-sharp-outline" style="border-radius: 0; border-color: rgba(255,255,255,0.4); color: #fff;">
                Browse Templates ↓
            </a>
        </div>
    </div>
</section>

{{-- Stats Bar --}}
<section style="padding: 24px 0; background: var(--bg-dark); border-bottom: 1px solid rgba(255,255,255,0.1);">
    <div class="container-wide" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 40px; color: #fff; text-align: center;">
        <div>
            <div style="font-size: 28px; font-weight: 900; color: var(--blue);">{{ $templateCount ?: '100' }}+</div>
            <div style="font-size: 13px; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.05em;">Templates</div>
        </div>
        <div>
            <div style="font-size: 28px; font-weight: 900; color: var(--blue);">{{ $subCategories->count() }}</div>
            <div style="font-size: 13px; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.05em;">Sub-Categories</div>
        </div>
        <div>
            <div style="font-size: 28px; font-weight: 900; color: var(--blue);">Free</div>
            <div style="font-size: 13px; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.05em;">To Start</div>
        </div>
        <div>
            <div style="font-size: 28px; font-weight: 900; color: var(--blue);">AI</div>
            <div style="font-size: 13px; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.05em;">Powered</div>
        </div>
    </div>
</section>

{{-- Sub-Categories --}}
@if($subCategories->count() > 0)
<section style="padding: 60px 0; background: #f9fafb;">
    <div class="container-wide">
        <h2 style="font-size: 28px; font-weight: 800; margin-bottom: 32px;">
            {{ $category->name }} Sub-Categories
        </h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px;">
            @foreach($subCategories as $sub)
                @php $subSlug = $sub->slug ?: \Illuminate\Support\Str::slug($sub->name); @endphp
                <a href="{{ url('/poster-maker/' . ($category->slug ?: \Illuminate\Support\Str::slug($category->name)) . '/' . $subSlug) }}"
                   style="display: block; padding: 20px; background: #fff; border: 1px solid rgba(0,0,0,0.06); text-decoration: none; color: var(--text-dark); transition: all 0.3s;"
                   onmouseover="this.style.borderColor='var(--blue)'; this.style.transform='translateY(-2px)'"
                   onmouseout="this.style.borderColor='rgba(0,0,0,0.06)'; this.style.transform='translateY(0)'">
                    <h3 style="font-size: 15px; font-weight: 600; margin-bottom: 4px;">{{ $sub->name }}</h3>
                    <span style="font-size: 13px; color: var(--text-muted);">{{ $sub->types_count ?? 0 }} types · View →</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Template Gallery --}}
<section style="padding: 80px 0;" id="templates">
    <div class="container-wide">
        <h2 style="font-size: 28px; font-weight: 800; margin-bottom: 12px;">
            {{ $category->name }} Poster Templates
        </h2>
        <p style="color: var(--text-gray); margin-bottom: 40px; font-size: 16px;">
            Browse and customize professional {{ strtolower($category->name) }} poster templates. Download free or create with AI.
        </p>

        @if($templates->count() > 0)
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px;">
                @foreach($templates as $tmpl)
                    @if($tmpl->frame_image)
                        <a href="{{ url('/template/c' . $tmpl->id . '/' . \Illuminate\Support\Str::slug(($tmpl->category ? $tmpl->category->name : $category->name) . ' poster template')) }}"
                           style="display: block; overflow: hidden; border-radius: 8px; border: 1px solid rgba(0,0,0,0.06); transition: transform 0.3s, box-shadow 0.3s;"
                           onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(0,0,0,0.1)'"
                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            <img src="{{ $tmpl->seo_image }}"
                                 alt="{{ $category->name }} Poster Template - {{ $tmpl->id }}"
                                 style="width: 100%; height: auto; display: block;"
                                 loading="lazy"
                                 width="{{ $tmpl->width ?? 400 }}"
                                 height="{{ $tmpl->height ?? 400 }}">
                        </a>
                    @endif
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 60px 20px; background: #f9fafb; border-radius: 12px;">
                <i class="fas fa-palette" style="font-size: 48px; color: var(--blue); margin-bottom: 16px;"></i>
                <h3 style="font-size: 20px; margin-bottom: 8px;">Templates Coming Soon</h3>
                <p style="color: var(--text-gray);">Download the app to browse all {{ strtolower($category->name) }} templates.</p>
                <a href="{{ config('seo.app_links.android') }}" class="btn-sharp btn-sharp-primary" style="border-radius: 0; margin-top: 20px; font-size: 14px; padding: 14px 28px;">
                    <i class="fab fa-google-play"></i> Open in App
                </a>
            </div>
        @endif
    </div>
</section>

{{-- Related Categories Sidebar --}}
<section style="padding: 60px 0; background: #f9fafb;">
    <div class="container-wide">
        <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 24px;">Browse More Categories</h2>
        <div style="display: flex; flex-wrap: wrap; gap: 12px;">
            @foreach($relatedCategories as $rc)
                @php $rcSlug = $rc->slug ?: \Illuminate\Support\Str::slug($rc->name); @endphp
                <a href="{{ url('/poster-maker/' . $rcSlug) }}"
                   style="padding: 10px 20px; border: 1px solid rgba(0,0,0,0.1); text-decoration: none; color: var(--text-dark); font-size: 14px; font-weight: 500; transition: all 0.2s;"
                   onmouseover="this.style.borderColor='var(--blue)'; this.style.color='var(--blue)'"
                   onmouseout="this.style.borderColor='rgba(0,0,0,0.1)'; this.style.color='var(--text-dark)'">
                    {{ $rc->name }} Poster Maker
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- FAQ Section --}}
@if(!empty($seo['faq']))
<section style="padding: 80px 0;" id="faq">
    <div class="container-wide">
        <h2 style="font-size: 28px; font-weight: 800; margin-bottom: 32px; text-align: center;">
            {{ $category->name }} Poster Maker — FAQ
        </h2>
        <div class="speakable-faq" style="max-width: 800px; margin: 0 auto;">
            @foreach($seo['faq'] as $item)
                <details style="margin-bottom: 12px; border: 1px solid rgba(0,0,0,0.08);">
                    <summary style="padding: 18px 24px; font-weight: 600; font-size: 15px; cursor: pointer; list-style: none; display: flex; justify-content: space-between; align-items: center;">
                        {{ $item['question'] }}
                        <i class="fas fa-chevron-down" style="font-size: 11px; color: var(--text-gray);"></i>
                    </summary>
                    <div style="padding: 0 24px 18px; color: var(--text-gray); line-height: 1.7; font-size: 14px;">
                        {{ $item['answer'] }}
                    </div>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- CTA --}}
<section style="padding: 80px 0; background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; text-align: center;">
    <div class="container-wide">
        <h2 class="heading-md" style="margin-bottom: 20px;">Create {{ $category->name }} Posters Now</h2>
        <p style="font-size: 1.1rem; color: rgba(255,255,255,0.8); margin-bottom: 32px; max-width: 550px; margin-left: auto; margin-right: auto;">
            Download Artera and start creating professional {{ strtolower($category->name) }} marketing posters in seconds. Free to use.
        </p>
        <a href="{{ config('seo.app_links.android') }}" class="btn-sharp btn-sharp-white" style="border-radius: 0;">
            <i class="fab fa-google-play"></i> Download Free App
        </a>
    </div>
</section>
@endsection
