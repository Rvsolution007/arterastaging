@extends('layouts.app')

@section('extra_css')
    @include('partials.modern_admin_css')
@endsection

@section('content')
@php($generalData = old('general_data', $scope->general_data ?? ['']))
@php($briefFields = old('brief_fields', $scope->brief_fields ?? [['key' => '', 'label' => '', 'hint' => '', 'required' => false]]))
@php($selectedCategoryId = old('business_category_id', $scope->business_category_id))
@php($selectedSubCategoryId = old('business_sub_category_id', $scope->business_sub_category_id))
@php($chosenStyleIds = array_map('intval', old('style_ids', $selectedStyleIds)))

<div class="modern-ui-wrapper container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-1">{{ $scope->exists ? 'Edit' : 'Add' }} Category General Data</h3>
      <p class="text-muted mb-0">{{ $businessAiPurpose->title }} — create separate approved content for Eye Clinic, Skin Care Clinic, or any other subcategory.</p>
    </div>
    <a href="{{ route('custom_post_types.scopes.index', $businessAiPurpose) }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left mr-1"></i> Category Data List</a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
  @endif

  <form method="POST" action="{{ $scope->exists ? route('custom_post_types.scopes.update', [$businessAiPurpose, $scope]) : route('custom_post_types.scopes.store', $businessAiPurpose) }}">
    @csrf @if($scope->exists) @method('PUT') @endif

    <div class="card shadow-sm mb-3">
      <div class="card-header bg-white"><strong>1. Connect this data to a business category</strong></div>
      <div class="card-body">
        <div class="alert alert-light border mb-3">
          Save <strong>one row per subcategory</strong> when the General Data differs. For example, create one row for <strong>Healthcare → Eye Clinic</strong> and a second row for <strong>Healthcare → Skin Care Clinic</strong>.
        </div>
        <div class="row">
          <div class="col-md-6 form-group">
            <label><strong>Business category</strong></label>
            <select id="business_category_id" class="form-control" name="business_category_id" required>
              <option value="">Select business category</option>
              @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((int) $selectedCategoryId === $category->id)>{{ $category->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6 form-group">
            <label><strong>Business subcategory</strong> <small class="text-muted">optional</small></label>
            <select id="business_sub_category_id" class="form-control" name="business_sub_category_id">
              <option value="">All subcategories in this category</option>
              @foreach($subcategories as $subcategory)
                <option value="{{ $subcategory->id }}" data-category-id="{{ $subcategory->business_category_id }}" @selected((int) $selectedSubCategoryId === $subcategory->id)>{{ $subcategory->name }}</option>
              @endforeach
            </select>
            <small class="form-text text-muted">Choose a subcategory to keep its content separate. Leave it empty only when the same data applies to the complete category.</small>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm mb-3">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
          <strong>2. General Data</strong>
          <small class="text-muted d-block">Reusable points that Admin has already checked and approved.</small>
        </div>
        <button type="button" id="add-general-data" class="btn btn-outline-primary btn-sm"><i class="fa fa-plus mr-1"></i> Add point</button>
      </div>
      <div class="card-body">
        <div class="alert alert-info border-0">
          Paste only clean, general points from your external Smart AI analysis. Do not paste competitor name, logo, phone number, price, temporary offer, patient details, or guaranteed-result claims.
        </div>
        <div id="general-data-points">
          @foreach($generalData as $index => $point)
            <div class="input-group mb-2 general-data-row">
              <div class="input-group-prepend"><span class="input-group-text">•</span></div>
              <textarea class="form-control" name="general_data[{{ $index }}]" rows="2" maxlength="1000" required placeholder="Example: Consultation is followed by an assessment and clear next-step guidance.">{{ $point }}</textarea>
              <div class="input-group-append"><button type="button" class="btn btn-outline-danger remove-general-data" title="Remove point"><i class="fa fa-times"></i></button></div>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    <div class="card shadow-sm mb-3">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
          <strong>3. Brief fields for this category/subcategory</strong>
          <small class="text-muted d-block">These are the fields the matching user sees after choosing this Custom Post Type.</small>
        </div>
        <button type="button" id="add-brief-field" class="btn btn-outline-primary btn-sm"><i class="fa fa-plus mr-1"></i> Add field</button>
      </div>
      <div class="card-body">
        <div id="brief-fields">
          @foreach($briefFields as $index => $field)
            <div class="border rounded p-3 mb-2 brief-field-row">
              <div class="row align-items-end">
                <div class="col-md-3 form-group mb-md-0"><label>Field key</label><input class="form-control" name="brief_fields[{{ $index }}][key]" value="{{ data_get($field, 'key') }}" maxlength="50" required placeholder="eye_service"></div>
                <div class="col-md-3 form-group mb-md-0"><label>Label in app</label><input class="form-control" name="brief_fields[{{ $index }}][label]" value="{{ data_get($field, 'label') }}" maxlength="150" required placeholder="Eye service"></div>
                <div class="col-md-3 form-group mb-md-0"><label>Example / hint</label><input class="form-control" name="brief_fields[{{ $index }}][hint]" value="{{ data_get($field, 'hint') }}" maxlength="200" placeholder="Vision test or eye consultation"></div>
                <div class="col-md-2 form-group mb-md-0"><label class="d-block">Required</label><input type="hidden" name="brief_fields[{{ $index }}][required]" value="0"><label class="mb-0"><input type="checkbox" name="brief_fields[{{ $index }}][required]" value="1" @checked(data_get($field, 'required'))> Yes</label></div>
                <div class="col-md-1 text-md-right"><button type="button" class="btn btn-outline-danger btn-sm remove-brief-field"><i class="fa fa-times"></i></button></div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    <div class="card shadow-sm mb-3">
      <div class="card-header bg-white"><strong>4. Optional style selection</strong></div>
      <div class="card-body">
        @if($parentStyles->isNotEmpty())
          <p class="text-muted">Select only the parent Type styles that should appear for this category/subcategory. If you select none, users can use all styles already linked to <strong>{{ $businessAiPurpose->title }}</strong>.</p>
          <div class="row">
            @foreach($parentStyles as $style)
              <div class="col-md-4 mb-2"><label class="border rounded p-3 d-block h-100 mb-0"><input type="checkbox" name="style_ids[]" value="{{ $style->id }}" @checked(in_array((int) $style->id, $chosenStyleIds, true))> <strong>{{ $style->name }}</strong>@if($style->description)<br><small class="text-muted">{{ $style->description }}</small>@endif</label></div>
            @endforeach
          </div>
        @else
          <div class="alert alert-warning mb-0">This parent Type has no active linked styles yet. Add them in Type Studio before making this scope active.</div>
        @endif
      </div>
    </div>

    <div class="card shadow-sm mb-3">
      <div class="card-header bg-white"><strong>5. Content instruction and visibility</strong></div>
      <div class="card-body">
        <div class="form-group">
          <label><strong>Optional content instruction</strong></label>
          <textarea class="form-control" rows="4" name="content_instruction" maxlength="3000" placeholder="Example: Use simple language, include an appointment CTA, and do not state guaranteed results.">{{ old('content_instruction', $scope->content_instruction) }}</textarea>
          <small class="form-text text-muted">This adds an extra rule only for this category/subcategory. The parent Type's universal rule still applies.</small>
        </div>
        <div class="row align-items-center">
          <div class="col-md-3 form-group mb-md-0"><label>Order</label><input class="form-control" type="number" min="0" name="sort_order" value="{{ old('sort_order', $scope->sort_order ?? 0) }}"></div>
          <div class="col-md-9 form-group mb-md-0 pt-md-4"><label class="mb-0"><input type="checkbox" name="status" value="1" @checked(old('status', $scope->status))> Active — matching users can use this category data</label></div>
        </div>
      </div>
    </div>

    <button class="btn btn-primary"><i class="fa fa-save mr-1"></i> {{ $scope->exists ? 'Update Category Data' : 'Save Category Data' }}</button>
  </form>
</div>

<script>
  (function () {
    const category = document.getElementById('business_category_id');
    const subcategory = document.getElementById('business_sub_category_id');

    function refreshSubcategories() {
      const categoryId = category.value;
      Array.from(subcategory.options).forEach(function (option) {
        if (!option.value) return;
        const isMatch = option.dataset.categoryId === categoryId;
        option.hidden = !isMatch;
        option.disabled = !isMatch;
        if (!isMatch && option.selected) subcategory.value = '';
      });
    }

    category.addEventListener('change', refreshSubcategories);
    refreshSubcategories();

    const generalHolder = document.getElementById('general-data-points');
    let nextGeneralIndex = {{ count($generalData) }};
    document.getElementById('add-general-data').addEventListener('click', function () {
      const index = nextGeneralIndex++;
      const row = document.createElement('div');
      row.className = 'input-group mb-2 general-data-row';
      row.innerHTML = `<div class="input-group-prepend"><span class="input-group-text">•</span></div><textarea class="form-control" name="general_data[${index}]" rows="2" maxlength="1000" required placeholder="Example: Consultation is followed by an assessment and clear next-step guidance."></textarea><div class="input-group-append"><button type="button" class="btn btn-outline-danger remove-general-data" title="Remove point"><i class="fa fa-times"></i></button></div>`;
      generalHolder.appendChild(row);
    });
    generalHolder.addEventListener('click', function (event) {
      const button = event.target.closest('.remove-general-data');
      if (button && generalHolder.querySelectorAll('.general-data-row').length > 1) button.closest('.general-data-row').remove();
    });

    const briefHolder = document.getElementById('brief-fields');
    let nextBriefIndex = {{ count($briefFields) }};
    document.getElementById('add-brief-field').addEventListener('click', function () {
      const index = nextBriefIndex++;
      const row = document.createElement('div');
      row.className = 'border rounded p-3 mb-2 brief-field-row';
      row.innerHTML = `<div class="row align-items-end"><div class="col-md-3 form-group mb-md-0"><label>Field key</label><input class="form-control" name="brief_fields[${index}][key]" maxlength="50" required placeholder="eye_service"></div><div class="col-md-3 form-group mb-md-0"><label>Label in app</label><input class="form-control" name="brief_fields[${index}][label]" maxlength="150" required placeholder="Eye service"></div><div class="col-md-3 form-group mb-md-0"><label>Example / hint</label><input class="form-control" name="brief_fields[${index}][hint]" maxlength="200" placeholder="Vision test or eye consultation"></div><div class="col-md-2 form-group mb-md-0"><label class="d-block">Required</label><input type="hidden" name="brief_fields[${index}][required]" value="0"><label class="mb-0"><input type="checkbox" name="brief_fields[${index}][required]" value="1"> Yes</label></div><div class="col-md-1 text-md-right"><button type="button" class="btn btn-outline-danger btn-sm remove-brief-field"><i class="fa fa-times"></i></button></div></div>`;
      briefHolder.appendChild(row);
    });
    briefHolder.addEventListener('click', function (event) {
      const button = event.target.closest('.remove-brief-field');
      if (button && briefHolder.querySelectorAll('.brief-field-row').length > 1) button.closest('.brief-field-row').remove();
    });
  })();
</script>
@endsection
