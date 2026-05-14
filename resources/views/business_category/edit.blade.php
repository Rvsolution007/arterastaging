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
            <h4 class="page-title"><i class="fa-solid fa-pen-to-square mr-2 text-primary"></i> Update Business Category</h4>
        </div>
    </div>

    <div class="row justify-content-start">
        <div class="col-lg-8">
            <div class="premium-card">
                <div class="premium-card-header">
                    <h5 class="premium-card-title">Category Details</h5>
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

                    {!! Form::open(['route' =>['business-category.update',$category->id],'method'=>'PATCH','files'=>true]) !!}
                    {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
                    {!! Form::hidden('id',$category->id)!!}

                    <div class="row">
                        <div class="col-md-7">
                            <div class="form-group mb-5">
                                {!! Form::label('name','Category Name', ['class' => 'form-label-premium']) !!}
                                {!! Form::text('name',$category->name,['class' => 'form-control-premium','required','placeholder'=>'e.g. Technology, Healthcare...']) !!}
                            </div>

                            <div class="form-group mb-4">
                                {!! Form::label('icon','Category Icon / Image', ['class' => 'form-label-premium']) !!}
                                <input class="form-control-premium" type="file" id="image" name="icon" style="padding: 0.5rem 1rem;">
                                <small class="text-muted mt-2 d-block">Recommended size: 512x512 pixels. PNG or JPG format.</small>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="d-flex flex-column align-items-center">
                                <label class="form-label-premium w-100 mb-3 text-center">Visual Preview</label>
                                <div id="preview">
                                    <img src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$category->icon)}} @else {{asset('uploads/'.$category->icon)}} @endif" 
                                         class="preview-box" alt="Category Icon">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section-divider"></div>

                    <div class="text-center mt-2">
                        @if(optional(Auth::user())->user_type == "Demo")
                        <button type="button" class="btn-premium ToastrButton">Update Category</button>
                        @else
                        <button type="submit" class="btn-premium"><i class="fa-solid fa-cloud-arrow-up mr-2"></i> Update Category</button>
                        @endif
                        <a href="{{ route('business-category.index') }}" class="btn btn-link text-muted ml-3" style="font-weight: 500; font-size: 0.9rem; text-decoration: none;">Cancel</a>
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
        // Initialization if needed
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