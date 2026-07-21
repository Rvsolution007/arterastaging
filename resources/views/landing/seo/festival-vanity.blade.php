@extends('landing.layout')
@section('title', $seo['title'] ?? 'AI Festival Poster Maker')
@section('seo')
    @include('components.seo-head', ['seo' => $seo])
@endsection
@section('content')
<div class="container py-5 mt-5">
    <div class="text-center mb-5 bg-danger text-white p-5 rounded-4 shadow">
        <h1 class="display-4 fw-bold">{{ $seo['h1'] ?? $seo['title'] }}</h1>
        <p class="lead mt-3">{{ $seo['intro'] ?? $seo['description'] ?? '' }}</p>
    </div>
    
    @if(isset($seo['features']))
    <div class="row mb-5">
        @foreach($seo['features'] as $feature)
        <div class="col-md-4 text-center mb-4">
            <div class="p-4 border border-danger rounded-3 h-100">
                <i class="fas {{ $feature['icon'] ?? 'fa-star' }} fa-3x text-danger mb-3"></i>
                <h5 class="fw-bold">{{ $feature['title'] }}</h5>
                <p class="text-muted">{{ $feature['desc'] }}</p>
            </div>
        </div>
        @endforeach
    </div>
    @endif
    
    @if(isset($templates) && count($templates) > 0)
    <h3 class="text-center mb-4 text-danger fw-bold">Festive Collection</h3>
    <div class="row">
        @foreach($templates as $template)
        <div class="col-md-3 col-6 mb-4">
            <img src="{{ asset('uploads/'.$template->frame_image) }}" class="img-fluid rounded-4 shadow" alt="Festival Template">
        </div>
        @endforeach
    </div>
    @endif
    
    @if(isset($seo['faq']))
    <div class="mt-5">
        <h3 class="text-center mb-4">FAQs</h3>
        <div class="accordion" id="faqAccordion">
            @foreach($seo['faq'] as $index => $faq)
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading{{$index}}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{$index}}">
                        {{ $faq['question'] }}
                    </button>
                </h2>
                <div id="collapse{{$index}}" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">{{ $faq['answer'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
