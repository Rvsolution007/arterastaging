@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-1">{{ $preset->exists ? 'Edit Header & Footer Style' : 'Add Header & Footer Style' }}</h3>
      <p class="text-muted mb-0">Define the visual direction for Festival AI's business branding zones.</p>
    </div>
    <a href="{{ route('festival_ai_brand_chrome.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left mr-1"></i> Header &amp; Footer Styles</a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
  @endif

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="POST" action="{{ $preset->exists ? route('festival_ai_brand_chrome.update', $preset) : route('festival_ai_brand_chrome.store') }}">
        @csrf
        @if($preset->exists) @method('PUT') @endif

        <div class="row">
          <div class="col-md-8 form-group">
            <label>Style name</label>
            <input class="form-control" name="name" maxlength="150" value="{{ old('name', $preset->name) }}" placeholder="Dark premium brand strip" required>
          </div>
          <div class="col-md-4 form-group">
            <label>Sort order</label>
            <input class="form-control" type="number" min="0" name="sort_order" value="{{ old('sort_order', $preset->sort_order ?? 0) }}">
          </div>
        </div>

        <div class="form-group">
          <label><strong>Header prompt</strong> <small class="text-muted">optional</small></label>
          <textarea class="form-control" rows="4" name="header_prompt" maxlength="5000" placeholder="Example: reserve a refined dark charcoal header band with a clean bright area for a business logo and name.">{{ old('header_prompt', $preset->header_prompt) }}</textarea>
          <small class="form-text text-muted">Controls the top area's colour, texture and visual style. The real business logo and name are added after AI generation.</small>
        </div>

        <div class="form-group">
          <label><strong>Footer prompt</strong> <small class="text-muted">optional</small></label>
          <textarea class="form-control" rows="4" name="footer_prompt" maxlength="5000" placeholder="Example: reserve a calm matching footer strip with enough contrast for phone, email, website and address.">{{ old('footer_prompt', $preset->footer_prompt) }}</textarea>
          <small class="form-text text-muted">Controls the lower contact area. Only fields currently enabled by the business owner are added.</small>
        </div>

        <div class="mb-4">
          <label><input type="checkbox" name="status" value="1" @checked(old('status', $preset->status))> Active and selectable in Festival AI Studio</label>
        </div>

        <button class="btn btn-primary"><i class="fa fa-save mr-1"></i> {{ $preset->exists ? 'Update Header & Footer Style' : 'Save Header & Footer Style' }}</button>
      </form>
    </div>
  </div>
</div>
@endsection
