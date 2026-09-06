@extends('layouts.app')

@section('extra_css')
    @include('partials.modern_admin_css')
    <style>
      /* ── Custom Post Styles: Premium Multi-Select ── */

      /* Container */
      #custom-post-style-ids + .select2-container { width: 100% !important; }

      #custom-post-style-ids + .select2-container .select2-selection--multiple {
        min-height: 48px;
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 6px 8px 2px 8px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        cursor: text;
      }

      #custom-post-style-ids + .select2-container.select2-container--open .select2-selection--multiple,
      #custom-post-style-ids + .select2-container .select2-selection--multiple:focus-within {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12);
      }

      /* Search input inside */
      #custom-post-style-ids + .select2-container .select2-search--inline .select2-search__field {
        margin-top: 4px;
        font-size: 13px;
        font-family: 'Poppins', sans-serif;
        color: #334155;
        height: 28px;
      }
      #custom-post-style-ids + .select2-container .select2-search--inline .select2-search__field::placeholder {
        color: #94a3b8;
      }

      /* Selected Tags / Pills */
      #custom-post-style-ids + .select2-container .select2-selection__choice {
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        border: 1px solid #a7f3d0;
        border-radius: 7px;
        color: #065f46;
        font-size: 12.5px;
        font-weight: 500;
        font-family: 'Poppins', sans-serif;
        padding: 4px 28px 4px 10px;
        margin: 3px 5px 3px 0;
        line-height: 1.4;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        position: relative;
        transition: all 0.15s ease;
        animation: tagSlideIn 0.2s ease-out;
      }

      @keyframes tagSlideIn {
        from { opacity: 0; transform: scale(0.9) translateY(-2px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
      }

      #custom-post-style-ids + .select2-container .select2-selection__choice:hover {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        border-color: #6ee7b7;
        box-shadow: 0 1px 3px rgba(16, 185, 129, 0.15);
      }

      /* Remove button (×) */
      #custom-post-style-ids + .select2-container .select2-selection__choice__remove {
        position: absolute;
        right: 6px;
        top: 50%;
        transform: translateY(-50%);
        color: #6ee7b7;
        font-size: 16px;
        font-weight: 700;
        line-height: 1;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.15s ease;
        cursor: pointer;
      }

      #custom-post-style-ids + .select2-container .select2-selection__choice__remove:hover {
        color: #fff;
        background: #ef4444;
      }

      /* ── Dropdown ── */
      .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background: #ecfdf5 !important;
        color: #065f46 !important;
      }

      .select2-dropdown {
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        margin-top: 4px;
      }

      .select2-results__option {
        padding: 10px 14px;
        font-size: 13px;
        font-family: 'Poppins', sans-serif;
        color: #334155;
        border-bottom: 1px solid #f8fafc;
        transition: background 0.1s ease;
        cursor: pointer;
      }

      .select2-results__option:last-child {
        border-bottom: none;
      }

      .select2-results__option[aria-selected="true"] {
        background: #f0fdf4 !important;
        color: #065f46 !important;
        position: relative;
      }

      .select2-results__option[aria-selected="true"]::after {
        content: '✓';
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #10b981;
        font-weight: 700;
        font-size: 14px;
      }

      .select2-search--dropdown {
        padding: 10px 12px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
      }

      .select2-search--dropdown .select2-search__field {
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 13px;
        font-family: 'Poppins', sans-serif;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        outline: none;
      }

      .select2-search--dropdown .select2-search__field:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12);
      }

      /* Header & Footer Style Select2 */
      .select2-container--default .select2-selection--single {
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        height: 42px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
      }
      .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 40px;
        padding-left: 12px;
        font-size: 13px;
        font-family: 'Poppins', sans-serif;
        color: #334155;
      }
      .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
      }
      .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12);
      }

      /* Collapse chevron animation */
      [data-toggle="collapse"] .fa-chevron-down { transition: transform 0.3s ease; }
      [data-toggle="collapse"][aria-expanded="true"] .fa-chevron-down { transform: rotate(180deg); }

      /* Tag counter badge */
      .style-count-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        background: #f0fdf4;
        border: 1px solid #a7f3d0;
        border-radius: 20px;
        color: #065f46;
        font-size: 11.5px;
        font-weight: 600;
        font-family: 'Poppins', sans-serif;
        margin-top: 6px;
        transition: all 0.2s ease;
      }
      .style-count-badge .count-num {
        background: #10b981;
        color: #fff;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
      }
    </style>
