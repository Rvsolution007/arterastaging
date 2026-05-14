@extends('layouts.app')

@section('extra_css')
<style type="text/css">

</style>
@endsection

@section('content')
<div class="container">
    <div class="card card-success">
        <div class="card-header">
        <h3 class="card-title">Update Offer</h3>
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

        {!! Form::open(['route' =>['offer.update',$offer->id],'method'=>'PATCH','files'=>true]) !!}
        {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
        {!! Form::hidden('id',$offer->id)!!}
        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('name','Name', ['class' => 'col-sm-3 col-form-label','placeholder'=>'Enter Name']) !!}
                    <div class="col-sm-4">
                        {!! Form::text('name',$offer->name,['class' => 'form-control','required']) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="Image" class="col-form-label">Select Image</label>
                        <small id="imageHelp" class="form-text text-muted">(Recommended Size : 1024x1200)<br>(Accept png,jpg,jpeg image files)</small>
                    </div>
                    <div class="col-sm-4"><input class="form-control" type="file" id="image" name="image"></div>
                </div>
                <div class="row mb-3" style="margin-top:-40px;">
                    <div class="col-sm-3"></div>
                    @if($offer->image != null)
                    <div class="col-sm-6" id="preview"><img src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$offer->image)}} @else {{asset('uploads/'.$offer->image)}} @endif" alt="Image" class="shadow bg-white rounded" width="auto" height="120px"></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="Banner" class="col-form-label">Select Banner</label>
                        <small id="bannerHelp" class="form-text text-muted">(Recommended Size : 900x450, 900x300)<br>(Accept png,jpg,jpeg image files)</small>
                    </div>
                    <div class="col-sm-4"><input class="form-control" type="file" id="banner" name="banner"></div>
                </div>
                <div class="row mb-3" style="margin-top:-40px;">
                    <div class="col-sm-3"></div>
                    @if($offer->banner != null)
                    <div class="col-sm-6" id="preview1"><img src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$offer->banner)}} @else {{asset('uploads/'.$offer->banner)}} @endif" alt="Image" class="shadow bg-white rounded" width="auto" height="120px"></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('subscription','Select Subscription Plan', ['class' => 'col-sm-3 col-form-label']) !!}
                    <div class="col-sm-4">
                        <select id="subscription_id" name="subscription_id" class="form-control" required>
                            <option value="">Select Subscription Plan</option>
                            @foreach($subscription as $s)
                                <option value="{{$s->id}}" @if($s->id == $offer->subscription_id) selected @endif>{{$s->plan_name}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 m-3 text-center">
            @if(optional(Auth::user())->user_type == "Demo")
            <button type="button" class="btn btn-success ToastrButton">Update</button>
            @else
            {!! Form::submit('Update', ['class' => 'btn btn-success']) !!}
            @endif
            </div>
        </div>
        {!! Form::close() !!}
        </div>
    </div>
</div>
@endsection

@section("script")
<script type="text/javascript">
    $(document).ready(function() {
        $('#subscription_id').select2();
    });

    function imagePreview(fileInput) 
    { 
        if (fileInput.files && fileInput.files[0]) 
        {
            var fileReader = new FileReader();
            fileReader.onload = function (event) 
            {
                $('#preview').html('<img src="'+event.target.result+'" class="shadow bg-white rounded" width="auto" alt="Select Image" height="120px"/>');
            };
            fileReader.readAsDataURL(fileInput.files[0]);
        }
    }

    $("#image").change(function () {
        imagePreview(this);
    });

    function imagePreview1(fileInput) 
    { 
        if (fileInput.files && fileInput.files[0]) 
        {
            var fileReader = new FileReader();
            fileReader.onload = function (event) 
            {
                $('#preview1').html('<img src="'+event.target.result+'" class="shadow bg-white rounded" width="auto" alt="Select Image" height="120px"/>');
            };
            fileReader.readAsDataURL(fileInput.files[0]);
        }
    }

    $("#banner").change(function () {
        imagePreview1(this);
    });
</script>
@endsection