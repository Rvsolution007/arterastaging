{{--
    SEO Head Component — Include in <head> of every public page
    
    Usage: @include('components.seo-head', ['seo' => $seo])
    
    $seo array keys:
    - title (required)
    - description (required)
    - canonical (required) 
    - og_image (optional)
    - og_type (optional, default: website)
    - keywords (optional)
    - robots (optional, default: index,follow)
    - breadcrumbs (optional, array of ['name', 'url'])
    - schema (optional, additional JSON-LD array)
    - article (optional, for BlogPosting schema)
    - faq (optional, array of ['question', 'answer'])
--}}

{{-- Title --}}
<title>{{ $seo['title'] ?? config('seo.default_title') }}</title>

{{-- Primary Meta Tags --}}
<meta name="description" content="{{ $seo['description'] ?? config('seo.default_description') }}">
@if(!empty($seo['keywords']))
<meta name="keywords" content="{{ $seo['keywords'] }}">
@else
<meta name="keywords" content="{{ config('seo.default_keywords') }}">
@endif
<meta name="robots" content="{{ $seo['robots'] ?? 'index, follow' }}">
<meta name="author" content="Artera">
<meta name="publisher" content="Artera Pixel">

{{-- Canonical URL --}}
<link rel="canonical" href="{{ $seo['canonical'] ?? url()->current() }}">

{{-- OpenGraph Tags --}}
<meta property="og:type" content="{{ $seo['og_type'] ?? 'website' }}">
<meta property="og:title" content="{{ $seo['title'] ?? config('seo.default_title') }}">
<meta property="og:description" content="{{ $seo['description'] ?? config('seo.default_description') }}">
<meta property="og:image" content="{{ !empty($seo['og_image']) ? $seo['og_image'] : asset(config('seo.default_og_image')) }}">
<meta property="og:url" content="{{ $seo['canonical'] ?? url()->current() }}">
<meta property="og:site_name" content="Artera">
<meta property="og:locale" content="en_IN">
@if(!empty($seo['og_image']))
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $seo['title'] ?? 'Artera' }}">
@endif

{{-- Twitter Card Tags --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo['title'] ?? config('seo.default_title') }}">
<meta name="twitter:description" content="{{ $seo['description'] ?? config('seo.default_description') }}">
<meta name="twitter:image" content="{{ !empty($seo['og_image']) ? $seo['og_image'] : asset(config('seo.default_og_image')) }}">
<meta name="twitter:site" content="@arterapixel">

{{-- Hreflang (Multi-language) --}}
<link rel="alternate" hreflang="en" href="{{ $seo['canonical'] ?? url()->current() }}">
<link rel="alternate" hreflang="x-default" href="{{ $seo['canonical'] ?? url()->current() }}">

{{-- App Links --}}
@if(config('seo.app_links.android'))
<meta name="google-play-app" content="app-id={{ str_replace('https://play.google.com/store/apps/details?id=', '', config('seo.app_links.android')) }}">
@endif

{{-- Google Site Verification --}}
@if(config('seo.google_site_verification'))
<meta name="google-site-verification" content="{{ config('seo.google_site_verification') }}">
@endif

{{-- Bing Site Verification --}}
@if(config('seo.bing_site_verification'))
<meta name="msvalidate.01" content="{{ config('seo.bing_site_verification') }}">
@endif

{{-- ======================== --}}
{{-- JSON-LD Structured Data   --}}
{{-- ======================== --}}

{{-- Organization Schema (Global) --}}
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "{{ config('seo.organization.name') }}",
    "legalName": "{{ config('seo.organization.legal_name') }}",
    "url": "{{ config('seo.organization.url') }}",
    "logo": "{{ asset(config('seo.organization.logo')) }}",
    "description": "{{ config('seo.organization.description') }}",
    "email": "{{ config('seo.organization.email') }}",
    "foundingDate": "{{ config('seo.organization.founding_date') }}",
    @if(config('seo.organization.founder.name'))
    "founder": {
        "@type": "Person",
        "name": "{{ config('seo.organization.founder.name') }}",
        "jobTitle": "{{ config('seo.organization.founder.title') }}"
        @if(config('seo.organization.founder.url'))
        ,"url": "{{ config('seo.organization.founder.url') }}"
        @endif
    },
    @endif
    "address": {
        "@type": "PostalAddress",
        "streetAddress": "{{ config('seo.organization.address.street') }}",
        "addressLocality": "{{ config('seo.organization.address.city') }}",
        "addressRegion": "{{ config('seo.organization.address.state') }}",
        "postalCode": "{{ config('seo.organization.address.zip') }}",
        "addressCountry": "{{ config('seo.organization.address.country') }}"
    },
    "sameAs": [
        @php
            $profiles = array_filter(config('seo.social_profiles', []));
        @endphp
        @foreach($profiles as $key => $url)
            "{{ $url }}"@if(!$loop->last),@endif
        @endforeach
    ]
}
</script>

