@extends('layouts.app')

@section('extra_css')
<style type="text/css">

</style>
@endsection

@section('content')
<div class="container">
<div class="card card-success">
    <div class="card-header">
    <h3 class="card-title">Update Sticker</h3>
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

    {!! Form::open(['route' =>['sticker.update',$sticker->id],'method'=>'PATCH','files'=>true]) !!}
    {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
    {!! Form::hidden('id',$sticker->id)!!}
    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('category','Select Sticker Category', ['class' => 'col-sm-3 col-form-label','placeholder'=>'Enter Name']) !!}
                <div class="col-sm-4">
                    <select id="sticker_category_id" name="sticker_category_id" class="form-control" required>
                        <option value="">Select Sticker Category</option>
                        @foreach($category as $c)
                            <option value="{{$c->id}}" @if($c->id == $sticker->sticker_category_id) selected @endif>{{$c->name}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('keywords','Keywords (Tags)', ['class' => 'col-sm-3 col-form-label']) !!}
                <div class="col-sm-4">
                    {!! Form::text('keywords', $sticker->keywords, ['class' => 'form-control', 'placeholder' => 'e.g. hiring, suit, professional']) !!}
                    <small class="text-muted">These tags help in searching.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('image',' Select Image', ['class' => 'col-sm-3 col-form-label']) !!}
                <div class="col-sm-4">
                    <input class="form-control" type="file" id="image" name="image">
                    <small class="text-muted d-block mt-1"><i class="fa fa-info-circle text-info"></i> Note: Image will be automatically compressed and converted to <b>.webp</b> format for faster loading.</small>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-3"></div>
                <div class="col-sm-6" id="preview"><img src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$sticker->image)}} @else {{asset('uploads/'.$sticker->image)}} @endif" alt="Image" class="shadow bg-white rounded" width="auto" height="120px"></div>
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
        $('#sticker_category_id').select2();
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