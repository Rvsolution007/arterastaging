@extends('layouts.app')

@section('extra_css')
<style type="text/css">
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

    .analytics-container {
        font-family: 'Poppins', sans-serif;
        padding: 1.5rem;
        background-color: #f8fafc;
        min-height: 100vh;
    }

    .page-title {
        font-weight: 700;
        color: #1e293b;
        font-size: 1.5rem;
        letter-spacing: -0.025em;
    }

    .premium-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .premium-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        background: #fff;
    }

    .premium-card-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .premium-card-body {
        padding: 2.5rem;
    }

    .form-label-premium {
        font-size: 0.875rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 0.5rem;
        display: block;
    }

    .form-control-premium {
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
        color: #334155;
        transition: all 0.2s ease;
        background-color: #f8fafc;
        width: 100%;
    }

    .form-control-premium:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        background-color: #ffffff;
    }

    .btn-premium {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white !important;
        border: none;
        padding: 0.85rem 2.5rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
        cursor: pointer;
    }

    .btn-premium:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4);
    }

    .preview-box {
        width: 150px;
        height: 150px;
        border-radius: 20px;
        border: 4px solid #fff;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        object-fit: cover;
        background: #f1f5f9;
        display: block;
        margin-bottom: 1.5rem;
    }

    .section-divider {
        height: 1px;
        background: #f1f5f9;
        margin: 2rem 0;
    }
</style>
@endsection

@section('content')
<div class="analytics-container">
    <div class="row align-items-center mb-4">
        <div class="col-md-12">
            <h4 class="page-title"><i class="fa-solid fa-pen-to-square mr-2 text-primary"></i> Update Business Type</h4>
        </div>
    </div>

    <div class="row justify-content-start">
        <div class="col-lg-8">
            <div class="premium-card">
                <div class="premium-card-header">
                    <h5 class="premium-card-title">Business Type Details</h5>
                </div>
                <div class="premium-card-body">
                    @if (count($errors) > 0)
                        <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius: 12px;">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {!! Form::open(['route' =>['business-type.update',$type->id],'method'=>'PATCH','files'=>true]) !!}
                    {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
                    {!! Form::hidden('id',$type->id)!!}

                    <div class="row">
                        <div class="col-md-7">
                            <div class="form-group mb-4">
                                {!! Form::label('category','Parent Business Category', ['class' => 'form-label-premium']) !!}
                                <select id="business_category_id" class="form-control-premium" required>
                                    <option value="">Select Category</option>
                                    @foreach($businessCategory as $c)
                                        <option value="{{$c->id}}" @if(isset($type->business_sub_category) && $c->id == $type->business_sub_category->business_category_id) selected @endif>{{$c->name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group mb-4">
                                {!! Form::label('sub_category','Parent Business Sub Category', ['class' => 'form-label-premium']) !!}
                                <select id="business_sub_category_id" name="business_sub_category_id" class="form-control-premium" required>
                                    <option value="">Select Sub Category</option>
                                    @foreach($businessSubCategory as $c)
                                        <option value="{{$c->id}}" @if($c->id == $type->business_sub_category_id) selected @endif>{{$c->name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group mb-4">
                                {!! Form::label('name','Type Name', ['class' => 'form-label-premium']) !!}
                                {!! Form::text('name',$type->name,['class' => 'form-control-premium','required','placeholder'=>'Enter type name']) !!}
                            </div>

                            <div class="form-group mb-4">
                                {!! Form::label('icon','Type Icon', ['class' => 'form-label-premium']) !!}
                                <input class="form-control-premium" type="file" id="image" name="icon" style="padding: 0.5rem 1rem;">
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="d-flex flex-column align-items-center">
                                <label class="form-label-premium w-100 mb-3 text-center">Visual Preview</label>
                                <div id="preview">
                                    <img src="@if($type->icon) @if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$type->icon)}} @else {{asset('uploads/'.$type->icon)}} @endif @else {{asset('assets/images/no-image.png')}} @endif" 
                                         class="preview-box" alt="Type Icon">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section-divider"></div>

                    <div class="text-center mt-2">
                        @if(optional(Auth::user())->user_type == "Demo")
                        <button type="button" class="btn-premium ToastrButton">Update Type</button>
                        @else
                        <button type="submit" class="btn-premium"><i class="fa-solid fa-cloud-arrow-up mr-2"></i> Update Type</button>
                        @endif
                        <a href="{{ route('business-type.index') }}" class="btn btn-link text-muted ml-3" style="font-weight: 500; font-size: 0.9rem; text-decoration: none;">Cancel</a>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section("script")
<script type="text/javascript">
    $(document).ready(function() {
        $('#business_category_id').select2();
        $('#business_sub_category_id').select2();

        $('#business_category_id').on('change', function() {
            var category_id = $(this).val();
            if(category_id) {
                $.ajax({
                    url: "{{ url('admin/business-type/get-sub-categories') }}",
                    type: "GET",
                    data: { category_id: category_id },
                    success: function(data) {
                        $('#business_sub_category_id').empty();
                        $('#business_sub_category_id').append('<option value="">Select Sub Category</option>');
                        $.each(data, function(key, value) {
                            $('#business_sub_category_id').append('<option value="'+ value.id +'">'+ value.name +'</option>');
                        });
                        $('#business_sub_category_id').trigger('change');
                    }
                });
            } else {
                $('#business_sub_category_id').empty();
                $('#business_sub_category_id').append('<option value="">Select Sub Category</option>');
                $('#business_sub_category_id').trigger('change');
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
                $('#preview').html('<img src="'+event.target.result+'" class="preview-box" alt="Selected Icon" />');
            };
            fileReader.readAsDataURL(fileInput.files[0]);
        }
    }

    $("#image").change(function () {
        imagePreview(this);
    });

    $('.ToastrButton').click(function() {
      toastr.error('This Action Not Available Demo User');
    });
</script>
@endsection