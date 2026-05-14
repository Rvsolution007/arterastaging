@extends("layouts.app")

@section('extra_css')
<style>
    .error-card {
        transition: all 0.3s ease;
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    .error-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    .status-badge {
        font-size: 0.85rem;
        padding: 0.4em 0.8em;
        border-radius: 50rem;
    }
    .user-info {
        line-height: 1.2;
    }
    .bulk-actions {
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        opacity: 0;
        transform: translateY(-10px);
        pointer-events: none;
    }
    .bulk-actions.active {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }
    .stats-card {
        border-radius: 10px;
        color: #fff;
    }
    .stats-card .icon {
        opacity: 0.3;
        font-size: 2rem;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Stats Summary -->
    <div class="row mb-4">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info stats-card">
                <div class="inner">
                    <h3>{{ $errors->total() }}</h3>
                    <p>Total Reports</p>
                </div>
                <div class="icon">
                    <i class="fas fa-bug"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning stats-card">
                <div class="inner">
                    <h3>{{ \App\Models\ClientError::where('status', 'Pending')->count() }}</h3>
                    <p>Pending Review</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success stats-card">
                <div class="inner">
                    <h3>{{ \App\Models\ClientError::where('status', 'Resolved')->count() }}</h3>
                    <p>Resolved</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger stats-card">
                <div class="inner">
                    <h3>{{ \App\Models\ClientError::where('created_at', '>=', now()->subDays(7))->count() }}</h3>
                    <p>Last 7 Days</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card error-card">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title font-weight-bold m-0">
                            <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
                            Reported Application Errors
                        </h3>
                        <div class="bulk-actions" id="bulk_actions_container">
                            <button type="button" class="btn btn-outline-success btn-sm shadow-sm mr-2" id="bulk_resolve">
                                <i class="fas fa-check-circle mr-1"></i> Mark Resolved (<span id="selected_count_resolve">0</span>)
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm shadow-sm" id="bulk_delete">
                                <i class="fas fa-trash-alt mr-1"></i> Delete Selected (<span id="selected_count">0</span>)
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle m-0" id="reported_errors_table">
                            <thead class="bg-light text-uppercase font-weight-bold" style="font-size: 0.75rem; letter-spacing: 1px;">
                                <tr>
                                    <th width="40" class="pl-4">
                                        <input type="checkbox" id="select_all_errors">
                                    </th>
                                    <th>User Information</th>
                                    <th>Error Signature</th>
                                    <th width="450">Message Details</th>
                                    <th>Status</th>
                                    <th>Reported At</th>
                                    <th width="100" class="text-center pr-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($errors as $row)
                                <tr>
                                    <td class="pl-4">
                                        <input type="checkbox" class="error_checkbox" value="{{ $row->id }}">
                                    </td>
                                    <td>
                                        @if($row->user)
                                        <div class="d-flex align-items-center user-info">
                                            <img src="{{ $row->user->image ? (substr($row->user->image, 0, 4)=='http' ? $row->user->image : asset('uploads/'.$row->user->image)) : asset('assets/images/no-user.jpg') }}" 
                                                 class="rounded-circle mr-3 shadow-sm" width="45" height="45" style="object-fit: cover; border: 2px solid #eee;"/>
                                            <div>
                                                <div class="font-weight-bold text-dark">{{ $row->user->name }}</div>
                                                <small class="text-muted">{{ $row->user->email }}</small>
                                            </div>
                                        </div>
                                        @else
                                        <div class="d-flex align-items-center text-muted">
                                            <div class="rounded-circle bg-light mr-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                                <i class="fas fa-user-secret"></i>
                                            </div>
                                            <span>Guest / Anonymous</span>
                                        </div>
                                        @endif
                                    </td>
                                    <td>
                                        <code class="bg-light px-2 py-1 rounded text-danger" style="font-size: 0.8rem;">
                                            {{ $row->error_code }}
                                        </code>
                                    </td>
                                    <td>
                                        <div class="text-wrap mb-2" style="max-width: 450px; font-size: 0.85rem; color: #555; word-break: break-word;">
                                            {{ Str::limit($row->error_message, 250) }}
                                        </div>
                                        @if($row->simple_message)
                                        <div class="p-2 rounded mt-2 shadow-sm" style="background: #fdfdfe; border-left: 3px solid #17a2b8; font-size: 0.85rem; max-width: 450px;">
                                            <strong class="text-info"><i class="fas fa-lightbulb mr-1"></i> Simple Explanation:</strong><br>
                                            <span class="text-dark">{{ $row->simple_message }}</span>
                                        </div>
                                        @endif
                                    </td>
                                    <td id="status_cell_{{ $row->id }}">
                                        @php
                                            $badgeClass = 'bg-warning';
                                            if($row->status == 'Resolved') $badgeClass = 'bg-success';
                                            if($row->status == 'In Progress') $badgeClass = 'bg-primary';
                                        @endphp
                                        <span class="badge {{ $badgeClass }} status-badge">{{ $row->status }}</span>
                                    </td>
                                    <td>
                                        <div class="small font-weight-bold">{{ $row->created_at->format('d M, Y') }}</div>
                                        <div class="small text-muted">{{ $row->created_at->format('h:i A') }}</div>
                                    </td>
                                    <td class="pr-4 text-center">
                                        <div class="d-flex justify-content-center align-items-center">
                                            @if($row->status == 'Pending')
                                            <button type="button" class="btn btn-link text-success p-0 mr-3 quick-resolve" data-id="{{ $row->id }}" title="Mark as Resolved">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                            @endif
                                            
                                            <form action="{{ route('admin.reported_errors.destroy', $row->id) }}" method="POST" onsubmit="return confirm('Archive this error report?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link text-danger p-0" title="Delete Report">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-check-circle fa-3x mb-3 text-light"></i>
                                            <p class="m-0">No application errors reported yet. Everything is smooth!</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            Showing {{ $errors->firstItem() }} to {{ $errors->lastItem() }} of {{ $errors->total() }} results
                        </div>
                        <div>
                            {{ $errors->appends(request()->input())->onEachSide(1)->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(document).ready(function() {
    function initICheck() {
        if ($.fn.iCheck) {
            $('input[type="checkbox"]').iCheck({
                checkboxClass: 'icheckbox_square-blue',
                radioClass: 'iradio_square-blue'
            });
        }
    }
    
    initICheck();

    function toggleBulkActions() {
        var count = $('.error_checkbox:checked').length;
        if (count > 0) {
            $('#bulk_actions_container').addClass('active');
            $('#selected_count').text(count);
            $('#selected_count_resolve').text(count);
        } else {
            $('#bulk_actions_container').removeClass('active');
        }
    }

    // Native events
    $(document).on('click', '#select_all_errors', function() {
        var isChecked = $(this).prop('checked');
        $('.error_checkbox').prop('checked', isChecked);
        if ($.fn.iCheck) {
            $('.error_checkbox').iCheck(isChecked ? 'check' : 'uncheck');
        }
        toggleBulkActions();
    });

    $(document).on('change', '.error_checkbox', function() {
        updateMasterCheckbox();
        toggleBulkActions();
    });

    // iCheck events
    $(document).on('ifChanged', '#select_all_errors', function(event) {
        var isChecked = event.target.checked;
        $('.error_checkbox').prop('checked', isChecked);
        if ($.fn.iCheck) { $('.error_checkbox').iCheck(isChecked ? 'check' : 'uncheck'); }
        toggleBulkActions();
    });

    $(document).on('ifChanged', '.error_checkbox', function() {
        updateMasterCheckbox();
        toggleBulkActions();
    });

    function updateMasterCheckbox() {
        var total = $('.error_checkbox').length;
        var checked = $('.error_checkbox:checked').length;
        
        if (total === checked && total > 0) {
            $('#select_all_errors').prop('checked', true);
            if ($.fn.iCheck) { $('#select_all_errors').iCheck('check'); }
        } else {
            $('#select_all_errors').prop('checked', false);
            if ($.fn.iCheck) { $('#select_all_errors').iCheck('uncheck'); }
        }
    }

    // Individual Resolve
    $(document).on('click', '.quick-resolve', function() {
        var id = $(this).data('id');
        var btn = $(this);
        
        $.ajax({
            url: "{{ url('admin/reported-errors/status') }}/" + id,
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                status: 'Resolved'
            },
            success: function(response) {
                if (typeof toastr !== 'undefined') {
                    toastr.success(response.message);
                }
                $('#status_cell_' + id).html('<span class="badge bg-success status-badge">Resolved</span>');
                btn.fadeOut();
            }
        });
    });

    // Bulk Resolve
    $('#bulk_resolve').on('click', function() {
        var ids = [];
        $('.error_checkbox:checked').each(function() {
            ids.push($(this).val());
        });

        if (ids.length > 0) {
            $.ajax({
                url: "{{ route('admin.reported_errors.bulk_status') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    ids: ids,
                    status: 'Resolved'
                },
                success: function(response) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message);
                    }
                    location.reload();
                }
            });
        }
    });

    // Bulk Delete
    $('#bulk_delete').on('click', function() {
        var ids = [];
        $('.error_checkbox:checked').each(function() {
            ids.push($(this).val());
        });

        if (ids.length > 0) {
            if (confirm('Permanently delete ' + ids.length + ' error reports?')) {
                $.ajax({
                    url: "{{ route('admin.reported_errors.bulk_destroy') }}",
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        ids: ids
                    },
                    success: function(response) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success(response.message);
                        }
                        location.reload();
                    }
                });
            }
        }
    });
});
</script>
@endsection
