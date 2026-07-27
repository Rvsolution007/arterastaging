@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-1">AI Image Models</h3>
      <p class="text-muted mb-0">Control exactly which image models, qualities, and output sizes can later appear in the app.</p>
    </div>
    <a href="{{ url('admin/settings') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-cog mr-1"></i> AI credentials</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger mb-3">
      <ul class="mb-0 pl-3">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
      </ul>
    </div>
  @endif

  <div class="alert alert-info border-0 shadow-sm">
    <i class="fa fa-info-circle mr-1"></i>
    A model is not app-visible only because it is active. It must also be allowed by the user's subscription plan; that plan mapping is configured separately.
  </div>

  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white"><strong><i class="fa fa-plus-circle text-primary mr-1"></i> Add image model</strong></div>
    <div class="card-body">
      <form method="POST" action="{{ route('admin.ai_image_models.store') }}">
        @csrf
        <div class="row">
          <div class="col-md-3 form-group">
            <label>Provider</label>
            <input class="form-control" name="provider" value="{{ old('provider', 'openai') }}" required>
          </div>
          <div class="col-md-3 form-group">
            <label>Model ID</label>
            <input class="form-control" name="model_id" value="{{ old('model_id', 'gpt-image-2') }}" required>
          </div>
          <div class="col-md-4 form-group">
            <label>App display name</label>
            <input class="form-control" name="display_name" value="{{ old('display_name', 'GPT Image 2') }}" required>
          </div>
          <div class="col-md-2 form-group">
            <label>Sort order</label>
            <input class="form-control" type="number" min="0" name="sort_order" value="{{ old('sort_order', 0) }}">
          </div>
        </div>
        <div class="form-group">
          <label>App description</label>
          <input class="form-control" name="description" maxlength="1000" value="{{ old('description') }}" placeholder="Example: Best for polished festival posters and product references">
        </div>
        <div class="row">
          <div class="col-md-6 form-group">
            <label class="d-block">App model options</label>
            <small class="text-muted d-block mb-2">Each enabled provider quality becomes its own selectable app model. Set the app-facing name you want users to see.</small>
            <div class="d-flex mb-1" style="gap:6px;">
              <div style="width:32px;"></div>
              <div style="min-width:160px;"><small class="text-muted font-weight-bold">Original Name</small></div>
              <div class="flex-fill"><small class="text-muted font-weight-bold">Display Name in App</small></div>
            </div>
            @foreach($qualityOptions as $key => $label)
              <div class="input-group input-group-sm mb-2">
                <div class="input-group-prepend"><div class="input-group-text"><input type="checkbox" name="quality_options[]" value="{{ $key }}" @checked(in_array($key, old('quality_options', ['low', 'medium', 'high']), true))></div><span class="input-group-text" style="min-width:140px;font-size:12px;background:#f8f9fa;">{{ $label }}</span></div>
                <input class="form-control" name="quality_display_names[{{ $key }}]" value="{{ old('quality_display_names.' . $key, $label) }}" maxlength="80" placeholder="App display name">
              </div>
            @endforeach
            <select class="form-control mt-2" name="default_quality">
              @foreach($qualityOptions as $key => $label)<option value="{{ $key }}" @selected(old('default_quality', 'medium') === $key)>Default: {{ $label }}</option>@endforeach
            </select>
          </div>
          <div class="col-md-6 form-group">
            <label class="d-block">App output sizes</label>
            @foreach($sizeOptions as $key => $size)
              <label class="mr-3"><input type="checkbox" name="size_keys[]" value="{{ $key }}" @checked(in_array($key, old('size_keys', ['square', 'landscape', 'portrait']), true))> {{ $size['label'] }} ({{ $size['ratio'] }})</label>
            @endforeach
            <select class="form-control mt-2" name="default_size_key">
              @foreach($sizeOptions as $key => $size)<option value="{{ $key }}" @selected(old('default_size_key', 'square') === $key)>Default: {{ $size['label'] }}</option>@endforeach
            </select>
          </div>
        </div>
        <div class="row align-items-end">
          <div class="col-md-3 form-group">
            <label>Maximum reference images</label>
            <input class="form-control" type="number" min="0" max="10" name="max_reference_images" value="{{ old('max_reference_images', 3) }}" required>
          </div>
          <div class="col-md-3 form-group">
            <label>Typical seconds</label>
            <input class="form-control" type="number" min="1" max="600" name="estimated_seconds" value="{{ old('estimated_seconds', 45) }}">
          </div>
          <div class="col-md-6 form-group mb-2">
            <label class="mr-3"><input type="checkbox" name="supports_reference_images" value="1" @checked(old('supports_reference_images', true))> Supports product/reference images</label>
            <label class="mr-3"><input type="checkbox" name="supports_edits" value="1" @checked(old('supports_edits', true))> Supports refinements/edits</label>
            <label class="mr-3"><input type="checkbox" name="supports_transparent_background" value="1" @checked(old('supports_transparent_background'))> Transparent output</label>
            <label class="mr-3"><input type="checkbox" name="is_active" value="1" @checked(old('is_active'))> System enabled</label>
            <label><input type="checkbox" name="is_recommended" value="1" @checked(old('is_recommended', true))> Recommended</label>
          </div>
        </div>
        <div class="border rounded bg-light p-3 mb-3">
          <div class="d-flex align-items-center mb-2">
            <strong><i class="fa fa-chart-line text-primary mr-1"></i> Analytics pricing parameters</strong>
          </div>
          <small class="text-muted d-block mb-3">Festival AI saves the provider-returned input/output tokens when available. If the image provider does not return usage, only the prompt token count is clearly marked as an estimate. Cost is calculated from the values below; leave a rate as 0 if you do not want to estimate it.</small>
          <div class="row">
            <div class="col-md-3 form-group mb-md-0"><label class="small mb-1">Input USD / 1M tokens</label><input class="form-control form-control-sm" type="number" min="0" step="0.000001" name="pricing_config[input_per_million_usd]" value="{{ old('pricing_config.input_per_million_usd', 0) }}"></div>
            <div class="col-md-3 form-group mb-md-0"><label class="small mb-1">Output USD / 1M tokens</label><input class="form-control form-control-sm" type="number" min="0" step="0.000001" name="pricing_config[output_per_million_usd]" value="{{ old('pricing_config.output_per_million_usd', 0) }}"></div>
            <div class="col-md-3 form-group mb-md-0"><label class="small mb-1">USD / generated image</label><input class="form-control form-control-sm" type="number" min="0" step="0.000001" name="pricing_config[image_per_unit_usd]" value="{{ old('pricing_config.image_per_unit_usd', 0) }}"></div>
            <div class="col-md-3 form-group mb-0"><label class="small mb-1">USD to INR rate</label><input class="form-control form-control-sm" type="number" min="0" step="0.01" name="pricing_config[usd_to_inr]" value="{{ old('pricing_config.usd_to_inr', 90) }}"></div>
          </div>
        </div>
        <button class="btn btn-primary"><i class="fa fa-save mr-1"></i> Save model</button>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-white"><strong>Configured models</strong></div>
    <div class="card-body p-0">
      @forelse($models as $model)
        @php
          $selectedSizes = collect($model->size_options ?? [])->pluck('key')->all();
          $selectedQualities = $model->quality_options ?? [];
          $qualityDisplayNames = $model->quality_display_names ?? [];
          $pricing = $model->pricing_config ?? [];
        @endphp
        <details class="border-bottom p-3" {{ $loop->first ? 'open' : '' }}>
          <summary class="d-flex justify-content-between align-items-center" style="cursor:pointer;">
            <span><strong>{{ $model->display_name }}</strong> <code class="ml-1">{{ $model->provider }} / {{ $model->model_id }}</code></span>
            <span>
              @if($model->is_recommended)<span class="badge badge-primary">Recommended</span>@endif
              <span class="badge badge-{{ $model->is_active ? 'success' : 'secondary' }}">{{ $model->is_active ? 'Enabled' : 'Disabled' }}</span>
            </span>
          </summary>
          <form method="POST" action="{{ route('admin.ai_image_models.update', $model) }}" class="pt-3">
            @csrf @method('PUT')
            <div class="row">
              <div class="col-md-3 form-group"><label>Provider</label><input class="form-control" name="provider" value="{{ $model->provider }}" required></div>
              <div class="col-md-3 form-group"><label>Model ID</label><input class="form-control" name="model_id" value="{{ $model->model_id }}" required></div>
              <div class="col-md-4 form-group"><label>App display name</label><input class="form-control" name="display_name" value="{{ $model->display_name }}" required></div>
              <div class="col-md-2 form-group"><label>Sort order</label><input class="form-control" type="number" min="0" name="sort_order" value="{{ $model->sort_order }}"></div>
            </div>
            <div class="form-group"><label>App description</label><input class="form-control" name="description" maxlength="1000" value="{{ $model->description }}"></div>
            <div class="row">
              <div class="col-md-6 form-group">
                <label class="d-block">App model options</label>
                <small class="text-muted d-block mb-2">The name beside each enabled quality is shown as a selectable AI model in the app.</small>
                <div class="d-flex mb-1" style="gap:6px;">
                  <div style="width:32px;"></div>
                  <div style="min-width:160px;"><small class="text-muted font-weight-bold">Original Name</small></div>
                  <div class="flex-fill"><small class="text-muted font-weight-bold">Display Name in App</small></div>
                </div>
                @foreach($qualityOptions as $key => $label)
                  <div class="input-group input-group-sm mb-2">
                    <div class="input-group-prepend"><div class="input-group-text"><input type="checkbox" name="quality_options[]" value="{{ $key }}" @checked(in_array($key, $selectedQualities, true))></div><span class="input-group-text" style="min-width:140px;font-size:12px;background:#f8f9fa;">{{ $label }}</span></div>
                    <input class="form-control" name="quality_display_names[{{ $key }}]" value="{{ old('quality_display_names.' . $key, data_get($qualityDisplayNames, $key, $label)) }}" maxlength="80" placeholder="App display name">
                  </div>
                @endforeach
                <select class="form-control mt-2" name="default_quality">@foreach($qualityOptions as $key => $label)<option value="{{ $key }}" @selected($model->default_quality === $key)>Default: {{ $label }}</option>@endforeach</select>
              </div>
              <div class="col-md-6 form-group">
                <label class="d-block">Sizes</label>
                @foreach($sizeOptions as $key => $size)<label class="mr-3"><input type="checkbox" name="size_keys[]" value="{{ $key }}" @checked(in_array($key, $selectedSizes, true))> {{ $size['label'] }} ({{ $size['ratio'] }})</label>@endforeach
                <select class="form-control mt-2" name="default_size_key">@foreach($sizeOptions as $key => $size)<option value="{{ $key }}" @selected($model->default_size_key === $key)>Default: {{ $size['label'] }}</option>@endforeach</select>
              </div>
            </div>
            <div class="row align-items-end">
              <div class="col-md-3 form-group"><label>Maximum reference images</label><input class="form-control" type="number" min="0" max="10" name="max_reference_images" value="{{ $model->max_reference_images }}" required></div>
              <div class="col-md-3 form-group"><label>Typical seconds</label><input class="form-control" type="number" min="1" max="600" name="estimated_seconds" value="{{ $model->estimated_seconds }}"></div>
              <div class="col-md-6 form-group mb-2">
                <label class="mr-3"><input type="checkbox" name="supports_reference_images" value="1" @checked($model->supports_reference_images)> Reference images</label>
                <label class="mr-3"><input type="checkbox" name="supports_edits" value="1" @checked($model->supports_edits)> Refinements/edits</label>
                <label class="mr-3"><input type="checkbox" name="supports_transparent_background" value="1" @checked($model->supports_transparent_background)> Transparent output</label>
                <label class="mr-3"><input type="checkbox" name="is_active" value="1" @checked($model->is_active)> System enabled</label>
                <label><input type="checkbox" name="is_recommended" value="1" @checked($model->is_recommended)> Recommended</label>
              </div>
            </div>
            <div class="border rounded bg-light p-3 mb-3">
              <div class="d-flex align-items-center mb-2"><strong><i class="fa fa-chart-line text-primary mr-1"></i> Analytics pricing parameters</strong></div>
              <small class="text-muted d-block mb-3">Usage uses the provider response when returned; otherwise the dashboard labels the prompt-token fallback as an estimate. No prompt, product, business data, or API key is stored in analytics.</small>
              <div class="row">
                <div class="col-md-3 form-group mb-md-0"><label class="small mb-1">Input USD / 1M tokens</label><input class="form-control form-control-sm" type="number" min="0" step="0.000001" name="pricing_config[input_per_million_usd]" value="{{ old('pricing_config.input_per_million_usd', data_get($pricing, 'input_per_million_usd', 0)) }}"></div>
                <div class="col-md-3 form-group mb-md-0"><label class="small mb-1">Output USD / 1M tokens</label><input class="form-control form-control-sm" type="number" min="0" step="0.000001" name="pricing_config[output_per_million_usd]" value="{{ old('pricing_config.output_per_million_usd', data_get($pricing, 'output_per_million_usd', 0)) }}"></div>
                <div class="col-md-3 form-group mb-md-0"><label class="small mb-1">USD / generated image</label><input class="form-control form-control-sm" type="number" min="0" step="0.000001" name="pricing_config[image_per_unit_usd]" value="{{ old('pricing_config.image_per_unit_usd', data_get($pricing, 'image_per_unit_usd', 0)) }}"></div>
                <div class="col-md-3 form-group mb-0"><label class="small mb-1">USD to INR rate</label><input class="form-control form-control-sm" type="number" min="0" step="0.01" name="pricing_config[usd_to_inr]" value="{{ old('pricing_config.usd_to_inr', data_get($pricing, 'usd_to_inr', 90)) }}"></div>
              </div>
            </div>
            <button class="btn btn-outline-primary btn-sm">Update model</button>
          </form>
        </details>
      @empty
        <div class="p-4 text-muted">No image model is configured yet. Add a supported model above before enabling the app flow.</div>
      @endforelse
    </div>
  </div>
</div>
@endsection
