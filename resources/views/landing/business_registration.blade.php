@extends('landing.layout')

@section('title', 'Register Your Business - ' . App\Models\AppSetting::getAppSetting('app_title'))

@section('extra_css')
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    .registration-section {
        padding: 120px 0 80px;
        background: #fafafa;
        min-height: 100vh;
        font-family: 'Poppins', sans-serif !important;
    }

    .form-wrapper {
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 24px 48px -12px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.05);
    }

    .form-header-box {
        background: var(--blue);
        padding: 48px 40px;
        color: #fff;
        position: relative;
        overflow: hidden;
    }

    .form-header-box::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%);
        border-radius: 50%;
    }

    .form-header-box h2 {
        font-family: 'Poppins', sans-serif !important;
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 12px;
        letter-spacing: -0.02em;
    }

    .form-header-box p {
        font-size: 16px;
        opacity: 0.9;
        margin: 0;
        max-width: 400px;
    }

    .form-body {
        padding: 48px 40px;
    }

    .input-group-custom {
        margin-bottom: 24px;
    }

    .input-group-custom label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #666;
        margin-bottom: 8px;
    }

    .input-group-custom input[type="text"],
    .input-group-custom input[type="email"],
    .input-group-custom input[type="password"],
    .input-group-custom input[type="file"],
    .input-group-custom select {
        width: 100%;
        padding: 16px;
        border: 2px solid #eee;
        border-radius: 12px;
        font-size: 16px;
        font-family: inherit;
        background: #fdfdfd;
        transition: all 0.2s ease;
    }

    .input-group-custom input:focus,
    .input-group-custom select:focus {
        outline: none;
        border-color: var(--blue);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    /* Select2 customizations for premium look */
    .select2-container--default .select2-selection--multiple {
        border: 2px solid #eee;
        border-radius: 12px;
        padding: 8px;
        min-height: 56px;
        background: #fdfdfd;
    }
    
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: var(--blue);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: var(--blue);
        border: none;
        color: white;
        border-radius: 6px;
        padding: 4px 8px;
        margin-top: 4px;
        font-size: 14px;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: rgba(255,255,255,0.7);
        margin-right: 8px;
        border: none;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        background: transparent;
        color: white;
    }

    .btn-submit {
        background: #000;
        color: #fff;
        width: 100%;
        padding: 20px;
        border: none;
        border-radius: 12px;
        font-size: 18px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }

    .btn-submit:hover {
        background: var(--blue);
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -10px var(--blue);
    }

    .section-divider {
        width: 100%;
        height: 1px;
        background: #eee;
        margin: 40px 0;
        position: relative;
    }

    .section-divider span {
        position: absolute;
        top: 50%;
        left: 0;
        transform: translateY(-50%);
        background: #fff;
        padding-right: 16px;
        font-size: 14px;
        font-weight: 700;
        color: #aaa;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }

    .alert-danger {
        background: #fef2f2;
        border: 1px solid #f87171;
        color: #991b1b;
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 24px;
        font-size: 14px;
    }

    .alert-danger ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .grid-2 {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0 24px;
    }

    @media(min-width: 768px) {
        .grid-2 {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>
@endsection

@section('content')
<section class="registration-section">
    <div class="container-wide" style="max-width: 900px;">
        <div class="form-wrapper">
            
            <div class="form-header-box">
                <h2>Launch Your Brand</h2>
                <p>Register your business today to access thousands of premium AI-generated templates and festival posters.</p>
            </div>

            <div class="form-body">
                @if($errors->any())
                    <div class="alert-danger">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('business.registration.post') }}" enctype="multipart/form-data">
                    @csrf
                    
                    <!-- Personal Info Section -->
                    <div class="section-divider">
                        <span>Personal Details</span>
                    </div>

                    <div class="grid-2">
                        <div class="input-group-custom">
                            <label>Full Name *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. John Doe">
                        </div>
                        <div class="input-group-custom">
                            <label>Mobile Number *</label>
                            <input type="text" name="mobile_no" value="{{ old('mobile_no') }}" required placeholder="e.g. 9876543210">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="input-group-custom">
                            <label>Email Address *</label>
                            <input type="email" name="email" value="{{ old('email') }}" required placeholder="john@example.com">
                        </div>
                        <div class="input-group-custom">
                            <label>Password *</label>
                            <input type="password" name="password" required placeholder="Min. 6 characters">
                        </div>
                    </div>

                    <!-- Business Info Section -->
                    <div class="section-divider">
                        <span>Business Profile</span>
                    </div>

                    <div class="grid-2">
                        <div class="input-group-custom">
                            <label>Business Name *</label>
                            <input type="text" name="bussinessName" value="{{ old('bussinessName') }}" required placeholder="e.g. Acme Corp">
                        </div>
                        <div class="input-group-custom">
                            <label>Website <span style="text-transform:none; font-weight:normal; color:#aaa;">(Optional)</span></label>
                            <input type="text" name="bussinessWebsite" value="{{ old('bussinessWebsite') }}" placeholder="https://www.example.com">
                        </div>
                    </div>

                    <div class="input-group-custom" style="margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="sameAsPersonal" style="width: 16px; height: 16px; margin: 0; cursor: pointer;">
                        <label for="sameAsPersonal" style="margin: 0; cursor: pointer; text-transform: none; color: #555;">Same as personal info</label>
                    </div>

                    <div class="grid-2">
                        <div class="input-group-custom">
                            <label>Business Email *</label>
                            <input type="email" name="bussinessEmail" id="bussinessEmail" value="{{ old('bussinessEmail') }}" required placeholder="business@example.com">
                        </div>
                        <div class="input-group-custom">
                            <label>Business Mobile Number *</label>
                            <input type="text" name="bussinessNumber" id="bussinessNumber" value="{{ old('bussinessNumber') }}" required placeholder="e.g. 9876543210">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="input-group-custom">
                            <label>Business Category *</label>
                            <select name="businessCategoryId" id="businessCategoryId" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('businessCategoryId') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="input-group-custom">
                            <label>Business Sub-Category</label>
                            <select class="select2" name="businessSubCategoryIds[]" id="businessSubCategoryIds" multiple="multiple" data-placeholder="Select Sub Categories">
                            </select>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="input-group-custom">
                            <label>Business Type</label>
                            <select class="select2" name="businessTypeIds[]" id="businessTypeIds" multiple="multiple" data-placeholder="Select Business Types">
                            </select>
                        </div>
                        <div class="input-group-custom">
                            <label>Products / Services</label>
                            <select class="select2" name="product_ids[]" id="product_ids" multiple="multiple" data-placeholder="Select Products">
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        Complete Registration <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>
            </div>
            
        </div>
    </div>
</section>
@endsection

@section('extra_js')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%',
            maximumSelectionLength: 5
        });

        var oldSubCategoryIds = {!! json_encode(old('businessSubCategoryIds', [])) !!};
        var oldBusinessTypeIds = {!! json_encode(old('businessTypeIds', [])) !!};
        var oldProductIds = {!! json_encode(old('product_ids', [])) !!};
        var isInitialLoad = true;

        $('#businessCategoryId').change(function() {
            var categoryId = $(this).val();
            if(categoryId) {
                $.get("{{ route('web.api.get_sub_categories') }}", { category_id: categoryId }, function(res) {
                    $('#businessSubCategoryIds').empty();
                    $.each(res.data, function(key, value) {
                        $('#businessSubCategoryIds').append('<option value="'+ value.id +'">'+ value.name +'</option>');
                    });
                    
                    if(isInitialLoad && oldSubCategoryIds.length > 0) {
                        $('#businessSubCategoryIds').val(oldSubCategoryIds);
                    }
                    $('#businessSubCategoryIds').trigger('change');
                });
            } else {
                $('#businessSubCategoryIds').empty().trigger('change');
            }
        });

        $('#businessSubCategoryIds').change(function() {
            var subCategoryIds = $(this).val();
            if(subCategoryIds && subCategoryIds.length > 0) {
                $.get("{{ route('web.api.get_business_types') }}", { sub_category_ids: subCategoryIds.join(',') }, function(res) {
                    $('#businessTypeIds').empty();
                    $.each(res.data, function(key, value) {
                        $('#businessTypeIds').append('<option value="'+ value.id +'">'+ value.name +'</option>');
                    });
                    
                    if(isInitialLoad && oldBusinessTypeIds.length > 0) {
                        $('#businessTypeIds').val(oldBusinessTypeIds);
                    }
                    $('#businessTypeIds').trigger('change');
                });
                fetchProducts();
            } else {
                $('#businessTypeIds').empty().trigger('change');
                $('#product_ids').empty().trigger('change');
            }
        });

        $('#businessTypeIds').change(function() {
            fetchProducts();
        });

        function fetchProducts() {
            var subCategoryIds = $('#businessSubCategoryIds').val();
            var businessTypeIds = $('#businessTypeIds').val();
            
            var data = {};
            if(subCategoryIds && subCategoryIds.length > 0) data.sub_category_ids = subCategoryIds.join(',');
            if(businessTypeIds && businessTypeIds.length > 0) data.business_type_ids = businessTypeIds.join(',');
            
            if(data.sub_category_ids || data.business_type_ids) {
                $.get("{{ route('web.api.get_products') }}", data, function(res) {
                    $('#product_ids').empty();
                    $.each(res.data, function(key, value) {
                        $('#product_ids').append('<option value="'+ value.id +'">'+ value.title +'</option>');
                    });
                    
                    if(isInitialLoad && oldProductIds.length > 0) {
                        $('#product_ids').val(oldProductIds);
                        isInitialLoad = false; // After products load, initial load is completely done
                    }
                    $('#product_ids').trigger('change');
                });
            } else {
                $('#product_ids').empty().trigger('change');
            }
        }

        // Trigger change on load if category is selected (for validation errors)
        if($('#businessCategoryId').val()) {
            $('#businessCategoryId').trigger('change');
        } else {
            isInitialLoad = false;
        }

        $('#sameAsPersonal').change(function() {
            if($(this).is(':checked')) {
                $('#bussinessEmail').val($('input[name="email"]').val()).prop('readonly', true).css('background-color', '#f5f5f5');
                $('#bussinessNumber').val($('input[name="mobile_no"]').val()).prop('readonly', true).css('background-color', '#f5f5f5');
            } else {
                $('#bussinessEmail').val('').prop('readonly', false).css('background-color', '');
                $('#bussinessNumber').val('').prop('readonly', false).css('background-color', '');
            }
        });

        $('input[name="email"], input[name="mobile_no"]').on('input', function() {
            if($('#sameAsPersonal').is(':checked')) {
                $('#bussinessEmail').val($('input[name="email"]').val());
                $('#bussinessNumber').val($('input[name="mobile_no"]').val());
            }
        });
    });
</script>
@endsection
