@extends("layouts.app")

@section('extra_css')
<style type="text/css">

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
                Entry
            </h3>
        </div> 
      
      <div class="card-body table-responsive table-bordered table-striped">
        <table class="table" id="data_table">
          <thead class="thead-inverse">
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Mobile No</th>
              <th>Subject</th>
              <th>Message</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          @foreach($data as $row)
          @if($row->subject)
            <tr>
              <td class="align-middle">{{$row->id}}</td>
              <td class="align-middle">{{$row->name}}</td>
              <td class="align-middle">{{$row->email}}</td>
              <td class="align-middle">{{$row->mobile_no}}</td>
              <td class="align-middle">{{$row->subject->title}}</td>
              <td class="align-middle">{{(strlen($row->message) >= 20)?substr_replace($row->message, "...", 20):$row->message}} <a href="#" data-toggle="modal" data-value="{{$row}}" data-subject="{{$row->subject->title}}" data-target="#detailModal">More</a></td>
              <td class="align-middle">{{$row->created_at->format('d/m/Y')}}</td>
              <td>
                <div class="btn-group">
                  <a data-id="{{$row->id}}" data-toggle="modal" data-target="#myModal"><button type="button" class="btn btn-danger ml-2"><span aria-hidden="true" class="fa fa-trash"></span></button></a>
                </div>
                {!! Form::open(['url' => 'admin/entry/'.$row->id,'method'=>'DELETE','class'=>'form-horizontal','id'=>'form_'.$row->id]) !!}
                {!! Form::hidden("id",$row->id) !!}
                {!! Form::close() !!}
              </td>
            </tr>
          @endif
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

  <!-- Detail Modal -->
  <div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Details</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-sm-4">
              <label class="control-label">Name</label>
            </div>
            <div class="col-sm-8">
              <span id="d_name"></span>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <label class="control-label">Email</label>
            </div>
            <div class="col-sm-8">
              <span id="d_email"></span>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <label class="control-label">Mobile No</label>
            </div>
            <div class="col-sm-8">
              <span id="d_mobile"></span>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <label class="control-label">Subject</label>
            </div>
            <div class="col-sm-8">
              <span id="d_subject"></span>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <label class="control-label">Message</label>
            </div>
            <div class="col-sm-8">
              <span id="d_message"></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- End Detail Modal -->
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

    $('#detailModal').on('show.bs.modal', function(e) {
        var detail = JSON.parse(e.relatedTarget.dataset.value);
        var subject = e.relatedTarget.dataset.subject;
        $('#d_name').text(detail['name']);
        $('#d_email').text(detail['email']);
        $('#d_mobile').text(detail['mobile_no']);
        $('#d_subject').text(subject);
        $('#d_message').text(detail['message']);
    });

    $('.ToastrButton').click(function() {
      toastr.error('This Action Not Available Demo User');
    });
</script>
@endsection
