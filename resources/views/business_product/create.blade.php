@extends('layouts.app')

@section('extra_css')
<style type="text/css">

</style>
@endsection

@section('content')
<div class="container">
    <div class="card card-success">
        <div class="card-header">
        <h3 class="card-title">Add Business Product</h3>
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

        {!! Form::open(['route' => 'business-product.store','method'=>'post','files'=>true]) !!}
        {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('category','Business Category', ['class' => 'col-sm-3 col-form-label']) !!}
                    <div class="col-sm-4">
                        <select name="business_category_id" id="business_category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $c)
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
                    {!! Form::label('sub_category','Business Sub Category', ['class' => 'col-sm-3 col-form-label']) !!}
                    <div class="col-sm-4">
                        <select name="business_sub_category_id" id="business_sub_category_id" class="form-control" required>
                            <option value="">Select Sub Category</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" id="business_type_container" style="display: none;">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('type','Business Type', ['class' => 'col-sm-3 col-form-label']) !!}
                    <div class="col-sm-4">
                        <select id="business_type_id" name="business_type_id" class="form-control">
                            <option value="">Select Business Type (Optional)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('product_type','Product Type', ['class' => 'col-sm-3 col-form-label']) !!}
                    <div class="col-sm-4">
                        <select id="product_type_id" name="product_type_id" class="form-control" required>
                            <option value="">Select Product Type</option>
                            @foreach($productTypes as $pt)
                                <option value="{{$pt->id}}" {{ old('product_type_id') == $pt->id ? 'selected' : '' }}>{{$pt->name}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('brand','Brand', ['class' => 'col-sm-3 col-form-label']) !!}
                    <div class="col-sm-4">
                        <select id="brand_id" name="brand_id[]" class="form-control" multiple>
                            <option value="">Select Brand (Optional)</option>
                            @foreach($brands as $b)
                                <option value="{{$b->id}}" {{ (is_array(old('brand_id')) && in_array($b->id, old('brand_id'))) ? 'selected' : '' }}>{{$b->name}}</option>
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
                <div class="form-group row">
                    {!! Form::label('keywords','Keywords', ['class' => 'col-sm-3 col-form-label']) !!}
                    <div class="col-sm-4">
                        {!! Form::text('keywords', null,['class' => 'form-control','placeholder'=>'Enter comma separated keywords']) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('icon',' Select Image', ['class' => 'col-sm-3 col-form-label']) !!}
                    <div class="col-sm-4"><input class="form-control" type="file" id="image" name="icon"></div>
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
        $('#business_category_id').select2();
        $('#business_sub_category_id').select2();
        $('#business_type_id').select2();
        $('#product_type_id').select2();
        $('#brand_id').select2();

        $('#business_category_id').on('change', function() {
            var category_id = $(this).val();
            if(category_id) {
                $.ajax({
                    url: "{{ url('admin/business-product/get-sub-categories') }}",
                    type: "GET",
                    data: { category_id: category_id },
                    success: function(data) {
                        $('#business_sub_category_id').empty();
                        $('#business_sub_category_id').append('<option value="" data-has-business-type="0">Select Sub Category</option>');
                        $.each(data.subCategories, function(key, value) {
                            $('#business_sub_category_id').append('<option value="'+ value.id +'" data-has-business-type="'+ value.has_business_type +'">'+ value.name +'</option>');
                        });
                        $('#business_sub_category_id').trigger('change');
                    }
                });
            } else {
                $('#business_sub_category_id').empty();
                $('#business_sub_category_id').append('<option value="" data-has-business-type="0">Select Sub Category</option>');
                $('#business_sub_category_id').trigger('change');
            }
        });

        $('#business_sub_category_id').on('change', function() {
            var sub_category_id = $(this).val();
            var has_business_type = $(this).find(':selected').data('has-business-type');
            
            if (has_business_type == 1) {
                $('#business_type_container').show();
            } else {
                $('#business_type_container').hide();
                $('#business_type_id').val('').trigger('change');
            }

            if(sub_category_id && has_business_type == 1) {
                $.ajax({
                    url: "{{ url('admin/business-product/get-business-types') }}",
                    type: "GET",
                    data: { sub_category_id: sub_category_id },
                    success: function(data) {
                        $('#business_type_id').empty();
                        $('#business_type_id').append('<option value="">Select Business Type (Optional)</option>');
                        if (data.businessTypes && data.businessTypes.length > 0) {
                            $.each(data.businessTypes, function(key, value) {
                                $('#business_type_id').append('<option value="'+ value.id +'">'+ value.name +'</option>');
                            });
                        }
                        $('#business_type_id').trigger('change');
                    }
                });
            } else {
                $('#business_type_id').empty();
                $('#business_type_id').append('<option value="">Select Business Type (Optional)</option>');
                $('#business_type_id').trigger('change');
            }
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
