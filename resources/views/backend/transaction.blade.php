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
                Transactions
            </h3>
        </div> 
      
      <div class="card-body table-responsive table-bordered table-striped">
        <table class="table" id="data_table">
          <thead class="thead-inverse">
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Plan Name</th>
              <th>Total Paid</th>
              <th>Payment Id</th>
              <th>Payment Type</th>
              <th>Date</th>
              <th>Payment Receipt</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          @foreach($data as $row)
            @if($row->user)
            <tr>
              <td>{{$row->id}}</td>
              <td><a href="{{url('admin/user/'.$row->user->id) }}" class="ml-3" style="font-size:15px;"><b>{{$row->user->name}}</b></a></td>
              <td>{{$row->subscription->plan_name}}</td>
              <td>{{$row->total_paid}}</td>
              <td>{{$row->payment_id}}</td>
              <td>
                @if($row->coupon_code_id && ($row->payment_type == 'Free' || $row->payment_type == '0' || $row->total_paid == 0))
                  [{{ ucfirst(strtolower($row->payment_type == '0' ? 'Free' : $row->payment_type)) }}] - Coupon Applied
                @else
                  {{$row->payment_type}}
                @endif
              </td>
              <td>{{date('d M, y',strtotime($row->date))}}</td>
              <td>
                @if($row->payment_receipt)
                  <a href="{{asset('uploads/payment/'.$row->payment_receipt)}}" target="_blank" class="text-primary">View Receipt</a>
                @else
                  -
                @endif
              </td>
              @if($row->status == "Completed")
                <td>{{$row->status}}</td>
              @else
                @if(optional(Auth::user())->user_type == "Demo")
                <td><button type="button" class="btn btn-secondary ToastrButton">Completed</button></td>
                @else
                <td><a class="btn btn-secondary" href="{{url('admin/payment-completed/'.$row->id)}}" role="button">Completed</a></td>
                @endif
              @endif
              <td class="align-middle">
                <a href="{{route('invoice.show', $row->id)}}" target="_blank" class="btn btn-sm btn-info" style="margin-right: 5px;"><i class="fa fa-file-pdf-o"></i> View Invoice</a>
                @if($row->status != "Completed")
                <a data-id="{{$row->id}}" data-toggle="modal" data-target="#myModal"><button type="button" class="btn btn-sm btn-danger ml-1"><span aria-hidden="true" class="fa fa-trash"></span></button></a>
                @endif
                {!! Form::open(['url' => 'admin/transaction-delete','method'=>'POST','class'=>'form-horizontal','id'=>'form_'.$row->id]) !!}
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
</script>
@endsection
