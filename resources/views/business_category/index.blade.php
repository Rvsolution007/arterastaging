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
        text-decoration: none;
    }

    .btn-premium:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4);
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

    .category-icon {
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

    .btn-edit { background: #d1fae5; color: #059669; }
    .btn-delete { background: #fee2e2; color: #e11d48; }

    .action-btn:hover { transform: scale(1.1); }

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
    
    .dataTables_wrapper .dataTables_paginate {
        padding-bottom: 1rem;
    }
</style>
@endsection

@section('content')
<div class="analytics-container">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h4 class="page-title mb-0"><i class="fa-solid fa-tags mr-2 text-primary"></i> Business Categories</h4>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('business-category.create')}}" class="btn-premium">
                <i class="fa-solid fa-plus mr-1"></i> Add New Category
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="table-panel">
                <div class="table-panel-header">
                    <h5 class="table-panel-title">Category Management</h5>
                    <span class="badge-soft">{{ count($data) }} Total Categories</span>
                </div>
                <div class="table-responsive">
                    <table class="custom-table" id="data_table">
                        <thead>
                            <tr>
                                <th style="width: 80px;"># ID</th>
                                <th>Category Details</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $row)
                            <tr>
                                <td><span class="text-muted">#{{$row->id}}</span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img class="category-icon mr-3" src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$row->icon)}} @else {{asset('uploads/'.$row->icon)}} @endif">
                                        <span class="text-dark font-weight-bold" style="font-size: 1rem;">{{$row->name}}</span>
                                    </div>
                                </td>
                                <td>
                                    <label class="switch my-auto">
                                        <input type="checkbox" name="status" data-id="{{$row->id}}" value="1" class="status" @if($row->status==1) checked @endif>
                                        <span class="slider"></span>
                                    </label>
                                </td>
                                <td class="text-right">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <a href="{{url('admin/business-category/'.$row->id.'/edit') }}" class="action-btn btn-edit mr-2" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <button type="button" class="action-btn btn-delete" data-id="{{$row->id}}" data-toggle="modal" data-target="#myModal" title="Delete">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                    {!! Form::open(['url' => 'admin/business-category/'.$row->id,'method'=>'DELETE','class'=>'d-none','id'=>'form_'.$row->id]) !!}
                                    {!! Form::hidden("id",$row->id) !!}
                                    {!! Form::close() !!}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
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
                <p class="text-center text-muted mb-0">Are you sure you want to delete this category? This will affect all businesses assigned to it.</p>
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
        url: "{{url('admin/business-category-status')}}",
        data: { checked : checked , id : id},
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
          new PNotify({
            title: 'Success!',
            text: "Business Category Status Has Been Changed.",
            type: 'success'
          });
        },
      });
    });
</script>
@endsection
