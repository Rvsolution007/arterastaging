@extends('layouts.app')

@section('extra_css')
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.11/summernote-lite.css" rel="stylesheet">
<style type="text/css">

</style>
@endsection

@section('content')
<div class="container">
    <div class="card card-success">
        <div class="card-header">
        <h3 class="card-title">Add Product</h3>
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

        {!! Form::open(['route' => 'product.store','method'=>'post','files'=>true]) !!}
        {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('category','Select Product Category', ['class' => 'col-sm-3 col-form-label','placeholder'=>'Enter Name']) !!}
                    <div class="col-sm-4">
                        <select id="product_category_id" name="product_category_id" class="form-control" required>
                            <option value="">Select Product Category</option>
                            @foreach($productCategory as $c)
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
                    {!! Form::label('title','Title', ['class' => 'col-sm-3 col-form-label']) !!}
                    <div class="col-sm-4">
                        {!! Form::text('title', null,['class' => 'form-control','required','placeholder'=>'Enter Title']) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('image',' Select Image', ['class' => 'col-sm-3 col-form-label']) !!}
                    <div class="col-sm-4"><input class="form-control" type="file" id="image" name="image" required></div>
                </div>
                <div class="row mb-3"><div class="col-sm-3"></div><div class="col-sm-6" id="preview"><img type="image" class="shadow bg-white rounded" src="{{asset('assets/images/no-image.png')}}" alt="Image" style="width: auto;height: 120px"/></div></div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('price','Price', ['class' => 'col-sm-3 col-form-label']) !!}
                    <div class="col-sm-4">
                        {!! Form::number('price', null,['class' => 'form-control','required','placeholder'=>'Enter Price']) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('discount_price','Discount Price', ['class' => 'col-sm-3 col-form-label']) !!}
                    <div class="col-sm-4">
                        {!! Form::number('discount_price', null,['class' => 'form-control','required','placeholder'=>'Enter Discount Price']) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('description','Description', ['class' => 'col-sm-3 col-form-label']) !!}
                    <div class="col-sm-9">
                        {!! Form::textarea('description', null,['class' => 'form-control','required','rows' => 10,'placeholder'=>'Enter Description']) !!}
                    </div>
                </div>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.11/summernote-lite.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#product_category_id').select2();
    });

    $('#description').summernote({
        placeholder: '',
        tabsize: 2,
        height: 200
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