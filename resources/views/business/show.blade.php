@extends('layouts.app')

@section('heading')
  <div class="text-primary">View Business</div>
@endsection

@section('extra_css')
  <style type="text/css">
    .card {
      position: relative;
      width: 100%;
      border-radius: 10px;
      border: none;
    }

    .upper {
      height: 100px;
    }

    .upper img {
      width: 100%;
      height: 100px;
      border-top-left-radius: 10px;
      border-top-right-radius: 10px;
    }

    .user {
      position: relative;
    }

    .profile {
      height: 100px;
      width: 100px;
      position: absolute;
      left: 50%;
      top: 15%;
      transform: translate(-50%, -50%);
    }
  </style>
@endsection

@section('content')
  <div class="row border-top">
    <div class="col-md-1 d-md-block"></div>
    <div class="col-md-5 mt-5">
      <div class="card card-light">
        <div class="upper">
          <img src="{{asset('assets/images/BG1.png')}}" class="img-fluid">
        </div>
        <div class="user text-center">
          <div class="profile">
            <img
              src="@if($data->logo) @if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/' . $data->logo)}} @else {{asset('uploads/' . $data->logo)}} @endif @else {{asset('assets/images/no-user.jpg')}} @endif"
              class="rounded-circle border-primary shadow" style="background-color:white;" width="90px" height="90px">
          </div>
        </div>
        <!-- <div class="w-100"><img src="@if($data->logo) {{asset('uploads/'.$data->logo)}} @else {{asset('assets/images/no-user.jpg')}} @endif" alt="Logo" class="rounded-circle profile_img shadow bg-white rounded border-primary"></div> -->
        <div class="mt-5 text-center">
          <h4 class="mb-0 text-primary">{{$data->name}}</h4>
          <span class="text-muted d-block">{{$data->address}}</span>
        </div>
        <div class="d-flex flex-column mt-4 mb-4">
          <div class="mt-2 ml-5">
            <i class="fa-solid fa-envelope text-primary"></i><span class="ml-3">{{$data->email}}</span>
          </div>

          <div class="mt-2 ml-5">
            <i class="fa-solid fa-phone text-primary"></i><span class="ml-3">{{$data->mobile_no}}</span>
          </div>

          <div class="mt-2 ml-5">
            <i class="fa-solid fa-globe text-primary"></i></i><span class="ml-3">{{$data->website}}</span>
          </div>

          <div class="mt-2 ml-5">
            <i class="fa-solid fa-circle-plus text-primary"></i></i><span
              class="ml-3">{{$data->created_at->format('d/m/Y')}}</span>
          </div>

          <div class="mt-2 ml-5">
            <i class="fa-solid fa-list text-primary"></i></i><span
              class="ml-3">@if($data->business_category_id){{$data->business_category->name}}@endif</span>
          </div>

          @if($data->extra_emails && count($data->extra_emails) > 0)
          <div class="mt-3 ml-5">
            <small class="text-muted d-block mb-1"><b>Extra Emails</b></small>
            @foreach($data->extra_emails as $extraEmail)
            <div class="mt-1">
              <i class="fa-regular fa-envelope text-secondary"></i><span class="ml-3">{{ $extraEmail }}</span>
            </div>
            @endforeach
          </div>
          @endif

          @if($data->extra_mobile_numbers && count($data->extra_mobile_numbers) > 0)
          <div class="mt-3 ml-5">
            <small class="text-muted d-block mb-1"><b>Extra Mobile Numbers</b></small>
            @foreach($data->extra_mobile_numbers as $extraPhone)
            <div class="mt-1">
              <i class="fa-solid fa-mobile-screen text-secondary"></i><span class="ml-3">{{ $extraPhone }}</span>
            </div>
            @endforeach
          </div>
          @endif

          @if($data->extra_websites && count($data->extra_websites) > 0)
          <div class="mt-3 ml-5">
            <small class="text-muted d-block mb-1"><b>Extra Websites</b></small>
            @foreach($data->extra_websites as $extraWeb)
            <div class="mt-1">
              <i class="fa-solid fa-link text-secondary"></i><span class="ml-3">{{ $extraWeb }}</span>
            </div>
            @endforeach
          </div>
          @endif

          @if($data->extra_addresses && count($data->extra_addresses) > 0)
          <div class="mt-3 ml-5">
            <small class="text-muted d-block mb-1"><b>Extra Addresses</b></small>
            @foreach($data->extra_addresses as $extraAddr)
            <div class="mt-1">
              <i class="fa-solid fa-location-dot text-secondary"></i><span class="ml-3">{{ $extraAddr }}</span>
            </div>
            @endforeach
          </div>
          @endif
        </div>
      </div>
      <!-- <div class="card-body">
            @if (count($errors) > 0)
              <div class="alert alert-danger">
                <ul>
                  @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <div class="row mt-5">
              <div class="col-12">
                <div class="form-group row">
                  {!! Form::label('name','Name', ['class' => 'col-sm-3 col-form-label text-primary']) !!}
                  <span class="my-auto col-sm-9">{{$data->name}}</sapn>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-12">
                <div class="form-group row">
                  {!! Form::label('email','Email', ['class' => 'col-sm-3 col-form-label text-primary']) !!}
                  <span class="my-auto col-sm-9">{{$data->email}}</sapn>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-12">
                <div class="form-group row">
                  {!! Form::label('mobile_no','Mobile No', ['class' => 'col-sm-3 col-form-label text-primary']) !!}
                  <span class="my-auto col-sm-9">{{$data->mobile_no}}</sapn>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-12">
                <div class="form-group row">
                  {!! Form::label('website','Website', ['class' => 'col-sm-3 col-form-label text-primary']) !!}
                  <span class="my-auto col-sm-9">{{$data->website}}</sapn>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-12">
                <div class="form-group row">
                  {!! Form::label('address','Address', ['class' => 'col-sm-3 col-form-label text-primary']) !!}
                  <span class="my-auto col-sm-9">{{$data->address}}</sapn>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-12">
                <div class="form-group row">
                  {!! Form::label('created_at','Created', ['class' => 'col-sm-3 col-form-label text-primary']) !!}
                  <span class="my-auto col-sm-9">{{$data->created_at->format('d/m/Y')}}</sapn>
                </div>
              </div>
            </div>
          </div>
        </div> -->
    </div>
    <div class="col-md-5 mt-5">
      <div class="card card-light">
        <div class="upper">
          <img src="{{asset('assets/images/BG2.png')}}" class="img-fluid">
        </div>
        <div class="user text-center">
          <div class="profile">
            <img
              src="@if($data->user->image) @if(substr($data->user->image, 0, 4) == 'http') {{$data->user->image}} @else {{asset('uploads/' . $data->user->image)}} @endif @else {{asset('assets/images/no-user.jpg')}} @endif"
              class="rounded-circle border-primary shadow" style="background-color:white;" width="90px" height="90px">
          </div>
        </div>
        <!-- <div class="w-100"><img src="@if($data->logo) {{asset('uploads/'.$data->logo)}} @else {{asset('assets/images/no-user.jpg')}} @endif" alt="Logo" class="rounded-circle profile_img shadow bg-white rounded border-primary"></div> -->
        <div class="mt-5 text-center">
          <h4 class="mb-0 text-primary">{{$data->user->name}}</h4>
          <span class="text-muted d-block">{{$data->user->email}}</span>
        </div>
        <div class="d-flex flex-column mt-4 mb-4">
          <div class="mt-2 ml-5">
            <i class="fa-solid fa-phone text-primary"></i><span class="ml-3">{{$data->user->mobile_no}}</span>
          </div>

          <div class="mt-2 ml-5">
            <i class="fa-solid fa-dollar-sign fa-xl text-primary"></i><span
              class="ml-3">@if($data->user->subscription_id){{$data->user->subscription->plan_name}}@endif</span>
          </div>

          <div class="mt-2 ml-5">
            <i class="fa-solid fa-hourglass-start text-primary"></i></i><span
              class="ml-3">@if($data->user->subscription_id){{date("d M, y", strtotime($data->user->subscription_start_date))}}@endif</span>
          </div>

          <div class="mt-2 ml-5">
            <i class="fa-solid fa-hourglass-end text-primary"></i></i><span
              class="ml-3">@if($data->user->subscription_id){{date("d M, y", strtotime($data->user->subscription_end_date))}}@endif</span>
          </div>
        </div>
        <!-- <div class="w-100"><img src="@if($data->user->image) @if(substr($data->user->image, 0, 4)=="http") {{$data->user->image}} @else {{asset('uploads/'.$data->user->image)}} @endif @else {{asset('assets/images/no-user.jpg')}} @endif" alt="Profile Image" class="rounded-circle profile_img shadow bg-white rounded border-primary"></div>
          <div class="card-body">

            <div class="row mt-5">
              <div class="col-12">
                <div class="form-group row">
                  {!! Form::label('name','Name', ['class' => 'col-sm-4 col-form-label text-primary']) !!}
                  <span class="my-auto col-sm-8">{{$data->user->name}}</sapn>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-12">
                <div class="form-group row">
                  {!! Form::label('email','Email', ['class' => 'col-sm-4 col-form-label text-primary']) !!}
                  <span class="my-auto col-sm-8">{{$data->user->email}}</sapn>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-12">
                <div class="form-group row">
                  {!! Form::label('mobile_no','Mobile No', ['class' => 'col-sm-4 col-form-label text-primary']) !!}
                  <span class="my-auto col-sm-8">{{$data->user->mobile_no}}</sapn>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-12">
                <div class="form-group row">
                  {!! Form::label('created_at','Entry', ['class' => 'col-sm-4 col-form-label text-primary']) !!}
                  <span class="my-auto col-sm-8">{{$data->user->created_at->format('d/m/Y')}}</sapn>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-12">
                <div class="form-group row">
                  {!! Form::label('current_plan','Current Plan', ['class' => 'col-sm-4 col-form-label text-primary']) !!}
                  <span class="my-auto col-sm-8">@if($data->user->subscription_id){{$data->user->subscription->plan_name}}@endif</sapn>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-12">
                <div class="form-group row">
                  {!! Form::label('sub_date','Subscription Start Date', ['class' => 'col-sm-4 col-form-label text-primary']) !!}
                  <span class="my-auto col-sm-8">@if($data->user->subscription_id){{$data->user->subscription_start_date}}@endif</sapn>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-12">
                <div class="form-group row">
                  {!! Form::label('expire_date','Expire Date', ['class' => 'col-sm-4 col-form-label text-primary']) !!}
                  <span class="my-auto col-sm-8">@if($data->user->subscription_id){{$data->user->subscription_end_date}}@endif</sapn>
                </div>
              </div>
            </div>
          </div>
        </div> -->
      </div>
    </div>
@endsection

  @section("script")
    <script type="text/javascript">
      $('.datepicker').datepicker({
        format: 'dd/mm/yyyy',
      });
    </script>
  @endsection