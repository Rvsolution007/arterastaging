@extends('layouts.app')

@section('extra_css')
    @include('partials.modern_admin_css')
@endsection

@section('content')
@php
  $parentCategoryName = optional($businessSubCategory->business_category)->name ?: 'Parent category';
  $isEditing = $scope->exists;
  $generalData = old('general_data', $scope->general_data ?? ['']);
  $generalData = count((array) $generalData) ? $generalData : [''];
  $isBriefLinked = !empty($briefFieldsSource);
  $resolvedBriefFields = $isBriefLinked
      ? $briefFieldsSource->resolvedBriefFields()
      : ($scope->brief_fields ?? null);
  $briefFields = old('brief_fields', $resolvedBriefFields ?? [[
      'key' => '',
      'label' => '',
      'hint' => '',
      'required' => false,
  ]]);
  $briefFields = count((array) $briefFields) ? $briefFields : [[
      'key' => '',
      'label' => '',
      'hint' => '',
      'required' => false,
  ]];
  // Validation can preserve sparse array keys after an admin removes a row.
  // Start new rows after the largest existing key so no input is overwritten.
  $nextGeneralDataIndex = count((array) $generalData)
      ? max(array_map('intval', array_keys((array) $generalData))) + 1
      : 0;
  $nextBriefFieldIndex = count((array) $briefFields)
      ? max(array_map('intval', array_keys((array) $briefFields))) + 1
      : 0;
  $chosenStyleIds = array_map('intval', old('style_ids', $selectedStyleIds ?? []));
  $chosenSharedBriefScopeIds = array_map('intval', old('shared_brief_scope_ids', $sharedBriefScopeIds ?? []));
  $shareBriefFields = old('share_brief_fields', !empty($sharedBriefScopeIds));
@endphp

