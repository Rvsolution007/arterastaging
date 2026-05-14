@extends('layouts.app')

@section('extra_css')
<style type="text/css">
  .checkbox, #chk_all{
    width: 20px;
    height: 20px;
  }
  .tag:not(body).is-large {
    font-size: 17px;
  }
  .tag:not(body).is-link {
      background-color: #363636;
      color: #fff;
  }
  .tag:not(body) {
      align-items: center;
      background-color: #f5f5f5;
      border-radius: 4px;
      color: #4a4a4a;
      display: inline-flex;
      font-size: .75rem;
      height: 2em;
      justify-content: center;
      line-height: 1.5;
      padding-left: 0.75em;
      padding-right: 0.75em;
      white-space: nowrap;
  }
  span {
      font-style: inherit;
      font-weight: inherit;
  }
</style>
@endsection
@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="card card-primary">
      <div class="card-header">
        <h3 class="card-title float-left">Roles</h3>
        <a href="{{ route('roles.create')}}" class="btn btn-success float-right">Add New</a>
      </div>

      <div class="card-body table-responsive table-bordered table-striped">
        <table class="table" id="data_table">
          <thead class="thead-inverse">
            <tr>
              
              <th>Role</th>
              <th>Permission</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($data as $row)
            <tr>
              <td class="align-middle">
                {{ $row->name }}
              </td>
              <td>
                @foreach($row->getAllPermissions() as $perm)
                  <span class="tag is-link is-large mt-1">{{ $perm->name }}</span>
                @endforeach
              </td>
              <td>
                <div class="btn-group">
                  <a href="{{url('admin/roles/'.$row->id.'/edit') }}"><button type="button" class="btn btn-success"><span aria-hidden="true" class="fa fa-edit"></span></button></a>
                  <a data-id="{{$row->id}}" data-toggle="modal" data-target="#myModal"><button type="button" class="btn btn-danger ml-2"><span aria-hidden="true" class="fa fa-trash"></span></button></a>
                </div>
                {!! Form::open(['url' => 'admin/roles/'.$row->id,'method'=>'DELETE','class'=>'form-horizontal','id'=>'form_'.$row->id]) !!}
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

  $('input[type="checkbox"]').on('click',function(){
    $('#bulk_delete').removeAttr('disabled');
  });

  $('.ToastrButton').click(function() {
    toastr.error('This Action Not Available Demo User');
  });

  $('#bulk_delete').on('click',function(){
    // console.log($( "input[name='ids[]']:checked" ).length);
    if($( "input[name='ids[]']:checked" ).length == 0){
      $('#bulk_delete').prop('type','button');
        new PNotify({
            title: 'Failed!',
            text: "@lang('fleet.delete_error')",
            type: 'error'
          });
        $('#bulk_delete').attr('disabled',true);
    }
    if($("input[name='ids[]']:checked").length > 0){
      // var favorite = [];
      $.each($("input[name='ids[]']:checked"), function(){
          // favorite.push($(this).val());
          $("#bulk_hidden").append('<input type=hidden name=ids[] value='+$(this).val()+'>');
      });
      // console.log(favorite);
    }
  });


  $('#chk_all').on('click',function(){
    if(this.checked){
      $('.checkbox').each(function(){
        $('.checkbox').prop("checked",true);
      });
    }else{
      $('.checkbox').each(function(){
        $('.checkbox').prop("checked",false);
      });
    }
  });

  // Checkbox checked
  function checkcheckbox(){
    // Total checkboxes
    var length = $('.checkbox').length;
    // Total checked checkboxes
    var totalchecked = 0;
    $('.checkbox').each(function(){
        if($(this).is(':checked')){
            totalchecked+=1;
        }
    });
    // console.log(length+" "+totalchecked);
    // Checked unchecked checkbox
    if(totalchecked == length){
        $("#chk_all").prop('checked', true);
    }else{
        $('#chk_all').prop('checked', false);
    }
  }
</script>

@endsection