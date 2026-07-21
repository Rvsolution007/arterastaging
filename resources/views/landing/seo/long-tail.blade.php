@extends('landing.layout')
@section('title', $seo['title'] ?? 'AI Tool')
@section('seo')
    @include('components.seo-head', ['seo' => $seo])
@endsection
@section('content')
<div class="container py-5 mt-5">
    <div class="row mb-5">
        <div class="col-lg-10 mx-auto">
            <h1 class="display-4 fw-bold mb-4">{{ $seo['h1'] ?? $seo['title'] }}</h1>
            <p class="lead text-muted">{{ $seo['intro'] ?? $seo['description'] ?? '' }}</p>
        </div>
    </div>
    
    @if(isset($seo['features']))
    <div class="row mb-5 col-lg-10 mx-auto">
        @foreach($seo['features'] as $feature)
        <div class="col-md-6 mb-4">
            <div class="d-flex align-items-start">
                <div class="bg-primary text-white p-3 rounded-circle me-3">
                    <i class="fas {{ $feature['icon'] ?? 'fa-check' }}"></i>
                </div>
                <div>
                    <h5 class="fw-bold">{{ $feature['title'] }}</h5>
                    <p class="text-muted">{{ $feature['desc'] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
    
    @if(isset($templates) && count($templates) > 0)
    <div class="col-lg-10 mx-auto">
        <h3 class="mb-4 fw-bold border-bottom pb-2">Top Templates</h3>
        <div class="row">
            @foreach($templates as $template)
            <div class="col-md-3 col-6 mb-4">
                <img src="{{ asset('uploads/'.$template->frame_image) }}" class="img-fluid rounded shadow-sm" alt="Template">
            </div>
            @endforeach
        </div>
    </div>
    @endif
    
    @if(isset($seo['faq']))
    <div class="mt-5 col-lg-10 mx-auto">
        <h3 class="mb-4 fw-bold border-bottom pb-2">Questions & Answers</h3>
        <div class="accordion" id="faqAccordion">
            @foreach($seo['faq'] as $index => $faq)
            <div class="accordion-item mb-2 border rounded">
                <h2 class="accordion-header" id="heading{{$index}}">
                    <button class="accordion-button collapsed bg-white text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{$index}}">
                        {{ $faq['question'] }}
                    </button>
                </h2>
                <div id="collapse{{$index}}" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted">{{ $faq['answer'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