{{-- WebSite + SearchAction Schema (Global) --}}
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebSite",
    "name": "{{ config('seo.site_name') }}",
    "url": "{{ config('seo.site_url') }}",
    "potentialAction": {
        "@type": "SearchAction",
        "target": {
            "@type": "EntryPoint",
            "urlTemplate": "{{ config('seo.site_url') }}/templates?q={search_term_string}"
        },
        "query-input": "required name=search_term_string"
    }
}
</script>

{{-- BreadcrumbList Schema --}}
@if(!empty($seo['breadcrumbs']))
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        @foreach($seo['breadcrumbs'] as $index => $crumb)
        {
            "@type": "ListItem",
            "position": {{ $index + 1 }},
            "name": "{{ $crumb['name'] }}"
            @if(!empty($crumb['url']))
            ,"item": "{{ url($crumb['url']) }}"
            @endif
        }@if(!$loop->last),@endif
        @endforeach
    ]
}
</script>
@endif

{{-- SoftwareApplication Schema (Homepage only) --}}
@if(isset($seo['show_app_schema']) && $seo['show_app_schema'])
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "SoftwareApplication",
    "name": "{{ config('seo.app.name') }}",
    "operatingSystem": "{{ config('seo.app.operating_system') }}",
    "applicationCategory": "{{ config('seo.app.category') }}",
    "offers": {
        "@type": "Offer",
        "price": "{{ config('seo.app.price') }}",
        "priceCurrency": "{{ config('seo.app.currency') }}"
    },
    "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "{{ config('seo.app.rating_value') }}",
        "ratingCount": "{{ config('seo.app.rating_count') }}"
    },
    "downloadUrl": "{{ config('seo.app_links.android') }}",
    "screenshot": "{{ asset(config('seo.default_og_image')) }}"
}
</script>
@endif

{{-- FAQPage Schema --}}
@if(!empty($seo['faq']))
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        @foreach($seo['faq'] as $item)
        {
            "@type": "Question",
            "name": "{{ addslashes($item['question']) }}",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "{{ addslashes($item['answer']) }}"
            }
        }@if(!$loop->last),@endif
        @endforeach
    ]
}
</script>
@endif

{{-- BlogPosting Schema --}}
@if(!empty($seo['article']))
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BlogPosting",
    "headline": "{{ addslashes($seo['article']['title'] ?? $seo['title']) }}",
    "description": "{{ addslashes($seo['description']) }}",
    "image": "{{ $seo['og_image'] ?? asset(config('seo.default_og_image')) }}",
    "datePublished": "{{ $seo['article']['published_at'] ?? '' }}",
    "dateModified": "{{ $seo['article']['updated_at'] ?? $seo['article']['published_at'] ?? '' }}",
    "author": {
        "@type": "Person",
        "name": "{{ $seo['article']['author'] ?? config('seo.organization.founder.name') }}"
    },
    "publisher": {
        "@type": "Organization",
        "name": "{{ config('seo.organization.name') }}",
        "logo": {
            "@type": "ImageObject",
            "url": "{{ asset(config('seo.organization.logo')) }}"
        }
    },
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "{{ $seo['canonical'] ?? url()->current() }}"
    }
    @if(!empty($seo['article']['read_time']))
    ,"timeRequired": "PT{{ $seo['article']['read_time'] }}M"
    @endif
    @if(!empty($seo['article']['word_count']))
    ,"wordCount": {{ $seo['article']['word_count'] }}
    @endif
}
</script>
@endif

{{-- CollectionPage Schema (for category/gallery pages) --}}
@if(!empty($seo['collection']))
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "CollectionPage",
    "name": "{{ addslashes($seo['title']) }}",
    "description": "{{ addslashes($seo['description']) }}",
    "url": "{{ $seo['canonical'] ?? url()->current() }}",
    "numberOfItems": {{ $seo['collection']['count'] ?? 0 }},
    "isPartOf": {
        "@type": "WebSite",
        "name": "Artera",
        "url": "{{ config('seo.site_url') }}"
    }
}
</script>
@endif

{{-- Speakable Schema --}}
@if(!empty($seo['speakable']))
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebPage",
    "name": "{{ addslashes($seo['title']) }}",
    "speakable": {
        "@type": "SpeakableSpecification",
        "cssSelector": [".speakable-intro", ".speakable-features", ".speakable-faq"]
    }
}
</script>
@endif

{{-- HowTo Schema --}}
@if(!empty($seo['howto']))
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "HowTo",
    "name": "{{ addslashes($seo['howto']['name']) }}",
    "description": "{{ addslashes($seo['howto']['description'] ?? '') }}",
    "step": [
        @foreach($seo['howto']['steps'] as $step)
        {
            "@type": "HowToStep",
            "position": {{ $loop->iteration }},
            "name": "{{ addslashes($step['name']) }}",
            "text": "{{ addslashes($step['text']) }}"
            @if(!empty($step['image']))
            ,"image": "{{ $step['image'] }}"
            @endif
        }@if(!$loop->last),@endif
        @endforeach
    ]
    @if(!empty($seo['howto']['total_time']))
    ,"totalTime": "{{ $seo['howto']['total_time'] }}"
    @endif
}
</script>
@endif

{{-- Additional Custom Schema --}}
@if(!empty($seo['custom_schema']))
<script type="application/ld+json">
{!! json_encode($seo['custom_schema'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif

