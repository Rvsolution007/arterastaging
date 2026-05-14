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
        padding: 2rem;
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
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
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
        padding: 0.75rem 2rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
        cursor: pointer;
    }

    .btn-premium:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4);
    }

    .btn-add-field {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        padding: 0.4rem 1rem;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 600;
        transition: all 0.2s;
    }

    .btn-add-field:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    .preview-box {
        width: 130px;
        height: 130px;
        border-radius: 16px;
        border: 3px solid #fff;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        object-fit: cover;
        background: #f1f5f9;
    }

    .input-group-premium {
        position: relative;
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        width: 100%;
        margin-bottom: 1rem;
    }

    .input-group-premium .form-control-premium {
        flex: 1 1 auto;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .btn-remove-field {
        border-top-right-radius: 10px;
        border-bottom-right-radius: 10px;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        background: #fee2e2;
        color: #e11d48;
        border: 1px solid #fecaca;
        padding: 0 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
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
            <h4 class="page-title"><i class="fa-solid fa-pen-to-square mr-2 text-primary"></i> Edit Business Profile</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="premium-card">
                <div class="premium-card-header">
                    <h5 class="premium-card-title">Business Information</h5>
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

                    {!! Form::open(['route' =>['business.update',$business->id],'method'=>'PATCH','files'=>true]) !!}
                    {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
                    {!! Form::hidden('id',$business->id)!!}

                    <div class="row">
                        <!-- Left Column: Core Info -->
                        <div class="col-md-7">
                            <div class="form-group mb-4">
                                {!! Form::label('name','Business Name', ['class' => 'form-label-premium']) !!}
                                {!! Form::text('name',$business->name,['class' => 'form-control-premium','required','placeholder'=>'e.g. Acme Corporation']) !!}
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        {!! Form::label('email','Contact Email', ['class' => 'form-label-premium']) !!}
                                        {!! Form::email('email',$business->email,['class' => 'form-control-premium','required','placeholder'=>'contact@business.com']) !!}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        {!! Form::label('mobile_no','Mobile Number', ['class' => 'form-label-premium']) !!}
                                        {!! Form::number('mobile_no',$business->mobile_no,['class' => 'form-control-premium','required','placeholder'=>'Enter Mobile No']) !!}
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                {!! Form::label('address','Physical Address', ['class' => 'form-label-premium']) !!}
                                {!! Form::textarea('address',$business->address,['class' => 'form-control-premium','required','rows'=>3,'placeholder'=>'Full street address...']) !!}
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        {!! Form::label('website','Website URL', ['class' => 'form-label-premium']) !!}
                                        {!! Form::text('website',$business->website,['class' => 'form-control-premium','required','placeholder'=>'https://yourbusiness.com']) !!}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        {!! Form::label('category','Business Category', ['class' => 'form-label-premium']) !!}
                                        <select id="business_category_id" name="business_category_id" class="form-control-premium" required>
                                            <option value="">Select Category</option>
                                            @foreach($category as $c)
                                                <option value="{{$c->id}}" @if($c->id == $business->business_category_id) selected @endif>{{$c->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Logo & Meta -->
                        <div class="col-md-5">
                            <div class="form-group mb-4 text-center">
                                <label class="form-label-premium text-left">Business Logo</label>
                                <div id="preview" class="mb-3">
                                    <img class="preview-box" src="@if($business->logo) @if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$business->logo)}} @else {{asset('uploads/'.$business->logo)}} @endif @else {{asset('assets/images/no-user.jpg')}} @endif">
                                </div>
                                <div class="custom-file" style="max-width: 250px; margin: 0 auto; display: block;">
                                    <input class="form-control-premium py-1" type="file" id="logo" name="logo" style="font-size: 0.75rem;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="sub_category_wrapper" style="display: none;" class="mb-4">
                        <div class="form-group">
                            {!! Form::label('sub_category','Sub Categories', ['class' => 'form-label-premium']) !!}
                            <select id="business_sub_category_ids" name="business_sub_category_ids[]" class="form-control-premium" multiple="multiple">
                            </select>
                        </div>
                    </div>

                    <div class="section-divider"></div>

                    <!-- Extra Details Section -->
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label-premium mb-0">Extra Email Addresses</label>
                                <button type="button" class="btn-add-field" onclick="addField('extra_emails_container', 'extra_emails[]', 'email', 'Enter email')"><i class="fa fa-plus mr-1"></i> Add</button>
                            </div>
                            <div id="extra_emails_container">
                                @if($business->extra_emails)
                                    @foreach($business->extra_emails as $i => $email)
                                    <div class="input-group-premium">
                                        <input type="email" name="extra_emails[]" class="form-control-premium" value="{{ $email }}">
                                        <button type="button" class="btn-remove-field"><i class="fa fa-times"></i></button>
                                    </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label-premium mb-0">Extra Phone Numbers</label>
                                <button type="button" class="btn-add-field" onclick="addField('extra_phones_container', 'extra_mobile_numbers[]', 'text', 'Enter phone')"><i class="fa fa-plus mr-1"></i> Add</button>
                            </div>
                            <div id="extra_phones_container">
                                @if($business->extra_mobile_numbers)
                                    @foreach($business->extra_mobile_numbers as $i => $phone)
                                    <div class="input-group-premium">
                                        <input type="text" name="extra_mobile_numbers[]" class="form-control-premium" value="{{ $phone }}">
                                        <button type="button" class="btn-remove-field"><i class="fa fa-times"></i></button>
                                    </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label-premium mb-0">Extra Websites</label>
                                <button type="button" class="btn-add-field" onclick="addField('extra_websites_container', 'extra_websites[]', 'url', 'Enter website')"><i class="fa fa-plus mr-1"></i> Add</button>
                            </div>
                            <div id="extra_websites_container">
                                @if($business->extra_websites)
                                    @foreach($business->extra_websites as $i => $web)
                                    <div class="input-group-premium">
                                        <input type="url" name="extra_websites[]" class="form-control-premium" value="{{ $web }}">
                                        <button type="button" class="btn-remove-field"><i class="fa fa-times"></i></button>
                                    </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label-premium mb-0">Extra Addresses</label>
                                <button type="button" class="btn-add-field" onclick="addField('extra_addresses_container', 'extra_addresses[]', 'text', 'Enter address')"><i class="fa fa-plus mr-1"></i> Add</button>
                            </div>
                            <div id="extra_addresses_container">
                                @if($business->extra_addresses)
                                    @foreach($business->extra_addresses as $i => $addr)
                                    <div class="input-group-premium">
                                        <input type="text" name="extra_addresses[]" class="form-control-premium" value="{{ $addr }}">
                                        <button type="button" class="btn-remove-field"><i class="fa fa-times"></i></button>
                                    </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        @if(optional(Auth::user())->user_type == "Demo")
                        <button type="button" class="btn-premium ToastrButton">Save Changes</button>
                        @else
                        <button type="submit" class="btn-premium"><i class="fa-solid fa-floppy-disk mr-2"></i> Save Changes</button>
                        @endif
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
        // Initialize Select2 with custom theme compatibility if possible
        $('#business_category_id').select2();
        $('#business_sub_category_ids').select2();

        var savedSubCategoryIds = {!! json_encode($business->business_sub_category_ids ?? []) !!};

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
                                var isSelected = (savedSubCategoryIds && savedSubCategoryIds.includes(subcat.id.toString())) ? 'selected' : '';
                                $('#business_sub_category_ids').append('<option value="'+ subcat.id +'" '+ isSelected +'>'+ subcat.name +'</option>');
                            });
                            $('#sub_category_wrapper').slideDown();
                            $('#business_sub_category_ids').select2();
                            savedSubCategoryIds = [];
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

        // Remove field button handler
        $(document).on('click', '.btn-remove-field', function() {
            $(this).closest('.input-group-premium').remove();
        });
    });

    // Add dynamic field
    function addField(containerId, fieldName, fieldType, placeholder) {
        var html = '<div class="input-group-premium">' +
            '<input type="' + fieldType + '" name="' + fieldName + '" class="form-control-premium" placeholder="' + placeholder + '">' +
            '<button type="button" class="btn-remove-field"><i class="fa fa-times"></i></button>' +
            '</div>';
        $('#' + containerId).append(html);
    }

    function imagePreview(fileInput) 
    { 
        if (fileInput.files && fileInput.files[0]) 
        {
            var fileReader = new FileReader();
            fileReader.onload = function (event) 
            {
                $('#preview').html('<img src="'+event.target.result+'" class="preview-box" alt="Select Image" />');
            };
            fileReader.readAsDataURL(fileInput.files[0]);
        }
    }

    $("#logo").change(function () {
        imagePreview(this);
    });
</script>
@endsection