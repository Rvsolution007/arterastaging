@extends('layouts.app')

@section('heading')
Add User
@endsection

@section('content')

  {!! Form::open(['route' => 'user.store','method'=>'post','files'=>true]) !!}
  {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
  <div class="row">
    <div class="col-md-6">
      <div class="card card-light">
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

          <div class="row">
            <div class="col-12">
              <div class="form-group row">
                {!! Form::label('name','Name', ['class' => 'col-sm-2 col-form-label']) !!}
                <div class="col-sm-10">
                  {!! Form::text('name', null,['class' => 'form-control','required']) !!}
                </div>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-12">
              <div class="form-group row">
                {!! Form::label('email','Email', ['class' => 'col-sm-2 col-form-label']) !!}
                <div class="col-sm-10">
                  {!! Form::email('email',null,['class'=>'form-control','required']) !!}
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <div class="form-group row">
                {!! Form::label('mobile_no','Mobile No', ['class' => 'col-sm-2 col-form-label']) !!}
                <div class="col-sm-10">
                  {!! Form::number('mobile_no', null,['class' => 'form-control','required']) !!}
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <div class="form-group row">
                {!! Form::label('password', 'Password', ['class' => 'col-sm-2 col-form-label']) !!}
                <div class="col-sm-10">
                  {!! Form::password('password', ['class' => 'form-control','required']) !!}
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <div class="form-group row">
                {!! Form::label('role', 'User Role', ['class' => 'col-sm-2 col-form-label']) !!}
                <div class="col-sm-10">
                  <select id="role_id" name="role_id" class="form-control">
                    <option value="">User (Default)</option>
                    @foreach($roles as $role)         
                    <option value="{{$role->id}}" >{{$role->name}}</option>
                    @endforeach
                  </select>
                  <small class="form-text text-muted">Leave as "User (Default)" for regular app users</small>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <div class="form-group row">
                {!! Form::label('image','Select Image', ['class' => 'col-sm-2 col-form-label']) !!}
                <div class="col-sm-10">
                  <div class="row">
                      <div class="col-6"><input class="form-control" type="file" id="image" name="image" required></div>
                      <div class="col-6" id="preview"><img type="image" class="shadow bg-white rounded" src="{{asset('assets/images/no-user.jpg')}}" alt="Image" style="width: 120px;height: 120px" /></div>  
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card card-light">
        <div class="card-body">
          <div class="row">
            <div class="col-12 mb-4">
              <b>Subscription Details:</b>
            </div>
          </div>
          
          <div class="row">
            <div class="col-12">
              <div class="form-group row">
                {!! Form::label('plan','Select Plan', ['class' => 'col-sm-4 col-form-label']) !!}
                <div class="col-sm-8">
                  <select id="plan" name="plan" class="form-control">
                    <option value="">Select Plan</option>
                    @foreach($subscription as $sub)
                    <option value="{{$sub->id}}">{{$sub->plan_name}}</option>
                    @endforeach
                  </select>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('subscription_start_from','Subscription Start From', ['class' => 'col-sm-4 col-form-label']) !!}
                    <div class="col-sm-8">
                        {!! Form::text('subscription_start_from', null,['class' => 'form-control datepicker','id'=>'subscription_start_from','placeholder'=>'Ex 02 Jan, 22',"autocomplete"=>"off"]) !!}
                    </div>
                </div>
            </div>
          </div>

          <div class="row">
              <div class="col-12">
                  <div class="form-group row">
                      {!! Form::label('subscription_start_to','Subscription Start To', ['class' => 'col-sm-4 col-form-label']) !!}
                      <div class="col-sm-8">
                          {!! Form::text('subscription_start_to', null,['class' => 'form-control datepicker','id'=>'subscription_start_to','placeholder'=>'Ex 02 Jan, 22',"autocomplete"=>"off"]) !!}
                      </div>
                  </div>
              </div>
          </div>
        </div>
      </div>

      <!-- Partner Settings -->
      <div class="card card-light mt-3">
        <div class="card-body">
          <div class="row">
            <div class="col-12 mb-4">
              <b>Partner / Affiliate Settings:</b>
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <div class="form-group row">
                {!! Form::label('is_partner','Is Partner?', ['class' => 'col-sm-4 col-form-label']) !!}
                <div class="col-sm-8">
                  <div class="custom-control custom-switch mt-2">
                    <input type="checkbox" class="custom-control-input" id="is_partner" name="is_partner" value="1">
                    <label class="custom-control-label" for="is_partner">Enable Partner Access</label>
                  </div>
                  <small class="form-text text-muted">Allow this user to earn commissions via coupon codes.</small>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <div class="form-group row">
                {!! Form::label('partner_commission_percent','Commission (%)', ['class' => 'col-sm-4 col-form-label']) !!}
                <div class="col-sm-8">
                  {!! Form::number('partner_commission_percent', null,['class' => 'form-control', 'placeholder' => 'Leave blank for global default', 'step' => '0.01']) !!}
                  <small class="form-text text-muted">Overrides the global default commission percentage.</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-12 text-center">
      @if(optional(Auth::user())->user_type == "Demo")
      <button type="button" class="btn btn-success ToastrButton">Save</button>
      @else
      {!! Form::submit('Save', ['class' => 'btn btn-success']) !!}
      @endif
    </div>
  </div>
  {!! Form::close() !!}
@endsection

@section("script")
<script type="text/javascript">
  $('#plan').select2();
  $('#role_id').select2();

  $('.datepicker').datepicker({
    format: 'dd M, y',
    minDate:'today',         
  });

  function imagePreview(fileInput) 
  { 
      if (fileInput.files && fileInput.files[0]) 
      {
          var fileReader = new FileReader();
          fileReader.onload = function (event) 
          {
              $('#preview').html('<img src="'+event.target.result+'" class="shadow bg-white rounded" width="120px" alt="Select Image" height="120px"/>');
          };
          fileReader.readAsDataURL(fileInput.files[0]);
      }
  }

  $("#image").change(function () {
      imagePreview(this);
  });

  $(document).ready(function()
  {
    $("#plan").change(function(){
      var value = $(this).val();
      
      $.ajax({
        type: "GET",
        url: "{{url('admin/user-get-plan')}}",
        data: { id : value},
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
          $("#subscription_start_from").val(data['start_date']);
          $("#subscription_start_to").val(data['end_date']);
        },
      });
    });
  });

  // $('#subscription_start_from').datepicker({
  //   onSelect: function(dateStr) {
  //     var dt = new Date(dateStr);
  //     dt.setDate(dt.getDate());
  //     $("#subscription_start_to").datepicker({
  //       format: 'dd/mm/yyyy',
  //       minDate: dt,     
  //     });
  //   },
  //   minDate:'today',
  //   forceParse : false,
  //   calendarWeeks : true,
  //   autoclose : true
  // });

  // $('#subscription_start_to').datepicker({
  //   onSelect: function(dateStr) {
  //     var dt = new Date(dateStr);
  //     dt.setDate(dt.getDate());
  //     $("#subscription_start_from").datepicker({
  //       format: 'dd/mm/yyyy',
  //       maxDate: dt,  
  //     });
  //   },
  //   forceParse : false,
  //   calendarWeeks : true,
  //   autoclose : true
  // });

</script>
@endsection