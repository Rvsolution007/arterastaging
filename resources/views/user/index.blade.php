@extends("layouts.app")

@section('extra_css')
<style type="text/css">
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

    .table-panel {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .table-panel-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .table-panel-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
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

    .custom-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .badge-soft {
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        background: #f1f5f9;
        color: #475569;
        display: inline-block;
    }

    .btn-premium {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white !important;
        border: none;
        padding: 0.5rem 1.25rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
    }

    .btn-premium:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4);
    }

    .search-input {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        background-color: #ffffff;
        width: 250px;
    }

    .search-input:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    /* Toggle Switch */
    .switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 22px;
    }

    .switch input { display:none; }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cbd5e1;
        transition: .4s;
        border-radius: 34px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 16px; width: 16px;
        left: 3px; bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked + .slider { background-color: #6366f1; }
    input:checked + .slider:before { transform: translateX(22px); }

    .user-avatar-wrapper {
        position: relative;
        display: inline-block;
    }

    .user-avatar {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .login-type-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        font-size: 10px;
    }

    .action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        border: none;
        margin-left: 4px;
    }

    .btn-whatsapp { background: #dcfce7; color: #16a34a; }
    .btn-view { background: #e0f2fe; color: #0284c7; }
    .btn-track { background: #fef3c7; color: #d97706; }
    .btn-delete { background: #fee2e2; color: #e11d48; }

    .action-btn:hover { transform: scale(1.1); }

    .badge-new {
        display: inline-block;
        padding: 0.15rem 0.5rem;
        border-radius: 4px;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #fff;
        margin-left: 6px;
        animation: newPulse 2s ease-in-out infinite;
        vertical-align: middle;
    }

    @keyframes newPulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    /* DataTables Alignment */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_info {
        padding-left: 1.5rem !important;
        padding-top: 1rem;
    }

    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_paginate {
        padding-right: 1.5rem !important;
        padding-top: 1rem;
    }
</style>
@endsection

@section('content')
<div class="analytics-container">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h4 class="page-title mb-0"><i class="fa-solid fa-users mr-2 text-primary"></i> User Management</h4>
        </div>
        <div class="col-md-6 text-right">
            <button type="button" id="bulkDeleteBtn" class="btn btn-danger mr-2" style="display: none; border-radius: 8px;"><i class="fa-solid fa-trash mr-1"></i> Delete Selected</button>
            <form method="GET" action="" class="d-inline-flex align-items-center justify-content-end">
                <input class="search-input mr-2" type="search" placeholder="Search users..." name="search" value="{{ $search ?? '' }}">
                <button class="btn-premium mr-2" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                <a href="{{ route('user.create')}}" class="btn-premium"><i class="fa-solid fa-plus mr-1"></i> Add New</a>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="table-panel">
                <div class="table-panel-header">
                    <h5 class="table-panel-title">User Directory</h5>
                    <span class="badge-soft">{{ $data->total() }} Registered Users</span>
                </div>
                <div class="table-responsive">
                    <table class="custom-table" id="data_table_user">
                        <thead>
                            <tr>
                                <th style="width: 40px;"><input type="checkbox" id="selectAll"></th>
                                <th style="width: 70px;"># ID</th>
                                <th>User Profile</th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th>Joined Date</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $row)
                            @if($row->email != "demo2023@gmail.com")
                            <tr>
                                <td>
                                    @if($row->user_type != "Super Admin")
                                    <input type="checkbox" class="user-checkbox" value="{{$row->id}}">
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted">#{{$row->id}}</span>
                                    @if($row->id > $last_seen_user_id)
                                    <span class="badge-new">NEW</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar-wrapper">
                                            <img class="user-avatar @if($row->is_subscribe && date_format(date_create(implode("", preg_split("/[-\s:,]/", $row->subscription_end_date))),'Y-m-d') >= date('Y-m-d')) border-primary @endif" 
                                                 src="@if($row->image) @if(substr($row->image, 0, 4)=="http") {{$row->image}} @else @if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$row->image)}} @else {{asset('uploads/'.$row->image)}} @endif @endif @else {{asset('assets/images/no-user.jpg')}} @endif"
                                                 @if($row->is_subscribe && date_format(date_create(implode("", preg_split("/[-\s:,]/", $row->subscription_end_date))),'Y-m-d') >= date('Y-m-d')) style="border-width: 3px;" @endif>
                                            
                                            @if($row->login_type=="google")
                                            <span class="login-type-badge text-danger"><i class="fa-brands fa-google"></i></span>
                                            @endif
                                            @if($row->login_type=="phone")
                                            <span class="login-type-badge text-primary"><i class="fas fa-phone"></i></span>
                                            @endif
                                        </div>
                                        <div class="ml-3">
                                            <a href="{{url('admin/user/'.$row->id) }}" class="text-dark font-weight-bold d-block" style="font-size: 0.95rem;">{{$row->name}}</a>
                                            @if($row->is_subscribe && date_format(date_create(implode("", preg_split("/[-\s:,]/", $row->subscription_end_date))),'Y-m-d') >= date('Y-m-d'))
                                            <span class="badge badge-primary px-2 py-0" style="font-size: 0.65rem;">PREMIUM</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td><span class="text-muted">{{$row->email}}</span></td>
                                <td><span class="font-math">{{$row->mobile_no}}</span></td>
                                <td><span class="badge-soft">{{date('d M Y',strtotime($row->created_at))}}</span></td>
                                <td>
                                    @if(($row->registration_source ?? null) === 'adlive')
                                        <span class="badge badge-success px-2 py-1" style="font-size: 0.75rem;"><i class="fa-solid fa-bullhorn mr-1"></i> AdLive</span>
                                    @elseif(($row->registration_source ?? null) === 'artera_pixel')
                                        <span class="badge badge-info px-2 py-1" style="font-size: 0.75rem;"><i class="fa-solid fa-mobile-screen mr-1"></i> Artera Pixel</span>
                                    @elseif(($row->registration_source ?? null) === 'Website')
                                        <span class="badge badge-info px-2 py-1" style="font-size: 0.75rem;"><i class="fa-solid fa-globe mr-1"></i> Website</span>
                                    @else
                                        <span class="badge badge-secondary px-2 py-1" style="font-size: 0.75rem;"><i class="fa-solid fa-mobile-screen mr-1"></i> App</span>
                                    @endif
                                </td>
                                <td>
                                    <label class="switch my-auto">
                                        <input type="checkbox" name="status" data-id="{{$row->id}}" value="1" class="status" @if($row->status==1) checked @endif>
                                        <span class="slider"></span>
                                    </label>
                                </td>
                                <td class="text-right">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <button type="button" class="action-btn btn-whatsapp" data-id="{{$row->id}}" data-toggle="modal" data-target="#whatsappModal" title="Send WhatsApp">
                                            <i class="fab fa-whatsapp"></i>
                                        </button>
                                        <a href="{{url('admin/user/'.$row->id) }}" class="action-btn btn-view" title="View Profile">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.user_performance.details', ['type' => 'user_session_tracking', 'user_id' => $row->id]) }}" class="action-btn btn-track" title="Track Live">
                                            <i class="fas fa-chart-line"></i>
                                        </a>
                                        @if($row->user_type != "Super Admin")
                                        <button type="button" class="action-btn btn-delete" data-id="{{$row->id}}" data-toggle="modal" data-target="#myModal" title="Delete">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                        @endif
                                    </div>
                                    {!! Form::open(['url' => 'admin/user/'.$row->id,'method'=>'DELETE','class'=>'d-none','id'=>'form_'.$row->id]) !!}
                                    {!! Form::hidden("id",$row->id) !!}
                                    {!! Form::close() !!}
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-top">
                    <div class="d-flex justify-content-center">{{ $data->appends(request()->input())->onEachSide(1)->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Whatsapp Modal -->
<div id="whatsappModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold">Send WhatsApp Message</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body py-4">
                <div class="form-group">
                    <label class="badge-soft mb-2">Select Template</label>
                    <select id="msg_id" name="msg_id" class="form-control" style="width:100%; border-radius: 10px;">
                        @foreach($whatsapp_messages as $message)
                        <option value="{{$message->id}}">{{$message->message}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light px-4" style="border-radius: 8px;" data-dismiss="modal">Cancel</button>
                @if(optional(Auth::user())->user_type == "Demo")
                <button type="button" class="btn btn-success ToastrButton px-4" style="border-radius: 8px;">Send</button>
                @else
                <button id="send_btn" class="btn btn-success px-4" style="border-radius: 8px;" data-dismiss="modal">Send Message</button>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body py-4 text-center">
                <i class="fa-solid fa-user-minus text-danger mb-3" style="font-size: 3rem;"></i>
                <p class="text-muted">Are you sure you want to delete this user? All their data will be permanently removed.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light px-4" style="border-radius: 8px;" data-dismiss="modal">Cancel</button>
                @if(optional(Auth::user())->user_type == "Demo")
                <button type="button" class="btn btn-danger ToastrButton px-4" style="border-radius: 8px;">Delete</button>
                @else
                <button id="del_btn" class="btn btn-danger px-4" style="border-radius: 8px;" type="button">Confirm Delete</button>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
    $("#del_btn").on("click",function(){
        var id=$(this).data("submit");
        $("#form_"+id).submit();
    });

    $('#myModal').on('show.bs.modal', function(e) {
        var id = e.relatedTarget.dataset.id;
        $("#del_btn").attr("data-submit",id);
    });

    $('.ToastrButton').click(function() {
      toastr.error('This Action Not Available Demo User');
    });
    
    $(".status").change(function(){
      var checked = $(this).is(':checked');
      var id = $(this).data("id");
     
      $.ajax({
        type: "POST",
        url: "{{url('admin/user-status')}}",
        data: { checked : checked , id : id},
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
          new PNotify({
            title: 'Success!',
            text: "User Status Has Been Changed.",
            type: 'success'
          });
        },
      });
    });

    $("#whatsappModal").on('show.bs.modal', function(e){
        var id = e.relatedTarget.dataset.id;
        $("#send_btn").attr("data-submit",id);
        $('#msg_id').select2();
    });
    
    $("#send_btn").on("click",function(){
        var id = $(this).data("submit");
        var msg_id = document.getElementById("msg_id").value;
        
        $.ajax({
            type: "POST",
            url: "{{url('admin/send-whatsapp-msg-user')}}",
            data: { user_id : id,msg_id : msg_id },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(data) {
                var text = data.replace('<br>', '');
                var result = $.parseJSON(text);
                if(result.message == "Message sent") {
                    toastr.success("Message Sent Successfully");
                } else {
                    toastr.error(result.message);
                }  
            },
            error: function(data) {
                toastr.error("Error sending message");
            }
        });
    });

    // Bulk Delete Logic
    $('#selectAll').change(function() {
        $('.user-checkbox').prop('checked', $(this).prop('checked'));
        toggleBulkDeleteBtn();
    });

    $('.user-checkbox').change(function() {
        toggleBulkDeleteBtn();
        if ($('.user-checkbox:checked').length === $('.user-checkbox').length) {
            $('#selectAll').prop('checked', true);
        } else {
            $('#selectAll').prop('checked', false);
        }
    });

    function toggleBulkDeleteBtn() {
        if ($('.user-checkbox:checked').length > 0) {
            $('#bulkDeleteBtn').show();
        } else {
            $('#bulkDeleteBtn').hide();
        }
    }

    $('#bulkDeleteBtn').click(function() {
        var selectedIds = [];
        $('.user-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length > 0) {
            if (confirm('Are you sure you want to delete selected users? All their data will be permanently removed.')) {
                $.ajax({
                    type: "POST",
                    url: "{{ route('admin.user.bulk_delete') }}",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        ids: selectedIds
                    },
                    success: function(response) {
                        if (response.status) {
                            toastr.success(response.message);
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        toastr.error('Something went wrong!');
                    }
                });
            }
        }
    });
</script>
@endsection
