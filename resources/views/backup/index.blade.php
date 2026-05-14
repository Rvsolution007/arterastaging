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
</style>
@endsection

@section('content')
<div class="row">
  <div class="col-md-12">
    @if(Session::has('success') )
      <div class="alert alert-success alert-dismissible">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          {{ Session::get('success') }}
      </div>
    @endif
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title float-left">
                Backup
            </h3>
            @if(optional(Auth::user())->user_type == "Demo")
            <button type="button" class="btn btn-success ToastrButton float-right">Create Backup</button>
            @else
            <a href="{{ route('backup.create')}}" class="btn btn-success float-right">Create Backup</a>
            @endif
        </div> 
      
      <div class="card-body table-responsive table-bordered table-striped">
        <table class="table" id="data_table">
          <thead class="thead-inverse">
            <tr>
              <th>#</th>
              <th>File Name</th>
              <th>File Size</th>
              <th>Created</th>
              <th>Download</th>
              <th>Delete</th>
            </tr>
          </thead>
          <tbody>
          @foreach($backups as $row)
            <tr>
              <td class="align-middle">{{$row['id']}}</td>
              <td class="align-middle">{{$row['file_name']}}</td>
              <td class="align-middle">{{$row['file_size']}}</td>
              <td class="align-middle">{{$row['last_modified']}}</td>
              <td class="align-middle">
                <div class="btn-group text-center">
                    @if(optional(Auth::user())->user_type == "Demo")
                    <button type="button" class="btn btn-primary ml-2 ToastrButton"><span aria-hidden="true" class="fa fa-download"></span> Download</button>
                    @else
                    <a href="{{url('admin/backup/download/'.$row['file_name'])}}"><button type="button" class="btn btn-primary ml-2"><span aria-hidden="true" class="fa fa-download"></span> Download</button></a>
                    @endif
                </div>
              </td>
              <td class="align-middle">
                <div class="btn-group text-center">
                    <a data-id="{{$row['id']}}" data-toggle="modal" data-target="#myModal"><button type="button" class="btn btn-danger ml-2"><span aria-hidden="true" class="fa fa-trash"></span> Delete</button></a>
                </div>
                {!! Form::open(['url' => 'admin/backup/'.$row['file_name'],'method'=>'DELETE','class'=>'form-horizontal','id'=>'form_'.$row['id']]) !!}
                {!! Form::hidden("id",$row['id']) !!}
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
        url: "{{url('admin/language-status')}}",
        data: { checked : checked , id : id},
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
          new PNotify({
            title: 'Success!',
            text: "Language Status Has Been Changed.",
            type: 'success'
          });
        },
      });
    });
</script>
@endsection
