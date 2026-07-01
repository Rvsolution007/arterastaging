@extends('landing.layout')

@section('title', $seo['title'])

@section('seo')
    @include('components.seo-head', ['seo' => $seo])
@endsection

@section('content')
<div class="header-spacer"></div>

{{-- Hero Section --}}
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

        <h1 class="heading-lg" style="margin-bottom: 20px;">
            Poster Maker for <span style="color: #93c5fd;">Every Business</span>
        </h1>
        <p class="speakable-intro" style="font-size: 1.25rem; color: rgba(255,255,255,0.8); max-width: 700px; line-height: 1.7; margin-bottom: 32px;">
            Create professional marketing posters, festival greetings, and social media content for your business. Choose from <strong>{{ $categories->count() }}+ business categories</strong> with thousands of industry-specific templates. Powered by AI.
        </p>
        <a href="{{ config('seo.app_links.android') }}" class="btn-sharp btn-sharp-white" style="border-radius: 0;">
            <i class="fab fa-google-play"></i> Download Free App
            <svg class="btn-arrow" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"/></svg>
        </a>
    </div>
</section>

{{-- Business Categories Grid --}}
<section style="padding: 80px 0;">
    <div class="container-wide">
        <div style="text-align: center; margin-bottom: 60px;">
            <span class="eyebrow">ALL BUSINESS CATEGORIES</span>
            <h2 class="heading-md" style="margin-top: 16px;">Choose Your Industry</h2>
            <p style="color: var(--text-gray); font-size: 1.1rem; margin-top: 12px; max-width: 600px; margin-left: auto; margin-right: auto;">
                Select your business category to browse industry-specific poster templates designed for your audience.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;">
            @foreach($categories as $cat)
                @php $catSlug = $cat->slug ?: \Illuminate\Support\Str::slug($cat->name); @endphp
                <a href="{{ url('/poster-maker/' . $catSlug) }}"
                   style="display: block; padding: 32px; border: 1px solid rgba(0,0,0,0.08); text-decoration: none; color: var(--text-dark); transition: all 0.3s ease; position: relative; overflow: hidden;"
                   onmouseover="this.style.borderColor='var(--blue)'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 20px 40px rgba(59,130,246,0.1)'"
                   onmouseout="this.style.borderColor='rgba(0,0,0,0.08)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    <div style="width: 56px; height: 56px; background: rgba(59,130,246,0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                        @if($cat->icon)
                            <img src="{{ asset('uploads/' . $cat->icon) }}" alt="{{ $cat->name }} icon" style="width: 32px; height: 32px; object-fit: contain;" loading="lazy">
                        @else
                            <i class="fas fa-palette" style="color: var(--blue); font-size: 24px;"></i>
                        @endif
                    </div>
                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">{{ $cat->name }}</h3>
                    <p style="color: var(--text-gray); font-size: 14px; line-height: 1.5;">
                        {{ $cat->sub_categories_count ?? 0 }} sub-categories · Browse templates →
                    </p>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- Features Section --}}
<section style="padding: 80px 0; background: var(--bg-dark); color: #fff;">
    <div class="container-wide">
        <div style="text-align: center; margin-bottom: 60px;">
            <span class="eyebrow-plain" style="color: #93c5fd;">WHY ARTERA?</span>
            <h2 class="heading-md speakable-features" style="margin-top: 16px;">Built for Indian Businesses</h2>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 32px;">
            <div style="padding: 32px; border: 1px solid rgba(255,255,255,0.1);">
                <i class="fas fa-robot" style="color: var(--blue); font-size: 28px; margin-bottom: 16px;"></i>
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 10px;">AI-Powered Content</h3>
                <p style="color: rgba(255,255,255,0.5); font-size: 15px; line-height: 1.6;">Automatically generates marketing copy tailored to your business category, products, and target audience.</p>
            </div>
            <div style="padding: 32px; border: 1px solid rgba(255,255,255,0.1);">
                <i class="fas fa-calendar-star" style="color: var(--blue); font-size: 28px; margin-bottom: 16px;"></i>
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 10px;">365+ Festival Templates</h3>
                <p style="color: rgba(255,255,255,0.5); font-size: 15px; line-height: 1.6;">Ready-made templates for every Indian festival — Diwali, Navratri, Holi, Ganesh Chaturthi, and more.</p>
            </div>
            <div style="padding: 32px; border: 1px solid rgba(255,255,255,0.1);">
                <i class="fas fa-language" style="color: var(--blue); font-size: 28px; margin-bottom: 16px;"></i>
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 10px;">Multi-Language Support</h3>
                <p style="color: rgba(255,255,255,0.5); font-size: 15px; line-height: 1.6;">Create posters in Hindi, Marathi, Gujarati, Tamil, Telugu, and more regional languages.</p>
            </div>
            <div style="padding: 32px; border: 1px solid rgba(255,255,255,0.1);">
                <i class="fas fa-bolt" style="color: var(--blue); font-size: 28px; margin-bottom: 16px;"></i>
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 10px;">Create in Seconds</h3>
                <p style="color: rgba(255,255,255,0.5); font-size: 15px; line-height: 1.6;">No design skills needed. Pick a template, add your branding, and download — all in under 60 seconds.</p>
            </div>
        </div>
    </div>
</section>

{{-- FAQ Section --}}
@if(!empty($seo['faq']))
<section style="padding: 80px 0;" id="faq">
    <div class="container-wide">
        <div style="text-align: center; margin-bottom: 60px;">
            <span class="eyebrow">FAQ</span>
            <h2 class="heading-md speakable-faq" style="margin-top: 16px;">Frequently Asked Questions</h2>
        </div>
        <div style="max-width: 800px; margin: 0 auto;">
            @foreach($seo['faq'] as $item)
                <details style="margin-bottom: 16px; border: 1px solid rgba(0,0,0,0.08); padding: 0;">
                    <summary style="padding: 20px 24px; font-weight: 600; font-size: 16px; cursor: pointer; list-style: none; display: flex; justify-content: space-between; align-items: center;">
                        {{ $item['question'] }}
                        <i class="fas fa-chevron-down" style="font-size: 12px; color: var(--text-gray); transition: transform 0.3s;"></i>
                    </summary>
                    <div style="padding: 0 24px 20px; color: var(--text-gray); line-height: 1.7; font-size: 15px;">
                        {{ $item['answer'] }}
                    </div>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- CTA Section --}}
<section style="padding: 80px 0; background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; text-align: center;">
    <div class="container-wide">
        <h2 class="heading-md" style="margin-bottom: 20px;">Start Creating Posters Today</h2>
        <p style="font-size: 1.2rem; color: rgba(255,255,255,0.8); margin-bottom: 40px; max-width: 600px; margin-left: auto; margin-right: auto;">
            Join thousands of businesses creating professional marketing posters with Artera. Free to start.
        </p>
        <a href="{{ config('seo.app_links.android') }}" class="btn-sharp btn-sharp-white" style="border-radius: 0;">
            <i class="fab fa-google-play"></i> Download Free on Google Play
        </a>
    </div>
</section>
@endsection
