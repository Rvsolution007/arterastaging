@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-1">{{ $preset->exists ? 'Edit Festival Style' : 'Add Festival Style' }}</h3>
      <p class="text-muted mb-0">This is the reusable design prompt. It can be selected from any festival's AI Studio.</p>
    </div>
    <a href="{{ route('festival_ai_styles.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left mr-1"></i> Festival Styles</a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
  @endif

  @php($selectedSizes = old('allowed_size_keys', $preset->allowed_size_keys ?? []))
  <div class="card shadow-sm">
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data" action="{{ $preset->exists ? route('festival_ai_styles.update', $preset) : route('festival_ai_styles.store') }}">
        @csrf
        @if($preset->exists) @method('PUT') @endif

        <div class="row">
          <div class="col-md-8 form-group">
            <label>Style name</label>
            <input class="form-control" name="name" maxlength="150" value="{{ old('name', $preset->name) }}" placeholder="Traditional gold" required>
          </div>
          <div class="col-md-4 form-group">
            <label>Sort order</label>
            <input class="form-control" type="number" min="0" name="sort_order" value="{{ old('sort_order', $preset->sort_order ?? 0) }}">
          </div>
        </div>

        <div class="form-group">
          <label><strong>Festival Style Prompt</strong></label>
          <textarea class="form-control" rows="6" name="prompt_text" maxlength="10000" placeholder="Describe the colour palette, composition, typography, illustration/design theme and overall visual direction." required>{{ old('prompt_text', $preset->prompt_text) }}</textarea>
          <small class="form-text text-muted">Example: dark emerald-green background, warm golden halo, premium spiritual style, elegant serif typography and a centered composition.</small>
        </div>

        <div class="form-group">
          <label>{{ $preset->exists ? 'Add preview images' : 'Style preview images' }} <small class="text-muted">1–3 JPG, PNG or WebP; max 5 MB each</small></label>
          <input class="form-control-file" type="file" name="preview_images[]" accept="image/jpeg,image/png,image/webp" multiple {{ $preset->exists ? '' : 'required' }}>
        </div>

        @if(!empty($preset->preview_urls))
          <div class="mb-3 d-flex flex-wrap">
            @foreach($preset->preview_urls as $index => $url)
              <label class="mr-3 text-center"><img src="{{ $url }}" alt="{{ $preset->name }} preview" class="img-thumbnail d-block mb-1" style="width:150px;height:100px;object-fit:cover;"><input type="checkbox" name="remove_preview_images[]" value="{{ $preset->preview_images[$index] }}"> Remove</label>
            @endforeach
          </div>
        @endif

        <div class="mb-3">
          <label class="d-block">Allowed output sizes <small class="text-muted">Leave blank to inherit the selected festival's sizes.</small></label>
          @foreach($sizeOptions as $key => $label)
            <label class="mr-3"><input type="checkbox" name="allowed_size_keys[]" value="{{ $key }}" @checked(in_array($key, $selectedSizes, true))> {{ $label }}</label>
          @endforeach
        </div>

        <div class="mb-4">
          <label class="mr-4"><input type="checkbox" name="product_required" value="1" @checked(old('product_required', $preset->product_required))> Product is required for this style</label>
          <label><input type="checkbox" name="status" value="1" @checked(old('status', $preset->status))> Active and visible in festivals</label>
        </div>

        <button class="btn btn-primary"><i class="fa fa-save mr-1"></i> {{ $preset->exists ? 'Update Festival Style' : 'Save Festival Style' }}</button>
      </form>
    </div>
  </div>
</div>
@endsection
