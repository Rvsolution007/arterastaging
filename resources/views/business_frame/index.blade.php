@extends("layouts.app")

@section('extra_css')
<link href="{{ asset('assets/css/frame.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/clean-switch.css')}}">

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

/* ===== Custom Posts Dashboard ===== */
.cf-container {
    font-family: 'Poppins', sans-serif;
    background-color: #f8fafc;
    padding: 1.5rem;
    min-height: 80vh;
}

/* Page Header */
.cf-page-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.75rem;
}
.cf-page-header .cf-icon-wrapper {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1.2rem;
    box-shadow: 0 4px 12px rgba(99,102,241,0.35);
}
.cf-page-header h4 {
    font-weight: 700; color: #1e293b; font-size: 1.5rem;
    letter-spacing: -0.025em; margin: 0;
}
.cf-page-header p {
    font-size: 0.85rem; color: #64748b; margin: 0;
}

/* Tab Navigation */
.cf-tabs-nav {
    display: flex; gap: 0.5rem;
    background: #ffffff;
    padding: 0.5rem;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04);
    margin-bottom: 1.75rem;
    flex-wrap: wrap;
}
.cf-tabs-nav .cf-tab-btn {
    padding: 0.65rem 1.5rem;
    border-radius: 10px;
    font-weight: 600; font-size: 0.875rem;
    color: #64748b;
    background: transparent;
    border: none; cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
    display: flex; align-items: center; gap: 0.5rem;
    text-decoration: none;
}
.cf-tabs-nav .cf-tab-btn:hover {
    color: #4f46e5; background: #eef2ff;
}
.cf-tabs-nav .cf-tab-btn.active {
    color: #ffffff;
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    box-shadow: 0 4px 12px rgba(99,102,241,0.35);
}
.cf-tabs-nav .cf-tab-btn i { font-size: 0.95rem; }

