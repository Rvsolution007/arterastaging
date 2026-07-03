@extends('landing.layout')
@section('title', $seo['title'])
@section('seo')
    @include('components.seo-head', ['seo' => $seo])
@endsection

@section('content')
<div class="header-spacer"></div>

{{-- Breadcrumbs --}}
<section style="padding: 16px 0; background: #f9fafb; border-bottom: 1px solid rgba(0,0,0,0.06);">
    <div class="container-wide">
        <nav style="font-size: 13px;">
            @foreach($seo['breadcrumbs'] as $crumb)
                @if(!$loop->last)
                    <a href="{{ url($crumb['url'] ?? '/') }}" style="color: var(--text-muted); text-decoration: none;">{{ $crumb['name'] }}</a>
                    <span style="color: #ccc; margin: 0 6px;">›</span>
                @else
                    <span style="color: var(--text-dark); font-weight: 500;">{{ $crumb['name'] }}</span>
                @endif
            @endforeach
        </nav>
    </div>
</section>

{{-- Template Detail --}}
<section style="padding: 60px 0;">
    <div class="container-wide">
        <div style="display: grid; grid-template-columns: 1fr; gap: 48px; max-width: 1200px; margin: 0 auto;">
            @php $isMobile = false; @endphp
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 48px; align-items: start;">
                {{-- Template Image --}}
                <div style="position: sticky; top: 80px;">
                    @if($imageUrl)
                        <div style="border-radius: 12px; overflow: hidden; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 20px 60px rgba(0,0,0,0.1);">
                            <img src="{{ $imageUrl }}"
                                 alt="{{ $templateName }} - Free Download"
                                 style="width: 100%; height: auto; display: block;"
                                 width="{{ $template->width ?? 400 }}"
                                 height="{{ $template->height ?? 400 }}">
                        </div>
                    @endif
                </div>

                {{-- Template Info --}}
                <div>
                    <h1 style="font-size: 32px; font-weight: 800; line-height: 1.2; margin-bottom: 16px;">
                        {{ $templateName }}
                    </h1>
                    <p style="color: var(--text-gray); font-size: 16px; line-height: 1.7; margin-bottom: 24px;">
                        Download this professional {{ strtolower($templateName) }} for free. Customize with your business logo, contact details,
                        and brand colors. Perfect for WhatsApp status, Instagram posts, Facebook marketing, and print.
                    </p>

                    {{-- Meta Info --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 32px;">
                        <div style="padding: 16px; background: #f9fafb; border-radius: 8px;">
                            <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Category</div>
                            <div style="font-size: 15px; font-weight: 600; margin-top: 4px;">{{ $parentName }}</div>
                        </div>
                        <div style="padding: 16px; background: #f9fafb; border-radius: 8px;">
                            <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Type</div>
                            <div style="font-size: 15px; font-weight: 600; margin-top: 4px; text-transform: capitalize;">{{ $type }} Template</div>
                        </div>
                        @if($template->width && $template->height)
                        <div style="padding: 16px; background: #f9fafb; border-radius: 8px;">
                            <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Size</div>
                            <div style="font-size: 15px; font-weight: 600; margin-top: 4px;">{{ $template->width }} × {{ $template->height }}</div>
                        </div>
                        @endif
                        <div style="padding: 16px; background: #f9fafb; border-radius: 8px;">
                            <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Price</div>
                            <div style="font-size: 15px; font-weight: 600; margin-top: 4px; color: #16a34a;">{{ $template->paid ? 'Premium' : 'Free' }}</div>
                        </div>
                    </div>

                    {{-- CTA Buttons --}}
                    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 32px;">
                        <a href="{{ config('seo.app_links.android') }}" class="btn-sharp btn-sharp-primary btn-glow" style="border-radius: 0; justify-content: center; font-size: 15px;">
                            <i class="fab fa-google-play"></i> Customize & Download Free
                        </a>
                        <a href="{{ config('seo.app_links.android') }}" class="btn-sharp btn-sharp-outline" style="border-radius: 0; justify-content: center; font-size: 14px;">
                            <i class="fas fa-mobile-alt"></i> Open in Artera App
                        </a>
                    </div>

                    {{-- How to Use --}}
                    <div style="border: 1px solid rgba(0,0,0,0.08); padding: 24px; margin-bottom: 24px;">
                        <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 16px;">How to Use This Template</h3>
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <div style="display: flex; gap: 12px; align-items: flex-start;">
                                <div style="width: 28px; height: 28px; background: var(--blue); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0;">1</div>
                                <div>
                                    <h4 style="font-size: 14px; font-weight: 600;">Download Artera App</h4>
                                    <p style="font-size: 13px; color: var(--text-gray);">Get the free app from Google Play Store</p>
                                </div>
                            </div>
                            <div style="display: flex; gap: 12px; align-items: flex-start;">
                                <div style="width: 28px; height: 28px; background: var(--blue); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0;">2</div>
                                <div>
                                    <h4 style="font-size: 14px; font-weight: 600;">Add Your Business Details</h4>
                                    <p style="font-size: 13px; color: var(--text-gray);">Logo, name, phone, address — everything auto-fills</p>
                                </div>
                            </div>
                            <div style="display: flex; gap: 12px; align-items: flex-start;">
                                <div style="width: 28px; height: 28px; background: var(--blue); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0;">3</div>
                                <div>
                                    <h4 style="font-size: 14px; font-weight: 600;">Download & Share</h4>
                                    <p style="font-size: 13px; color: var(--text-gray);">HD quality — share on WhatsApp, Instagram, Facebook</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Related Templates --}}
@if($relatedTemplates->count() > 0)
<section style="padding: 60px 0; background: #f9fafb;">
    <div class="container-wide">
        <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 24px;">Similar {{ $parentName }} Templates</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 14px;">
            @foreach($relatedTemplates as $rel)
                @if($rel->frame_image)
                    @php
                        $relPrefix = $type === 'festival' ? 'f' : ($type === 'category' ? 'c' : 'p');
                        $relSlug = \Illuminate\Support\Str::slug($parentName . ' poster template');
                    @endphp
                    <a href="{{ url('/template/' . $relPrefix . $rel->id . '/' . $relSlug) }}"
                       style="display: block; overflow: hidden; border-radius: 8px; border: 1px solid rgba(0,0,0,0.06); transition: transform 0.3s;"
                       onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                        <img src="{{ $rel->seo_image }}"
                             alt="{{ $parentName }} Poster Template"
                             style="width: 100%; height: auto; display: block;" loading="lazy">
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- CTA --}}
<section style="padding: 60px 0; background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; text-align: center;">
    <div class="container-wide">
        <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 16px;">Create More {{ $parentName }} Posters</h2>
        <p style="color: rgba(255,255,255,0.8); margin-bottom: 24px;">Thousands of templates. Free to start. No design skills needed.</p>
        <a href="{{ config('seo.app_links.android') }}" class="btn-sharp btn-sharp-white" style="border-radius: 0;">
            <i class="fab fa-google-play"></i> Download Free App
        </a>
    </div>
</section>

@push('styles')
<style>
    @media (max-width: 768px) {
        .container-wide div[style*="grid-template-columns: 1fr 1fr"] {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endpush
@endsection
