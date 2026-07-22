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

    .business-logo {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
    }

    .btn-view { background: #e0f2fe; color: #0284c7; }
    .btn-edit { background: #d1fae5; color: #059669; }
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
</style>
@endsection

@section('content')
<div class="analytics-container">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h4 class="page-title mb-0"><i class="fa-solid fa-briefcase mr-2 text-primary"></i> Business Directory</h4>
        </div>
        <div class="col-md-6 text-right">
            <form method="GET" action="" class="d-inline-flex align-items-center justify-content-end">
                <input class="search-input mr-2" type="search" placeholder="Search businesses..." name="search" value="{{ $search ?? '' }}">
                <button class="btn-premium" type="submit"><i class="fa-solid fa-magnifying-glass mr-1"></i> Search</button>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="table-panel">
                <div class="table-panel-header">
                    <h5 class="table-panel-title">All Registered Businesses</h5>
                    <span class="badge-soft">{{ $data->total() }} Total Records</span>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;"># ID</th>
                                <th>Business Details</th>
                                <th>Mobile No</th>
                                <th>Created By</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $row)
                            <tr>
                                <td>
                                    <span class="text-muted">#{{$row->id}}</span>
                                    @if($row->id > $last_seen_business_id)
                                    <span class="badge-new">NEW</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img class="business-logo mr-3" src="@if($row->logo) @if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$row->logo)}} @else {{asset('uploads/'.$row->logo)}} @endif @else {{asset('assets/images/no-user.jpg')}} @endif">
                                        <div>
                                            <a href="{{url('admin/business/'.$row->id) }}" class="text-dark font-weight-bold d-block" style="font-size: 0.95rem;">{{$row->name}}</a>
                                            <small class="text-muted">{{ $row->email ?? 'No Email' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-math text-dark">{{$row->mobile_no}}</div>
                                </td>
                                <td>
                                    <span class="badge-soft"><i class="fa-regular fa-user mr-1"></i> {{$row->user->name ?? 'N/A'}}</span>
                                </td>
                                <td class="text-right">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <a href="{{url('admin/business/'.$row->id) }}" class="action-btn btn-view mr-1" title="View">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <label class="switch my-auto mr-2">
                                            <input type="checkbox" name="status" data-id="{{$row->id}}" value="1" class="status" @if($row->status==1) checked @endif>
                                            <span class="slider"></span>
                                        </label>
                                        <a href="{{url('admin/business/'.$row->id.'/edit') }}" class="action-btn btn-edit mr-1" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <button type="button" class="action-btn btn-delete" data-id="{{$row->id}}" data-toggle="modal" data-target="#myModal" title="Delete">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                    {!! Form::open(['url' => 'admin/business/'.$row->id,'method'=>'DELETE','class'=>'d-none','id'=>'form_'.$row->id]) !!}
                                    {!! Form::hidden("id",$row->id) !!}
                                    {!! Form::close() !!}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-inbox d-block mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                                    No data available in table
                                </td>
                            </tr>
                            @endforelse
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

<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body py-4">
                <div class="text-center mb-3">
                    <i class="fa-solid fa-triangle-exclamation text-danger" style="font-size: 3rem;"></i>
                </div>
                <p class="text-center text-muted mb-0">Are you sure you want to delete this business? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light px-4" style="border-radius: 8px;" data-dismiss="modal">Cancel</button>
                @if(optional(Auth::user())->user_type == "Demo")
                <button type="button" class="btn btn-danger ToastrButton px-4" style="border-radius: 8px;">Delete</button>
                @else
                <button id="del_btn" class="btn btn-danger px-4" style="border-radius: 8px;" type="button" data-submit="">Confirm Delete</button>
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
        url: "{{url('admin/business-status')}}",
        data: { checked : checked , id : id},
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
          new PNotify({
            title: 'Success!',
            text: "Business Status Has Been Changed.",
            type: 'success'
          });
        },
      });
    });
</script>
@endsection
