@extends('layouts.app')

@section('extra_css')
    @include('partials.modern_admin_css')
@endsection


@section('content')
<div class="modern-ui-wrapper container-fluid py-3"><div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="mb-1">{{ $style->exists ? 'Edit Header & Footer Style' : 'Add Header & Footer Style' }}</h3><p class="text-muted mb-0">Give AI and the editable overlay a consistent branding direction for this Custom Post Type.</p></div><a href="{{ route('custom_post_header_footer_styles.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left mr-1"></i> Header &amp; Footer Styles</a></div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="card shadow-sm"><div class="card-body"><form method="POST" action="{{ $style->exists ? route('custom_post_header_footer_styles.update', $style) : route('custom_post_header_footer_styles.store') }}">@csrf @if($style->exists) @method('PUT') @endif
<div class="row"><div class="col-md-8 form-group"><label>Style name</label><input class="form-control" name="name" maxlength="150" value="{{ old('name', $style->name) }}" required placeholder="Clean Business Header & Footer"></div><div class="col-md-4 form-group"><label>Order</label><input class="form-control" type="number" min="0" name="sort_order" value="{{ old('sort_order', $style->sort_order ?? 0) }}"></div></div>
<div class="form-group"><label><strong>Header prompt</strong> <small class="text-muted">optional</small></label><textarea class="form-control" rows="4" name="header_prompt" maxlength="5000" placeholder="Example: reserve a clean upper safe zone for logo and business name.">{{ old('header_prompt', $style->header_prompt) }}</textarea></div>
<div class="form-group"><label><strong>Footer prompt</strong> <small class="text-muted">optional</small></label><textarea class="form-control" rows="4" name="footer_prompt" maxlength="5000" placeholder="Example: reserve a clear lower safe zone for concise contact details and CTA.">{{ old('footer_prompt', $style->footer_prompt) }}</textarea></div>
<div class="border rounded bg-light p-3 mb-3"><label class="mb-0"><input type="checkbox" name="overlay_enabled" value="1" @checked(old('overlay_enabled', $style->overlay_enabled ?? true))> Include current business branding direction</label><small class="d-block text-muted mt-1">Logo and contact details remain editable app layers; this style controls their intended placement and visual treatment.</small></div>
<div class="mb-4"><label><input type="checkbox" name="status" value="1" @checked(old('status', $style->status))> Active and selectable in Custom Post Types</label></div><button class="btn btn-primary"><i class="fa fa-save mr-1"></i> {{ $style->exists ? 'Update Header & Footer Style' : 'Save Header & Footer Style' }}</button>
</form></div></div></div>
@endsection
