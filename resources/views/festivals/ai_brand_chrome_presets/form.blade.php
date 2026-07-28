@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-1">{{ $preset->exists ? 'Edit AI Business Branding' : 'Add AI Business Branding' }}</h3>
      <p class="text-muted mb-0">Give Artera AI aesthetic hints for integrating active-business branding into the generated poster.</p>
    </div>
    <a href="{{ route('festival_ai_brand_chrome.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left mr-1"></i> Business Branding</a>
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
          <textarea class="form-control" rows="4" name="header_prompt" maxlength="5000" placeholder="Example: integrate the supplied logo naturally with the festival artwork.">{{ old('header_prompt', $preset->header_prompt) }}</textarea>
          <small class="form-text text-muted">Artera AI uses this as an aesthetic hint. It decides the final placement from the artwork, logo and business data; do not type sample names or contact details here.</small>
        </div>

        <div class="form-group">
          <label><strong>Footer prompt</strong> <small class="text-muted">optional</small></label>
          <textarea class="form-control" rows="4" name="footer_prompt" maxlength="5000" placeholder="Example: blend useful contact details naturally into the festival artwork.">{{ old('footer_prompt', $preset->footer_prompt) }}</textarea>
          <small class="form-text text-muted">Artera AI receives all current visible contact fields from My Business and chooses a readable subset for the artwork.</small>
        </div>

        <div class="border rounded bg-light p-3 mb-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <strong>AI business branding</strong>
              <div class="small text-muted">The active business logo, name and visible contact details are included in the same AI request. AI chooses the layout; nothing is overlaid after generation.</div>
            </div>
            <label class="mb-0"><input type="checkbox" name="overlay_enabled" value="1" @checked(old('overlay_enabled', $preset->overlay_enabled ?? true))> Include business branding</label>
          </div>
        </div>

        <div class="mb-4">
          <label><input type="checkbox" name="status" value="1" @checked(old('status', $preset->status))> Active and selectable in Festival AI Studio</label>
        </div>

        <button class="btn btn-primary"><i class="fa fa-save mr-1"></i> {{ $preset->exists ? 'Update Business Branding' : 'Save Business Branding' }}</button>
      </form>
    </div>
  </div>
</div>
@endsection