<div class="modern-ui-wrapper container-fluid py-3">
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap" style="gap: 12px;">
    <div>
      <div class="text-muted small mb-1">
        <a href="{{ route('business-sub-category.index') }}" class="text-muted">Business Subcategories</a>
        <span class="mx-1">/</span>
        <a href="{{ route('business_sub_category.ai_data.index', $businessSubCategory) }}" class="text-muted">{{ $businessSubCategory->name }} AI Post Data</a>
      </div>
      <h3 class="mb-1">{{ $isEditing ? 'Edit' : 'Add' }} AI Post Data</h3>
      <p class="text-muted mb-0">The business category and subcategory are already fixed below. You only choose the Custom Post Type.</p>
    </div>
    <a href="{{ route('business_sub_category.ai_data.index', $businessSubCategory) }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left mr-1"></i> Back to AI Post Data</a>
  </div>

  <div class="card shadow-sm border-primary mb-3">
    <div class="card-body py-3 d-flex align-items-center flex-wrap" style="gap: 10px;">
      <span class="text-muted small text-uppercase font-weight-bold">Fixed context</span>
      <span class="badge badge-light border px-3 py-2">{{ $parentCategoryName }}</span>
      <i class="fa fa-arrow-right text-muted"></i>
      <span class="badge badge-primary px-3 py-2">{{ $businessSubCategory->name }}</span>
      <small class="text-muted ml-md-2">This data cannot be saved for another category or subcategory from this page.</small>
    </div>
  </div>

  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
  @endif

  @if(!$purpose && !$isEditing)
    <div class="card shadow-sm">
      <div class="card-header bg-white"><strong>1. Choose Custom Post Type</strong></div>
      <div class="card-body">
        <p class="text-muted">First choose the type for which you want to add separate data for <strong>{{ $businessSubCategory->name }}</strong>.</p>
        <form method="GET" action="{{ route('business_sub_category.ai_data.create', $businessSubCategory) }}" class="row align-items-end">
          <div class="col-md-7 form-group mb-md-0">
            <label for="purpose_id"><strong>Custom Post Type</strong></label>
            <select id="purpose_id" name="purpose_id" class="form-control" required>
              <option value="">Select Custom Post Type</option>
              @foreach($purposeOptions as $purposeOption)
                <option value="{{ $purposeOption->id }}" @selected((int) request('purpose_id') === (int) $purposeOption->id)>{{ $purposeOption->title }}</option>
              @endforeach
            </select>
            <small class="form-text text-muted">For example: Treatment Process, Doctor Introduction, or Why Choose Us.</small>
          </div>
          <div class="col-md-5"><button class="btn btn-primary"><i class="fa fa-arrow-right mr-1"></i> Continue</button></div>
        </form>
      </div>
    </div>
  @elseif(!$purpose)
    <div class="alert alert-danger">The selected Custom Post Type is no longer available. Go back to the AI Post Data list and choose another type.</div>
  @else
    <form method="POST" action="{{ $isEditing ? route('business_sub_category.ai_data.update', [$businessSubCategory, $scope]) : route('business_sub_category.ai_data.store', $businessSubCategory) }}">
      @csrf
      @if($isEditing) @method('PUT') @endif
      <input type="hidden" name="business_ai_purpose_id" value="{{ $purpose->id }}">

      <div class="row">
        <!-- Left Side: 1. Custom Post Type & 3. User Brief Fields -->
        <div class="col-lg-6 d-flex flex-column">
          
          <div class="card shadow-sm mb-3 border-0" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #f1f5f9; border-radius: 12px 12px 0 0;">
              <div>
                <strong>1. Custom Post Type</strong>
                <small class="text-muted d-block">This is the only setup selection on this page.</small>
              </div>
              @if(!$isEditing)
                <a href="{{ route('business_sub_category.ai_data.create', $businessSubCategory) }}" class="btn btn-outline-secondary btn-sm">Change Type</a>
              @endif
            </div>
            <div class="card-body">
              <div class="border rounded p-3 bg-light" style="border-color: #e2e8f0 !important;">
                <strong class="text-primary">{{ $purpose->title }}</strong>
                @if($purpose->description)<small class="text-muted d-block mt-1">{{ $purpose->description }}</small>@endif
              </div>
            </div>
          </div>

          <div class="card shadow-sm mb-3 border-0" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #f1f5f9; border-radius: 12px 12px 0 0;">
              <div>
                <strong>1.1 Allowed Custom Post Styles</strong>
                <small class="text-muted d-block">These are the styles the app will show for {{ $purpose->title }} in {{ $businessSubCategory->name }}.</small>
              </div>
              <a href="{{ route('custom_post_types.edit', $purpose) }}" class="btn btn-outline-secondary btn-sm">Manage master styles</a>
            </div>
            <div class="card-body">
              @if($parentStyles->isNotEmpty())
                <div class="alert alert-info border-0 py-2 small" style="background-color: #e0f2fe; color: #0369a1; border-radius: 8px;">
                  <strong>{{ $parentStyles->count() }} master style{{ $parentStyles->count() === 1 ? '' : 's' }} available.</strong>
                  Tick only the styles allowed for this subcategory. Leave all unchecked to allow every master style.
                </div>
                <div class="row">
                  @foreach($parentStyles as $style)
                    <div class="col-md-6 mb-2">
                      <label class="border rounded p-3 d-block h-100 mb-0" style="cursor: pointer;">
                        <input type="checkbox" name="style_ids[]" value="{{ $style->id }}" @checked(in_array((int) $style->id, $chosenStyleIds, true))>
                        <strong>{{ $style->name }}</strong>
                        @if($style->description)<br><small class="text-muted">{{ $style->description }}</small>@endif
                        @php
                          $styleColors = $style->colors ?? [];
                        @endphp
                        @if(!empty($styleColors[0]) && !empty($styleColors[1]))
                          <span class="d-block mt-2 small text-muted"><span style="display:inline-block;width:11px;height:11px;border-radius:50%;background:{{ $styleColors[0] }}"></span> <span style="display:inline-block;width:11px;height:11px;border-radius:50%;background:{{ $styleColors[1] }}"></span> Default style colours</span>
                        @endif
                      </label>
                    </div>
                  @endforeach
                </div>
              @else
                <div class="alert alert-warning mb-0">
                  No active Custom Post Style is linked to this Type yet. Open <strong>Manage master styles</strong>, select at least one style and save; it will then appear here automatically.
                </div>
              @endif
            </div>
          </div>

          <div class="card shadow-sm mb-3 border-0 flex-grow-1" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #f1f5f9; border-radius: 12px 12px 0 0;">
              <div>
                <strong>3. User Brief Fields</strong>
                <small class="text-muted d-block">These fields appear to the user when they choose {{ $purpose->title }} for {{ $businessSubCategory->name }}.</small>
              </div>
              <div class="d-flex align-items-center flex-wrap justify-content-end" style="gap: 10px;">
                @if($isBriefLinked)
                  <span class="badge badge-info px-3 py-2">Shared Brief Fields</span>
                @else
                  <label class="mb-0 small font-weight-bold" for="share-brief-fields">
                    <input type="checkbox" id="share-brief-fields" name="share_brief_fields" value="1" @checked($shareBriefFields)>
                    Same fields in other subcategories
                  </label>
                  <button type="button" id="add-brief-field" class="btn btn-outline-primary btn-sm"><i class="fa fa-plus mr-1"></i> Add Field</button>
                @endif
              </div>
            </div>
            <div class="card-body">
              @if($isBriefLinked)
                <div class="alert alert-info border-0" style="background-color: #e0f2fe; color: #0284c7; border-radius: 8px;">
                  <strong>Shared from {{ optional($briefFieldsSource->category)->name }} &rarr; {{ optional($briefFieldsSource->subCategory)->name }}.</strong>
                  Edit the source subcategory to change these fields everywhere it is shared.
                </div>
              @else
                <div id="shared-brief-settings" class="border rounded p-3 mb-3 bg-light" style="border-color: #e2e8f0 !important;" @if(!$shareBriefFields) hidden @endif>
                  <label class="mb-1" for="shared-brief-scope-ids"><strong class="text-dark">Apply same Brief Fields to</strong></label>
                  <select id="shared-brief-scope-ids" name="shared_brief_scope_ids[]" class="form-control" multiple data-placeholder="Search subcategories...">
                    @foreach($shareableBriefScopes as $shareableScope)
                      @php
                        $shareCategoryName = optional($shareableScope->category)->name ?: 'Category';
                        $shareSubCategoryName = optional($shareableScope->subCategory)->name ?: 'Subcategory';
                      @endphp
                      <option value="{{ $shareableScope->id }}" @selected(in_array((int) $shareableScope->id, $chosenSharedBriefScopeIds, true))>{{ $shareCategoryName }} &rarr; {{ $shareSubCategoryName }}</option>
                    @endforeach
                  </select>
                  @if($shareableBriefScopes->isEmpty())
                    <small class="form-text text-muted">First add this Custom Post Type's General Data in the other subcategory. It will then appear here.</small>
                  @else
                    <small class="form-text text-muted">Search and select one or more saved subcategories. Their General Data, style, and instruction stay separate; only User Brief Fields are shared.</small>
                  @endif
                </div>
              @endif
              <div id="brief-fields">
                @foreach($briefFields as $index => $field)
                  <div class="border rounded p-3 mb-2 brief-field-row" style="background-color: #f8fafc; border-color: #e2e8f0 !important;">
                    <div class="row align-items-end">
                      <div class="col-md-3 form-group mb-md-0"><label class="text-dark">Field Key</label><input class="form-control bg-white" name="brief_fields[{{ $index }}][key]" value="{{ data_get($field, 'key') }}" maxlength="50" required placeholder="eye_service" @readonly($isBriefLinked)></div>
                      <div class="col-md-3 form-group mb-md-0"><label class="text-dark">Label in App</label><input class="form-control bg-white" name="brief_fields[{{ $index }}][label]" value="{{ data_get($field, 'label') }}" maxlength="150" required placeholder="Eye service" @readonly($isBriefLinked)></div>
                      <div class="col-md-3 form-group mb-md-0"><label class="text-dark">Example / Hint</label><input class="form-control bg-white" name="brief_fields[{{ $index }}][hint]" value="{{ data_get($field, 'hint') }}" maxlength="200" placeholder="Vision test or eye consultation" @readonly($isBriefLinked)></div>
                      <div class="col-md-2 form-group mb-md-0"><label class="d-block text-dark">Required</label><input type="hidden" name="brief_fields[{{ $index }}][required]" value="0"><label class="mb-0"><input type="checkbox" name="brief_fields[{{ $index }}][required]" value="1" @checked(data_get($field, 'required')) @if($isBriefLinked) onclick="return false;" tabindex="-1" @endif> Yes</label></div>
                      @if(!$isBriefLinked)<div class="col-md-1 text-md-right"><button type="button" class="btn btn-outline-danger btn-sm remove-brief-field" title="Remove field"><i class="fa fa-times"></i></button></div>@endif
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
          
        </div>

        <!-- Right Side: 2. General Data -->
        <div class="col-lg-6 d-flex flex-column">
          
          <div class="card shadow-sm mb-3 border-0 flex-grow-1" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #f1f5f9; border-radius: 12px 12px 0 0;">
              <div>
                <strong>2. General Data</strong>
                <small class="text-muted d-block">Approved reusable points from your external Smart AI analysis.</small>
              </div>
              <button type="button" id="add-general-data" class="btn btn-outline-primary btn-sm"><i class="fa fa-plus mr-1"></i> Add Point</button>
            </div>
            <div class="card-body">
              <div class="alert alert-info border-0" style="background-color: #e0f2fe; color: #0284c7; border-radius: 8px;">
                Add only general, approved knowledge for this type and subcategory. Do not include competitor names, patient details, temporary offers, phone numbers, prices, or guaranteed-result claims.
              </div>
              <div id="general-data-points">
                @foreach($generalData as $index => $point)
                  <div class="input-group mb-2 general-data-row shadow-sm" style="border-radius: 8px; overflow: hidden;">
                    <div class="input-group-prepend"><span class="input-group-text bg-light border-0 text-primary">&bull;</span></div>
                    <textarea class="form-control border-0 bg-light" style="resize: none;" name="general_data[{{ $index }}]" rows="2" maxlength="1000" required placeholder="Example: Consultation is followed by assessment and clear next-step guidance.">{{ $point }}</textarea>
                    <div class="input-group-append"><button type="button" class="btn btn-light border-0 text-danger remove-general-data" title="Remove point"><i class="fa fa-times"></i></button></div>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
          
        </div>
      </div>

      <div class="card shadow-sm mb-3">
        <div class="card-header bg-white"><strong>5. Optional Instruction and Visibility</strong></div>
        <div class="card-body">
          <div class="form-group">
            <label><strong>Optional Content Instruction</strong></label>
            <textarea class="form-control" rows="4" name="content_instruction" maxlength="3000" placeholder="Example: Use simple language, include an appointment CTA, and do not state guaranteed results.">{{ old('content_instruction', $scope->content_instruction) }}</textarea>
            <small class="form-text text-muted">This rule applies only to {{ $businessSubCategory->name }}. The fixed universal AI rule still applies to every post.</small>
          </div>
          <div class="row align-items-center">
            <div class="col-md-3 form-group mb-md-0"><label>Order</label><input class="form-control" type="number" min="0" name="sort_order" value="{{ old('sort_order', $scope->sort_order ?? 0) }}"></div>
            <div class="col-md-9 form-group mb-md-0 pt-md-4"><label class="mb-0"><input type="checkbox" name="status" value="1" @checked(old('status', $scope->status))> Active &mdash; matching users can use this AI Post Data</label></div>
          </div>
        </div>
      </div>

      <button class="btn btn-primary"><i class="fa fa-save mr-1"></i> {{ $isEditing ? 'Update AI Post Data' : 'Save AI Post Data' }}</button>
    </form>
  @endif
