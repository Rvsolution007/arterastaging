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
            {{ $festival->title }} <span style="color: #93c5fd;">Poster Maker {{ now()->year }}</span>
        </h1>
        <p class="speakable-intro" style="font-size: 1.15rem; color: rgba(255,255,255,0.8); max-width: 700px; line-height: 1.7; margin-bottom: 16px;">
            Create stunning {{ $festival->title }} posters for your business. Browse <strong>{{ $templateCount ?: '50' }}+ free {{ $festival->title }} templates</strong>.
            Add your business branding and share on WhatsApp, Instagram & Facebook.
        </p>
        @if($festival->festivals_date)
            <p style="font-size: 1rem; color: #93c5fd; margin-bottom: 32px;">
                <i class="fas fa-calendar-alt"></i>
                {{ $festival->title }} {{ now()->year }}: {{ \Carbon\Carbon::parse($festival->festivals_date)->format('F j, Y') }}
            </p>
        @endif
        <a href="{{ config('seo.app_links.android') }}" class="btn-sharp btn-sharp-white" style="border-radius: 0;">
            <i class="fab fa-google-play"></i> Create {{ $festival->title }} Poster Free
        </a>
    </div>
</section>

{{-- Template Gallery --}}
<section style="padding: 80px 0;" id="templates">
    <div class="container-wide">
        <h2 style="font-size: 28px; font-weight: 800; margin-bottom: 12px;">
            {{ $festival->title }} Poster Templates
        </h2>
        <p style="color: var(--text-gray); margin-bottom: 40px;">
            Professional {{ $festival->title }} poster templates for your business marketing. Customize and download free.
        </p>
        @if($templates->count() > 0)
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px;">
                @foreach($templates as $tmpl)
                    @if($tmpl->frame_image)
                        <a href="{{ url('/template/f' . $tmpl->id . '/' . \Illuminate\Support\Str::slug($festival->title . ' poster template')) }}"
                           style="display: block; overflow: hidden; border-radius: 8px; border: 1px solid rgba(0,0,0,0.06); transition: transform 0.3s, box-shadow 0.3s;"
                           onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(0,0,0,0.1)'"
                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            <img src="{{ $tmpl->seo_image }}"
                                 alt="{{ $festival->title }} Poster Template - Design {{ $loop->iteration }}"
                                 style="width: 100%; height: auto; display: block;"
                                 loading="lazy"
                                 width="{{ $tmpl->width ?? 400 }}" height="{{ $tmpl->height ?? 400 }}">
                        </a>
                    @endif
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 60px; background: #f9fafb; border-radius: 8px;">
                <i class="fas fa-star" style="font-size: 48px; color: var(--blue); margin-bottom: 16px;"></i>
                <h3 style="margin-bottom: 8px;">{{ $festival->title }} Templates Available in App</h3>
                <p style="color: var(--text-gray);">Download Artera to browse all {{ $festival->title }} poster templates.</p>
            </div>
        @endif
    </div>
</section>

{{-- Related Festivals --}}
<section style="padding: 48px 0; background: #f9fafb;">
    <div class="container-wide">
        <h2 style="font-size: 22px; font-weight: 700; margin-bottom: 20px;">More Festival Poster Makers</h2>
        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
            @foreach($relatedFestivals as $rf)
                <a href="{{ url('/festival-poster/' . \Illuminate\Support\Str::slug($rf->title)) }}"
                   style="padding: 10px 18px; border: 1px solid rgba(0,0,0,0.08); text-decoration: none; color: var(--text-dark); font-size: 14px; font-weight: 500; transition: border-color 0.2s;"
                   onmouseover="this.style.borderColor='var(--blue)'" onmouseout="this.style.borderColor='rgba(0,0,0,0.08)'">
                    {{ $rf->title }}
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- FAQ --}}
@if(!empty($seo['faq']))
<section style="padding: 60px 0;">
    <div class="container-wide">
        <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 24px; text-align: center;">{{ $festival->title }} Poster Maker — FAQ</h2>
        <div class="speakable-faq" style="max-width: 750px; margin: 0 auto;">
            @foreach($seo['faq'] as $item)
                <details style="margin-bottom: 10px; border: 1px solid rgba(0,0,0,0.08);">
                    <summary style="padding: 16px 20px; font-weight: 600; font-size: 15px; cursor: pointer; list-style: none;">{{ $item['question'] }}</summary>
                    <div style="padding: 0 20px 16px; color: var(--text-gray); line-height: 1.7; font-size: 14px;">{{ $item['answer'] }}</div>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif

<section style="padding: 60px 0; background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; text-align: center;">
    <div class="container-wide">
        <h2 style="font-size: 28px; font-weight: 800; margin-bottom: 16px;">Create {{ $festival->title }} Posters Now</h2>
        <p style="color: rgba(255,255,255,0.8); margin-bottom: 28px;">Send branded {{ $festival->title }} greetings to your customers.</p>
        <a href="{{ config('seo.app_links.android') }}" class="btn-sharp btn-sharp-white" style="border-radius: 0;">
            <i class="fab fa-google-play"></i> Download Free App
        </a>
    </div>
</section>
@endsection
