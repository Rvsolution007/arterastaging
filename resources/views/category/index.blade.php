@extends("layouts.app")

@section('extra_css')
<style type="text/css">
.switch {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 25px;
}

/* Hide default HTML checkbox */
.switch input {display:none;}

/* The slider */
.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  -webkit-transition: .4s;
  transition: .4s;
}

.slider:before {
  position: absolute;
  content: "";
  height: 16px;
  width: 16px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  -webkit-transition: .4s;
  transition: .4s;
}

input:checked + .slider {
  background-color: #2196F3;
}

input:focus + .slider {
  box-shadow: 0 0 1px #2196F3;
}

input:checked + .slider:before {
  -webkit-transform: translateX(26px);
  -ms-transform: translateX(26px);
  transform: translateX(26px);
}

/* Rounded sliders */
.slider.round {
  border-radius: 34px;
}

.slider.round:before {
  border-radius: 50%;
}
/* Minimalist Dashboard UI Port */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

.dash-panel {
    background-color: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
    margin-bottom: 24px;
    overflow: hidden;
}
.dash-panel-body {
    padding: 0;
}
.dash-panel-body .table {
    margin-bottom: 0;
    font-family: 'Poppins', sans-serif;
}
.dash-panel-body .table thead th {
    border-top: none;
    border-bottom: 1px solid #e2e8f0;
    background-color: #f8fafc;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 13px;
    padding: 12px 20px;
}
.dash-panel-body .table tbody td {
    padding: 12px 20px;
    vertical-align: middle;
    border-top: 1px solid #f1f5f9;
    font-size: 14px;
    color: #334155;
}
.dash-panel-body .dataTables_wrapper {
    padding: 20px;
}
.btn-action {
    background: transparent;
    border: none;
    padding: 6px;
    margin-right: 4px;
    border-radius: 6px;
    transition: background 0.2s;
}
.btn-action-edit { color: #10B981; }
.btn-action-edit:hover { background: rgba(16, 185, 129, 0.1); }
.btn-action-delete { color: #EF4444; }
.btn-action-delete:hover { background: rgba(239, 68, 68, 0.1); }
</style>
@endsection

@section('content')
<div style="font-family: 'Poppins', sans-serif;">
    <!-- Breadcrumbs -->
    <div style="font-size: 14px; color: #64748b; margin-bottom: 24px; margin-top: 10px;">
        <span>Home</span> <span style="margin: 0 8px; color: #cbd5e1;">/</span> <span style="color: #64748b;">Category</span>
    </div>

    <!-- Title Row -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 style="font-weight:700; color:#0f172a; margin:0 0 6px 0; font-size:28px; letter-spacing: -0.5px;">Category</h2>
            <p style="color:#6b7280; margin:0; font-size:15px; font-weight:400;">Manage application categories</p>
        </div>
        <div class="d-flex align-items-center">
            <a href="{{ route('category.create')}}" style="background:#f97316; color:white; border-radius:6px; font-weight:600; padding:10px 20px; text-decoration:none; display:flex; align-items:center; gap:8px; font-size:14px; box-shadow: 0 4px 6px -1px rgba(249, 115, 22, 0.2);">
               <i class="fa fa-plus"></i> Add New
            </a>
        </div>
    </div>
</div>

<div class="row">
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
    <div class="dash-panel">
      <div class="dash-panel-body table-responsive">
        <table class="table table-borderless" id="data_table">
          <thead class="thead-light">
            <tr>
              <th>No.</th>
              <th>Name</th>
              <th>Status</th>
              <th>Feature</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          @foreach($data as $row)
            <tr>
              <td class="align-middle" style="color:#64748b;">{{$row->id}}</td>
              <td>
                <div class="d-flex align-items-center">
                  <img class="rounded-circle shadow-sm" src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {{\Storage::disk('spaces')->url('uploads/'.$row->icon)}} @else {{asset('uploads/'.$row->icon)}} @endif" width="36px" height="36px" style="object-fit:cover;">
                  <span class="ml-3" style="font-size:14px; font-weight:600; color:#1e293b;">{{$row->name}}</span>
                </div>
              </td>
              <td class="align-middle">
                <label class="switch" style="margin-bottom:0; transform:scale(0.8); transform-origin:left;">
                    <input type="checkbox" name="status" data-id="{{$row->id}}" value="1" class="status" @if($row->status==1) checked @endif>
                    <span class="slider round"></span>
                </label>
              </td>
              <td class="align-middle">
                <label class="switch" style="margin-bottom:0; transform:scale(0.8); transform-origin:left;">
                  @php $post = App\Models\FeaturePost::where("category_id",$row->id)->get(); @endphp
                    <input type="checkbox" name="feature_status" data-id="{{$row->id}}" value="1" class="feature_status" @if(!$post->isEmpty()) checked @endif>
                    <span class="slider round"></span>
                </label>
              </td>
              <td class="align-middle">
                <div class="d-flex align-items-center">
                    <a href="{{url('admin/category/'.$row->id.'/edit') }}" class="btn-action btn-action-edit" title="Edit"><i class="fa fa-edit"></i></a>
                    <button type="button" class="btn-action btn-action-delete" title="Delete" data-id="{{$row->id}}" data-toggle="modal" data-target="#myModal"><i class="fa fa-trash"></i></button>
                </div>
                {!! Form::open(['url' => 'admin/category/'.$row->id,'method'=>'DELETE','class'=>'form-horizontal','id'=>'form_'.$row->id]) !!}
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
        url: "{{url('admin/category-status')}}",
        data: { checked : checked , id : id},
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
          new PNotify({
            title: 'Success!',
            text: "Category Status Has Been Changed.",
            type: 'success'
          });
        },
      });
    });

    $(".feature_status").change(function(){
      var checked = $(this).is(':checked');
      var id = $(this).data("id");
     
      $.ajax({
        type: "POST",
        url: "{{url('admin/category-feature-status')}}",
        data: { checked : checked , id : id},
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
          if(data == 1){
            new PNotify({
              title: 'Success!',
              text: "Category Feature Set!.",
              type: 'success'
            });
          }
          else
          {
            new PNotify({
              title: 'Success!',
              text: "Category Feature Unset!.",
              type: 'success'
            });
          }
        },
      });
    });
</script>
@endsection
