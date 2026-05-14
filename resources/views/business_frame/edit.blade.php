@extends('layouts.app')

@section('extra_css')
<style type="text/css">

</style>
@endsection

@section('content')
<div class="container">
<div class="card card-success">
    <div class="card-header">
    <h3 class="card-title">Update Business Frame Image</h3>
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

    {!! Form::open(['route' =>['business-frame.update',$businessFrame->id],'method'=>'PATCH','files'=>true]) !!}
    {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
    {!! Form::hidden('id',$businessFrame->id)!!}
    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('category','Select Business Category', ['class' => 'col-sm-3 col-form-label']) !!}
                <div class="col-sm-4">
                    <select id="business_category_id" name="business_category_id" class="form-control" required>
                        <option value="">Select Business Category</option>
                        @foreach($category as $c)
                            <option value="{{$c->id}}" @if($c->id == $businessFrame->business_category_id) selected @endif>{{$c->name}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('subCategory','Business Sub Category', ['class' => 'col-sm-3 col-form-label']) !!}
                <div class="col-sm-4">
                    <select id="business_sub_category_id" name="business_sub_category_id" class="form-control">
                        <option value="">Select Sub Business Category</option>
                        @foreach($businessSubCategory as $cat)
                            <option value="{{$cat->id}}" @if($cat->id == $businessFrame->business_sub_category_id) selected @endif>{{$cat->name}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    </div>

    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('frame_image',' Select Frame Image', ['class' => 'col-sm-3 col-form-label']) !!}
                <div class="col-sm-4"><input class="form-control" type="file" id="frame_image" name="frame_image"></div>
            </div>
            <div class="row">
                <div class="col-sm-3" style="margin-top:-20px;">
                    <small class="form-text text-muted">
                        Recommendation Size: 
                        <ul>
                            <li>1024*1024 px (Square)</li>
                            <li>1080*1350 px (Portrait)</li>
                            <li>1280*720 px (Landscap)</li>
                            <li>1080*1920 px (Story)</li>
                        </ul>
                    </small>
                </div>
                <div class="col-sm-6" id="preview"><img src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$businessFrame->frame_image)}} @else {{asset('uploads/'.$businessFrame->frame_image)}} @endif" alt="Image" class="shadow bg-white rounded" width="auto" height="120px"></div>
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
        $('#category_id').select2();
        $('#business_category_id').select2();
        $('#business_sub_category_id').select2();
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

    $("#frame_image").change(function () {
        imagePreview(this);
    });

    $('#business_category_id').on('change', function () {
        var id = $(this).val();
        
        $.ajax({
            type: "GET",
            url: "{{url('admin/get-business-sub-category')}}",
            data: {id : id},
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(data) {
                $('#business_sub_category_id').empty();
                $("#business_sub_category_id").append('<option value="">Select Business Sub Category</option>');
                $.each(data, function (key, val) {
                    $("#business_sub_category_id").append('<option value='+val.id+'>'+val.name+'</option>');
                });
                $('#business_sub_category_id').select2();
            },
        });
    });
</script>
@endsection