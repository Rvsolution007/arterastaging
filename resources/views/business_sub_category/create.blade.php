@extends('layouts.app')

@section('extra_css')
<style type="text/css">

</style>
@endsection

@section('content')
<div class="container">
    <div class="card card-success">
        <div class="card-header">
        <h3 class="card-title">Add Business Sub Category</h3>
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

        {!! Form::open(['route' => 'business-sub-category.store','method'=>'post','files'=>true]) !!}
        {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('category','Business Category', ['class' => 'col-sm-3 col-form-label','placeholder'=>'Enter Name']) !!}
                    <div class="col-sm-4">
                        <select id="business_category_id" name="business_category_id" class="form-control" required>
                            <option value="">Select Business Category</option>
                            @foreach($category as $c)
                                <option value="{{$c->id}}">{{$c->name}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('name','Name', ['class' => 'col-sm-3 col-form-label']) !!}
                    <div class="col-sm-4">
                        {!! Form::text('name', null,['class' => 'form-control','required','placeholder'=>'Enter Name']) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('has_business_type','Has Business Type', ['class' => 'col-sm-3 col-form-label']) !!}
                    <div class="col-sm-4" style="display:flex; align-items:center;">
                        <input type="checkbox" name="has_business_type" id="has_business_type" value="1" style="width:20px; height:20px; margin-right:10px;">
                        <span style="font-size:0.9rem; color:#64748b;">If checked, this sub-category will require a Business Type.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('icon',' Select Image', ['class' => 'col-sm-3 col-form-label']) !!}
                    <div class="col-sm-4"><input class="form-control" type="file" id="image" name="icon" required></div>
                </div>
                <div class="row"><div class="col-sm-3"></div><div class="col-sm-6" id="preview"><img type="image" class="shadow bg-white rounded" src="{{asset('assets/images/no-image.png')}}" alt="Image" style="width: auto;height: 120px" /></div></div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 m-3 text-center">
            @if(optional(Auth::user())->user_type == "Demo")
            <button type="button" class="btn btn-success ToastrButton">Save</button>
            @else 
            {!! Form::submit('Save', ['class' => 'btn btn-success']) !!}
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
        $('#business_category_id').select2();
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