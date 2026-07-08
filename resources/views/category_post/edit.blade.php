@extends('layouts.app')

@section('extra_css')
<style type="text/css">
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

    .admin-container {
        font-family: 'Poppins', sans-serif;
        padding: 1.5rem;
        background-color: #f8fafc;
        min-height: 100vh;
    }

    .page-title {
        font-weight: 700;
        color: #1e293b;
        font-size: 1.75rem;
        letter-spacing: -0.025em;
        margin-bottom: 0.25rem;
    }

    .premium-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -33px rgba(0, 0, 0, 0.03);
        overflow: hidden;
    }

    .card-header-premium {
        padding: 1.25rem 1.5rem;
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: white;
        border-bottom: none;
    }

    .card-title-premium {
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .form-label-premium {
        font-weight: 600;
        color: #475569;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .form-control-premium {
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    .form-control-premium:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        outline: none;
    }

    .btn-premium-save {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        border: none;
        padding: 0.75rem 2.5rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
    }

    .btn-premium-save:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4);
    }

    .recommendation-box {
        background-color: #f1f5f9;
        padding: 1.25rem;
        border-radius: 12px;
        border-left: 4px solid #6366f1;
    }

    .preview-container {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        min-height: 200px;
    }

    .preview-container img {
        max-width: 100%;
        max-height: 250px;
        object-fit: contain;
    }

    /* Select2 Customization */
    .select2-container--default .select2-selection--single {
        height: 42px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 10px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 40px !important;
        padding-left: 12px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
    }
</style>
@endsection

@section('content')
<div class="admin-container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('category-post.index') }}" class="mr-3 text-muted" style="font-size: 1.2rem;"><i class="fa fa-arrow-left"></i></a>
                <h1 class="page-title">Update Frame</h1>
            </div>

            <div class="premium-card">
                <div class="card-header-premium">
                    <h3 class="card-title-premium"><i class="fa fa-edit mr-2"></i> Update Configuration</h3>
                </div>

                <div class="card-body p-4">
                    @if (count($errors) > 0)
                        <div class="alert alert-danger shadow-sm border-0 mb-4" style="border-radius: 12px;">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {!! Form::open(['route' =>['category-post.update',$categoryPost->id],'method'=>'PATCH','files'=>true, 'class' => 'poppins-font']) !!}
                    {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
                    {!! Form::hidden('id',$categoryPost->id)!!}

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label-premium">Select Category</label>
                            <select id="category_id" name="category_id" class="form-control" required>
                                <option value="">Choose a category...</option>
                                @foreach($category as $c)
                                    <option value="{{$c->id}}" @if($c->id == $categoryPost->category_id) selected @endif>{{$c->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label-premium">Select Language</label>
                            <select id="language_id" name="language_id" class="form-control" required>
                                <option value="">Choose a language...</option>
                                @foreach($language as $l)
                                    <option value="{{$l->id}}" @if($l->id == $categoryPost->language_id) selected @endif>{{$l->title}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-4">
                            <label class="form-label-premium" for="is_ai">Is AI Template?</label>
                            <div class="d-flex align-items-center" style="margin-top: 5px;">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="is_ai" value="1" class="custom-control-input" id="is_ai" @if($categoryPost->is_ai == 1) checked @endif>
                                    <label class="custom-control-label" for="is_ai" style="font-weight: 500; color: #475569;">Yes, this is an AI template</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-4">
                            <label class="form-label-premium">Update Frame Image</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="frame_image" name="frame_image" accept=".jpg, .png, jpeg, .PNG, .JPG, .JPEG">
                                <label class="custom-file-label form-control-premium" for="frame_image">Choose new file to replace...</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="recommendation-box mb-4">
                                <h6 class="font-weight-bold mb-2 text-dark" style="font-size: 0.85rem;">Recommended Dimensions:</h6>
                                <ul class="mb-0 text-muted" style="font-size: 0.8rem; padding-left: 1.2rem;">
                                    <li>Square: 1024 × 1024 px</li>
                                    <li>Portrait: 1080 × 1350 px</li>
                                    <li>Landscape: 1280 × 720 px</li>
                                    <li>Story: 1080 × 1920 px</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label-premium">Current / New Preview</label>
                            <div class="preview-container" id="preview">
                                <img src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$categoryPost->frame_image)}} @else {{asset('uploads/'.$categoryPost->frame_image)}} @endif" alt="Preview">
                            </div>
                        </div>
                    </div>

                    <div class="row mt-5">
                        <div class="col-12 text-center">
                            @if(optional(Auth::user())->user_type == "Demo")
                                <button type="button" class="btn btn-premium-save ToastrButton">Update Frame</button>
                            @else
                                <button type="submit" class="btn btn-premium-save">Update Frame</button>
                            @endif
                        </div>
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
        $('#category_id').select2();
        $('#language_id').select2();

        // Custom file input label update
        $('#frame_image').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
            imagePreview(this);
        });
    });

    function imagePreview(fileInput) 
    { 
        if (fileInput.files && fileInput.files[0]) 
        {
            var fileReader = new FileReader();
            fileReader.onload = function (event) 
            {
                $('#preview').html('<img src="'+event.target.result+'" alt="New Preview"/>');
            };
            fileReader.readAsDataURL(fileInput.files[0]);
        }
    }
</script>
@endsection