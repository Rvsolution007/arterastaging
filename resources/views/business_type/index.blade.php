@extends("layouts.app")

@section('extra_css')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

.aim-container { font-family: 'Inter', sans-serif; padding: 1rem; }

/* Header */
.aim-header { display: flex; align-items: center; gap: 16px; margin-bottom: 2rem; flex-wrap: wrap; justify-content: space-between; }
.aim-header-left { display: flex; align-items: center; gap: 16px; }
.aim-header-icon { width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, #10b981, #34d399); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.5rem; box-shadow: 0 8px 24px rgba(16,185,129,0.3); }
.aim-header h2 { font-size: 1.5rem; font-weight: 800; color: #1e293b; margin: 0; }
.aim-header p { font-size: 0.85rem; color: #64748b; margin: 0; }

.aim-header-right { display: flex; gap: 10px; }

/* Buttons */
.aim-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 10px; font-size: 0.85rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; font-family: 'Inter', sans-serif; text-decoration: none; }
.aim-btn-primary { background: linear-gradient(135deg, #10b981, #34d399); color: #fff; box-shadow: 0 4px 16px rgba(16,185,129,0.3); }
.aim-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(16,185,129,0.4); color: #fff; }
.aim-btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; box-shadow: 0 4px 16px rgba(239,68,68,0.3); }
.aim-btn-danger:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(239,68,68,0.4); color: #fff; }
.aim-btn:disabled { opacity: 0.6; cursor: not-allowed; }

/* Search Input */
.aim-search-bar { position: relative; max-width: 400px; width: 100%; margin-bottom: 1.5rem; }
.aim-search-bar i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
.aim-search-input { width: 100%; padding: 12px 16px 12px 40px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 0.85rem; font-family: 'Inter', sans-serif; transition: all 0.2s; background: #fff; }
.aim-search-input:focus { outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.1); }

/* Panels */
.aim-panel { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem; overflow: hidden; }

/* Table */
.aim-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.aim-table th { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; font-weight: 600; padding: 1rem 1.25rem; text-align: left; border-bottom: 1px solid #f1f5f9; background: #f8fafc; }
.aim-table td { padding: 1rem 1.25rem; font-size: 0.85rem; color: #334155; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.aim-table tr:hover td { background: #f8fafc; }

/* Custom Checkbox */
.custom-checkbox { position: relative; display: inline-block; width: 18px; height: 18px; }
.custom-checkbox input { opacity: 0; width: 0; height: 0; }
.custom-checkbox .checkmark { position: absolute; top: 0; left: 0; height: 18px; width: 18px; background-color: #fff; border: 2px solid #cbd5e1; border-radius: 4px; transition: all 0.2s; cursor: pointer; }
.custom-checkbox:hover input ~ .checkmark { border-color: #94a3b8; }
.custom-checkbox input:checked ~ .checkmark { background-color: #10b981; border-color: #10b981; }
.custom-checkbox .checkmark:after { content: ""; position: absolute; display: none; }
.custom-checkbox input:checked ~ .checkmark:after { display: block; }
.custom-checkbox .checkmark:after { left: 5px; top: 2px; width: 5px; height: 10px; border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg); }

/* Switch */
.switch { position: relative; display: inline-block; width: 40px; height: 22px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
.slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
input:checked + .slider { background-color: #10b981; }
input:checked + .slider:before { transform: translateX(18px); }

/* Actions */
.action-btn { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; border: none; cursor: pointer; }
.btn-edit { background: #d1fae5; color: #059669; }
.btn-delete { background: #fee2e2; color: #e11d48; }
.action-btn:hover { transform: scale(1.1); }

.category-icon { width: 40px; height: 40px; border-radius: 10px; object-fit: cover; }
.badge-category { background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 600; }
</style>
@endsection

@section('content')
<div class="aim-container">
    <!-- Header -->
    <div class="aim-header">
        <div class="aim-header-left">
            <div class="aim-header-icon"><i class="fa-solid fa-layer-group"></i></div>
            <div>
                <h2>Business Business Types</h2>
                <p>Manage all sub-categories mapped to your root categories</p>
            </div>
        </div>
        <div class="aim-header-right">
            <button id="bulk_delete_btn" class="aim-btn aim-btn-danger" style="display: none;" data-toggle="modal" data-target="#bulkDeleteModal">
                <i class="fa-solid fa-trash"></i> Delete Selected (<span id="selected_count">0</span>)
            </button>
            <a href="#" id="export_btn" class="aim-btn aim-btn-primary" style="background: linear-gradient(135deg, #3b82f6, #60a5fa); box-shadow: 0 4px 16px rgba(59,130,246,0.3);">
                <i class="fa-solid fa-file-export"></i> Export
            </a>
            <button class="aim-btn aim-btn-primary" data-toggle="modal" data-target="#importModal" style="background: linear-gradient(135deg, #f59e0b, #fbbf24); box-shadow: 0 4px 16px rgba(245,158,11,0.3);">
                <i class="fa-solid fa-file-import"></i> Import
            </button>
            <a href="{{ route('business-type.create')}}" class="aim-btn aim-btn-primary">
                <i class="fa-solid fa-plus"></i> Add Business Type
            </a>
        </div>
    </div>

    <!-- Search -->
    <div class="aim-search-bar">
        <i class="fa-solid fa-search"></i>
        <input type="text" id="search_input" class="aim-search-input" placeholder="Search Business Types by name...">
    </div>

    <!-- Table Panel -->
    <div class="aim-panel">
        <div class="table-responsive">
            <table class="aim-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">
                            <label class="custom-checkbox">
                                <input type="checkbox" id="select_all">
                                <span class="checkmark"></span>
                            </label>
                        </th>
                        <th style="width: 80px;"># ID</th>
                        <th>Business Type Details</th>
                        <th>Parent Sub Category</th>
                        <th>Parent Category</th>
                        <th>Connected Products</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="table_body">
                    @foreach($data as $row)
                    <tr>
                        <td>
                            <label class="custom-checkbox">
                                <input type="checkbox" class="row-checkbox" value="{{$row->id}}">
                                <span class="checkmark"></span>
                            </label>
                        </td>
                        <td><span style="color:#94a3b8; font-weight:600;">#{{$row->id}}</span></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                @if($row->icon)
                                <img class="category-icon" src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$row->icon)}} @else {{asset('uploads/'.$row->icon)}} @endif">
                                @else
                                <div class="category-icon" style="background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#94a3b8;"><i class="fa-solid fa-image"></i></div>
                                @endif
                                <span style="font-weight: 600; color:#1e293b;">{{$row->name}}</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge-category">{{ $row->business_sub_category ? $row->business_sub_category->name : '--' }}</span>
                        </td>
                        <td>
                            <span class="badge-category" style="background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;">{{ ($row->business_sub_category && $row->business_sub_category->business_category) ? $row->business_sub_category->business_category->name : '--' }}</span>
                        </td>
                        <td>
                            <span class="badge-category" style="background: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe;">
                                <i class="fa-solid fa-box"></i> {{ $row->products_count ?? 0 }}
                            </span>
                        </td>
                        <td>
                            <label class="switch">
                                <input type="checkbox" data-id="{{$row->id}}" class="status" @if($row->status==1) checked @endif>
                                <span class="slider"></span>
                            </label>
                        </td>
                        <td class="text-right">
                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                <a href="{{url('admin/business-type/'.$row->id.'/edit')}}" class="action-btn btn-edit" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                @if(Auth::user()->user_type == "Demo")
                                <button type="button" class="action-btn btn-delete ToastrButton" title="Delete">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                @else
                                <button type="button" class="action-btn btn-delete" data-toggle="modal" data-target="#myModal" data-id="{{$row->id}}" title="Delete">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                @endif
                            </div>
                            @if(Auth::user()->user_type != "Demo")
                            <form action="{{url('admin/business-type/'.$row->id)}}" id="form_{{$row->id}}" method="post" class="d-none">
                                @csrf
                                @method('DELETE')
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div id="pagination-wrapper" style="margin-top: 20px; display: flex; justify-content: center;">
            {{ $data->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Bulk Delete Modal -->
<div id="bulkDeleteModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-body" style="padding: 2rem; text-align: center;">
                <i class="fa-solid fa-dumpster-fire text-danger" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h5 style="font-weight: 700; color: #1e293b; margin-bottom: 0.5rem;">Bulk Delete</h5>
                <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 2rem;">Are you sure you want to delete <strong id="bulk_count_text">0</strong> Business Types? This action cannot be undone.</p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button type="button" class="aim-btn" style="background: #f1f5f9; color: #475569;" data-dismiss="modal">Cancel</button>
                    @if(optional(Auth::user())->user_type == "Demo")
                    <button type="button" class="aim-btn aim-btn-danger ToastrButton">Delete Selected</button>
                    @else
                    <button id="confirm_bulk_delete" class="aim-btn aim-btn-danger" type="button">Confirm Delete</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <h5 class="mb-3">Confirm Delete</h5>
                <p>Are you sure you want to delete this?</p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="$('#form_'+$('.btn-delete[data-target=\'#myModal\']').attr('data-id')).submit()">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('business-type.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Import Business Types</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <div class="form-group">
                        <label>Upload CSV File</label>
                        <input type="file" name="file" class="form-control" accept=".csv" required>
                        <small class="text-muted mt-2 d-block">Required Format: <br><b>ID, Business Type Details, Parent Sub Category, Parent Category, Status</b><br>Leave ID blank to create new (must map subcategory manually later if created without ID).</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background: #f59e0b; border: none;">Import CSV</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    $('#export_btn').on('click', function(e) {
        e.preventDefault();
        var query = $('#search_input').val();
        var exportUrl = "{{ route('business-type.export') }}";
        if (query) {
            exportUrl += "?query=" + encodeURIComponent(query);
        }
        window.location.href = exportUrl;
    });

    // Status Toggle
    $(document).on("change", ".status", function(){
      var checked = $(this).is(':checked');
      var id = $(this).data("id");
      $.ajax({
        type: "POST",
        url: "{{url('admin/business-type-status')}}",
        data: { checked : checked , id : id},
        headers: { 'X-CSRF-TOKEN': csrfToken },
        success: function(data) {
          new PNotify({ title: 'Success!', text: "Status updated.", type: 'success' });
        },
      });
    });

    // Multi-Select Logic
    function updateBulkActions() {
        var selectedCount = $('.row-checkbox:checked').length;
        if (selectedCount > 0) {
            $('#bulk_delete_btn').fadeIn(200);
            $('#selected_count').text(selectedCount);
            $('#bulk_count_text').text(selectedCount);
        } else {
            $('#bulk_delete_btn').fadeOut(200);
        }
    }

    $(document).on('change', '#select_all', function() {
        var isChecked = $(this).is(':checked');
        $('.row-checkbox').prop('checked', isChecked);
        updateBulkActions();
    });

    $(document).on('change', '.row-checkbox', function() {
        if (!$(this).is(':checked')) {
            $('#select_all').prop('checked', false);
        } else {
            if ($('.row-checkbox:checked').length === $('.row-checkbox').length) {
                $('#select_all').prop('checked', true);
            }
        }
        updateBulkActions();
    });

    $('#confirm_bulk_delete').on('click', function() {
        var selectedIds = [];
        $('.row-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) return;

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Deleting...');

        $.ajax({
            url: "{{ url('admin/business-type/bulk-delete') }}",
            type: "POST",
            data: { ids: selectedIds },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function(res) {
                if(res.success) {
                    location.reload();
                } else {
                    alert(res.message);
                    btn.prop('disabled', false).text('Confirm Delete');
                }
            },
            error: function() {
                alert("An error occurred.");
                btn.prop('disabled', false).text('Confirm Delete');
            }
        });
    });

    // Ajax Search and Pagination
    let searchTimeout;
    
    function fetchResults(url, query) {
        $.ajax({
            type: "POST",
            url: url,
            data: { query: query },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function(response) {
                if (response.success) {
                    var tbody = $('#table_body');
                    tbody.empty();
                    
                    if (response.data.length === 0) {
                        tbody.append('<tr><td colspan="6" class="text-center" style="padding:20px; color:#64748b;">No Business Types found.</td></tr>');
                    } else {
                        response.data.forEach(function(row) {
                            var iconHtml = '';
                            if (row.icon_url) {
                                iconHtml = `<img class="category-icon" src="${row.icon_url}">`;
                            } else {
                                iconHtml = `<div class="category-icon" style="background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#94a3b8;"><i class="fa-solid fa-image"></i></div>`;
                            }

                            var isChecked = row.status == 1 ? 'checked' : '';
                            var editUrl = "{{url('admin/business-type')}}/" + row.id + "/edit";
                            var deleteUrl = "{{url('admin/business-type')}}/" + row.id;

                            var tr = `
                                <tr>
                                    <td>
                                        <label class="custom-checkbox">
                                            <input type="checkbox" class="row-checkbox" value="${row.id}">
                                            <span class="checkmark"></span>
                                        </label>
                                    </td>
                                    <td><span style="color:#94a3b8; font-weight:600;">#${row.id}</span></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            ${iconHtml}
                                            <span style="font-weight: 600; color:#1e293b;">${row.name}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-category">${row.sub_category_name}</span>
                                    </td>
                                    <td>
                                        <span class="badge-category" style="background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;">${row.category_name}</span>
                                    </td>
                                    <td>
                                        <span class="badge-category" style="background: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe;">
                                            <i class="fa-solid fa-box"></i> ${row.products_count ?? 0}
                                        </span>
                                    </td>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" data-id="${row.id}" class="status" ${isChecked}>
                                            <span class="slider"></span>
                                        </label>
                                    </td>
                                    <td class="text-right">
                                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                            <a href="${editUrl}" class="action-btn btn-edit" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <button type="button" class="action-btn btn-delete" data-toggle="modal" data-target="#myModal" data-id="${row.id}" title="Delete">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                        <form action="${deleteUrl}" id="form_${row.id}" method="post" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            `;
                            tbody.append(tr);
                        });
                    }
                    
                    // Update pagination HTML
                    if(response.pagination) {
                        $('#pagination-wrapper').html(response.pagination);
                    } else {
                        $('#pagination-wrapper').empty();
                    }
                    
                    // Reset multi-select state
                    $('#select_all').prop('checked', false);
                    updateBulkActions();
                }
            }
        });
    }

    $('#search_input').on('keyup', function() {
        clearTimeout(searchTimeout);
        var query = $(this).val();
        searchTimeout = setTimeout(function() {
            fetchResults("{{url('admin/business-type/search')}}", query);
        }, 300);
    });

    // Handle Pagination clicks through Ajax
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        var query = $('#search_input').val();
        
        // Change url to point to search endpoint
        var ajaxUrl = "{{url('admin/business-type/search')}}";
        // Extract page parameter
        var page = new URL(url).searchParams.get("page");
        if(page) {
            ajaxUrl += "?page=" + page;
        }
        
        fetchResults(ajaxUrl, query);
    });
</script>
@endsection
