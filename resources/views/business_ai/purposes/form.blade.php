@extends('layouts.app')

@section('extra_css')
    @include('partials.modern_admin_css')
@endsection


@section('content')
<div class="modern-ui-wrapper container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div><h3 class="mb-1">{{ $purpose->exists ? 'Custom Post Type Studio: ' . $purpose->title : 'Add Custom Post Type' }}</h3><p class="text-muted mb-0">Configure the first-screen card, its AI rules, linked styles and user brief.</p></div>
    <a href="{{ route('custom_post_types.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left mr-1"></i> Custom Post Types</a>
  </div>
  @if($errors->any())<div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

  @php($fields = old('brief_fields', $purpose->brief_fields ?? [['key' => '', 'label' => '', 'hint' => '', 'required' => false]]))
  @php($selectedSizes = old('allowed_size_keys', $purpose->allowed_size_keys ?? []))
  @php($selectedStyles = old('style_ids', $selectedStyleIds))
  <form method="POST" action="{{ $purpose->exists ? route('custom_post_types.update', $purpose) : route('custom_post_types.store') }}">
    @csrf @if($purpose->exists) @method('PUT') @endif

    <div class="card shadow-sm mb-3"><div class="card-header bg-white"><strong>1. First-screen Custom Post Type card</strong></div><div class="card-body">
      <div class="row">
        <div class="col-md-5 form-group"><label>Custom Post Type name</label><input class="form-control" name="title" maxlength="150" required value="{{ old('title', $purpose->title) }}" placeholder="Hiring"><small class="form-text text-muted">Shown on the first app screen.</small></div>
        <div class="col-md-4 form-group"><label>Short app description</label><input class="form-control" name="description" maxlength="300" value="{{ old('description', $purpose->description) }}" placeholder="Job posts and recruitment"></div>
        <div class="col-md-2 form-group"><label>Icon key</label><input class="form-control" name="icon" maxlength="100" value="{{ old('icon', $purpose->icon) }}" placeholder="work"><small class="form-text text-muted">work, local_offer, inventory, campaign</small></div>
        <div class="col-md-1 form-group"><label>Order</label><input class="form-control" type="number" min="0" name="sort_order" value="{{ old('sort_order', $purpose->sort_order ?? 0) }}"></div>
      </div>
    </div></div>

    <div class="card shadow-sm mb-3"><div class="card-header bg-white"><strong>2. Custom Post Type Prompt</strong></div><div class="card-body">
      <textarea class="form-control" rows="6" name="base_prompt" maxlength="10000" required placeholder="Describe the fixed purpose, visual goals, audience and non-negotiable business direction for this Type.">{{ old('base_prompt', $purpose->base_prompt) }}</textarea>
      <small class="form-text text-muted">This Type Prompt is used first. The selected Custom Post Style Prompt, user brief, products and business details are added afterwards.</small>
      <div class="mt-3"><label><strong>Universal product / service rule</strong> <small class="text-muted">optional</small></label><textarea class="form-control" rows="3" name="product_prompt" maxlength="3000" placeholder="Rules that must apply whenever this Type uses a product or service, such as preserving product label and keeping it clearly visible.">{{ old('product_prompt', $purpose->product_prompt) }}</textarea><small class="form-text text-muted">Like Festival AI: add only rules common to every linked style. Exact visual look belongs in the Custom Post Style Prompt.</small></div>
    </div></div>

    <div class="card shadow-sm mb-3"><div class="card-header bg-white d-flex justify-content-between"><strong>3. Header/Footer and Custom Post Styles</strong><span><a href="{{ route('custom_post_header_footer_styles.index') }}" class="btn btn-outline-primary btn-sm mr-1">Manage Header &amp; Footer</a><a href="{{ route('custom_post_styles.index') }}" class="btn btn-outline-primary btn-sm">Manage Styles</a></span></div><div class="card-body"><div class="row">
      <div class="col-md-5 form-group"><label><strong>Header &amp; Footer Style</strong></label><select class="form-control" name="business_ai_header_footer_style_id"><option value="">Select Header &amp; Footer Style</option>@foreach($headerFooterStyles as $style)<option value="{{ $style->id }}" @selected((int) old('business_ai_header_footer_style_id', $purpose->business_ai_header_footer_style_id) === $style->id)>{{ $style->name }}</option>@endforeach</select><small class="form-text text-muted">Admin-selected. The user does not need to choose this in the app.</small></div>
      <div class="col-md-7 form-group"><label><strong>Custom Post Styles</strong> <small class="text-danger">select the choices user should see after Brief</small></label><select class="form-control" name="style_ids[]" multiple size="6">@forelse($styleLibrary as $style)<option value="{{ $style->id }}" @selected(in_array($style->id, array_map('intval', $selectedStyles), true))>{{ $style->name }}{{ $style->description ? ' — ' . $style->description : '' }}</option>@empty<option disabled>No active Custom Post Style exists. Add one first.</option>@endforelse</select><small class="form-text text-muted">Use Ctrl / Cmd to select multiple styles. Only these linked styles appear for this Type in the app.</small></div>
    </div></div></div>

    <div class="card shadow-sm mb-3"><div class="card-header bg-white"><strong>4. Generation rules</strong></div><div class="card-body"><div class="row">
      <div class="col-md-5 form-group"><label class="d-block"><strong>Allowed output sizes</strong></label>@foreach($sizeOptions as $key => $label)<label class="mr-3"><input type="checkbox" name="allowed_size_keys[]" value="{{ $key }}" @checked(in_array($key, $selectedSizes, true))> {{ $label }}</label>@endforeach</div>
      <div class="col-md-3 form-group"><label>Maximum product photos</label><select class="form-control" name="max_product_references">@for($count = 1; $count <= 4; $count++)<option value="{{ $count }}" @selected((int) old('max_product_references', $purpose->max_product_references ?? 4) === $count)>{{ $count }} photo{{ $count > 1 ? 's' : '' }}</option>@endfor</select></div>
      <div class="col-md-4 form-group"><label>Change instruction limit</label><input class="form-control" type="number" min="50" max="1000" name="change_instruction_limit" value="{{ old('change_instruction_limit', $purpose->change_instruction_limit ?? 300) }}"><small class="form-text text-muted">For visual instruction and Generate New Version.</small></div>
    </div><label class="mr-4"><input type="checkbox" name="product_upload_enabled" value="1" @checked(old('product_upload_enabled', $purpose->product_upload_enabled))> Allow product photo upload</label><label><input type="checkbox" name="product_required" value="1" @checked(old('product_required', $purpose->product_required))> Product photo is required to generate</label></div></div>

    <div class="card shadow-sm mb-3"><div class="card-header bg-white d-flex justify-content-between align-items-center"><div><strong>5. Post Brief fields</strong><br><small class="text-muted">Input fields shown after the user selects this Custom Post Type.</small></div><button type="button" id="add-brief-field" class="btn btn-outline-primary btn-sm"><i class="fa fa-plus mr-1"></i> Add field</button></div><div class="card-body"><div id="brief-fields">
      @foreach($fields as $index => $field)<div class="border rounded p-3 mb-2 brief-field-row"><div class="row align-items-end"><div class="col-md-3 form-group mb-md-0"><label>Field key</label><input class="form-control" name="brief_fields[{{ $index }}][key]" value="{{ data_get($field, 'key') }}" maxlength="50" required placeholder="job_role"></div><div class="col-md-3 form-group mb-md-0"><label>Label in app</label><input class="form-control" name="brief_fields[{{ $index }}][label]" value="{{ data_get($field, 'label') }}" maxlength="150" required placeholder="Job role"></div><div class="col-md-3 form-group mb-md-0"><label>Example / hint</label><input class="form-control" name="brief_fields[{{ $index }}][hint]" value="{{ data_get($field, 'hint') }}" maxlength="200" placeholder="Sales Executive"></div><div class="col-md-2 form-group mb-md-0"><label class="d-block">Required</label><input type="hidden" name="brief_fields[{{ $index }}][required]" value="0"><label class="mb-0"><input type="checkbox" name="brief_fields[{{ $index }}][required]" value="1" @checked(data_get($field, 'required'))> Yes</label></div><div class="col-md-1 text-md-right"><button type="button" class="btn btn-outline-danger btn-sm remove-brief-field"><i class="fa fa-times"></i></button></div></div></div>@endforeach
    </div></div></div>

    <div class="mb-4"><label><input type="checkbox" name="status" value="1" @checked(old('status', $purpose->status))> Active and visible as a card in the app</label></div>
    <button class="btn btn-primary"><i class="fa fa-save mr-1"></i> {{ $purpose->exists ? 'Update Custom Post Type' : 'Save Custom Post Type' }}</button>
  </form>
