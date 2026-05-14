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
                Withdraw Request
            </h3>
        </div> 
      
      <div class="card-body table-responsive table-bordered table-striped">
        <table class="table" id="data_table">
          <thead class="thead-inverse">
            <tr>
              <th>#</th>
              <th>User</th>
              <th>UPI Id</th>
              <th>Withdraw Amount</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          @foreach($data as $row)
            <tr>
              <td class="align-middle">{{$row->id}}</td>
              <td class="align-middle">{{$row->user->name}}</td>
              <td class="align-middle">{{$row->upi_id}}</td>
              <td class="align-middle">{{$row->withdraw_amount}}</td>
              <td>
                <div class="btn-group">
                  @if($row->status == 1)
                  Success
                  @else
                  <form method="post" action="{{url('admin/withdraw-request')}}">
                    @csrf
                    <input type="hidden" name="id" value="{{$row->id}}">
                    @if(optional(Auth::user())->user_type == "Demo")
                    <button type="button" class="btn btn-success ToastrButton">Pending</button>
                    @else
                    <button type="submit" class="btn btn-danger ml-2">Pending</button>
                    @endif
                  </form>
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
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
</script>
@endsection
