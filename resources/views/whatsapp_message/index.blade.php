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
    @if (count($errors) > 0)
    <div class="alert alert-danger">
      <ul>
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title float-left">
                Whatsapp Message
            </h3>
            <a href="{{ route('whatsapp-message.create')}}" class="btn btn-success float-right">Add New</a>
        </div> 
      
      <div class="card-body table-responsive table-bordered table-striped">
        <table class="table" id="data_table">
          <thead class="thead-inverse">
            <tr>
              <th>#</th>
              <th>Image</th>
              <th>Message</th>
              <th>Type</th>
              <th style="width:10%">Created</th>
              <th style="width:10%">Action</th>
            </tr>
          </thead>
          <tbody>
          @foreach($data as $row)
            <tr>
              <td class="align-middle">{{$row->id}}</td>
              <td class="align-middle"><img src="@if($row->image)@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$row->image)}} @else {{asset('uploads/'.$row->image)}} @endif @else {{asset('assets/images/no-image.png')}} @endif" width="100px" height="100px"></td>
              <td class="align-middle">{{$row->message}}</td>
              <td class="align-middle">{{$row->type}}</td>
              <td class="align-middle">{{date('d M, Y',strtotime($row->created_at))}}</td>
              <td class="align-middle">
                <div class="btn-group text-center">
                    <a data-id="{{$row->id}}" data-toggle="modal" data-target="#whatsappModal"><button type="button" class="btn btn-primary"><span aria-hidden="true" class="fab fa-whatsapp"></span></button></a>
                    <a href="{{url('admin/whatsapp-message/'.$row->id.'/edit') }}"><button type="button" class="btn btn-success ml-2"><span aria-hidden="true" class="fa fa-edit"></span></button></a>
                    <a data-id="{{$row->id}}" data-toggle="modal" data-target="#myModal"><button type="button" class="btn btn-danger ml-2"><span aria-hidden="true" class="fa fa-trash"></span></button></a>
                </div>
              </div>
                {!! Form::open(['url' => 'admin/whatsapp-message/'.$row->id,'method'=>'DELETE','class'=>'form-horizontal','id'=>'form_'.$row->id]) !!}
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

<!-- Start whatsapp Modal -->
<div id="whatsappModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Do you really want to send message on whatsapp ?</h6>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="m-3">
                <div class="row modal-body">
                    <div class="col-md-6">
                        <div class="form-group" id="type_lay">
                            <label for="example-text-input" class="form-control-label">Total User</label>
                            <select class="form-control" id="quantity" name="quantity" required>
                                <option value="100">100</option>
                                <option value="200">200</option>
                                <option value="300">300</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" id="type_lay">
                            <label for="example-text-input" class="form-control-label">Select Type</label>
                            <select class="form-control" id="user_type" name="user_type" required>
                                <option value="newest">Newest</option>
                                <option value="older">20 Day Older</option>
                                <option value="random">Random</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    @if(optional(Auth::user())->user_type == "Demo")
                    <button type="button" class="btn btn-success ToastrButton">Send</button>
                    @else
                    <button id="send_btn" class="btn btn-success" type="button" data-submit="" data-dismiss="modal">Send</button>
                    @endif
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End whatsapp Modal -->

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

    $("#whatsappModal").on('show.bs.modal', function(e){
        var id = e.relatedTarget.dataset.id;
        $("#send_btn").attr("data-submit",id);
    });
    
    $("#send_btn").on("click",function(){
        var id = $(this).data("submit");
        var quantity = document.getElementById("quantity").value;
        var user_type = document.getElementById("user_type").value;
        
        $.ajax({
          type: "POST",
          url: "{{url('admin/send-whatsapp-msg')}}",
          data: { id : id,quantity : quantity,user_type : user_type },
          headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
          success: function(data) {
          //  console.log(data);
              toastr.success("Message Send Successfully");
          },
          error (data) {
              toastr.error(JSON.stringify(data));
          }
          
        });
    });
</script>
@endsection
