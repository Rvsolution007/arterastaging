@extends('layouts.app')

@section('extra_css')
<style type="text/css">

</style>
@endsection

@section('content')
<div class="container">
<div class="card card-success">
    <div class="card-header">
    <h3 class="card-title">Update Festivals</h3>
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

    {!! Form::open(['route' =>['festivals.update',$festivals->id],'method'=>'PATCH','files'=>true]) !!}
    {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
    {!! Form::hidden('id',$festivals->id)!!}
    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('title','Title', ['class' => 'col-sm-2 col-form-label']) !!}
                <div class="col-sm-4">
                    {!! Form::text('title',$festivals->title,['class' => 'form-control','required','placeholder'=>'Enter Title']) !!}
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('image',' Select Image', ['class' => 'col-sm-2 col-form-label']) !!}
                <div class="col-sm-4"><input class="form-control" type="file" id="image" name="image"></div>
            </div>
            <div class="row">
                <div class="col-sm-2"></div>
                <div class="col-sm-9 mb-3" id="preview"><img src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$festivals->image)}} @else {{asset('uploads/'.$festivals->image)}} @endif" alt="Image" class="shadow bg-white rounded" width="auto" height="120px"></div> 
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('festivals_date','Festivals Date', ['class' => 'col-sm-2 col-form-label']) !!}
                <div class="col-sm-4">
                    {!! Form::text('festivals_date',date_format(date_create(implode("", preg_split("/[-\s:,]/",$festivals->festivals_date))),"d M, y"),['class' => 'form-control datepicker','required','placeholder'=>'Ex. 12 Jun, 22', 'autocomplete' => "off"]) !!}
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('activation_date','Activation Date', ['class' => 'col-sm-2 col-form-label']) !!}
                <div class="col-sm-4">
                    {!! Form::text('activation_date',date_format(date_create(implode("", preg_split("/[-\s:,]/",$festivals->activation_date))),"d M, y"),['class' => 'form-control datepicker','required','placeholder'=>'Ex. 12 Jun, 22', 'autocomplete' => "off"]) !!}
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
        $('#status_id').select2();
        $('.datepicker').datepicker({
            dateFormat: 'dd M, y',
            minDate:'today',
        });
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
</script>
@endsection