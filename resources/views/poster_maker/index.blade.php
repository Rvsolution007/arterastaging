@extends("layouts.app")

@section('extra_css')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

    .analytics-container {
        font-family: 'Poppins', sans-serif;
        padding: 1.5rem;
        background-color: #f8fafc;
        min-height: 100vh;
    }

    .page-title {
        font-weight: 700;
        color: #1e293b;
        font-size: 1.5rem;
        letter-spacing: -0.025em;
    }

    .btn-action-primary {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        border: none;
        padding: 0.5rem 1.25rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
    }

    .btn-action-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4);
        color: white;
    }

    .btn-action-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        border: none;
        padding: 0.5rem 1.25rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.3);
    }

    .btn-action-danger:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(220, 38, 38, 0.4);
        color: white;
    }

    .table-panel {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .custom-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin: 0;
    }

    .custom-table th {
        background: #f8fafc;
        padding: 1rem 1.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    .custom-table td {
        padding: 1rem 1.5rem;
        font-size: 0.875rem;
        color: #334155;
        font-weight: 500;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .custom-table tbody tr {
        transition: background-color 0.2s ease;
    }

    .custom-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .badge-soft {
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        background: #f1f5f9;
        color: #475569;
        display: inline-block;
    }

    .badge-soft-primary { background: #e0e7ff; color: #4338ca; }
    .badge-soft-success { background: #d1fae5; color: #059669; }

    .table-img-preview {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .table-img-preview:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .checkbox-custom {
        width: 1.1rem;
        height: 1.1rem;
        border-radius: 4px;
        border: 2px solid #cbd5e1;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        color: #64748b;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .action-btn:hover {
        background: #e2e8f0;
        color: #0f172a;
        transform: translateY(-1px);
    }
    
    .action-btn.delete:hover {
        background: #fee2e2;
        color: #ef4444;
        border-color: #fca5a5;
    }

    .ui-switcher {
        background-color: #cbd5e1;
        display: inline-block;
        height: 24px;
        width: 64px;
        border-radius: 12px;
        box-sizing: border-box;
        vertical-align: middle;
        position: relative;
        cursor: pointer;
        transition: background-color 0.25s;
    }

    .ui-switcher:before {
        font-family: 'Poppins', sans-serif;
        font-size: 11px;
        font-weight: 600;
        color: #ffffff;
        line-height: 1;
        display: inline-block;
        position: absolute;
        top: 6px;
        height: 12px;
        width: 24px;
        text-align: center;
    }

    .ui-switcher[aria-checked=false]:before { content: 'Free'; right: 8px; }
    .ui-switcher[aria-checked=true]:before { content: 'Paid'; left: 8px; }
    .ui-switcher[aria-checked=true] { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }

    .ui-switcher:after {
        background-color: #ffffff;
        content: '\0020';
        display: inline-block;
        position: absolute;
        top: 3px;
        height: 18px;
        width: 18px;
        border-radius: 50%;
        transition: left 0.25s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .ui-switcher[aria-checked=false]:after { left: 4px; }
    .ui-switcher[aria-checked=true]:after { left: 42px; }

    /* Pagination container matching modern style */
    .pagination-wrapper .pagination {
        margin: 0;
    }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
@endsection

<!-- Import Frames Modal -->
<div id="importFramesModal" class="modal fade" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
      <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; border-radius: 12px 12px 0 0;">
        <h4 class="modal-title" style="font-weight: 600; color: #1e293b;">Import Frames</h4>
        <button type="button" class="close" data-dismiss="modal" style="color: #64748b;">&times;</button>
      </div>
      <form action="{{ route('admin.poster_maker.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body" style="padding: 1.5rem;">
          <div class="form-group">
            <label style="font-weight: 600; color: #475569;">Upload Exported ZIP File</label>
            <div class="cf-file-upload" onclick="document.getElementById('import_file').click()" style="border: 2px dashed #cbd5e1; border-radius: 12px; padding: 2rem; text-align: center; cursor: pointer; transition: all 0.2s ease; background: #f8fafc;">
              <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2.5rem; color: #94a3b8; margin-bottom: 1rem;"></i>
              <p style="font-weight: 500; color: #475569; margin-bottom: 0.25rem;">Click to select the exported zip file</p>
              <p style="font-size: 0.75rem; color: #94a3b8;">Must contain data.json and templates folder</p>
              <input type="file" id="import_file" name="import_file" accept=".zip" required onchange="document.getElementById('import_file_name').innerText = this.files[0] ? this.files[0].name : '';" style="display: none;">
            </div>
            <p id="import_file_name" style="margin-top: 10px; font-weight: 600; text-align: center; color: #6366f1;"></p>
          </div>
        </div>
        <div class="modal-footer" style="border-top: 1px solid #e2e8f0;">
          <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 8px;">Cancel</button>
          <button type="submit" class="btn btn-primary" style="border-radius: 8px; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none;" onclick="this.innerHTML='<i class=\'fa-solid fa-spinner fa-spin\'></i> Importing...'; this.style.opacity='0.8';"><i class="fa-solid fa-download"></i> Import</button>
        </div>
      </form>
    </div>
  </div>
</div>

@section('content')
<div class="analytics-container">
    <div class="row align-items-center mb-4">
        <div class="col-md-5">
            <h4 class="page-title mb-0"><i class="fa-solid fa-layer-group mr-2 text-primary"></i> Custom Frames</h4>
        </div>
        <div class="col-md-7 text-right">
            <button type="button" id="bulkDeleteBtn" class="btn-action-danger mr-2" style="display: none;">
                <i class="fa-solid fa-trash mr-1"></i> Delete Selected (<span id="selectedCount">0</span>)
            </button>
            
            <div class="dropdown d-inline-block mr-2">
                <button class="btn-action-primary dropdown-toggle" type="button" id="importExportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background: #fff; border: 1px solid #e2e8f0; color: #475569;">
                    <i class="fa-solid fa-ellipsis-vertical mr-1"></i> Manage
                </button>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="importExportDropdown" style="border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
                    <a class="dropdown-item" href="#" id="exportSelectedBtn" style="padding: 10px 20px; font-weight: 500; color: #1e293b;"><i class="fa-solid fa-download" style="color: #6366f1; width: 20px;"></i> Export Selected</a>
                    <a class="dropdown-item" href="{{ route('admin.poster_maker.export') }}" style="padding: 10px 20px; font-weight: 500; color: #1e293b;"><i class="fa-solid fa-download" style="color: #6366f1; width: 20px;"></i> Export All</a>
                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#importFramesModal" style="padding: 10px 20px; font-weight: 500; color: #1e293b;"><i class="fa-solid fa-upload" style="color: #10b981; width: 20px;"></i> Import Frames</a>
                </div>
            </div>
            <a href="{{ route('template_builder.index', ['mode' => 'frame']) }}" class="btn-action-primary">
                <i class="fa-solid fa-plus mr-1"></i> Add New Frame
            </a>
        </div>
    </div>

    @if (count($errors) > 0)
    <div class="alert alert-danger" style="border-radius: 12px;">
        <ul class="mb-0">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
        </ul>
    </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="table-panel">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">
                                    <input type="checkbox" id="selectAll" class="checkbox-custom">
                                </th>
                                <th>Thumb</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Zip Name</th>
                                <th>Status</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $frame)
                            <tr>
                                <td>
                                    <input type="checkbox" class="checkbox-custom row-checkbox" value="{{ $frame->id }}">
                                </td>
                                <td>
                                    @php
                                        $url = (App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') 
                                            ? Storage::disk('spaces')->url('uploads/' . $frame->post_thumb) 
                                            : asset('uploads/' . $frame->post_thumb);
                                    @endphp
                                    <img src="{{ $url }}" class="table-img-preview img-preview-trigger" data-url="{{ $url }}" alt="Thumb" />
                                </td>
                                <td>
                                    <span class="font-weight-bold text-dark">{{ $frame->poster_category->name ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="badge-soft badge-soft-primary text-uppercase">{{ $frame->template_type }}</span>
                                </td>
                                <td>
                                    <code class="badge-soft" style="background:#f1f5f9; border: 1px solid #e2e8f0; color: #64748b;">{{ $frame->zip_name }}</code>
                                </td>
                                <td>
                                    <input class="checkbox2" type="checkbox" data-id="{{ $frame->id }}" value="1" @if($frame->paid == 1) checked @endif>
                                </td>
                                <td class="text-right">
                                    <a href="{{ url('admin/Frame/' . $frame->id . '/edit') }}" class="action-btn" title="Edit Legacy">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <a href="{{ route('template_builder.index', ['mode' => 'frame', 'frame_id' => $frame->id]) }}" class="action-btn text-success" title="Edit in Web Editor">
                                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                                    </a>
                                    <button type="button" class="action-btn text-info duplicate-btn ml-1" data-id="{{ $frame->id }}" data-zip="{{ $frame->zip_name }}" title="Duplicate">
                                        <i class="fa-solid fa-clone"></i>
                                    </button>
                                    <button type="button" data-id="{{ $frame->id }}" class="action-btn delete ml-1 btn_delete_a" data-toggle="modal" data-target="#myModal" title="Delete">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                    
                                    {!! Form::open(['url' => 'admin/Frame/' . $frame->id, 'method' => 'DELETE', 'class' => 'd-none', 'id' => 'form_' . $frame->id]) !!}
                                    {!! Form::hidden("id", $frame->id) !!}
                                    {!! Form::close() !!}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted border-0">
                                    <i class="fa-regular fa-folder-open mb-3" style="font-size: 2rem; color: #cbd5e1;"></i>
                                    <p class="mb-0">No frames found.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top pagination-wrapper bg-light d-flex justify-content-end">
                    {{ $data->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal (Single Item) -->
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
            <div class="modal-body text-center p-4">
                <div class="mb-3">
                    <i class="fa-solid fa-circle-exclamation text-danger" style="font-size: 3rem;"></i>
                </div>
                <h5 class="mb-3 font-weight-bold">Are you sure?</h5>
                <p class="text-muted mb-4">You are about to delete this frame. This action cannot be undone.</p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    @if(optional(Auth::user())->user_type == "Demo")
                        <button type="button" class="btn btn-danger ToastrButton ml-2" style="border-radius: 8px;">Delete</button>
                    @else
                        <button id="del_btn" class="btn btn-danger ml-2" type="button" data-submit="" style="border-radius: 8px;">Delete</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div id="previewModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden; border: none; background: transparent; box-shadow: none;">
            <div class="modal-body p-0 text-center position-relative">
                <button type="button" class="close position-absolute" data-dismiss="modal" style="right: -30px; top: -30px; color: white; opacity: 1; text-shadow: none; font-size: 2rem;">&times;</button>
                <img id="previewImageFull" src="" style="max-width: 100%; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);">
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="{{ asset('assets/js/jquery.switcher.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        // Initialize Switcher
        $.switcher('.checkbox2');

        // Setup Bulk Delete variables
        let selectedIds = [];

        function updateBulkDeleteBtn() {
            selectedIds = [];
            $('.row-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length > 0) {
                $('#selectedCount').text(selectedIds.length);
                $('#bulkDeleteBtn').fadeIn(200);
            } else {
                $('#bulkDeleteBtn').fadeOut(200);
            }
        }

        // Handle Export Selected
        $('#exportSelectedBtn').on('click', function(e) {
            e.preventDefault();
            if (selectedIds.length === 0) {
                toastr.warning('Please select at least one frame to export.');
                return;
            }
            let url = "{{ route('admin.poster_maker.export') }}?ids=" + selectedIds.join(',');
            window.location.href = url;
        });

        // Select All toggle
        $('#selectAll').on('change', function() {
            $('.row-checkbox').prop('checked', $(this).is(':checked'));
            updateBulkDeleteBtn();
        });

        // Individual row toggle
        $('.row-checkbox').on('change', function() {
            if (!$(this).is(':checked')) {
                $('#selectAll').prop('checked', false);
            } else if ($('.row-checkbox:checked').length === $('.row-checkbox').length) {
                $('#selectAll').prop('checked', true);
            }
            updateBulkDeleteBtn();
        });

        // Bulk Delete Action
        $('#bulkDeleteBtn').on('click', function() {
            @if(optional(Auth::user())->user_type == "Demo")
                toastr.error('This action is disabled in demo mode.');
                return;
            @endif

            swal({
                title: "Delete " + selectedIds.length + " frames?",
                text: "This will permanently remove the frames and their files. This cannot be undone.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#dc2626",
                confirmButtonText: "Yes, delete them!",
                closeOnConfirm: false,
                showLoaderOnConfirm: true
            }, function() {
                $.ajax({
                    url: "{{ route('admin.poster_maker.bulk_delete') }}",
                    type: "POST",
                    data: {
                        ids: selectedIds,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if(response.success) {
                            swal("Deleted!", response.message, "success");
                            setTimeout(() => { location.reload(); }, 1500);
                        } else {
                            swal("Error", response.message, "error");
                        }
                    },
                    error: function() {
                        swal("Error", "Something went wrong while deleting.", "error");
                    }
                });
            });
        });

        // Single Item Delete Model
        $("#del_btn").on("click", function () {
            var id = $(this).data("submit");
            $("#form_" + id).submit();
        });

        $('#myModal').on('show.bs.modal', function (e) {
            var id = e.relatedTarget.dataset.id;
            $("#del_btn").attr("data-submit", id);
        });

        // Image Preview Modal
        $(document).on('click', '.img-preview-trigger', function () {
            var url = $(this).data('url');
            $('#previewImageFull').attr('src', url);
            $('#previewModal').modal('show');
        });

        // Paid/Free Toggle
        $(document).on('change', ".checkbox2", function () {
            var checked = $(this).is(':checked');
            var id = $(this).data("id");

            $.ajax({
                type: "POST",
                url: "{{ url('admin/Frame-frame-type') }}",
                data: { checked: checked, id: id },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (data) {
                    if (data == 1) {
                        new PNotify({ title: 'Success!', text: "Custom Frame Set to Paid", type: 'success' });
                    } else {
                        new PNotify({ title: 'Success!', text: "Custom Frame Set to Free", type: 'success' });
                    }
                },
            });
        });
        // Duplicate Frame Action
        $(document).on('click', '.duplicate-btn', function() {
            var frameId = $(this).data('id');
            var currentZip = $(this).data('zip');
            
            swal({
                title: "Duplicate Frame",
                text: "Enter a unique ZIP name for the duplicate:",
                type: "input",
                showCancelButton: true,
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                inputPlaceholder: "e.g. " + currentZip + "_copy"
            }, function (inputValue) {
                if (inputValue === false) return false;
                if (inputValue === "") {
                    swal.showInputError("Zip Name is required!");
                    return false;
                }
                
                $.ajax({
                    url: "{{ route('admin.poster_maker.duplicate') }}",
                    type: "POST",
                    data: {
                        id: frameId,
                        zip_name: inputValue,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if(response.success) {
                            swal("Success!", "Frame duplicated successfully.", "success");
                            setTimeout(() => { location.reload(); }, 1500);
                        } else {
                            swal.showInputError(response.message);
                        }
                    },
                    error: function(xhr) {
                        if(xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.zip_name) {
                            swal.showInputError(xhr.responseJSON.errors.zip_name[0]);
                        } else if(xhr.responseJSON && xhr.responseJSON.message) {
                            swal.showInputError(xhr.responseJSON.message);
                        } else {
                            swal.showInputError("Something went wrong!");
                        }
                    }
                });
            });
        });

    });
</script>
@endsection