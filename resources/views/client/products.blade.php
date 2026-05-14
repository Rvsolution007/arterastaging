@extends('layouts.client')

@section('main_bg', 'bg-[#f8fafc]')

@section('content')
    <div class="fade-in space-y-4 pb-32">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-white shadow-sm sticky top-0 z-30">
            <div class="flex items-center gap-3">
                <a href="{{ route('business') }}" class="w-10 h-10 rounded-full flex items-center justify-center bg-slate-100 active:scale-95 transition-transform">
                    <i data-lucide="arrow-left" class="w-5 h-5 text-gray-700"></i>
                </a>
                <h1 class="text-[18px] font-bold text-gray-800 tracking-tight">Products <span class="text-sm font-normal text-gray-400">({{ $products->total() }})</span></h1>
            </div>
            <button onclick="openProductModal()" class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center active:scale-95 transition-transform shadow-md shadow-indigo-200">
                <i data-lucide="plus" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="px-4 pt-2">
            <!-- Search & Filter -->
            <form action="{{ route('products') }}" method="GET" class="mb-4">
                <div class="relative">
                    <i data-lucide="search" class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products, SKU..." 
                        class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:border-indigo-500 shadow-sm transition-colors">
                </div>
            </form>

            @if($categories->count() > 0)
            <div class="flex overflow-x-auto scrollbar-hide gap-2 mb-4 pb-1">
                <a href="{{ route('products', ['search' => request('search')]) }}" 
                    class="px-4 py-1.5 rounded-full text-xs font-bold whitespace-nowrap {{ !request('category') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-white border border-slate-200 text-gray-600' }}">
                    All
                </a>
                @foreach($categories as $cat)
                <a href="{{ route('products', ['category' => $cat, 'search' => request('search')]) }}" 
                    class="px-4 py-1.5 rounded-full text-xs font-bold whitespace-nowrap {{ request('category') === $cat ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-white border border-slate-200 text-gray-600' }}">
                    {{ $cat }}
                </a>
                @endforeach
            </div>
            @endif

            <!-- Products List -->
            <div class="space-y-4">
                @forelse($products as $product)
                    <div class="group bg-white rounded-[20px] p-5 border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-indigo-100/40 hover:border-indigo-100 transition-all duration-300 cursor-pointer active:scale-[0.99]" onclick="editProduct({{ json_encode($product->load('customValues', 'combos', 'variations')) }})">
                        <div class="flex items-start gap-4 mb-2">
                            <!-- Icon/Avatar/Image -->
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shrink-0 shadow-inner group-hover:scale-105 transition-transform duration-300 overflow-hidden relative">
                                @if($product->image)
                                    <img src="{{ asset('uploads/' . $product->image) }}" class="w-full h-full object-cover" alt="Product">
                                @else
                                    <i data-lucide="box" class="w-6 h-6 text-white opacity-90 drop-shadow-sm"></i>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0 pr-2">
                                @php
                                    $uniqueCol = $customColumns->firstWhere('is_unique', true);
                                    $categoryCol = $customColumns->firstWhere('is_category', true);
                                    $uniqueVal = $uniqueCol ? optional($product->customValues->firstWhere('column_id', $uniqueCol->id))->value : null;
                                    $categoryVal = $categoryCol ? optional($product->customValues->firstWhere('column_id', $categoryCol->id))->value : null;

                                    $mainTitle = $uniqueVal ?: $product->display_name;
                                    $mainLabel = $uniqueCol ? $uniqueCol->name : 'Product';
                                    $secondaryText = $categoryVal ?: $product->category_name;
                                @endphp

                                <div class="flex items-baseline gap-1.5 truncate mb-0.5">
                                    <span class="text-[11px] text-slate-400 font-bold uppercase tracking-wider whitespace-nowrap">{{ $mainLabel }}:</span>
                                    <h3 class="font-black text-[18px] text-slate-800 tracking-tight">{{ $mainTitle }}</h3>
                                </div>
                                
                                @if($secondaryText)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-600 border border-indigo-100/50 uppercase tracking-wider mt-1">
                                        {{ $secondaryText }}
                                    </span>
                                @endif
                                
                                @if($product->sku)
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1 block">SKU: {{ $product->sku }}</span>
                                @endif
                            </div>

                            <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center shrink-0 group-hover:bg-indigo-600 transition-colors duration-300">
                                <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:text-white transition-colors"></i>
                            </div>
                        </div>

                        <!-- Custom Field Previews -->
                        @if($customColumns->where('show_on_list', true)->count() > 0)
                            <div class="mt-4 pt-4 border-t border-slate-50 grid grid-cols-2 gap-x-3 gap-y-3">
                                @foreach($customColumns->where('show_on_list', true) as $col)
                                    @php
                                        $val = $product->customValues->firstWhere('column_id', $col->id);
                                        $displayVal = $val ? $val->value : '-';
                                        if($displayVal && is_string($displayVal) && str_starts_with($displayVal, '["')) {
                                            $decoded = json_decode($displayVal, true);
                                            if(is_array($decoded)) $displayVal = implode(', ', $decoded);
                                        }
                                    @endphp
                                    <div class="bg-slate-50/50 rounded-xl p-2.5 border border-slate-100/50">
                                        <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wide mb-1 flex items-center gap-1">
                                            <i data-lucide="grip-horizontal" class="w-3 h-3 opacity-50"></i> {{ $col->name }}
                                        </span>
                                        <span class="block text-sm font-semibold text-slate-700 truncate">{{ $displayVal }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-10 bg-white rounded-2xl border border-slate-100 border-dashed">
                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="package-search" class="w-8 h-8 text-slate-400"></i>
                        </div>
                        <h4 class="font-bold text-gray-700">No products found</h4>
                        <p class="text-xs text-gray-500 mt-1">Try extracting them using the AI Setup Wizard.</p>
                        <a href="{{ route('setup.wizard') }}" class="inline-block mt-4 text-indigo-600 font-bold text-sm bg-indigo-50 px-4 py-2 rounded-xl">Go to Wizard</a>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $products->links('pagination::tailwind') }}
            </div>
        </div>

        <!-- Add/Edit Modal (Full Screen) -->
        <div id="product-modal" class="fixed inset-0 z-50 hidden bg-[#f8fafc] flex-col h-full overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 bg-white shadow-sm sticky top-0 z-10 shrink-0">
                <div class="flex items-center gap-3">
                    <button onclick="closeProductModal()" class="w-10 h-10 rounded-full flex items-center justify-center bg-slate-100 active:scale-95 transition-transform focus:outline-none">
                        <i data-lucide="arrow-left" class="w-5 h-5 text-gray-700"></i>
                    </button>
                    <h3 class="font-bold text-[18px] text-gray-800" id="modal-title">Add Product</h3>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="deleteProduct()" id="btn-delete" class="hidden w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-500 active:scale-95 transition-transform focus:outline-none">
                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                    </button>
                    <button type="button" onclick="saveProduct()" id="btn-save" class="px-4 h-10 rounded-full bg-indigo-600 text-white font-bold text-sm shadow-md shadow-indigo-200 active:scale-95 transition-transform focus:outline-none">
                        Save
                    </button>
                </div>
            </div>
            
            <div class="p-4 overflow-y-auto w-full flex-1 pb-32">
                <form id="product-form" class="space-y-6">
                    <input type="hidden" id="prod_id">
                    
                    <!-- Image Upload Section -->
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex gap-4 items-center">
                        <div class="relative w-20 h-20 rounded-xl bg-slate-50 border-2 border-dashed border-slate-200 flex items-center justify-center overflow-hidden shrink-0 group">
                            <img id="prod_image_preview" src="" class="w-full h-full object-cover hidden">
                            <i data-lucide="image-plus" class="w-6 h-6 text-slate-400 group-hover:text-indigo-500 transition-colors" id="prod_image_icon"></i>
                            <input type="file" id="prod_image" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="previewProductImage(this)">
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-800 text-sm">Product Image</h4>
                            <p class="text-xs text-gray-500 mt-1">Upload a clear product photo. Max 4MB.</p>
                            <div class="mt-2 flex gap-2">
                                <button type="button" onclick="document.getElementById('prod_image').click()" class="text-xs font-bold text-indigo-600 bg-indigo-50 px-3 py-1.5 rounded-lg active:scale-95 transition-transform">Upload Photo</button>
                                <button type="button" onclick="removeProductImage()" id="btn_remove_image" class="hidden text-xs font-bold text-red-600 bg-red-50 px-3 py-1.5 rounded-lg active:scale-95 transition-transform">Remove</button>
                                <input type="hidden" id="remove_image_flag" value="0">
                            </div>
                        </div>
                    </div>

                    <!-- Basic Information (Hidden usually if using dynamic titles, but good as fallback) -->
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 space-y-4 hidden">
                        <h4 class="font-bold text-gray-800 text-sm border-b border-gray-100 pb-2">System Fields</h4>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Title (Fallback)</label>
                            <input type="text" id="prod_title" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 transition-colors">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Native Category</label>
                                <input type="text" id="prod_category" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">SKU</label>
                                <input type="text" id="prod_sku" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 transition-colors">
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">MRP</label>
                                <input type="number" id="prod_mrp" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500" step="0.01">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Sale Price</label>
                                <input type="number" id="prod_sale" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500" step="0.01">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Unit</label>
                                <input type="text" id="prod_unit" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500" placeholder="pcs">
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Custom Fields -->
                    @if($customColumns->count() > 0)
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 space-y-4">
                        <h4 class="font-bold text-gray-800 text-sm border-b border-gray-100 pb-2">Custom Fields</h4>
                        
                        <div id="dynamic-fields-container" class="space-y-4">
                            @foreach($customColumns as $col)
                                @if(!$col->is_combo && !$col->is_variation_field)
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">
                                            {{ $col->name }}
                                            @if($col->is_required) <span class="text-red-500">*</span> @endif
                                        </label>
                                        
                                        @if($col->type === 'textarea')
                                            <textarea data-col-id="{{ $col->id }}" rows="3" class="custom-field w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 transition-colors"></textarea>
                                        @elseif($col->type === 'number')
                                            <input type="number" data-col-id="{{ $col->id }}" class="custom-field w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500">
                                        @elseif($col->type === 'select')
                                            <select data-col-id="{{ $col->id }}" class="custom-field w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500">
                                                <option value="">Select {{ $col->name }}</option>
                                                @if($col->options)
                                                    @foreach($col->options as $opt)
                                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        @elseif($col->type === 'multiselect')
                                            <!-- Simple simulation for MultiSelect (could use select2 or similar if needed) -->
                                            <input type="text" data-col-id="{{ $col->id }}" data-type="multiselect" class="custom-field w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500" placeholder="Comma separated values">
                                            @if($col->options)
                                                <p class="text-[10px] text-gray-400 mt-1">Available: {{ implode(', ', $col->options) }}</p>
                                            @endif
                                        @elseif($col->type === 'boolean')
                                            <select data-col-id="{{ $col->id }}" class="custom-field w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500">
                                                <option value="0">No</option>
                                                <option value="1">Yes</option>
                                            </select>
                                        @else
                                            <input type="text" data-col-id="{{ $col->id }}" class="custom-field w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 transition-colors">
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Combinations (Combos) -->
                    @if($customColumns->where('is_combo', true)->count() > 0)
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 space-y-4">
                        <h4 class="font-bold text-gray-800 text-sm border-b border-gray-100 pb-2">Variations (Combos)</h4>
                        
                        <div id="combo-fields-container" class="space-y-4">
                            @foreach($customColumns->where('is_combo', true) as $col)
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">{{ $col->name }}</label>
                                    <input type="text" data-combo-id="{{ $col->id }}" class="combo-field w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500" placeholder="Select multiple options (comma separated)">
                                    @if($col->options)
                                        <p class="text-[10px] text-gray-400 mt-1">Available: {{ implode(', ', $col->options) }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
    });

    function openProductModal() {
        document.getElementById('product-form').reset();
        document.getElementById('prod_id').value = '';
        document.getElementById('modal-title').textContent = 'Add Product';
        document.getElementById('btn-delete').classList.add('hidden');
        
        // Reset Image
        document.getElementById('prod_image_preview').src = '';
        document.getElementById('prod_image_preview').classList.add('hidden');
        document.getElementById('prod_image_icon').classList.remove('hidden');
        document.getElementById('btn_remove_image').classList.add('hidden');
        document.getElementById('remove_image_flag').value = '0';
        
        document.getElementById('product-modal').classList.remove('hidden');
        document.getElementById('product-modal').classList.add('flex');
    }

    function editProduct(product) {
        document.getElementById('prod_id').value = product.id;
        document.getElementById('modal-title').textContent = 'Edit Product';
        document.getElementById('btn-delete').classList.remove('hidden');
        
        // Basic fields
        document.getElementById('prod_title').value = product.title || '';
        document.getElementById('prod_category').value = product.category_name || '';
        document.getElementById('prod_sku').value = product.sku || '';
        document.getElementById('prod_mrp').value = (product.mrp / 100).toFixed(2);
        document.getElementById('prod_sale').value = (product.sale_price / 100).toFixed(2);
        document.getElementById('prod_unit').value = product.unit || 'pcs';

        // Custom Fields
        document.querySelectorAll('.custom-field').forEach(el => {
            const colId = el.getAttribute('data-col-id');
            const valObj = product.custom_values.find(v => v.column_id == colId);
            if (valObj && valObj.value) {
                if (el.getAttribute('data-type') === 'multiselect') {
                    try {
                        const arr = JSON.parse(valObj.value);
                        el.value = Array.isArray(arr) ? arr.join(', ') : valObj.value;
                    } catch(e) { el.value = valObj.value; }
                } else {
                    el.value = valObj.value;
                }
            } else {
                el.value = '';
            }
        });

        // Combo Fields
        document.querySelectorAll('.combo-field').forEach(el => {
            const colId = el.getAttribute('data-combo-id');
            const valObj = product.combos.find(v => v.column_id == colId);
            if (valObj && valObj.selected_values) {
                el.value = valObj.selected_values.join(', ');
            } else {
                el.value = '';
            }
        });

        // Set Image if available
        if (product.image) {
            document.getElementById('prod_image_preview').src = '/uploads/' + product.image;
            document.getElementById('prod_image_preview').classList.remove('hidden');
            document.getElementById('prod_image_icon').classList.add('hidden');
            document.getElementById('btn_remove_image').classList.remove('hidden');
        } else {
            document.getElementById('prod_image_preview').src = '';
            document.getElementById('prod_image_preview').classList.add('hidden');
            document.getElementById('prod_image_icon').classList.remove('hidden');
            document.getElementById('btn_remove_image').classList.add('hidden');
        }
        document.getElementById('remove_image_flag').value = '0';

        document.getElementById('product-modal').classList.remove('hidden');
        document.getElementById('product-modal').classList.add('flex');
    }

    function previewProductImage(input) {
        if (input.files && input.files[0]) {
            if (input.files[0].size > 4 * 1024 * 1024) {
                alert('File size exceeds 4MB limit.');
                input.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('prod_image_preview').src = e.target.result;
                document.getElementById('prod_image_preview').classList.remove('hidden');
                document.getElementById('prod_image_icon').classList.add('hidden');
                document.getElementById('btn_remove_image').classList.remove('hidden');
                document.getElementById('remove_image_flag').value = '0';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function removeProductImage() {
        document.getElementById('prod_image').value = '';
        document.getElementById('prod_image_preview').src = '';
        document.getElementById('prod_image_preview').classList.add('hidden');
        document.getElementById('prod_image_icon').classList.remove('hidden');
        document.getElementById('btn_remove_image').classList.add('hidden');
        document.getElementById('remove_image_flag').value = '1';
    }

    function closeProductModal() {
        document.getElementById('product-modal').classList.add('hidden');
        document.getElementById('product-modal').classList.remove('flex');
    }

    async function saveProduct() {
        const id = document.getElementById('prod_id').value;
        const btn = document.getElementById('btn-save');
        const oldText = btn.innerHTML;
        
        btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin inline"></i>';
        lucide.createIcons();
        btn.disabled = true;

        const url = id ? `/products/${id}` : '{{ route("products.store") }}';
        
        // Use FormData for file upload
        const formData = new FormData();
        if (id) {
            formData.append('_method', 'PUT'); // Laravel form method spoofing
        }

        const imageFile = document.getElementById('prod_image').files[0];
        if (imageFile) {
            formData.append('product_image', imageFile);
        }
        formData.append('remove_image', document.getElementById('remove_image_flag').value);

        // Build Custom Data
        document.querySelectorAll('.custom-field').forEach(el => {
            const colId = el.getAttribute('data-col-id');
            let val = el.value;
            if (el.getAttribute('data-type') === 'multiselect') {
                const arr = val ? val.split(',').map(s => s.trim()).filter(s => s) : [];
                arr.forEach((v, idx) => formData.append(`custom_data[${colId}][${idx}]`, v));
            } else {
                formData.append(`custom_data[${colId}]`, val);
            }
        });

        // Build Combo Data
        document.querySelectorAll('.combo-field').forEach(el => {
            const colId = el.getAttribute('data-combo-id');
            formData.append(`combo_data[${colId}]`, el.value);
        });

        formData.append('title', document.getElementById('prod_title').value || 'New Product');
        formData.append('category_name', document.getElementById('prod_category').value);
        formData.append('sku', document.getElementById('prod_sku').value);
        formData.append('mrp', document.getElementById('prod_mrp').value);
        formData.append('sale_price', document.getElementById('prod_sale').value);
        formData.append('unit', document.getElementById('prod_unit').value);

        try {
            const response = await fetch(url, {
                method: 'POST', // Always POST for FormData in Laravel, use _method=PUT
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            });
            
            const data = await response.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Error occurred.');
            }
        } catch (err) {
            console.error(err);
            alert('Request failed.');
        } finally {
            btn.innerHTML = oldText;
            btn.disabled = false;
        }
    }

    async function deleteProduct() {
        const id = document.getElementById('prod_id').value;
        if(!id || !confirm('Are you sure you want to delete this product?')) return;
        
        try {
            const response = await fetch(`/products/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            if(data.success) {
                window.location.reload();
            } else {
                alert(data.message);
            }
        } catch (e) {
            console.error(e);
        }
    }
</script>
@endsection
