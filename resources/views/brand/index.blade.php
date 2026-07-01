@extends('layouts.app')

@section('extra_css')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

.aim-container { font-family: 'Inter', sans-serif; padding: 1rem; }

/* Header */
.aim-header { display: flex; align-items: center; gap: 16px; margin-bottom: 2rem; flex-wrap: wrap; justify-content: space-between; }
.aim-header-left { display: flex; align-items: center; gap: 16px; }
.aim-header-icon { width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, #6366f1, #8b5cf6); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.5rem; box-shadow: 0 8px 24px rgba(99,102,241,0.3); }
.aim-header h2 { font-size: 1.5rem; font-weight: 800; color: #1e293b; margin: 0; }
.aim-header p { font-size: 0.85rem; color: #64748b; margin: 0; }

.aim-header-right { display: flex; gap: 10px; }

/* Buttons */
.aim-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 10px; font-size: 0.85rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; font-family: 'Inter', sans-serif; text-decoration: none; }
.aim-btn-primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; box-shadow: 0 4px 16px rgba(99,102,241,0.3); }
.aim-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99,102,241,0.4); color: #fff; }
.aim-btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; box-shadow: 0 4px 16px rgba(239,68,68,0.3); }
.aim-btn-danger:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(239,68,68,0.4); color: #fff; }
.aim-btn:disabled { opacity: 0.6; cursor: not-allowed; }

/* Panels */
.aim-panel { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem; overflow: hidden; }

