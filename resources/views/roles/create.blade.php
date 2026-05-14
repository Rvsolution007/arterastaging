@extends('layouts.app')

@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="card card-success">
      <div class="card-header">
        <h3 class="card-title">Add Role</h3>
      </div>

      <div class="card-body">
        @if (count($errors) > 0)
          <div class="alert alert-danger">
            <ul>
              @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        {!! Form::open(['route' => 'roles.store','method'=>'post']) !!}
        <div class="row">
          <div class="form-group col-md-6">
            {!! Form::label('name', "Role Name", ['class' => 'form-label']) !!}
            {!! Form::text('name', null,['class' => 'form-control','required']) !!}
          </div>
        </div>
        <div class="row">
          {!! Form::label('permission',"Permission Module:", ['class' => 'col-xs-5 control-label']) !!}
        </div>
        <div class="row">  
        @foreach($modules as $row)
          <div class="col-md-4">
            <div class="form-group">
              <input type="checkbox" name="{{$row}}" value="1" class="flat-red form-control">
                @if($row == "FinancialStatistics")
                <label name="permission" class="col-xs-5 control-label" data-toggle="tooltip" data-placement="right" title="Transactions, Today Payment, Weekly Payment, Monthly Payment, Total Payment, Monthly Payment Report, Recent Purchase">{{$row}} *</label>
                @else
                <label name="permission" class="col-xs-5 control-label">{{$row}}</label>
                @endif
            </div>
          </div>
        @endforeach 
        </div> 
      </div>
      <div class="card-footer">
        <div class="row">
          <div class="form-group col-md-4">
            @if(optional(Auth::user())->user_type == "Demo")
            <button type="button" class="btn btn-success ToastrButton">Save</button>
            @else
            {!! Form::submit('Save', ['class' => 'btn btn-success']) !!}
            @endif
          </div>
        </div>
      </div>
      {!! Form::close() !!}
    </div>
  </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript">
  $(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip();   
  });
  //Flat red color scheme for iCheck
  $('input[type="checkbox"].flat-red, input[type="radio"].flat-red').iCheck({
    checkboxClass: 'icheckbox_flat-green',
    radioClass   : 'iradio_flat-green'
  })
</script>
@endsection