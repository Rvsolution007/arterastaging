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
        <h1 class="heading-lg" style="margin-bottom: 20px;">
            Festival <span style="color: #93c5fd;">Poster Maker</span>
        </h1>
        <p class="speakable-intro" style="font-size: 1.2rem; color: rgba(255,255,255,0.8); max-width: 700px; line-height: 1.7; margin-bottom: 32px;">
            Create beautiful festival posters for <strong>{{ $festivals->count() }}+ festivals</strong>.
            Diwali, Navratri, Holi, Eid, Christmas, and every Indian festival. Add your business branding and share instantly.
        </p>
        <a href="{{ config('seo.app_links.android') }}" class="btn-sharp btn-sharp-white" style="border-radius: 0;">
            <i class="fab fa-google-play"></i> Download Free App
        </a>
    </div>
</section>

{{-- Festival Grid by Month --}}
<section style="padding: 80px 0;">
    <div class="container-wide">
        @foreach($grouped as $month => $monthFestivals)
            <div style="margin-bottom: 48px;">
                <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid var(--blue); display: inline-block;">
                    {{ $month }}
                </h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px;">
                    @foreach($monthFestivals as $fest)
                        @php $festSlug = \Illuminate\Support\Str::slug($fest->title); @endphp
                        <a href="{{ url('/festival-poster/' . $festSlug) }}"
                           style="display: flex; align-items: center; gap: 16px; padding: 18px 20px; border: 1px solid rgba(0,0,0,0.06); text-decoration: none; color: var(--text-dark); transition: all 0.3s;"
                           onmouseover="this.style.borderColor='var(--blue)'; this.style.transform='translateY(-2px)'"
                           onmouseout="this.style.borderColor='rgba(0,0,0,0.06)'; this.style.transform='translateY(0)'">
                            @if($fest->image)
                                <img src="{{ asset('uploads/' . $fest->image) }}" alt="{{ $fest->title }} poster"
                                     style="width: 48px; height: 48px; border-radius: 8px; object-fit: cover;" loading="lazy">
                            @else
                                <div style="width: 48px; height: 48px; background: rgba(59,130,246,0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-star" style="color: var(--blue);"></i>
                                </div>
                            @endif
                            <div>
                                <h3 style="font-size: 15px; font-weight: 600; margin-bottom: 2px;">{{ $fest->title }}</h3>
                                <span style="font-size: 12px; color: var(--text-muted);">
                                    {{ $fest->festivals_date ? \Carbon\Carbon::parse($fest->festivals_date)->format('M j') : '' }} · View Templates →
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- FAQ --}}
@if(!empty($seo['faq']))
<section style="padding: 60px 0; background: #f9fafb;">
    <div class="container-wide">
        <h2 style="font-size: 28px; font-weight: 800; margin-bottom: 32px; text-align: center;">Festival Poster Maker — FAQ</h2>
        <div class="speakable-faq" style="max-width: 800px; margin: 0 auto;">
            @foreach($seo['faq'] as $item)
                <details style="margin-bottom: 12px; border: 1px solid rgba(0,0,0,0.08); background: #fff;">
                    <summary style="padding: 18px 24px; font-weight: 600; font-size: 15px; cursor: pointer; list-style: none;">{{ $item['question'] }}</summary>
                    <div style="padding: 0 24px 18px; color: var(--text-gray); line-height: 1.7; font-size: 14px;">{{ $item['answer'] }}</div>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif

<section style="padding: 60px 0; background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; text-align: center;">
    <div class="container-wide">
        <h2 style="font-size: 28px; font-weight: 800; margin-bottom: 16px;">Create Festival Posters Today</h2>
        <p style="color: rgba(255,255,255,0.8); margin-bottom: 28px;">Branded festival greetings for every occasion. Free to start.</p>
        <a href="{{ config('seo.app_links.android') }}" class="btn-sharp btn-sharp-white" style="border-radius: 0;">
            <i class="fab fa-google-play"></i> Download Free App
        </a>
    </div>
</section>
@endsection
