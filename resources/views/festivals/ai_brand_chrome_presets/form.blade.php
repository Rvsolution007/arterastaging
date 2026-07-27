@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-1">{{ $preset->exists ? 'Edit Header & Footer Style' : 'Add Header & Footer Style' }}</h3>
      <p class="text-muted mb-0">Define how Artera AI should build the business header and footer into the generated poster.</p>
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
          <textarea class="form-control" rows="4" name="header_prompt" maxlength="5000" placeholder="Example: a clean dark-gold header with room for the supplied logo and business name.">{{ old('header_prompt', $preset->header_prompt) }}</textarea>
          <small class="form-text text-muted">Artera AI builds this inside the poster. The current visible logo and business name are supplied automatically; do not type sample names or contact details here.</small>
        </div>

        <div class="form-group">
          <label><strong>Footer prompt</strong> <small class="text-muted">optional</small></label>
          <textarea class="form-control" rows="4" name="footer_prompt" maxlength="5000" placeholder="Example: a minimal high-contrast footer with a single readable contact line.">{{ old('footer_prompt', $preset->footer_prompt) }}</textarea>
          <small class="form-text text-muted">Artera AI renders only the current business contact fields that are enabled and not hidden in My Business.</small>
        </div>

        <div class="border rounded bg-light p-3 mb-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <strong>AI business header &amp; footer</strong>
              <div class="small text-muted">Controls the logo, name and contact instructions included in the same AI image request. Nothing is overlaid after generation.</div>
            </div>
            <label class="mb-0"><input type="checkbox" name="overlay_enabled" value="1" @checked(old('overlay_enabled', $preset->overlay_enabled ?? true))> Include business header &amp; footer</label>
          </div>
          <div class="row">
            <div class="col-md-2 form-group mb-md-0">
              <label class="small font-weight-bold">Header height</label>
              <div class="input-group input-group-sm">
                <input class="form-control" type="number" min="6" max="20" name="header_height_percent" value="{{ old('header_height_percent', $preset->header_height_percent ?? 12) }}" required>
                <div class="input-group-append"><span class="input-group-text">%</span></div>
              </div>
            </div>
            <div class="col-md-2 form-group mb-md-0">
              <label class="small font-weight-bold">Footer height</label>
              <div class="input-group input-group-sm">
                <input class="form-control" type="number" min="6" max="20" name="footer_height_percent" value="{{ old('footer_height_percent', $preset->footer_height_percent ?? 10) }}" required>
                <div class="input-group-append"><span class="input-group-text">%</span></div>
              </div>
            </div>
            <div class="col-md-2 form-group mb-md-0">
              <label class="small font-weight-bold">Panel style</label>
              <select class="form-control form-control-sm" name="panel_style" required>
                @foreach(['adaptive' => 'Adaptive contrast', 'light' => 'Light glass', 'dark' => 'Dark glass', 'none' => 'No panel'] as $value => $label)
                  <option value="{{ $value }}" @selected(old('panel_style', $preset->panel_style ?? 'adaptive') === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2 form-group mb-md-0">
              <label class="small font-weight-bold">Logo position</label>
              <select class="form-control form-control-sm" name="logo_position" required>
                <option value="left" @selected(old('logo_position', $preset->logo_position ?? 'left') === 'left')>Left</option>
                <option value="right" @selected(old('logo_position', $preset->logo_position ?? 'left') === 'right')>Right</option>
              </select>
            </div>
            <div class="col-md-2 form-group mb-md-0">
              <label class="small font-weight-bold">Text tone</label>
              <select class="form-control form-control-sm" name="text_tone" required>
                @foreach(['auto' => 'Auto contrast', 'light' => 'Light text', 'dark' => 'Dark text'] as $value => $label)
                  <option value="{{ $value }}" @selected(old('text_tone', $preset->text_tone ?? 'auto') === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2 form-group mb-0">
              <label class="small font-weight-bold">Max contacts</label>
              <input class="form-control form-control-sm" type="number" min="1" max="8" name="max_contact_items" value="{{ old('max_contact_items', $preset->max_contact_items ?? 4) }}" required>
            </div>
          </div>
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
