@extends('layouts.client')

@section('title', 'Register Business')

@section('styles')
    <style>
        /* Hide nav and fab for auth pages */
        nav,
        #fab-container,
        #fab-backdrop {
            display: none !important;
        }

        #main-content {
            padding-bottom: 0 !important;
        }

        .auth-header {
            background: linear-gradient(135deg, #1E3A8A, #3B82F6);
            padding: 16px;
            padding-top: max(16px, env(safe-area-inset-top));
            display: flex;
            align-items: center;
            color: white;
        }

        .back-btn {
            margin-right: 15px;
        }

        .logo-upload-container {
            display: flex;
            padding: 24px 16px;
            gap: 16px;
            align-items: center;
        }

        .logo-preview-wrapper {
            width: 110px;
            height: 110px;
            border: 2px solid #3B82F6;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .logo-preview-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .tap-choose-badge {
            position: absolute;
            top: 0;
            left: 0;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            font-weight: 700;
            border-bottom-right-radius: 4px;
        }

        .hint-text {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }

        .pro-logo-card {
            margin-left: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .pro-logo-card img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
        }

        .pro-logo-text {
            font-size: 10px;
            font-weight: 800;
            text-align: center;
            color: #1e293b;
            line-height: 1.1;
        }

        .form-container {
            padding: 0 16px 32px;
            padding-bottom: max(32px, env(safe-area-inset-bottom));
        }

        .input-row {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            padding: 4px 16px;
            margin-bottom: 12px;
            position: relative;
        }

        .input-row .icon {
            color: #3B82F6;
            width: 20px;
            height: 20px;
            margin-right: 12px;
        }

        .input-row input,
        .input-row select {
            flex: 1;
            border: none;
            padding: 12px 0;
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            outline: none;
        }

        .input-row input::placeholder {
            color: #94a3b8;
            font-weight: 500;
        }

        .required-dot {
            position: absolute;
            left: -12px;
            color: #ef4444;
            font-weight: bold;
            font-size: 18px;
        }

        .finish-btn {
            background: linear-gradient(135deg, #1E3A8A, #3B82F6);
            color: white;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 700;
            margin-top: 20px;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.25);
            cursor: pointer;
        }

        .btn-google-reg {
            width: 100%;
            padding: 14px;
            background: white;
            color: #333;
            border: 1px solid #dadce0;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-decoration: none;
            margin-bottom: 16px;
        }
        .btn-google-reg img { width: 20px; height: 20px; }

        .divider-or {
            display: flex; align-items: center; text-align: center;
            margin: 0 0 16px; color: #94a3b8; font-size: 12px;
            text-transform: uppercase; letter-spacing: 1px; font-weight: 600;
        }
        .divider-or::before, .divider-or::after { content: ''; flex: 1; border-bottom: 1px solid #e2e8f0; }
        .divider-or:not(:empty)::before { margin-right: 12px; }
        .divider-or:not(:empty)::after { margin-left: 12px; }

        .error-text {
            font-size: 12px;
            color: #ef4444;
            margin-top: -8px;
            margin-bottom: 10px;
            margin-left: 4px;
        }

        .subcategory-label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            padding-left: 4px;
        }
        .subcategory-label .icon {
            color: #4f46e5;
            width: 18px;
            height: 18px;
        }
        .subcategory-label span {
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .chip-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
        }
        .chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            user-select: none;
            -webkit-user-select: none;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1.5px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            -webkit-tap-highlight-color: transparent;
        }
        .chip:active { transform: scale(0.95); }
        .chip .chip-check {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            flex-shrink: 0;
        }
        .chip .chip-check svg {
            width: 10px;
            height: 10px;
            opacity: 0;
            transform: scale(0);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .chip.selected {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border-color: transparent;
            color: white;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
        }
        .chip.selected .chip-check {
            border-color: rgba(255,255,255,0.5);
            background: rgba(255,255,255,0.25);
        }
        .chip.selected .chip-check svg {
            opacity: 1;
            transform: scale(1);
            color: white;
        }
        .chip-skeleton {
            width: 100px;
            height: 36px;
            border-radius: 50px;
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite ease-in-out;
        }
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
@endsection

@section('content')
    <header class="auth-header">
        <a href="{{ route('login') }}" class="back-btn"><i data-lucide="chevron-left"></i></a>
        <h1 class="flex-1 text-center font-bold text-lg mr-8">Register your business</h1>
    </header>

    <form action="{{ route('client.register.post') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="logo-upload-container">
            <div class="logo-preview-wrapper" onclick="document.getElementById('logoInput').click()">
                <span class="tap-choose-badge">Tap & Choose</span>
                <img id="logoPreview" src="https://via.placeholder.com/150?text=Logo" alt="Business Logo">
                <input type="file" name="logo" id="logoInput" class="hidden" accept="image/*" onchange="previewLogo(this)">
            </div>
            <div class="hint-text">
                Upload your<br>business logo
            </div>
        </div>
        @error('logo') <p class="error-text px-4">{{ $message }}</p> @enderror

        <div class="form-container">
            {{-- Google Sign-In --}}
            <a href="{{ route('auth.google') }}" class="btn-google-reg">
                <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google">
                Sign up with Google
            </a>
            <div class="divider-or">or register manually</div>
            <!-- Business Name -->
            <div class="input-row">
                <span class="required-dot text-rose-500">*</span>
                <i data-lucide="building-2" class="icon"></i>
                <input type="text" name="name" placeholder="Enter your business name" value="{{ old('name') }}" required>
            </div>
            @error('name') <p class="error-text">{{ $message }}</p> @enderror

            <!-- Primary Mobile -->
            <div class="input-row">
                <span class="required-dot text-rose-500">*</span>
                <i data-lucide="smartphone" class="icon"></i>
                <input type="text" name="mobile_no" placeholder="Enter your primary mobile number"
                    value="{{ old('mobile_no') }}" required>
            </div>
            @error('mobile_no') <p class="error-text">{{ $message }}</p> @enderror

            <!-- Business Email -->
            <div class="input-row">
                <i data-lucide="mail" class="icon"></i>
                <input type="email" name="email" placeholder="Enter your business email" value="{{ old('email') }}"
                    required>
            </div>
            @error('email') <p class="error-text">{{ $message }}</p> @enderror

            <!-- Website -->
            <div class="input-row">
                <i data-lucide="globe" class="icon"></i>
                <input type="text" name="website" placeholder="Enter your business website" value="{{ old('website') }}">
            </div>

            <!-- Address -->
            <div class="input-row">
                <i data-lucide="map-pin" class="icon"></i>
                <input type="text" name="address" placeholder="Enter your business address" value="{{ old('address') }}">
            </div>

            <!-- Category -->
            <div class="input-row">
                <i data-lucide="tag" class="icon"></i>
                <select name="business_category_id" id="business_category_id" required>
                    <option value="" disabled selected>Select Category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('business_category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @error('business_category_id') <p class="error-text">{{ $message }}</p> @enderror

            <!-- Sub Category Chips -->
            <div id="sub_category_wrapper" style="display: none;">
                <div class="subcategory-label">
                    <i data-lucide="layers" class="icon"></i>
                    <span>Select Sub Categories</span>
                </div>
                <div id="sub_category_chips" class="chip-container">
                </div>
                <div id="sub_category_hidden_inputs"></div>
            </div>
            @error('business_sub_category_ids') <p class="error-text">{{ $message }}</p> @enderror

            <!-- Password (Required for Auth) -->
            <div class="input-row">
                <i data-lucide="lock" class="icon"></i>
                <input type="password" name="password" placeholder="Create your login password" required>
            </div>
            @error('password') <p class="error-text">{{ $message }}</p> @enderror

            <button type="submit" class="finish-btn">Register</button>

            <p class="text-center mt-6 text-slate-500 font-medium text-sm">
                Already have an account? <a href="{{ route('client.login') }}" style="color: #3B82F6; font-weight: 700; text-decoration: none;">Sign In</a>
            </p>
        </div>
    </form>
@endsection



@section('scripts')
    <script>
        function previewLogo(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('logoPreview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            var savedSubCategoryIds = {!! json_encode(old('business_sub_category_ids', [])) !!};
            if (savedSubCategoryIds && !Array.isArray(savedSubCategoryIds)) {
                try { savedSubCategoryIds = JSON.parse(savedSubCategoryIds); } catch(e) { savedSubCategoryIds = []; }
            }
            if (savedSubCategoryIds) {
                savedSubCategoryIds = savedSubCategoryIds.map(String);
            }

            var categorySelect = document.getElementById('business_category_id');
            var chipContainer = document.getElementById('sub_category_chips');
            var hiddenInputs = document.getElementById('sub_category_hidden_inputs');
            var wrapper = document.getElementById('sub_category_wrapper');
            var selectedIds = new Set(savedSubCategoryIds || []);

            function syncHiddenInputs() {
                hiddenInputs.innerHTML = '';
                selectedIds.forEach(function(id) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'business_sub_category_ids[]';
                    input.value = id;
                    hiddenInputs.appendChild(input);
                });
            }

            function renderChips(subcategories, preselectIds) {
                chipContainer.innerHTML = '';
                selectedIds = new Set(preselectIds || []);
                subcategories.forEach(function(subcat) {
                    var chip = document.createElement('div');
                    chip.className = 'chip' + (selectedIds.has(String(subcat.id)) ? ' selected' : '');
                    chip.dataset.id = subcat.id;
                    chip.innerHTML = '<span class="chip-check" style="overflow: hidden;"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width: 10px; height: 10px; min-width: 10px; min-height: 10px;"><polyline points="20 6 9 17 4 12"></polyline></svg></span>' +
                                     '<span>' + subcat.name + '</span>';
                    chip.addEventListener('click', function() {
                        var id = String(this.dataset.id);
                        if (selectedIds.has(id)) { selectedIds.delete(id); this.classList.remove('selected'); }
                        else { selectedIds.add(id); this.classList.add('selected'); }
                        syncHiddenInputs();
                    });
                    chipContainer.appendChild(chip);
                });
                syncHiddenInputs();
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }

            function showSkeletons() {
                chipContainer.innerHTML = '';
                for (var i = 0; i < 3; i++) {
                    var skel = document.createElement('div');
                    skel.className = 'chip-skeleton';
                    skel.style.width = (80 + Math.random() * 60) + 'px';
                    chipContainer.appendChild(skel);
                }
            }

            function loadSubCategories(categoryId, preselectIds) {
                if (!categoryId) { wrapper.style.display = 'none'; chipContainer.innerHTML = ''; hiddenInputs.innerHTML = ''; return; }
                wrapper.style.display = '';
                showSkeletons();
                fetch('{{ url("client/get-sub-categories") }}/' + categoryId, { method: 'GET', headers: { 'Accept': 'application/json' } })
                .then(function(res) { return res.json(); })
                .then(function(response) {
                    if (response.success && response.data && response.data.length > 0) { renderChips(response.data, preselectIds); }
                    else { wrapper.style.display = 'none'; chipContainer.innerHTML = ''; hiddenInputs.innerHTML = ''; }
                })
                .catch(function(err) { console.error('Sub-category fetch error:', err); wrapper.style.display = 'none'; });
            }

            categorySelect.addEventListener('change', function() { loadSubCategories(this.value, null); });
            if (categorySelect.value) { loadSubCategories(categorySelect.value, savedSubCategoryIds); }
        });
    </script>
@endsection