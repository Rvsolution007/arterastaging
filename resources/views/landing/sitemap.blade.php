@extends('landing.layout')
@section('title', $seo['title'])
@section('seo')
    @include('components.seo-head', ['seo' => $seo])
@endsection

@section('content')
<div class="header-spacer"></div>

<section style="padding: 80px 0;">
    <div class="container-wide">
        <h1 style="font-size: 36px; font-weight: 800; margin-bottom: 40px;">Sitemap</h1>

        {{-- Main Pages --}}
        <div style="margin-bottom: 48px;">
            <h2 style="font-size: 22px; font-weight: 700; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid var(--blue); display: inline-block;">Main Pages</h2>
            <ul style="list-style: none; columns: 2; gap: 32px;">
                <li style="margin-bottom: 8px;"><a href="{{ url('/') }}" style="color: var(--blue); text-decoration: none; font-size: 15px;">Home</a></li>
                <li style="margin-bottom: 8px;"><a href="{{ url('/about') }}" style="color: var(--blue); text-decoration: none; font-size: 15px;">About Us</a></li>
                <li style="margin-bottom: 8px;"><a href="{{ url('/features') }}" style="color: var(--blue); text-decoration: none; font-size: 15px;">Features</a></li>
                <li style="margin-bottom: 8px;"><a href="{{ url('/packages') }}" style="color: var(--blue); text-decoration: none; font-size: 15px;">Pricing</a></li>
                <li style="margin-bottom: 8px;"><a href="{{ url('/reviews') }}" style="color: var(--blue); text-decoration: none; font-size: 15px;">Reviews</a></li>
                <li style="margin-bottom: 8px;"><a href="{{ url('/templates') }}" style="color: var(--blue); text-decoration: none; font-size: 15px;">Templates</a></li>
                <li style="margin-bottom: 8px;"><a href="{{ url('/blogs') }}" style="color: var(--blue); text-decoration: none; font-size: 15px;">Blog</a></li>
                <li style="margin-bottom: 8px;"><a href="{{ url('/contact') }}" style="color: var(--blue); text-decoration: none; font-size: 15px;">Contact</a></li>
                <li style="margin-bottom: 8px;"><a href="{{ url('/poster-maker') }}" style="color: var(--blue); text-decoration: none; font-size: 15px;">Poster Maker</a></li>
                <li style="margin-bottom: 8px;"><a href="{{ url('/festival-poster') }}" style="color: var(--blue); text-decoration: none; font-size: 15px;">Festival Poster Maker</a></li>
            </ul>
        </div>

        {{-- Business Categories --}}
        <div style="margin-bottom: 48px;">
            <h2 style="font-size: 22px; font-weight: 700; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid var(--blue); display: inline-block;">Business Categories</h2>
            <div style="columns: 3; gap: 32px;">
                @foreach($categories as $cat)
                    @php $catSlug = $cat->slug ?: \Illuminate\Support\Str::slug($cat->name); @endphp
                    <div style="break-inside: avoid; margin-bottom: 16px;">
                        <a href="{{ url('/poster-maker/' . $catSlug) }}" style="color: var(--blue); text-decoration: none; font-size: 15px; font-weight: 600;">{{ $cat->name }}</a>
                        @if($cat->subCategories->count() > 0)
                            <ul style="list-style: none; margin-top: 4px; padding-left: 16px;">
                                @foreach($cat->subCategories->take(10) as $sub)
                                    <li style="margin-bottom: 4px;">
                                        <a href="{{ url('/poster-maker/' . $catSlug . '/' . ($sub->slug ?: \Illuminate\Support\Str::slug($sub->name))) }}"
                                           style="color: var(--text-gray); text-decoration: none; font-size: 13px;">{{ $sub->name }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Festivals --}}
        <div style="margin-bottom: 48px;">
            <h2 style="font-size: 22px; font-weight: 700; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid var(--blue); display: inline-block;">Festival Poster Makers</h2>
            <div style="columns: 3; gap: 32px;">
                @foreach($festivals as $fest)
                    <div style="break-inside: avoid; margin-bottom: 6px;">
                        <a href="{{ url('/festival-poster/' . \Illuminate\Support\Str::slug($fest->title)) }}"
                           style="color: var(--text-gray); text-decoration: none; font-size: 14px;">{{ $fest->title }}</a>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Blog Posts --}}
        @if($blogs->count() > 0)
        <div style="margin-bottom: 48px;">
            <h2 style="font-size: 22px; font-weight: 700; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid var(--blue); display: inline-block;">Recent Blog Posts</h2>
            <ul style="list-style: none; columns: 2; gap: 32px;">
                @foreach($blogs as $blog)
                    <li style="margin-bottom: 6px;">
                        <a href="{{ url('/blog/' . $blog->slug) }}" style="color: var(--text-gray); text-decoration: none; font-size: 14px;">{{ $blog->title ?? $blog->slug }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</section>
@endsection
