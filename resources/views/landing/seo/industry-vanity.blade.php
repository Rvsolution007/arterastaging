@extends('landing.layout')
@section('title', $seo['title'] ?? 'AI Industry Templates')
@section('seo')
    @include('components.seo-head', ['seo' => $seo])
@endsection
@section('content')
<div class="container py-5 mt-5">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bolder">{{ $seo['h1'] ?? $seo['title'] }}</h1>
        <p class="lead mt-3 text-secondary">{{ $seo['intro'] ?? $seo['description'] ?? '' }}</p>
    </div>
    
    @if(isset($seo['features']))
    <div class="row mb-5">
        @foreach($seo['features'] as $feature)
        <div class="col-md-3 col-sm-6 text-center mb-4">
            <div class="card h-100 border bg-light shadow-sm p-4">
                <i class="fas {{ $feature['icon'] ?? 'fa-briefcase' }} fa-2x text-dark mb-3"></i>
                <h6 class="fw-bold">{{ $feature['title'] }}</h6>
                <p class="text-muted small mb-0">{{ $feature['desc'] }}</p>
            </div>
        </div>
        @endforeach
    </div>
    @endif
    
    @if(isset($templates) && count($templates) > 0)
    <h3 class="text-center mb-4">Industry Templates</h3>
    <div class="row">
        @foreach($templates as $template)
        <div class="col-md-3 col-6 mb-4">
            <div class="position-relative">
                <img src="{{ asset('uploads/'.$template->frame_image) }}" class="img-fluid rounded border shadow-sm" alt="Industry Template">
            </div>
        </div>
        @endforeach
    </div>
    @endif
    
    @if(isset($seo['faq']))
    <div class="mt-5">
        <h3 class="text-center mb-4">Common Questions</h3>
        <div class="accordion accordion-flush shadow-sm border rounded" id="faqAccordion">
            @foreach($seo['faq'] as $index => $faq)
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading{{$index}}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{$index}}">
                        <strong>{{ $faq['question'] }}</strong>
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