</div>
<script>
  (function () { const holder = document.getElementById('brief-fields'); let nextIndex = {{ count($fields) }}; document.getElementById('add-brief-field').addEventListener('click', function () { const index = nextIndex++; const row = document.createElement('div'); row.className = 'border rounded p-3 mb-2 brief-field-row'; row.innerHTML = `<div class="row align-items-end"><div class="col-md-3 form-group mb-md-0"><label>Field key</label><input class="form-control" name="brief_fields[${index}][key]" maxlength="50" required placeholder="job_role"></div><div class="col-md-3 form-group mb-md-0"><label>Label in app</label><input class="form-control" name="brief_fields[${index}][label]" maxlength="150" required placeholder="Job role"></div><div class="col-md-3 form-group mb-md-0"><label>Example / hint</label><input class="form-control" name="brief_fields[${index}][hint]" maxlength="200" placeholder="Sales Executive"></div><div class="col-md-2 form-group mb-md-0"><label class="d-block">Required</label><input type="hidden" name="brief_fields[${index}][required]" value="0"><label class="mb-0"><input type="checkbox" name="brief_fields[${index}][required]" value="1"> Yes</label></div><div class="col-md-1 text-md-right"><button type="button" class="btn btn-outline-danger btn-sm remove-brief-field"><i class="fa fa-times"></i></button></div></div>`; holder.appendChild(row); }); holder.addEventListener('click', function (event) { const button = event.target.closest('.remove-brief-field'); if (button && holder.querySelectorAll('.brief-field-row').length > 1) button.closest('.brief-field-row').remove(); }); })();
</script>
@endsection