/* Panel Card */
.cf-panel {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.03), 0 4px 6px -2px rgba(0,0,0,0.02);
    overflow: hidden;
    margin-bottom: 1.5rem;
}
.cf-panel-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: 0.75rem;
}
.cf-panel-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
}
.cf-panel-icon.purple { background: #ede9fe; color: #7c3aed; }
.cf-panel-icon.blue { background: #e0f2fe; color: #0284c7; }
.cf-panel-icon.green { background: #d1fae5; color: #059669; }
.cf-panel-icon.orange { background: #ffedd5; color: #ea580c; }
.cf-panel-icon.rose { background: #fce7f3; color: #db2777; }

.cf-panel-title {
    font-size: 1.1rem; font-weight: 700; color: #1e293b; margin: 0;
}
.cf-panel-body {
    padding: 1.5rem;
}

/* Form Styling */
.cf-form-group { margin-bottom: 1.25rem; }
.cf-form-group label {
    font-size: 0.8rem; font-weight: 600;
    color: #475569; text-transform: uppercase;
    letter-spacing: 0.05em; margin-bottom: 0.5rem;
    display: block;
}
.cf-input, .cf-textarea, .cf-select {
    width: 100%;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.65rem 1rem;
    font-size: 0.875rem; font-family: 'Poppins', sans-serif;
    color: #334155; background-color: #f8fafc;
    transition: all 0.2s ease;
}
.cf-input:focus, .cf-textarea:focus, .cf-select:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    background-color: #ffffff;
}
.cf-textarea { resize: vertical; min-height: 120px; }

/* Dynamic Tags */
.cf-tags-wrapper {
    display: flex; flex-wrap: wrap; gap: 0.4rem;
    margin-bottom: 0.75rem;
}
.cf-tag-chip {
    display: inline-flex; align-items: center; gap: 0.3rem;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem; font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1.5px solid transparent;
}
.cf-tag-chip.col-tag {
    background: #eef2ff; color: #4338ca; border-color: #c7d2fe;
}
.cf-tag-chip.col-tag:hover {
    background: #4338ca; color: #fff; border-color: #4338ca;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(67,56,202,0.25);
}
.cf-tag-chip.product-tag {
    background: #fef3c7; color: #92400e; border-color: #fcd34d;
}
.cf-tag-chip.product-tag:hover {
    background: #92400e; color: #fff; border-color: #92400e;
    transform: translateY(-1px);
}

/* Buttons */
.cf-btn-primary {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    color: white; border: none;
    padding: 0.65rem 1.5rem;
    border-radius: 10px;
    font-weight: 600; font-size: 0.875rem;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 6px -1px rgba(79,70,229,0.3);
    display: inline-flex; align-items: center; gap: 0.5rem;
}
.cf-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px -3px rgba(79,70,229,0.4);
}
.cf-btn-danger {
    background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
    color: white; border: none;
    padding: 0.4rem 0.85rem;
    border-radius: 8px;
    font-weight: 500; font-size: 0.75rem;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(225,29,72,0.25);
}
.cf-btn-danger:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(225,29,72,0.35);
}
.cf-btn-edit {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white; border: none;
    padding: 0.4rem 0.85rem;
    border-radius: 8px;
    font-weight: 500; font-size: 0.75rem;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(37,99,235,0.25);
    margin-right: 4px;
}
.cf-btn-edit:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(37,99,235,0.35);
}

/* Table Styling */
.cf-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0; margin: 0;
}
.cf-table th {
    background: #f8fafc;
    padding: 0.85rem 1.25rem;
    font-size: 0.7rem; font-weight: 600;
    color: #64748b; text-transform: uppercase;
    letter-spacing: 0.05em; text-align: left;
    border-bottom: 1px solid #e2e8f0;
}
.cf-table td {
    padding: 0.85rem 1.25rem;
    font-size: 0.85rem; color: #334155;
    font-weight: 500;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.cf-table tbody tr { transition: background-color 0.2s ease; }
.cf-table tbody tr:hover { background-color: #f8fafc; }
.cf-table tbody tr:last-child td { border-bottom: none; }

/* Badge */
.cf-badge {
    padding: 0.3rem 0.65rem;
    border-radius: 20px;
    font-size: 0.7rem; font-weight: 500;
    display: inline-block;
}
.cf-badge-purple { background: #ede9fe; color: #6d28d9; }
.cf-badge-blue { background: #e0f2fe; color: #0369a1; }
.cf-badge-green { background: #d1fae5; color: #047857; }
.cf-badge-orange { background: #ffedd5; color: #c2410c; }

/* Empty State */
.cf-empty-state {
    text-align: center; padding: 3rem 1rem;
    color: #94a3b8;
}
.cf-empty-state i { font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.4; }
.cf-empty-state p { font-size: 0.9rem; font-weight: 500; }

/* Prompt Cell */
.cf-prompt-cell {
    max-width: 350px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 0.8rem; color: #64748b;
}

/* Sub Category Multi Select Override */
.cf-sub-cat-select { min-height: 44px; }

/* Selected tags display */
.cf-selected-tags-area {
    display: flex; flex-wrap: wrap; gap: 0.4rem;
    min-height: 28px; margin-top: 0.5rem;
}
.cf-selected-tag {
    display: inline-flex; align-items: center; gap: 0.3rem;
    padding: 0.3rem 0.7rem;
    border-radius: 20px; font-size: 0.72rem; font-weight: 500;
    background: #d1fae5; color: #047857;
    border: 1px solid #a7f3d0;
    animation: tagPop 0.25s ease;
}
.cf-selected-tag .remove-tag {
    cursor: pointer; font-weight: 700; font-size: 0.85rem;
    margin-left: 0.2rem; color: #dc2626;
}
@keyframes tagPop {
    0% { transform: scale(0.8); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

/* File Upload */
.cf-file-upload {
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #f8fafc;
    position: relative;
}
.cf-file-upload:hover {
    border-color: #6366f1; background: #eef2ff;
}
.cf-file-upload i { font-size: 2rem; color: #94a3b8; margin-bottom: 0.5rem; }
.cf-file-upload p { font-size: 0.85rem; color: #64748b; margin: 0; }
.cf-file-upload input[type=file] {
    position: absolute; top: 0; left: 0;
    width: 100%; height: 100%;
    opacity: 0; cursor: pointer;
}

/* Old Frame Tab Styling Overrides */
.ui-switcher {
  background-color: #bdc1c2;
  display: inline-block; top: 7px; height: 25px; width: 70px;
  border-radius: 15px; box-sizing: border-box;
  vertical-align: middle; position: relative; cursor: pointer;
  transition: border-color 0.25s;
  box-shadow: inset 1px 1px 1px rgba(0,0,0,0.15);
}
.ui-switcher:before {
  font-family: sans-serif; font-size: 13px; font-weight: 400;
  color: #ffffff; line-height: 1; display: inline-block;
  position: absolute; top: 6px; height: 15px; width: 27px; text-align: center;
}
.ui-switcher[aria-checked=false]:before { content: 'Free'; right: 10px; }
.ui-switcher[aria-checked=true]:before { content: 'Paid'; left: 10px; }
.ui-switcher[aria-checked=true] { background-color: #e91e63; }
.ui-switcher:after {
  background-color: #ffffff; content: '\0020';
  display: inline-block; position: absolute; top: 2px;
  height: 20px; width: 20px; border-radius: 50%;
  transition: left 0.25s;
}
.ui-switcher[aria-checked=false]:after { left: 5px; }
.ui-switcher[aria-checked=true]:after { left: 45px; }

.dropbtn { color: white; font-size: 16px; border: none; cursor: pointer; }
#myInput {
  box-sizing: border-box; background-image: url('searchicon.png');
  background-position: 14px 12px; background-repeat: no-repeat;
  font-size: 16px; padding: 14px 20px 12px 10px; border: none;
  width: 100%; border-bottom: 1px solid #056fed;
}
#myInput:focus { outline: 1px solid #056fed; }
.dropdown { position: relative; display: inline-block; }
.dropdown-content {
  display: none; position: absolute; background-color: #f6f6f6;
  min-width: 20px; overflow: auto; padding: 0 0;
  border: 1px solid #ddd; z-index: 1;
}
.dropdown-content a {
  color: black; padding: 7px 7px; text-decoration: none; display: block;
}
.dropdown-content a:hover { background-color: #056fed; color: white; }
.show { display: block; }
.select2-container--default .select2-selection--single { background-color:#007bff; }
.select2-container--default .select2-selection--single .select2-selection__rendered { color: white; }
.select2-container--default .select2-selection--single .select2-selection__arrow b { border-color: white transparent transparent transparent; }
.select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b { border-color: transparent transparent white transparent; }
</style>
@endsection

@section('content')
<div class="row" style="margin-right: 0px;">
  <div class="col-md-12">
    @if (count($errors) > 0)
    <div class="alert alert-danger">
      <ul>
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif
    <div class="cf-container pt-0">
      
      <div class="cf-tabs-nav mb-4" id="custom-tabs-four-tab" role="tablist">
          <a class="cf-tab-btn active" id="tabs-manage-purposes-tab" data-toggle="pill" href="#tabs-manage-purposes" role="tab" aria-controls="tabs-manage-purposes" aria-selected="true"><i class="fa-solid fa-list-check"></i> Manage Purposes</a>

          <a class="cf-tab-btn" id="tabs-purposes-tab" data-toggle="pill" href="#tabs-purposes" role="tab" aria-controls="tabs-purposes" aria-selected="false"><i class="fa-solid fa-robot"></i> Configure AI</a>
          
          <a class="cf-tab-btn" id="tabs-image-types-tab" data-toggle="pill" href="#tabs-image-types" role="tab" aria-controls="tabs-image-types" aria-selected="false"><i class="fa-solid fa-images"></i> Image Types</a>
          
          <a class="cf-tab-btn" id="tabs-custom-frames-tab" data-toggle="pill" href="#tabs-custom-frames" role="tab" aria-controls="tabs-custom-frames" aria-selected="false"><i class="fa-solid fa-file-zipper"></i> Custom Posts (ZIPs)</a>
      </div>

      <div>
        <div class="tab-content" id="custom-tabs-four-tabContent">
      
      <div class="card-body">
        <div class="tab-content" id="custom-tabs-four-tabContent">
            <!-- Manage Purposes Tab -->
            <div class="tab-pane fade show active" id="tabs-manage-purposes" role="tabpanel" aria-labelledby="tabs-manage-purposes-tab">
                <div class="row">
                    <div class="col-md-4">
                        <div class="cf-panel">
                            <div class="cf-panel-header">
                                <div class="cf-panel-icon purple"><i class="fa-solid fa-layer-group"></i></div>
                                <h5 class="cf-panel-title">Create New Purpose</h5>
                            </div>
                            <div class="cf-panel-body">
                                <form action="{{ url('admin/custom-frame-purpose-create') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="cf-form-group">
                                        <label>Purpose Name</label>
                                        <input type="text" name="name" class="cf-input" placeholder="e.g. Daily Motivation" required>
                                    </div>
                                    <div class="cf-form-group">
                                        <label>Purpose Icon</label>
                                        <div class="cf-file-upload">
                                            <i class="fa-solid fa-image"></i>
                                            <p>Click to select icon</p>
                                            <input type="file" name="icon" accept="image/*" required>
                                        </div>
                                    </div>
                                    <button type="submit" class="cf-btn-primary w-100 justify-content-center mt-2"><i class="fa-solid fa-plus"></i> Add Purpose</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="cf-panel">
                            <div class="cf-panel-header">
                                <div class="cf-panel-icon blue"><i class="fa-solid fa-list-check"></i></div>
                                <h5 class="cf-panel-title">All Purposes</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="cf-table">
                                    <thead><tr><th>Icon</th><th>Purpose Name</th><th>Templates Count</th><th class="text-right">Action</th></tr></thead>
                                    <tbody>
                                        @forelse($purposes as $p)
                                        <tr>
                                            <td>
                                                @if($p->icon)
                                                    <img src="{{ (App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') ? Storage::disk('spaces')->url('uploads/'.$p->icon) : asset('uploads/'.$p->icon) }}" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
                                                @else
                                                    <div style="width: 40px; height: 40px; border-radius: 8px; background: #e2e8f0; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-image text-muted"></i></div>
                                                @endif
                                            </td>
                                            <td><span class="cf-badge cf-badge-purple">{{$p->name}}</span></td>
                                            <td><span class="cf-badge cf-badge-orange">{{ \App\Models\BusinessCustomFrame::where('custom_frame_purpose_id', $p->id)->count() }} Templates</span></td>
                                            <td class="text-right">
                                                <button type="button" class="cf-btn-edit" onclick="openEditPurpose({{$p->id}}, '{{$p->name}}', '{{ $p->icon ? ((App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') ? Storage::disk('spaces')->url('uploads/'.$p->icon) : asset('uploads/'.$p->icon)) : '' }}')"><i class="fa-solid fa-pen-to-square"></i></button>
                                                <form action="{{ url('admin/custom-frame-purpose/'.$p->id) }}" method="POST" style="display:inline-block">
                                                    @method('DELETE')
                                                    @csrf
                                                    <button type="submit" class="cf-btn-danger" onclick="return confirm('Delete this purpose?')"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="4"><div class="cf-empty-state"><i class="fa-solid fa-box-open"></i><p>No Purposes Created Yet</p></div></td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Purpose Modal -->
            <div class="modal fade" id="editPurposeModal" tabindex="-1" role="dialog" aria-labelledby="editPurposeModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">
                        <div class="modal-header" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none; padding: 1.25rem 1.5rem;">
                            <h5 class="modal-title" id="editPurposeModalLabel" style="color: #fff; font-weight: 700; font-family: 'Poppins', sans-serif;">
                                <i class="fa-solid fa-pen-to-square mr-2"></i>Edit Purpose
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff; opacity: 1; text-shadow: none;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="editPurposeForm" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="modal-body" style="padding: 1.5rem;">
                                <div class="cf-form-group">
                                    <label style="font-weight: 600; color: #334155; font-size: 0.85rem;">Purpose Name</label>
                                    <input type="text" name="name" id="edit_purpose_name" class="cf-input" placeholder="Purpose Name" required>
                                </div>
                                <div class="cf-form-group mt-3">
                                    <label style="font-weight: 600; color: #334155; font-size: 0.85rem;">Current Icon</label>
                                    <div id="edit_purpose_icon_preview" style="margin-bottom: 10px;">
                                        <div style="width: 60px; height: 60px; border-radius: 10px; background: #e2e8f0; display:flex; align-items:center; justify-content:center;">
                                            <i class="fa-solid fa-image text-muted"></i>
                                        </div>
                                    </div>
                                    <label style="font-weight: 600; color: #334155; font-size: 0.85rem;">Change Icon <small class="text-muted">(optional)</small></label>
                                    <div class="cf-file-upload" style="min-height: 60px;">
                                        <i class="fa-solid fa-cloud-arrow-up"></i>
                                        <p style="margin: 0; font-size: 0.8rem;">Click to upload new icon</p>
                                        <input type="file" name="icon" accept="image/*" id="edit_purpose_icon_input">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 1rem 1.5rem;">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 10px; font-weight: 600;">Cancel</button>
                                <button type="submit" class="cf-btn-primary" style="border-radius: 10px;"><i class="fa-solid fa-save mr-1"></i> Update Purpose</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Configure AI Tab -->
            <div class="tab-pane fade" id="tabs-purposes" role="tabpanel" aria-labelledby="tabs-purposes-tab">
                <div class="row">
                    <div class="col-md-4">
                        <div class="cf-panel">
                            <div class="cf-panel-header">
                                <div class="cf-panel-icon purple"><i class="fa-solid fa-robot"></i></div>
                                <h5 class="cf-panel-title">Configure Purpose AI</h5>
                            </div>
                            <div class="cf-panel-body">
                                <form action="{{ url('admin/custom-frame-purpose') }}" method="POST">
                                    @csrf
                                    <div class="cf-form-group">
                                        <label>Select Purpose</label>
                                        <select name="purpose_id" class="cf-select" required>
                                            <option value="">-- Choose Purpose --</option>
                                            @foreach($purposes as $p)
                                                <option value="{{$p->id}}">{{$p->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="cf-form-group">
                                        <label>Product Data Requirement</label>
                                        <select name="data_requirement" class="cf-select" required>
                                            <option value="single_column">Product Name Only (Saves AI Tokens)</option>
                                            <option value="basic_columns">Product Name & Price</option>
                                            <option value="full_row">Full Product Details</option>
                                        </select>
                                    </div>
                                    <div class="cf-form-group">
                                        <label>AI Prompt Generator</label>
                                        <div class="cf-tags-wrapper">
                                            @foreach($dynamic_tags as $tag)
                                                <span class="cf-tag-chip col-tag" onclick="insertTag('{{$tag}}', 'ai_prompt')"><i class="fa-solid fa-plus-circle"></i> {{$tag}}</span>
                                            @endforeach
                                        </div>
                                        <textarea name="ai_prompt" id="ai_prompt" class="cf-textarea mt-2" placeholder="Write AI prompt using {tags}..." required></textarea>
                                    </div>



                                    <button type="submit" class="cf-btn-primary w-100 justify-content-center"><i class="fa-solid fa-save"></i> Save AI Config</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="cf-panel">
                            <div class="cf-panel-header">
                                <div class="cf-panel-icon blue"><i class="fa-solid fa-list-check"></i></div>
                                <h5 class="cf-panel-title">Configured Purposes</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="cf-table">
                                    <thead><tr><th>Purpose Name</th><th>Data Mode</th><th>AI Prompt Map</th></tr></thead>
                                    <tbody>
                                        @forelse($purposes->whereNotNull('ai_prompt') as $p)
                                        <tr>
                                            <td><span class="cf-badge cf-badge-purple">{{$p->name}}</span></td>
                                            <td><span class="cf-badge cf-badge-green">{{ str_replace('_', ' ', ucfirst($p->data_requirement ?? 'single_column')) }}</span></td>
                                            <td><div class="cf-prompt-cell" title="{{$p->ai_prompt}}">{{$p->ai_prompt}}</div></td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="3"><div class="cf-empty-state"><i class="fa-solid fa-box-open"></i><p>No Purposes Configured Yet</p></div></td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Image Types Tab -->
            <div class="tab-pane fade" id="tabs-image-types" role="tabpanel" aria-labelledby="tabs-image-types-tab">
                <div class="row">
                    <div class="col-md-4">
                        <div class="cf-panel">
                            <div class="cf-panel-header">
                                <div class="cf-panel-icon orange"><i class="fa-solid fa-images"></i></div>
                                <h5 class="cf-panel-title">Add Image Type</h5>
                            </div>
                            <div class="cf-panel-body">
                                <form action="{{ url('admin/custom-frame-image-type') }}" method="POST">
                                    @csrf
                                    <div class="cf-form-group">
                                        <label>Image Type Name</label>
                                        <input type="text" name="name" class="cf-input" placeholder="e.g. Transparent / Full" required>
                                    </div>
                                    <div class="cf-form-group">
                                        <label>Linked Business Sub Categories</label>
                                        <select name="business_sub_category_ids[]" class="cf-select select2 cf-sub-cat-select" multiple required style="width: 100%;">
                                            @foreach($business_sub_categories as $bsc)
                                                <option value="{{$bsc->id}}">{{$bsc->name}}</option>
                                            @endforeach
                                        </select>
                                        <div id="selected_sub_categories_tags" class="cf-selected-tags-area mt-2"></div>
                                    </div>
                                    <button type="submit" class="cf-btn-primary w-100 justify-content-center"><i class="fa-solid fa-plus"></i> Save Image Type</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                         <div class="cf-panel">
                            <div class="cf-panel-header">
                                <div class="cf-panel-icon green"><i class="fa-solid fa-clipboard-list"></i></div>
                                <h5 class="cf-panel-title">Available Image Types</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="cf-table">
                                    <thead><tr><th>Image Type</th><th>Categories Linked</th></tr></thead>
                                    <tbody>
                                        @forelse($image_types as $it)
                                        <tr>
                                            <td><span class="cf-badge cf-badge-orange">{{$it->name}}</span></td>
                                            <td>
                                                <div class="cf-selected-tags-area">
                                                @foreach($it->subCategories as $sub)
                                                    <span class="cf-selected-tag"><i class="fa-solid fa-tag"></i> {{$sub->name}}</span>
                                                @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="2"><div class="cf-empty-state"><i class="fa-solid fa-folder-open"></i><p>No Image Types Added</p></div></td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom Posts ZIP Uploads Tab -->
            <div class="tab-pane fade" id="tabs-custom-frames" role="tabpanel" aria-labelledby="tabs-custom-frames-tab">
                <div class="row">
                    <div class="col-md-4">
                        <div class="cf-panel">
                            <div class="cf-panel-header">
                                <div class="cf-panel-icon rose"><i class="fa-solid fa-file-zipper"></i></div>
                                <h5 class="cf-panel-title">Upload Frame Template</h5>
                            </div>
                            <div class="cf-panel-body">
                                <form action="{{ url('admin/business-custom-frame-zip') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="cf-form-group">
                                        <label>Linked Purpose</label>
                                        <select name="custom_frame_purpose_id" class="cf-select" required>
                                            @foreach($purposes as $p)
                                                <option value="{{$p->id}}">{{$p->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="cf-form-group">
                                        <label>Linked Image Type</label>
                                        <select name="custom_frame_image_type_id" class="cf-select" required>
                                            @foreach($image_types as $it)
                                                <option value="{{$it->id}}">{{$it->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="cf-form-group">
                                        <label>Template Zip File</label>
                                        <div class="cf-file-upload">
                                            <i class="fa-solid fa-cloud-arrow-up"></i>
                                            <p>Click to browse ZIP file</p>
                                            <input type="file" name="zip_file" accept=".zip" required>
                                        </div>
                                    </div>
                                    <button type="submit" class="cf-btn-primary w-100 justify-content-center mt-2"><i class="fa-solid fa-upload"></i> Process & Upload</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="cf-panel">
                            <div class="cf-panel-header">
                                <div class="cf-panel-icon blue"><i class="fa-solid fa-box-archive"></i></div>
                                <h5 class="cf-panel-title">Uploaded Custom Posts</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="cf-table">
                                    <thead><tr><th>Preview</th><th>Purpose & Image Type</th><th>ZIP File Reference</th><th>Uploaded At</th><th>Landing</th><th class="text-right">Action</th></tr></thead>
                                    <tbody>
                                        @forelse($business_custom_frames as $frame)
                                        @php
                                            $zipFolder = str_replace('.zip', '', $frame->zip_file_path);
                                            $previewUrl = (App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') 
                                                ? Storage::disk('spaces')->url('uploads/template/'.$zipFolder.'/preview.jpg') 
                                                : asset('uploads/template/'.$zipFolder.'/preview.jpg');
                                        @endphp
                                        <tr>
                                            <td>
                                                <img src="{{ $previewUrl }}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0; background: #f8fafc;" onerror="this.src='{{ asset('assets/images/placeholder.png') }}'; this.onerror=null;">
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column gap-1">
                                                    <span class="cf-badge cf-badge-purple" style="width:fit-content;">{{$frame->purpose->name ?? 'N/A'}}</span>
                                                    <span class="cf-badge cf-badge-orange" style="width:fit-content; margin-top:4px;">{{$frame->imageType->name ?? 'N/A'}}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column gap-1">
                                                    <div style="font-size: 12px; color: #6b7280;">Original: <strong style="color: #111;">{{ $frame->original_zip_name ?? 'N/A' }}</strong></div>
                                                    <div class="font-math" style="font-size: 11px; word-break: break-all; max-width: 250px;">System: {{ $frame->zip_file_path }}</div>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-size: 13px; color: #4b5563;">
                                                    <div>{{ $frame->created_at->format('d M Y') }}</div>
                                                    <div style="font-size: 11px; color: #9ca3af;">{{ $frame->created_at->format('h:i A') }}</div>
                                                </div>
                                            </td>
                                            <td>
                                                <label class="cl-switch cl-switch-green" title="Show on Landing Page">
                                                    <input type="checkbox" class="zip-landing-switch" data-id="{{$frame->id}}" value="1" @if($frame->show_on_landing==1) checked @endif>
                                                    <span class="switcher"></span>
                                                </label>
                                            </td>
                                            <td class="text-right">
                                                <form action="{{ url('admin/business-custom-frame-zip/'.$frame->id) }}" method="POST" style="display:inline-block">
                                                    @method('DELETE')
                                                    @csrf
                                                    <button type="submit" class="cf-btn-danger" onclick="return confirm('Delete this custom post template?')"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="6"><div class="cf-empty-state"><i class="fa-solid fa-file-excel"></i><p>No Frame ZIPs Uploaded</p></div></td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- End tab-content -->
      </div> <!-- End cf-container -->
    </div>
  </div>
</div>

  <!-- Modal -->
  <div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Delete</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to Delete ?</p>
        </div>
        <div class="modal-footer">
          @if(optional(Auth::user())->user_type == "Demo")
          <button type="button" class="btn btn-danger ToastrButton">Delete</button>
          @else
          <button id="del_btn" class="btn btn-danger" type="button" data-submit="">Delete</button>
          @endif
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  <!-- Modal -->

<!-- enableModal -->
<div id="enableModal" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Enable</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <p>Do you really want to perform?</p>
      </div>
      <div class="modal-footer">
        @if(optional(Auth::user())->user_type == "Demo")
        <button type="button" class="btn btn-danger ToastrButton">Yes</button>
        @else
        <button id="enable_btn" class="btn btn-danger" type="button">Yes</button>
        @endif
        <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
      </div>
    </div>
  </div>
</div>
<!-- enableModal -->

<!-- disableModal -->
<div id="disableModal" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Disable</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <p>Do you really want to perform?</p>
      </div>
      <div class="modal-footer">
        @if(optional(Auth::user())->user_type == "Demo")
        <button type="button" class="btn btn-danger ToastrButton">Yes</button>
        @else
        <button id="disable_btn" class="btn btn-danger" type="button">Yes</button>
        @endif
        <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
      </div>
    </div>
  </div>
</div>
<!-- disableModal -->

<!-- deleteModal -->
<div id="deleteModal" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Delete</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <p>Do you really want to perform?</p>
      </div>
      <div class="modal-footer">
        @if(optional(Auth::user())->user_type == "Demo")
        <button type="button" class="btn btn-danger ToastrButton">Yes</button>
        @else
        <button id="delete_btn" class="btn btn-danger" type="button">Yes</button>
        @endif
        <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
      </div>
    </div>
  </div>
</div>
<!-- deleteModal -->
@endsection

@section('script')
<script src="{{ asset('assets/js/jquery.switcher.js')}}"></script>
<script type="text/javascript">
    $('#business_category_dropdown').select2();
    var checkarray = [];
    $("#checkall").click(function() {
      checkarray = [];
      $("input[name='post_ids[]']").not(this).prop('checked', this.checked);
      $.each($("input[name='post_ids[]']:checked"), function() {
        checkarray.push($(this).val());
      });
      $("input[name='select_post']").val(checkarray);
    });
    
    $(".post_ids").click(function(e) {
      if ($(this).prop("checked") == true) {
        checkarray.push($(this).val());
      } else if ($(this).prop("checked") == false) {
        checkarray.splice($.inArray($(this).val(),checkarray), 1);
      }
      $("input[name='select_post']").val(checkarray);
    });

    $("#enable_btn").on("click",function(){
        $("#form1").submit();
    });

    $('#enableModal').on('show.bs.modal', function(e) {
        var id = e.relatedTarget.dataset.id;
        $("input[name='action_type']").val("enable");
    });

    $("#disable_btn").on("click",function(){
        $("#form1").submit();
    });

    $('#disableModal').on('show.bs.modal', function(e) {
        var id = e.relatedTarget.dataset.id;
        $("input[name='action_type']").val("disable");
    });

    $("#delete_btn").on("click",function(){
        $("#form1").submit();
    });

    $('#deleteModal').on('show.bs.modal', function(e) {
        var id = e.relatedTarget.dataset.id;
        $("input[name='action_type']").val("delete");
    });

    function myFunction() {
      document.getElementById("myDropdown").classList.toggle("show");
    }

    function filterFunction() {
      var input, filter, ul, li, a, i;
      input = document.getElementById("myInput");
      filter = input.value.toUpperCase();
      div = document.getElementById("myDropdown");
      a = div.getElementsByTagName("a");
      for (i = 0; i < a.length; i++) {
        txtValue = a[i].textContent || a[i].innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
          a[i].style.display = "";
        } else {
          a[i].style.display = "none";
        }
      }
    }

    $(function(){
      $('#category').select2();
      $.switcher('.checkbox2');
    
      // $('#category').on('change', function () {
      //   var id = $(this).val();
        
      //   $.ajax({
      //     type: "GET",
      //     url: "{{url('admin/get-category-post')}}",
      //     data: {id : id},
      //     headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      //     success: function(data) {
      //         //console.log(data);
      //         $('#frame_data').html(data);
      //     },
      //   });
      // });

      $(".checkbox2").change(function(){
        var checked = $(this).is(':checked');
        var id = $(this).data("id");

        $.ajax({
          type: "POST",
          url: "{{url('admin/custom-post-type')}}",
          data: { checked : checked , id : id},
          headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
          success: function(data) {
            if(data == 1)
            {
              new PNotify({
                title: 'Success!',
                text: "Business Frame Set Paid",
                type: 'success'
              });
            }
            else
            {
              new PNotify({
                title: 'Success!',
                text: "Business Frame Set Free",
                type: 'success'
              });
            }
          },
        });
      });
    
      $("#del_btn").on("click",function(){
          var id=$(this).data("submit");
          $("#form_"+id).submit();
      });

      $('#myModal').on('show.bs.modal', function(e) {
          var id = e.relatedTarget.dataset.id;
          $("#del_btn").attr("data-submit",id);
      });

      $(".frame-switch").change(function(){
        var checked = $(this).is(':checked');
        var id = $(this).data("id");
        
        $.ajax({
          type: "POST",
          url: "{{url('admin/custom-post-status-bf')}}",
          data: { checked : checked , id : id},
          headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
          success: function(data) {
            new PNotify({
              title: 'Success!',
              text: "Business Frame Status Has Been Changed.",
              type: 'success'
            });
          },
        });
      });

      $(".zip-landing-switch").change(function(){
        var checked = $(this).is(':checked');
        var id = $(this).data("id");
        
        $.ajax({
          type: "POST",
          url: "{{url('admin/business-custom-frame-landing')}}",
          data: { checked : checked , id : id},
          headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
          success: function(data) {
            new PNotify({
              title: 'Success!',
              text: "Landing Visibility Has Been Changed.",
              type: 'success'
            });
          },
        });
      });

      // Initialize select2 securely
      $('.cf-sub-cat-select').select2();
      
      // Tags logic
      $('.cf-sub-cat-select').on('change', function() {
          var selected = $(this).select2('data');
          var html = '';
          if(selected) {
              selected.forEach(function(item) {
                  if (item.id) {
                      html += '<span class="cf-selected-tag"><i class="fa-solid fa-tag"></i> ' + item.text + '</span>';
                  }
              });
          }
          $('#selected_sub_categories_tags').html(html);
      });
      // Initial call
      setTimeout(function() {
          $('.cf-sub-cat-select').trigger('change');
      }, 100);
      // Tab switching logic for custom tabs
      $('.cf-tab-btn').on('click', function(e) {
          e.preventDefault();
          // Hide all panes
          $('.tab-pane').removeClass('show active');
          $('.cf-tab-btn').removeClass('active');
          
          // Show target pane
          $(this).addClass('active');
          $($(this).attr('href')).addClass('show active');
      });

    });

    function openEditPurpose(id, name, iconUrl) {
        document.getElementById('edit_purpose_name').value = name;
        document.getElementById('editPurposeForm').action = '{{ url("admin/custom-frame-purpose") }}/' + id;
        document.getElementById('edit_purpose_icon_input').value = '';

        var previewDiv = document.getElementById('edit_purpose_icon_preview');
        if (iconUrl && iconUrl.length > 0) {
            previewDiv.innerHTML = '<img src="' + iconUrl + '" style="width: 60px; height: 60px; border-radius: 10px; object-fit: cover; border: 2px solid #e2e8f0;">';
        } else {
            previewDiv.innerHTML = '<div style="width: 60px; height: 60px; border-radius: 10px; background: #e2e8f0; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-image text-muted"></i></div>';
        }

        // Live preview when new file is selected
        document.getElementById('edit_purpose_icon_input').onchange = function(e) {
            if (e.target.files && e.target.files[0]) {
                var reader = new FileReader();
                reader.onload = function(ev) {
                    previewDiv.innerHTML = '<img src="' + ev.target.result + '" style="width: 60px; height: 60px; border-radius: 10px; object-fit: cover; border: 2px solid #6366f1;">';
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        };

        $('#editPurposeModal').modal('show');
    }

    function insertTag(tag, textAreaId) {
        var txtarea = document.getElementById(textAreaId);
        var scrollPos = txtarea.scrollTop;
        var caretPos = txtarea.selectionStart;

        var front = (txtarea.value).substring(0, caretPos);
        var back = (txtarea.value).substring(txtarea.selectionEnd, txtarea.value.length);
        txtarea.value = front + tag + back;
        caretPos = caretPos + tag.length;
        txtarea.selectionStart = caretPos;
        txtarea.selectionEnd = caretPos;
        txtarea.focus();
        txtarea.scrollTop = scrollPos;
    }
</script>
@endsection