@endsection


@section('content')
<div class="modern-ui-wrapper container-fluid py-3">
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap" style="gap: 12px;">
    <div>
      <div class="text-muted small mb-1">
        <a href="{{ route('custom_post_types.index') }}" class="text-muted">Custom Post Types</a>
        <span class="mx-1">/</span>
        <span class="text-muted">{{ $purpose->exists ? 'Edit' : 'Add' }}</span>
      </div>
      <h3 class="mb-1">{{ $purpose->exists ? 'Custom Post Type Studio: ' . $purpose->title : 'Add Custom Post Type' }}</h3>
      <p class="text-muted mb-0">Configure the first-screen card, universal rules, header/footer and linked styles. Brief fields are set inside each Business Subcategory.</p>
    </div>
    <div class="text-nowrap"><a href="{{ route('custom_post_types.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left mr-1"></i> Back to Custom Post Types</a></div>
  </div>
  @if($errors->any())<div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

  @php($selectedSizes = old('allowed_size_keys', $purpose->allowed_size_keys ?? []))
  @php($selectedStyles = old('style_ids', $selectedStyleIds))
  <form method="POST" action="{{ $purpose->exists ? route('custom_post_types.update', $purpose) : route('custom_post_types.store') }}">
    @csrf @if($purpose->exists) @method('PUT') @endif

    <div class="row">
      <div class="col-lg-6 mb-3">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;">
          <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #f1f5f9; border-radius: 12px 12px 0 0;">
            <div>
              <strong>1. First-screen Custom Post Type card</strong>
            </div>
          </div>
          <div class="card-body">
            <div class="border rounded p-3 bg-light h-100" style="border-color: #e2e8f0 !important;">
              <div class="row">
                <div class="col-12 form-group"><label class="text-dark">Custom Post Type name</label><input class="form-control bg-white" name="title" maxlength="150" required value="{{ old('title', $purpose->title) }}" placeholder="Hiring"><small class="form-text text-muted">Shown on the first app screen.</small></div>
                <div class="col-12 form-group"><label class="text-dark">Short app description</label><input class="form-control bg-white" name="description" maxlength="300" value="{{ old('description', $purpose->description) }}" placeholder="Job posts and recruitment"></div>
                <div class="col-6 form-group mb-0"><label class="text-dark">Icon key</label><input class="form-control bg-white" name="icon" maxlength="100" value="{{ old('icon', $purpose->icon) }}" placeholder="work"><small class="form-text text-muted">work, local_offer, etc.</small></div>
                <div class="col-6 form-group mb-0"><label class="text-dark">Order</label><input class="form-control bg-white" type="number" min="0" name="sort_order" value="{{ old('sort_order', $purpose->sort_order ?? 0) }}"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-lg-6 mb-3">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;">
          <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #f1f5f9; border-radius: 12px 12px 0 0;">
            <div>
              <strong>2. Header/Footer and Custom Post Styles</strong>
            </div>
            <div>
              <a href="{{ route('custom_post_header_footer_styles.index') }}" class="btn btn-outline-primary btn-sm mr-1">Manage Header &amp; Footer</a>
              <a href="{{ route('custom_post_styles.index') }}" class="btn btn-outline-primary btn-sm">Manage Styles</a>
            </div>
          </div>
          <div class="card-body">
            <div class="border rounded p-3 bg-light h-100" style="border-color: #e2e8f0 !important;">
              <div class="row">
                <div class="col-12 form-group"><label class="text-dark"><strong>Header &amp; Footer Style</strong></label><select class="form-control select2 bg-white" name="business_ai_header_footer_style_id"><option value="">Select Header &amp; Footer Style</option>@foreach($headerFooterStyles as $style)<option value="{{ $style->id }}" @selected((int) old('business_ai_header_footer_style_id', $purpose->business_ai_header_footer_style_id) === $style->id)>{{ $style->name }}</option>@endforeach</select><small class="form-text text-muted">Admin-selected. The user does not need to choose this in the app.</small></div>
                <div class="col-12 form-group mb-0">
                  <label class="text-dark" for="custom-post-style-ids"><strong>Custom Post Styles</strong></label>
                  <select id="custom-post-style-ids" class="form-control bg-white" name="style_ids[]" multiple data-placeholder="🔍 Type to search styles...">@foreach($initialStyleOptions as $style)<option value="{{ $style->id }}" @selected(in_array($style->id, array_map('intval', $selectedStyles), true))>{{ $style->name }}{{ $style->description ? ' — ' . $style->description : '' }}</option>@endforeach</select>
                  <div id="style-count-container" class="mt-2 mb-1"></div>
                  <div id="custom-tags-container" class="d-flex flex-wrap mb-2" style="gap: 8px;"></div>
                  <small class="form-text text-muted">Search and select the style choices the user should see after completing the Brief. You can choose multiple styles.</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm mb-3 border-0" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;">
      <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #f1f5f9; border-radius: 12px 12px 0 0;">
        <div>
          <strong>3. Generation rules</strong>
        </div>
      </div>
      <div class="card-body">
        <div class="border rounded p-3 bg-light" style="border-color: #e2e8f0 !important;">
          <div class="row">
            <div class="col-md-5 form-group mb-md-0"><label class="text-dark d-block"><strong>Allowed output sizes</strong></label>@foreach($sizeOptions as $key => $label)<label class="mr-3 text-dark"><input type="checkbox" name="allowed_size_keys[]" value="{{ $key }}" @checked(in_array($key, $selectedSizes, true))> {{ $label }}</label>@endforeach</div>
            <div class="col-md-3 form-group mb-md-0"><label class="text-dark">Maximum product photos</label><select class="form-control bg-white" name="max_product_references">@for($count = 1; $count <= 4; $count++)<option value="{{ $count }}" @selected((int) old('max_product_references', $purpose->max_product_references ?? 4) === $count)>{{ $count }} photo{{ $count > 1 ? 's' : '' }}</option>@endfor</select></div>
            <div class="col-md-4 form-group mb-md-0"><label class="text-dark">Change instruction limit</label><input class="form-control bg-white" type="number" min="50" max="1000" name="change_instruction_limit" value="{{ old('change_instruction_limit', $purpose->change_instruction_limit ?? 300) }}"><small class="form-text text-muted">For visual instruction and Generate New Version.</small></div>
          </div>
          <hr style="border-color: #e2e8f0 !important;">
          <div>
            <label class="mr-4 text-dark"><input type="checkbox" name="product_upload_enabled" value="1" @checked(old('product_upload_enabled', $purpose->product_upload_enabled))> Allow product photo upload</label>
            <label class="text-dark"><input type="checkbox" name="product_required" value="1" @checked(old('product_required', $purpose->product_required))> Product photo is required to generate</label>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm mb-3 border-0" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;">
      <div class="card-header bg-white d-flex align-items-center w-100" style="border-bottom: 1px solid #f1f5f9; border-radius: 12px 12px 0 0; cursor: pointer;" data-toggle="collapse" data-target="#optionalInstructionCollapse" aria-expanded="false" aria-controls="optionalInstructionCollapse">
        <div>
          <strong>4. Optional Type Instruction</strong>
        </div>
        <div class="ml-auto">
          <i class="fa fa-chevron-down text-muted" style="font-size: 12px;"></i>
        </div>
      </div>
      <div id="optionalInstructionCollapse" class="collapse">
        <div class="card-body">
          <div class="border rounded p-3 mb-3 bg-light" style="border-color: #e2e8f0 !important;">
            <label class="text-dark d-block"><strong>Base Prompt</strong></label>
            <textarea class="form-control bg-white" rows="6" name="base_prompt" maxlength="10000" placeholder="Optional: add a rule common to every category using this Type. If left blank, Artera uses its fixed universal AI rule.">{{ old('base_prompt', $purpose->base_prompt) }}</textarea>
            <small class="form-text text-muted">You do not need a separate prompt for every category or subcategory. Add their approved General Data from Business Subcategory &rarr; AI Post Data.</small>
          </div>
          <div class="border rounded p-3 bg-light" style="border-color: #e2e8f0 !important;">
            <label class="text-dark d-block"><strong>Universal product / service rule</strong> <small class="text-muted">optional</small></label>
            <textarea class="form-control bg-white" rows="3" name="product_prompt" maxlength="3000" placeholder="Rules that must apply whenever this Type uses a product or service, such as preserving product label and keeping it clearly visible.">{{ old('product_prompt', $purpose->product_prompt) }}</textarea>
            <small class="form-text text-muted">Like Festival AI: add only rules common to every linked style. Exact visual look belongs in the Custom Post Style Prompt.</small>
          </div>
        </div>
      </div>
    </div>

    <div class="mb-4"><label class="text-dark font-weight-bold"><input type="checkbox" name="status" value="1" @checked(old('status', $purpose->status))> Active and visible as a card in the app</label></div>
    <button class="btn btn-primary"><i class="fa fa-save mr-1"></i> {{ $purpose->exists ? 'Update Custom Post Type' : 'Save Custom Post Type' }}</button>
  </form>
