@extends('layouts.app')

@section('extra_css')
    @include('partials.modern_admin_css')
@endsection


@section('content')
<div class="modern-ui-wrapper container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-1"><i class="fa fa-th-large text-primary mr-1"></i> Custom Post Types</h3>
      <p class="text-muted mb-0">Manage app AI Types and ZIP Custom Post Purposes from one place. Their storage stays separate so existing templates and AI batches keep working.</p>
    </div>
    <div>
      <a href="{{ route('custom_post_styles.index') }}" class="btn btn-outline-primary mr-1"><i class="fa fa-palette mr-1"></i> Custom Post Styles</a>
      <a href="{{ route('custom_post_types.create') }}" class="btn btn-primary"><i class="fa fa-plus mr-1"></i> Add AI Custom Post Type</a>
    </div>
  </div>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <div class="alert alert-info border-0 shadow-sm">
    <strong>App flow:</strong> Custom Post Type → matching Category General Data and Brief → only that Type's linked Custom Post Styles → AI Preview. Each Type also decides its Header &amp; Footer Style, allowed sizes, product rule and change-instruction limit.
  </div>

  <ul class="nav nav-pills mb-3" id="custom-post-type-tabs" role="tablist">
    <li class="nav-item">
      <a class="nav-link active" id="ai-post-types-tab" data-toggle="pill" href="#ai-post-types" role="tab" aria-controls="ai-post-types" aria-selected="true"><i class="fa fa-magic mr-1"></i> AI Post Types</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="zip-post-purposes-tab" data-toggle="pill" href="#zip-post-purposes" role="tab" aria-controls="zip-post-purposes" aria-selected="false"><i class="fa fa-file-archive-o mr-1"></i> ZIP Post Purposes</a>
    </li>
  </ul>

  <div class="tab-content" id="custom-post-type-tabs-content">
    <div class="tab-pane fade show active" id="ai-post-types" role="tabpanel" aria-labelledby="ai-post-types-tab">
      <div class="card shadow-sm"><div class="card-header bg-white"><strong>AI Custom Post Types</strong><small class="text-muted ml-2">Mobile Custom tab cards and AI editable-image rules</small></div><div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Custom Post Type</th><th>Header &amp; Footer</th><th>Connected Category</th><th>Connected Subcategory</th><th>Linked styles</th><th>Product rule</th><th>Status</th><th class="text-right">Action</th></tr></thead>
      <tbody>
        @forelse($purposes as $purpose)
          @php
            $connectedCategories = $purpose->scopes->map(fn ($scope) => $scope->category)->filter()->unique('id')->values();
            $connectedSubcategories = $purpose->scopes->map(fn ($scope) => $scope->subCategory)->filter()->unique('id')->values();
          @endphp
          <tr>
            <td class="align-middle"><strong>{{ $purpose->title }}</strong><br><small class="text-muted">{{ $purpose->description ?: 'No app description' }}</small></td>
            <td class="align-middle">{{ optional($purpose->headerFooterStyle)->name ?: 'Not selected' }}</td>
            <td class="align-middle">
              @forelse($connectedCategories as $category)
                <span class="badge badge-light border mr-1 mb-1">{{ $category->name }}</span>
              @empty
                <span class="text-muted">Not connected</span>
              @endforelse
            </td>
            <td class="align-middle">
              @forelse($connectedSubcategories as $subcategory)
                <span class="badge badge-info mr-1 mb-1">{{ $subcategory->name }}</span>
              @empty
                <span class="text-muted">Not connected</span>
              @endforelse
            </td>
            <td class="align-middle">{{ $purpose->styles_count }}</td>
            <td class="align-middle">{{ $purpose->product_upload_enabled ? ($purpose->product_required ? 'Required' : 'Optional') . ' · max ' . $purpose->max_product_references : 'Off' }}</td>
            <td class="align-middle"><span class="badge badge-{{ $purpose->status ? 'success' : 'secondary' }}">{{ $purpose->status ? 'Active' : 'Hidden' }}</span></td>
            <td class="align-middle text-right">
              <a class="btn btn-outline-primary btn-sm" href="{{ route('custom_post_types.edit', $purpose) }}">Edit Studio</a>
              <form class="d-inline" method="POST" action="{{ route('custom_post_types.destroy', $purpose) }}" onsubmit="return confirm('Delete this Custom Post Type? Its reusable styles will remain in the library.');">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete</button></form>
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-center text-muted py-5">No AI Custom Post Type exists. Add the first card, its Type Prompt and style configuration.</td></tr>
        @endforelse
      </tbody>
    </table>
      </div>@if($purposes->hasPages())<div class="card-body">{{ $purposes->links() }}</div>@endif</div>
    </div>

    <div class="tab-pane fade" id="zip-post-purposes" role="tabpanel" aria-labelledby="zip-post-purposes-tab">
      <div class="card shadow-sm mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <div>
        <strong>ZIP Custom Post Purposes</strong>
        <small class="text-muted d-block">Purpose, legacy AI prompt and uploaded Custom Post ZIP templates</small>
      </div>
      <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#createZipPurposeModal"><i class="fa fa-plus mr-1"></i> Add ZIP Purpose</button>
    </div>
    <div class="card-body border-bottom bg-light">
      <i class="fa fa-info-circle text-primary mr-1"></i>
      These records stay separate from AI Custom Post Types because uploaded ZIP templates and their background AI batches already reference them. Manage both here; use <strong>Manage ZIP Posts</strong> for the actual ZIP files.
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr><th>Icon</th><th>ZIP Purpose</th><th>ZIP templates</th><th>ZIP AI configuration</th><th>Status</th><th class="text-right">Action</th></tr></thead>
        <tbody>
          @forelse($zipPurposes as $zipPurpose)
            @php($iconUrl = $zipPurpose->icon ? ((App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') ? Storage::disk('spaces')->url('uploads/'.$zipPurpose->icon) : asset('uploads/'.$zipPurpose->icon)) : '')
            <tr>
              <td class="align-middle">
                @if($iconUrl)<img src="{{ $iconUrl }}" alt="" style="width:38px;height:38px;object-fit:cover;border-radius:9px;">
                @else <span class="d-inline-flex align-items-center justify-content-center bg-light text-muted" style="width:38px;height:38px;border-radius:9px;"><i class="fa fa-image"></i></span>@endif
              </td>
              <td class="align-middle"><strong>{{ $zipPurpose->name }}</strong></td>
              <td class="align-middle"><span class="badge badge-info">{{ $zipPurpose->frames_count }} templates</span></td>
              <td class="align-middle">
                @if($zipPurpose->ai_prompt)<span class="badge badge-success">Configured</span><br><small class="text-muted">{{ str_replace('_', ' ', ucfirst($zipPurpose->data_requirement ?: 'single_column')) }}</small>
                @else <span class="badge badge-warning">Not configured</span>@endif
              </td>
              <td class="align-middle"><span class="badge badge-{{ $zipPurpose->status ? 'success' : 'secondary' }}">{{ $zipPurpose->status ? 'Active' : 'Hidden' }}</span></td>
              <td class="align-middle text-right text-nowrap">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('custom-post.index', ['filter_purpose_id' => $zipPurpose->id, 'tab' => 'custom-frames']) }}"><i class="fa fa-file-archive-o mr-1"></i> Manage ZIP Posts</a>
                <button type="button" class="btn btn-outline-primary btn-sm" data-id="{{ $zipPurpose->id }}" data-name="{{ $zipPurpose->name }}" data-prompt="{{ $zipPurpose->ai_prompt }}" data-requirement="{{ $zipPurpose->data_requirement ?: 'single_column' }}" onclick="openZipPurposeAi(this)" title="Configure ZIP AI"><i class="fa fa-robot"></i></button>
                <button type="button" class="btn btn-outline-primary btn-sm" data-id="{{ $zipPurpose->id }}" data-name="{{ $zipPurpose->name }}" data-icon="{{ $iconUrl }}" onclick="openZipPurposeEditor(this)" title="Edit purpose"><i class="fa fa-pencil"></i></button>
                <form class="d-inline" method="POST" action="{{ url('admin/custom-frame-purpose/'.$zipPurpose->id) }}" onsubmit="return confirm('Delete this ZIP Custom Post Purpose? Existing ZIP templates must be moved first.');">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm" title="Delete purpose"><i class="fa fa-trash"></i></button></form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-5">No ZIP Custom Post Purpose exists. Add one before uploading ZIP templates.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="createZipPurposeModal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form action="{{ url('admin/custom-frame-purpose-create') }}" method="POST" enctype="multipart/form-data">@csrf
  <div class="modal-header"><h5 class="modal-title">Add ZIP Custom Post Purpose</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
  <div class="modal-body"><div class="form-group"><label>Purpose name</label><input class="form-control" name="name" required maxlength="255" placeholder="Daily Motivation"></div><div class="form-group mb-0"><label>Purpose icon</label><input class="form-control-file" type="file" name="icon" accept="image/*" required></div></div>
  <div class="modal-footer"><button class="btn btn-primary"><i class="fa fa-save mr-1"></i>Add ZIP Purpose</button></div>
</form></div></div></div>

<div class="modal fade" id="editZipPurposeModal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form id="editZipPurposeForm" method="POST" enctype="multipart/form-data">@csrf @method('PUT')
  <div class="modal-header"><h5 class="modal-title">Edit ZIP Custom Post Purpose</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
  <div class="modal-body"><div class="form-group"><label>Purpose name</label><input id="edit_zip_purpose_name" class="form-control" name="name" required maxlength="255"></div><div class="form-group mb-0"><label>Change icon <small class="text-muted">(optional)</small></label><input class="form-control-file" type="file" name="icon" accept="image/*"><img id="edit_zip_purpose_icon" class="d-none mt-2" alt="" style="width:48px;height:48px;border-radius:9px;object-fit:cover;"></div></div>
  <div class="modal-footer"><button class="btn btn-primary"><i class="fa fa-save mr-1"></i>Save purpose</button></div>
</form></div></div></div>

<div class="modal fade" id="zipPurposeAiModal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><form action="{{ url('admin/custom-frame-purpose') }}" method="POST">@csrf
  <div class="modal-header"><h5 class="modal-title">Configure ZIP Purpose AI</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
  <div class="modal-body"><input id="zip_purpose_id" type="hidden" name="purpose_id"><div class="form-group"><label>Purpose</label><input id="zip_purpose_name" class="form-control" readonly></div><div class="form-group"><label>Product data requirement</label><select id="zip_purpose_requirement" class="form-control" name="data_requirement"><option value="single_column">Product Name Only (Saves AI Tokens)</option><option value="basic_columns">Product Name and Price</option><option value="full_row">Full Product Details</option></select></div><div class="form-group mb-0"><label>AI prompt generator</label><div class="mb-2">@foreach($dynamicTags as $tag)<button class="btn btn-sm btn-outline-primary mr-1 mb-1 zip-tag" type="button" data-zip-tag="{{ $tag }}"><i class="fa fa-plus-circle"></i> {{ $tag }}</button>@endforeach</div><textarea id="zip_purpose_prompt" class="form-control" rows="7" name="ai_prompt" required placeholder="Write the ZIP template AI prompt using tags..."></textarea></div></div>
  <div class="modal-footer"><button class="btn btn-primary"><i class="fa fa-save mr-1"></i>Save ZIP AI configuration</button></div>
</form></div></div></div>

<script>
  function openZipPurposeEditor(button) {
    document.getElementById('editZipPurposeForm').action = '{{ url('admin/custom-frame-purpose') }}/' + button.dataset.id;
    document.getElementById('edit_zip_purpose_name').value = button.dataset.name || '';
    const image = document.getElementById('edit_zip_purpose_icon');
    image.src = button.dataset.icon || '';
    image.classList.toggle('d-none', !button.dataset.icon);
    $('#editZipPurposeModal').modal('show');
  }
  function openZipPurposeAi(button) {
    document.getElementById('zip_purpose_id').value = button.dataset.id;
    document.getElementById('zip_purpose_name').value = button.dataset.name || '';
    document.getElementById('zip_purpose_requirement').value = button.dataset.requirement || 'single_column';
    document.getElementById('zip_purpose_prompt').value = button.dataset.prompt || '';
    $('#zipPurposeAiModal').modal('show');
  }
  document.addEventListener('click', function (event) {
    const tag = event.target.closest('.zip-tag');
    if (!tag) return;
    const field = document.getElementById('zip_purpose_prompt');
    const start = field.selectionStart || 0;
    const end = field.selectionEnd || 0;
    field.value = field.value.slice(0, start) + tag.dataset.zipTag + field.value.slice(end);
    field.focus();
    field.selectionStart = field.selectionEnd = start + tag.dataset.zipTag.length;
  });
</script>
@endsection