/* Table */
.aim-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.aim-table th { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; font-weight: 600; padding: 1rem 1.25rem; text-align: left; border-bottom: 1px solid #f1f5f9; background: #f8fafc; }
.aim-table td { padding: 1rem 1.25rem; font-size: 0.85rem; color: #334155; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.aim-table tr:hover td { background: #f8fafc; }

/* Actions */
.action-btn { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; border: none; cursor: pointer; }
.btn-edit { background: #d1fae5; color: #059669; }
.btn-delete { background: #fee2e2; color: #e11d48; }
.action-btn:hover { transform: scale(1.1); }

/* Custom Checkbox */
.custom-checkbox { position: relative; display: inline-block; width: 18px; height: 18px; }
.custom-checkbox input { opacity: 0; width: 0; height: 0; }
.custom-checkbox .checkmark { position: absolute; top: 0; left: 0; height: 18px; width: 18px; background-color: #fff; border: 2px solid #cbd5e1; border-radius: 4px; transition: all 0.2s; cursor: pointer; }
.custom-checkbox:hover input ~ .checkmark { border-color: #94a3b8; }
.custom-checkbox input:checked ~ .checkmark { background-color: #10b981; border-color: #10b981; }
.custom-checkbox .checkmark:after { content: ""; position: absolute; display: none; }
.custom-checkbox input:checked ~ .checkmark:after { display: block; }
.custom-checkbox .checkmark:after { left: 5px; top: 2px; width: 5px; height: 10px; border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg); }
</style>
@endsection

@section('content')
<div class="aim-container">
    <div class="aim-header">
        <div class="aim-header-left">
            <div class="aim-header-icon"><i class="fa-solid fa-copyright"></i></div>
            <div>
                <h2>Brands</h2>
                <p>Manage the master list of Brands</p>
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
            <a href="{{ url('admin/brand/create') }}" class="aim-btn aim-btn-primary">
                <i class="fa fa-plus"></i> Add Brand
            </a>
        </div>
    </div>

    @if(Session::has('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px;">
        <i class="fa fa-check-circle mr-2"></i> {{ Session::get('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    <!-- Search -->
    <div class="aim-search-bar" style="position: relative; max-width: 400px; width: 100%; margin-bottom: 1.5rem;">
        <i class="fa-solid fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
        <input type="text" id="search_input" style="width: 100%; padding: 12px 16px 12px 40px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 0.85rem; font-family: 'Inter', sans-serif; transition: all 0.2s; background: #fff;" placeholder="Search brands by name...">
    </div>

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
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="table_body">
                    @forelse($data as $row)
                    <tr>
                        <td>
                            <label class="custom-checkbox">
                                <input type="checkbox" class="row-checkbox" value="{{ $row->id }}">
                                <span class="checkmark"></span>
                            </label>
                        </td>
                        <td><span style="color:#94a3b8; font-weight:600;">#{{ $row->id }}</span></td>
                        <td>
                            <span style="font-weight: 600; color:#1e293b;">{{ $row->name }}</span>
                        </td>
                        <td><span style="color:#64748b; font-family: monospace; background:#f8fafc; padding:2px 6px; border-radius:4px;">{{ $row->slug }}</span></td>
                        <td>
                            @if($row->status)
                                <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 600;">Active</span>
                            @else
                                <span style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 600;">Inactive</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                <a href="{{ url('admin/brand/'.$row->id.'/edit') }}" class="action-btn btn-edit" title="Edit">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <button type="button" class="action-btn btn-delete" data-id="{{ $row->id }}" data-toggle="modal" data-target="#myModal" title="Delete">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                            <form action="{{ url('admin/brand/'.$row->id) }}" method="POST" class="d-none" id="form_{{ $row->id }}">
                                @csrf
                                @method('DELETE')
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div style="color: #94a3b8; font-size: 1.1rem;">
                                <i class="fa fa-copyright mb-3" style="font-size: 2rem;"></i><br>
                                No Brands Found
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div id="no_results" style="display: none; padding: 3rem; text-align: center; color: #94a3b8;">
                <i class="fa-solid fa-copyright mb-3" style="font-size: 3rem; opacity: 0.5;"></i><br>
                <h5 style="margin: 0; font-size: 1.1rem;">No brands found</h5>
            </div>
            <div id="pagination_links" class="d-flex justify-content-center mt-4 mb-4">
                {{ $data->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<!-- Bulk Delete Modal -->
<div id="bulkDeleteModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-body" style="padding: 2rem; text-align: center;">
                <i class="fa-solid fa-dumpster-fire text-danger" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h5 style="font-weight: 700; color: #1e293b; margin-bottom: 0.5rem;">Bulk Delete</h5>
                <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 2rem;">Are you sure you want to delete <strong id="bulk_count_text">0</strong> brands? This action cannot be undone.</p>
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
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-body" style="padding: 2rem; text-align: center;">
                <i class="fa-solid fa-triangle-exclamation text-danger" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h5 style="font-weight: 700; color: #1e293b; margin-bottom: 0.5rem;">Confirm Delete</h5>
                <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 2rem;">Are you sure you want to delete this brand?</p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button type="button" class="aim-btn" style="background: #f1f5f9; color: #475569;" data-dismiss="modal">Cancel</button>
                    @if(optional(Auth::user())->user_type == "Demo")
                    <button type="button" class="aim-btn aim-btn-danger ToastrButton">Delete</button>
                    @else
                    <button id="del_btn" class="aim-btn aim-btn-danger" type="button" data-submit="">Confirm Delete</button>
                    @endif
                </div>
            </div>
        </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('brand.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Import Brands</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <div class="form-group">
                        <label>Upload CSV File</label>
                        <input type="file" name="file" class="form-control" accept=".csv" required>
                        <small class="text-muted mt-2 d-block">Required Format: <br><b>ID, Name, Slug, Status</b><br>Leave ID blank to create new, provide ID to update existing.</small>
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
    const csrfToken = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';

    $('#export_btn').on('click', function(e) {
        e.preventDefault();
        var query = $('#search_input').val();
        var exportUrl = "{{ route('brand.export') }}";
        if (query) {
            exportUrl += "?query=" + encodeURIComponent(query);
        }
        window.location.href = exportUrl;
    });

    $('#myModal').on('show.bs.modal', function(e) {
        var id = e.relatedTarget.dataset.id;
        $("#del_btn").attr("data-submit", id);
    });
    $("#del_btn").on("click", function(){
        var id = $(this).attr("data-submit");
        $("#form_"+id).submit();
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

    $('#select_all').on('change', function() {
        $('.row-checkbox').prop('checked', $(this).is(':checked'));
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

    // Bulk Delete Action
    $('#confirm_bulk_delete').on('click', function() {
        var selectedIds = [];
        $('.row-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) return;

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Deleting...');

        $.ajax({
            url: "{{ url('admin/brand/bulk-delete') }}",
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

    // Ajax Search Logic
    let searchTimeout;
    $('#search_input').on('input', function() {
        clearTimeout(searchTimeout);
        let query = $(this).val();

        if (!query) {
            location.reload();
            return;
        }

        searchTimeout = setTimeout(function() {
            $.ajax({
                url: "{{ url('admin/brand/search') }}",
                type: "POST",
                data: { query: query },
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function(res) {
                    if(res.success) {
                        let html = '';
                        if (res.data.length === 0) {
                            $('#table_body').html('');
                            $('#no_results').show();
                            $('#pagination_links').hide();
                        } else {
                            $('#no_results').hide();
                            $('#pagination_links').hide();
                            res.data.forEach(function(row) {
                                let statusHtml = row.status == 1 ? 
                                    '<span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 600;">Active</span>' : 
                                    '<span style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 600;">Inactive</span>';
                                
                                html += `
                                <tr>
                                    <td>
                                        <label class="custom-checkbox">
                                            <input type="checkbox" class="row-checkbox" value="${row.id}">
                                            <span class="checkmark"></span>
                                        </label>
                                    </td>
                                    <td><span style="color:#94a3b8; font-weight:600;">#${row.id}</span></td>
                                    <td>
                                        <span style="font-weight: 600; color:#1e293b;">${row.name}</span>
                                    </td>
                                    <td><span style="color:#64748b; font-family: monospace; background:#f8fafc; padding:2px 6px; border-radius:4px;">${row.slug}</span></td>
                                    <td>
                                        ${statusHtml}
                                    </td>
                                    <td class="text-right">
                                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                            <a href="{{url('admin/brand')}}/${row.id}/edit" class="action-btn btn-edit" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <button type="button" class="action-btn btn-delete" data-id="${row.id}" data-toggle="modal" data-target="#myModal" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                        <form action="{{url('admin/brand')}}/${row.id}" method="POST" class="d-none" id="form_${row.id}">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <input type="hidden" name="_token" value="${csrfToken}">
                                        </form>
                                    </td>
                                </tr>
                                `;
                            });
                            $('#table_body').html(html);
                        }
                        // Reset select all checkbox
                        $('#select_all').prop('checked', false);
                        updateBulkActions();
                    }
                }
            });
        }, 300); // 300ms debounce
    });
</script>
@endsection