</div>
@endsection

@section('script')
<style>
  /* Hide the default select2 pills inside the input */
  #custom-post-style-ids + .select2-container .select2-selection--multiple .select2-selection__choice {
      display: none !important;
  }
  /* Ensure the input doesn't grow huge */
  #custom-post-style-ids + .select2-container .select2-selection--multiple {
      min-height: 38px;
      padding-bottom: 0px !important;
  }
  /* Optional: Show placeholder even when items are selected */
  #custom-post-style-ids + .select2-container .select2-selection--multiple .select2-search__field::placeholder {
      color: #999;
  }
</style>
<script>
  (function () {
    const styleSelect = $('#custom-post-style-ids');
    const headerFooterSelect = $('select[name="business_ai_header_footer_style_id"]');
    const countContainer = $('#style-count-container');
    const tagsContainer = $('#custom-tags-container');

    if (headerFooterSelect.length && $.fn.select2) {
      headerFooterSelect.select2({
        width: '100%',
        placeholder: 'Select Header & Footer Style'
      });
    }

    if (!styleSelect.length || !$.fn.select2) return;

    window.removeStyleTag = function(id) {
        var values = styleSelect.val() || [];
        var index = values.indexOf(id.toString());
        if (index !== -1) {
            values.splice(index, 1);
            styleSelect.val(values).trigger('change');
        }
    };

    /* ── Update the counter badge and tags ── */
    function updateCounterAndTags() {
      var data = styleSelect.select2('data');
      var count = data.length;

      if (count > 0) {
        countContainer.html(
          '<span class="style-count-badge" style="font-size: 13px; font-weight: 600; color: #475569;">' +
            '<span class="count-num">' + count + '</span> ' +
            'style' + (count !== 1 ? 's' : '') + ' selected' +
          '</span>'
        );
      } else {
        countContainer.html('');
      }

      tagsContainer.empty();
      data.forEach(function(item) {
        if (!item.id) return;
        var parts = item.text.split(' — ');
        var name = parts[0];
        
        var tagHtml = '<div class="d-inline-flex align-items-center bg-white border rounded px-2 py-1 shadow-sm" style="font-size: 13px; border-color: #cbd5e1 !important; color: #1e293b;">' +
                        '<span class="mr-2 font-weight-bold">' + name + '</span>' +
                        '<span style="cursor: pointer; color: #ef4444; font-size: 14px; display: flex; align-items: center;" onclick="removeStyleTag(' + item.id + ')"><i class="fa fa-times-circle"></i></span>' +
                      '</div>';
        tagsContainer.append(tagHtml);
      });
    }

    /* ── Format dropdown items ── */
    function formatStyleOption(option) {
      if (!option.id) return option.text;
      var parts = option.text.split(' — ');
      var name = parts[0];
      var desc = parts.length > 1 ? parts[1] : '';
      var $el = $(
        '<div style="display:flex; align-items:center; gap:8px;">' +
          '<div>' +
            '<div style="font-weight:500; color:#1e293b; font-size:13px;">' + name + '</div>' +
            (desc ? '<div style="font-size:11.5px; color:#64748b; margin-top:1px;">' + desc + '</div>' : '') +
          '</div>' +
        '</div>'
      );
      return $el;
    }

    styleSelect.select2({
      width: '100%',
      closeOnSelect: false,
      placeholder: styleSelect.data('placeholder') || '🔍 Type to search styles...',
      templateResult: formatStyleOption,
      ajax: {
        url: '{{ route("custom_post_types.styles.search") }}',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return { q: params.term || '', page: params.page || 1 };
        },
        processResults: function (data) {
          return data;
        },
        cache: true
      }
    });

    /* ── Listen for changes to update counter and tags ── */
    styleSelect.on('change', updateCounterAndTags);

    /* ── Initial count and tags ── */
    updateCounterAndTags();
  })();
</script>
@endsection

