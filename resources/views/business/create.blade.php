@extends('layouts.app')

@section('extra_css')
<style type="text/css">

</style>
@endsection

@section('content')
<div class="container">
    <div class="card card-success">
        <div class="card-header">
        <h3 class="card-title">Add Business</h3>
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

        {!! Form::open(['route' => 'business.store','method'=>'post','files'=>true]) !!}
        @if(!empty($data))
        {!! Form::hidden('user_id', $data)!!}
        @else
        {!! Form::hidden('user_id', optional(Auth::user())->id)!!}
        @endif
        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('name','Name', ['class' => 'col-sm-2 col-form-label']) !!}
                    <div class="col-sm-4">
                        {!! Form::text('name', null,['class' => 'form-control','required','placeholder'=>'Enter Business Name']) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('email','Email', ['class' => 'col-sm-2 col-form-label']) !!}
                    <div class="col-sm-4">
                        {!! Form::email('email', null,['class' => 'form-control','required','placeholder'=>'Enter Business Email']) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('mobile_no','Mobile No', ['class' => 'col-sm-2 col-form-label']) !!}
                    <div class="col-sm-4">
                        {!! Form::number('mobile_no', null,['class' => 'form-control','required','placeholder'=>'Enter Business Mobile Number']) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('address','Address', ['class' => 'col-sm-2 col-form-label']) !!}
                    <div class="col-sm-4">
                        {!! Form::textarea('address', null,['class' => 'form-control','required','rows'=>4,'placeholder'=>'Enter Business Address']) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('website','Website', ['class' => 'col-sm-2 col-form-label']) !!}
                    <div class="col-sm-4">
                        {!! Form::text('website', null,['class' => 'form-control','required','placeholder'=>'Enter Business Website']) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('category','Business Category', ['class' => 'col-sm-2 col-form-label','placeholder'=>'Enter Name']) !!}
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

        <div class="row" id="sub_category_wrapper" style="display: none;">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('sub_category','Sub Category', ['class' => 'col-sm-2 col-form-label']) !!}
                    <div class="col-sm-4">
                        <select id="business_sub_category_ids" name="business_sub_category_ids[]" class="form-control" multiple="multiple" style="width: 100%;">
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('logo','Logo', ['class' => 'col-sm-2 col-form-label']) !!}
                    <div class="col-sm-4">
                        <input class="form-control" type="file" id="logo" name="logo" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2"></div>
                    <div class="col-sm-9" id="preview"><img type="image" class="shadow bg-white rounded" src="{{asset('assets/images/no-image.png')}}" alt="Image" style="width: 120px;height: 120px"/></div> 
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
<script type="text/javascript">
    $(document).ready(function() {
        $('#status_id').select2();
        $('#business_category_id').select2();
        $('#business_sub_category_ids').select2();

        $('#business_category_id').on('change', function() {
            var categoryId = $(this).val();
            if(categoryId) {
                $.ajax({
                    url: '{{ url('get-business-sub-category') }}',
                    type: 'GET',
                    data: { id: categoryId },
                    dataType: 'json',
                    success: function(response) {
                        if(response && response.length > 0) {
                            $('#business_sub_category_ids').empty();
                            $.each(response, function(key, subcat) {
                                $('#business_sub_category_ids').append('<option value="'+ subcat.id +'">'+ subcat.name +'</option>');
                            });
                            $('#sub_category_wrapper').slideDown();
                            $('#business_sub_category_ids').select2();
                        } else {
                            $('#sub_category_wrapper').slideUp();
                            $('#business_sub_category_ids').empty();
                            $('#business_sub_category_ids').select2();
                        }
                    }
                });
            } else {
                $('#sub_category_wrapper').slideUp();
                $('#business_sub_category_ids').empty();
                $('#business_sub_category_ids').select2();
            }
        });

        if($('#business_category_id').val()) {
            $('#business_category_id').trigger('change');
        }
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

    $("#logo").change(function () {
        imagePreview(this);
    });
</script>
@endsection