</div>
@endsection

@section('script')
@if($purpose)
<script>
  (function () {
    const generalHolder = document.getElementById('general-data-points');
    let nextGeneralIndex = {{ $nextGeneralDataIndex }};
    document.getElementById('add-general-data').addEventListener('click', function () {
      const index = nextGeneralIndex++;
      const row = document.createElement('div');
      row.className = 'input-group mb-2 general-data-row shadow-sm';
      row.style.borderRadius = '8px';
      row.style.overflow = 'hidden';
      row.innerHTML = '<div class="input-group-prepend"><span class="input-group-text bg-light border-0 text-primary">&bull;</span></div><textarea class="form-control border-0 bg-light" style="resize: none;" name="general_data[' + index + ']" rows="2" maxlength="1000" required placeholder="Example: Consultation is followed by assessment and clear next-step guidance."></textarea><div class="input-group-append"><button type="button" class="btn btn-light border-0 text-danger remove-general-data" title="Remove point"><i class="fa fa-times"></i></button></div>';
      generalHolder.appendChild(row);
    });
    generalHolder.addEventListener('click', function (event) {
      const button = event.target.closest('.remove-general-data');
      if (button && generalHolder.querySelectorAll('.general-data-row').length > 1) {
        button.closest('.general-data-row').remove();
      }
    });

    const shareBriefToggle = document.getElementById('share-brief-fields');
    const sharedBriefSettings = document.getElementById('shared-brief-settings');
    const sharedBriefSelect = document.getElementById('shared-brief-scope-ids');
    if (shareBriefToggle && sharedBriefSettings) {
      shareBriefToggle.addEventListener('change', function () {
        sharedBriefSettings.hidden = !this.checked;
      });
    }
    if (sharedBriefSelect && window.jQuery && $.fn.select2) {
      $(sharedBriefSelect).select2({
        width: '100%',
        placeholder: sharedBriefSelect.dataset.placeholder || 'Search subcategories...',
      });
    }

    const briefHolder = document.getElementById('brief-fields');
    const addBriefButton = document.getElementById('add-brief-field');
    let nextBriefIndex = {{ $nextBriefFieldIndex }};
    if (addBriefButton) addBriefButton.addEventListener('click', function () {
      const index = nextBriefIndex++;
      const row = document.createElement('div');
      row.className = 'border rounded p-3 mb-2 brief-field-row';
      row.style.backgroundColor = '#f8fafc';
      row.style.borderColor = '#e2e8f0 !important';
      row.innerHTML = '<div class="row align-items-end"><div class="col-md-3 form-group mb-md-0"><label class="text-dark">Field Key</label><input class="form-control bg-white" name="brief_fields[' + index + '][key]" maxlength="50" required placeholder="eye_service"></div><div class="col-md-3 form-group mb-md-0"><label class="text-dark">Label in App</label><input class="form-control bg-white" name="brief_fields[' + index + '][label]" maxlength="150" required placeholder="Eye service"></div><div class="col-md-3 form-group mb-md-0"><label class="text-dark">Example / Hint</label><input class="form-control bg-white" name="brief_fields[' + index + '][hint]" maxlength="200" placeholder="Vision test or eye consultation"></div><div class="col-md-2 form-group mb-md-0"><label class="d-block text-dark">Required</label><input type="hidden" name="brief_fields[' + index + '][required]" value="0"><label class="mb-0"><input type="checkbox" name="brief_fields[' + index + '][required]" value="1"> Yes</label></div><div class="col-md-1 text-md-right"><button type="button" class="btn btn-outline-danger btn-sm remove-brief-field" title="Remove field"><i class="fa fa-times"></i></button></div></div>';
      briefHolder.appendChild(row);
    });
    briefHolder.addEventListener('click', function (event) {
      const button = event.target.closest('.remove-brief-field');
      if (button && briefHolder.querySelectorAll('.brief-field-row').length > 1) {
        button.closest('.brief-field-row').remove();
      }
    });
  })();
</script>
@endif
@endsection
