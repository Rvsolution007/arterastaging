@if(isset($internalLinks) && count($internalLinks) > 0)
<section class="internal-links-section py-5 bg-light">
    <div class="container">
        <h3 class="mb-4">Explore More Tools</h3>
        <div class="row">
            @foreach($internalLinks as $link)
            <div class="col-md-3 col-sm-6 mb-3">
                <a href="{{ $link['url'] }}" class="text-decoration-none text-dark fw-bold border-bottom pb-1">{{ $link['label'] }}</a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
