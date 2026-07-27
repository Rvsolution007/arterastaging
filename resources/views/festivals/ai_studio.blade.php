@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-1"><i class="fa fa-magic text-primary mr-1"></i> AI Studio: {{ $festival->title }}</h3>
      <p class="text-muted mb-0">Set this festival's content rules, then choose reusable Festival Styles from the central library.</p>
    </div>
    <a href="{{ route('festivals.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left mr-1"></i> Festivals</a>
  </div>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
  @endif

  <div class="alert alert-info border-0 shadow-sm">
    Enable this only after saving a <strong>Festival Prompt</strong> and selecting at least one active <strong>Festival Style</strong>.
  </div>

  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white"><strong>Festival AI rules</strong></div>
    <div class="card-body">
      <form method="POST" action="{{ route('festivals.ai_studio.update', $festival) }}">
        @csrf @method('PUT')
        <div class="custom-control custom-switch mb-3">
          <input type="checkbox" class="custom-control-input" id="is_enabled" name="is_enabled" value="1" @checked(old('is_enabled', $config->is_enabled))>
          <label class="custom-control-label font-weight-bold" for="is_enabled">Enable AI for this festival</label>
        </div>

        <div class="form-group">
          <label><strong>Festival Prompt</strong> <small class="text-danger">required before enabling</small></label>
          <textarea class="form-control" rows="5" name="base_prompt" maxlength="10000" placeholder="Describe the fixed festival content: festival name, subject, required people or objects, mood, and text restrictions.">{{ old('base_prompt', $config->base_prompt) }}</textarea>
          <small class="form-text text-muted">Example: Guru Purnima's guru, disciples, blessing scene and spiritual meaning. This stays the same across all selected styles.</small>
        </div>

        <div class="form-group">
          <label><strong>Product / service placement prompt</strong> <small class="text-muted">optional</small></label>
          <textarea class="form-control" rows="3" name="product_prompt" maxlength="3000" placeholder="Explain how a product selected or uploaded by the user should be integrated into the festival composition.">{{ old('product_prompt', $config->product_prompt) }}</textarea>
        </div>

        <div class="row">
          <div class="form-group col-md-6">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="mb-0"><strong>Header &amp; Footer Style</strong> <small class="text-muted">optional business branding</small></label>
              <a href="{{ route('festival_ai_brand_chrome.index') }}" class="btn btn-outline-primary btn-sm"><i class="fa fa-id-card mr-1"></i> Manage Header &amp; Footer Styles</a>
            </div>
            <select class="form-control" name="festival_ai_brand_chrome_preset_id">
              <option value="">No Header &amp; Footer Style</option>
              @foreach($brandChromePresets as $preset)
                <option value="{{ $preset->id }}" @selected((int) old('festival_ai_brand_chrome_preset_id', $config->festival_ai_brand_chrome_preset_id) === $preset->id)>{{ $preset->name }}</option>
              @endforeach
            </select>
            <small class="form-text text-muted">When selected, the generated visual reserves branded top and bottom zones. The current business logo and only visible business details are added automatically; fields set to Hide in frame are excluded.</small>
          </div>

          <div class="form-group col-md-6">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="mb-0"><strong>Festival Styles</strong> <small class="text-danger">select at least one before enabling</small></label>
              <a href="{{ route('festival_ai_styles.index') }}" class="btn btn-outline-primary btn-sm"><i class="fa fa-palette mr-1"></i> Manage Festival Styles</a>
            </div>

            {{-- Hidden inputs container (submitted with form) --}}
            <div id="fsSelectedInputs"></div>

            {{-- Custom multi-select dropdown --}}
            <div class="fs-multiselect" id="fsMultiselect">
              {{-- Trigger button --}}
              <div class="fs-multiselect__trigger" id="fsTrigger" tabindex="0">
                <div class="fs-multiselect__tags" id="fsTags">
                  <span class="text-muted" id="fsPlaceholder">Select Festival Styles...</span>
                </div>
                <div class="d-flex align-items-center">
                  <span class="badge badge-primary fs-multiselect__count d-none" id="fsCount">0</span>
                  <i class="fa fa-chevron-down fs-multiselect__arrow" id="fsArrow"></i>
                </div>
              </div>

              {{-- Dropdown panel --}}
              <div class="fs-multiselect__dropdown d-none" id="fsDropdown">
                {{-- Search --}}
                <div class="fs-multiselect__search-wrap">
                  <i class="fa fa-search fs-multiselect__search-icon"></i>
                  <input type="text" class="fs-multiselect__search" id="fsSearch" placeholder="Search styles..." autocomplete="off">
                </div>

                {{-- Select All --}}
                @if($stylePresets->count() > 1)
                <label class="fs-multiselect__item fs-multiselect__item--all" id="fsSelectAllWrap">
                  <input type="checkbox" id="fsSelectAll" class="fs-multiselect__checkbox">
                  <span class="fs-multiselect__checkmark"></span>
                  <span class="font-weight-bold">Select All</span>
                  <span class="ml-auto badge badge-light">{{ $stylePresets->count() }}</span>
                </label>
                <div class="fs-multiselect__divider"></div>
                @endif

                {{-- Options --}}
                <div class="fs-multiselect__options" id="fsOptions">
                  @forelse($stylePresets as $preset)
                    <label class="fs-multiselect__item" data-value="{{ $preset->id }}" data-name="{{ strtolower($preset->name) }}">
                      <input type="checkbox" class="fs-multiselect__checkbox fs-opt-checkbox"
                             value="{{ $preset->id }}"
                             @checked(in_array($preset->id, old('style_preset_ids', $selectedStylePresetIds), false))>
                      <span class="fs-multiselect__checkmark"></span>
                      <span>{{ $preset->name }}</span>
                      @if($preset->product_required)
                        <span class="badge badge-warning ml-2" style="font-size:10px">Product required</span>
                      @endif
                    </label>
                  @empty
                    <div class="text-muted text-center py-3"><i class="fa fa-info-circle mr-1"></i> No active Festival Style exists. Add one from Festival Styles first.</div>
                  @endforelse
                  <div class="text-muted text-center py-3 d-none" id="fsNoResults"><i class="fa fa-search mr-1"></i> No styles found</div>
                </div>
              </div>
            </div>
            <small class="form-text text-muted">Click to select multiple styles. Festival Style Prompt is managed centrally from the sidebar.</small>
          </div>
        </div>

        <style>
          .fs-multiselect { position: relative; }
          .fs-multiselect__trigger {
            display: flex; align-items: center; justify-content: space-between;
            min-height: 42px; padding: 6px 12px; cursor: pointer;
            border: 1px solid #ced4da; border-radius: 6px; background: #fff;
            transition: border-color .2s, box-shadow .2s;
          }
          .fs-multiselect__trigger:hover { border-color: #80bdff; }
          .fs-multiselect__trigger.open { border-color: #80bdff; box-shadow: 0 0 0 .2rem rgba(0,123,255,.25); border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
          .fs-multiselect__tags { display: flex; flex-wrap: wrap; gap: 4px; flex: 1; }
          .fs-multiselect__tag {
            display: inline-flex; align-items: center; gap: 4px;
            background: #e7f1ff; color: #0d6efd; border-radius: 4px;
            padding: 2px 8px; font-size: 12px; font-weight: 500;
            animation: fsTagIn .2s ease;
          }
          @keyframes fsTagIn { from { opacity: 0; transform: scale(.8); } to { opacity: 1; transform: scale(1); } }
          .fs-multiselect__tag-remove { cursor: pointer; font-size: 14px; line-height: 1; opacity: .6; transition: opacity .15s; }
          .fs-multiselect__tag-remove:hover { opacity: 1; }
          .fs-multiselect__count { font-size: 11px; margin-right: 8px; }
          .fs-multiselect__arrow { font-size: 12px; color: #6c757d; transition: transform .25s ease; }
          .fs-multiselect__arrow.open { transform: rotate(180deg); }

          .fs-multiselect__dropdown {
            position: absolute; top: 100%; left: 0; right: 0; z-index: 1050;
            background: #fff; border: 1px solid #80bdff; border-top: none;
            border-bottom-left-radius: 6px; border-bottom-right-radius: 6px;
            box-shadow: 0 6px 20px rgba(0,0,0,.12);
            max-height: 300px; overflow: hidden; display: flex; flex-direction: column;
            animation: fsDropIn .2s ease;
          }
          @keyframes fsDropIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
          .fs-multiselect__search-wrap { position: relative; padding: 8px; border-bottom: 1px solid #eee; }
          .fs-multiselect__search-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #adb5bd; font-size: 13px; }
          .fs-multiselect__search {
            width: 100%; border: 1px solid #e9ecef; border-radius: 4px;
            padding: 6px 10px 6px 30px; font-size: 13px; outline: none;
            transition: border-color .2s;
          }
          .fs-multiselect__search:focus { border-color: #80bdff; }

          .fs-multiselect__divider { height: 1px; background: #eee; }
          .fs-multiselect__options { overflow-y: auto; max-height: 220px; padding: 4px 0; }

          .fs-multiselect__item {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 12px; margin: 0; cursor: pointer; font-size: 13px;
            transition: background .15s;
          }
          .fs-multiselect__item:hover { background: #f0f6ff; }
          .fs-multiselect__item--all { padding: 10px 12px; background: #fafafa; }

          .fs-multiselect__checkbox { display: none; }
          .fs-multiselect__checkmark {
            width: 18px; height: 18px; min-width: 18px; border: 2px solid #ced4da;
            border-radius: 4px; display: flex; align-items: center; justify-content: center;
            transition: all .2s ease; position: relative;
          }
          .fs-multiselect__checkbox:checked + .fs-multiselect__checkmark {
            background: #0d6efd; border-color: #0d6efd;
          }
          .fs-multiselect__checkbox:checked + .fs-multiselect__checkmark::after {
            content: ''; display: block; width: 5px; height: 9px;
            border: solid #fff; border-width: 0 2px 2px 0;
            transform: rotate(45deg); margin-top: -1px;
          }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
          const trigger = document.getElementById('fsTrigger');
          const dropdown = document.getElementById('fsDropdown');
          const arrow = document.getElementById('fsArrow');
          const search = document.getElementById('fsSearch');
          const options = document.getElementById('fsOptions');
          const tagsEl = document.getElementById('fsTags');
          const placeholder = document.getElementById('fsPlaceholder');
          const countBadge = document.getElementById('fsCount');
          const hiddenContainer = document.getElementById('fsSelectedInputs');
          const selectAll = document.getElementById('fsSelectAll');
          const noResults = document.getElementById('fsNoResults');
          const checkboxes = document.querySelectorAll('.fs-opt-checkbox');
          const items = document.querySelectorAll('.fs-multiselect__item[data-value]');

          function toggleDropdown(show) {
            const isOpen = typeof show === 'boolean' ? show : dropdown.classList.contains('d-none');
            dropdown.classList.toggle('d-none', !isOpen);
            trigger.classList.toggle('open', isOpen);
            arrow.classList.toggle('open', isOpen);
            if (isOpen) setTimeout(() => search.focus(), 50);
          }

          trigger.addEventListener('click', () => toggleDropdown());
          trigger.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleDropdown(); } });

          document.addEventListener('click', (e) => {
            if (!document.getElementById('fsMultiselect').contains(e.target)) toggleDropdown(false);
          });

          // Search filter
          search.addEventListener('input', () => {
            const q = search.value.toLowerCase().trim();
            let visibleCount = 0;
            items.forEach(item => {
              const match = item.dataset.name.includes(q);
              item.style.display = match ? '' : 'none';
              if (match) visibleCount++;
            });
            noResults.classList.toggle('d-none', visibleCount > 0);
          });

          // Select All
          if (selectAll) {
            selectAll.addEventListener('change', () => {
              const checked = selectAll.checked;
              items.forEach(item => {
                if (item.style.display !== 'none') {
                  item.querySelector('.fs-opt-checkbox').checked = checked;
                }
              });
              syncUI();
            });
          }

          // Individual checkbox change
          checkboxes.forEach(cb => cb.addEventListener('change', syncUI));

          function syncUI() {
            const selected = [];
            checkboxes.forEach(cb => { if (cb.checked) selected.push({ id: cb.value, name: cb.closest('.fs-multiselect__item').querySelector('span:nth-child(3)').textContent.trim() }); });

            // Update hidden inputs
            hiddenContainer.innerHTML = '';
            selected.forEach(s => {
              const inp = document.createElement('input');
              inp.type = 'hidden'; inp.name = 'style_preset_ids[]'; inp.value = s.id;
              hiddenContainer.appendChild(inp);
            });

            // Update tags
            tagsEl.querySelectorAll('.fs-multiselect__tag').forEach(t => t.remove());
            placeholder.style.display = selected.length ? 'none' : '';
            selected.forEach(s => {
              const tag = document.createElement('span');
              tag.className = 'fs-multiselect__tag';
              tag.innerHTML = s.name + ' <span class="fs-multiselect__tag-remove" data-id="'+s.id+'">&times;</span>';
              tagsEl.appendChild(tag);
            });

            // Update count badge
            countBadge.textContent = selected.length;
            countBadge.classList.toggle('d-none', selected.length === 0);

            // Update Select All state
            if (selectAll) {
              const visibleCheckboxes = [...checkboxes].filter(cb => cb.closest('.fs-multiselect__item').style.display !== 'none');
              const allChecked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(cb => cb.checked);
              selectAll.checked = allChecked;
            }
          }

          // Tag remove click
          tagsEl.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('.fs-multiselect__tag-remove');
            if (removeBtn) {
              e.stopPropagation();
              const id = removeBtn.dataset.id;
              const cb = [...checkboxes].find(c => c.value === id);
              if (cb) { cb.checked = false; syncUI(); }
            }
          });

          // Init on page load
          syncUI();
        });
        </script>

        <div class="row">
          <div class="col-md-5 form-group">
            <label class="d-block">Sizes visible for this festival</label>
            @foreach($sizeOptions as $key => $label)
              <label class="mr-3"><input type="checkbox" name="allowed_size_keys[]" value="{{ $key }}" @checked(in_array($key, old('allowed_size_keys', $config->allowed_size_keys ?? []), true))> {{ $label }}</label>
            @endforeach
          </div>
          <div class="col-md-3 form-group"><label>Maximum products</label><input class="form-control" type="number" min="1" max="3" name="max_products" value="{{ old('max_products', $config->max_products) }}" required></div>
          <div class="col-md-4 form-group"><label>User change instruction limit</label><input class="form-control" type="number" min="50" max="1000" name="max_user_instruction_characters" value="{{ old('max_user_instruction_characters', $config->max_user_instruction_characters) }}" required></div>
        </div>

        <div class="mb-3">
          <label class="mr-3"><input type="checkbox" name="allow_product_upload" value="1" @checked(old('allow_product_upload', $config->allow_product_upload))> Allow users to upload a new product photo</label>
          <label><input type="checkbox" name="require_product_name_for_upload" value="1" @checked(old('require_product_name_for_upload', $config->require_product_name_for_upload))> Require a product name for uploaded photos</label>
        </div>
        <button class="btn btn-primary"><i class="fa fa-save mr-1"></i> Save festival AI rules</button>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-white"><strong>Selected Festival Styles</strong></div>
    <div class="card-body">
      @forelse($styles->whereNotNull('festival_ai_style_preset_id') as $style)
        <span class="badge badge-{{ $style->status ? 'primary' : 'secondary' }} mr-2 mb-2 p-2">{{ $style->name }}{{ $style->status ? '' : ' (hidden)' }}</span>
      @empty
        <span class="text-muted">No Festival Style selected yet. Create a style in the sidebar, then select it above.</span>
      @endforelse
    </div>
  </div>
</div>
@endsection
