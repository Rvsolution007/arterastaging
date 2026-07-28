@extends('layouts.app')

@section('extra_css')
    @include('partials.modern_admin_css')
@endsection


@section('content')
<div class="modern-ui-wrapper container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="mb-1">{{ $style->exists ? 'Edit Custom Post Style' : 'Add Custom Post Style' }}</h3><p class="text-muted mb-0">Create the visual option that users select after filling their Type-specific Brief.</p></div><a href="{{ route('custom_post_styles.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left mr-1"></i> Custom Post Styles</a></div>
  @if($errors->any())<div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
  @php($colors = $style->colors ?? ['#4338CA', '#0F172A'])
  <div class="card shadow-sm"><div class="card-body"><form method="POST" enctype="multipart/form-data" action="{{ $style->exists ? route('custom_post_styles.update', $style) : route('custom_post_styles.store') }}">@csrf @if($style->exists) @method('PUT') @endif
    <div class="row"><div class="col-md-5 form-group"><label>Style name</label><input class="form-control" name="name" maxlength="150" required value="{{ old('name', $style->name) }}" placeholder="Modern Corporate"></div><div class="col-md-5 form-group"><label>Short app description</label><input class="form-control" name="description" maxlength="300" value="{{ old('description', $style->description) }}" placeholder="Clean, premium and confident"></div><div class="col-md-2 form-group"><label>Order</label><input class="form-control" type="number" min="0" name="sort_order" value="{{ old('sort_order', $style->sort_order ?? 0) }}"></div></div>
    <div class="form-group"><label><strong>Custom Post Style Prompt</strong></label><textarea class="form-control" rows="7" name="prompt_text" maxlength="10000" required placeholder="Describe the palette, composition, lighting, visual hierarchy, mood and safe-space preference.">{{ old('prompt_text', $style->prompt_text) }}</textarea><small class="form-text text-muted">Do not tell AI to render business text, contact numbers, icons or logo. The app adds those as editable layers.</small></div>
    <div class="row"><div class="col-md-3 form-group"><label>Primary colour</label><input class="form-control" type="color" name="primary_color" value="{{ old('primary_color', $colors[0] ?? '#4338CA') }}"></div><div class="col-md-3 form-group"><label>Secondary colour</label><input class="form-control" type="color" name="secondary_color" value="{{ old('secondary_color', $colors[1] ?? '#0F172A') }}"></div><div class="col-md-6 form-group"><label>Optional preview image <small class="text-muted">JPG, PNG or WebP; max 5 MB</small></label><input class="form-control-file" type="file" name="preview_image" accept="image/jpeg,image/png,image/webp">@if(!empty($style->preview_image_url))<img class="img-thumbnail d-block mt-2" src="{{ $style->preview_image_url }}" alt="{{ $style->name }} preview" style="width:160px;height:100px;object-fit:cover">@endif</div></div>
    <div class="mb-4"><label><input type="checkbox" name="status" value="1" @checked(old('status', $style->status))> Active and available for Custom Post Types</label></div><button class="btn btn-primary"><i class="fa fa-save mr-1"></i> {{ $style->exists ? 'Update Custom Post Style' : 'Save Custom Post Style' }}</button>
  </form></div></div>
</div>
@endsection
