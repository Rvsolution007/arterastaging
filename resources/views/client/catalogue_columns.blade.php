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
                <h1 class="text-[18px] font-bold text-gray-800 tracking-tight">Catalogue Columns</h1>
            </div>
            <button onclick="openModal()" class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center active:scale-95 transition-transform shadow-md shadow-indigo-200">
                <i data-lucide="plus" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="px-4 pt-2">
            <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-4 mb-4 flex items-start gap-3">
                <i data-lucide="info" class="w-5 h-5 text-indigo-500 shrink-0 mt-0.5"></i>
                <p class="text-xs text-indigo-700 leading-relaxed">
                    Define the custom properties for your products. The AI Content Engine uses these fields to generate accurate daily posts.
                </p>
            </div>

            <!-- Columns List -->
            <div class="space-y-3" id="sortable-columns">
                @forelse($columns as $col)
                    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-4 transition-all {{ !$col->is_active ? 'opacity-60' : '' }}" data-id="{{ $col->id }}">
                        <div class="drag-handle cursor-grab text-gray-300">
                            <i data-lucide="grip-vertical" class="w-5 h-5"></i>
                        </div>
                        
                        <div class="flex-1 min-w-0" onclick="editColumn({{ json_encode($col) }})">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="font-bold text-[15px] text-gray-800 truncate">{{ $col->name }}</h3>
                                @if($col->is_system)
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-500 text-[9px] font-bold uppercase rounded flex-shrink-0">System</span>
                                @endif
                                @if($col->is_category)
                                    <span class="px-2 py-0.5 bg-orange-100 text-orange-600 text-[9px] font-bold uppercase rounded flex-shrink-0">Category</span>
                                @elseif($col->is_unique)
                                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-600 text-[9px] font-bold uppercase rounded flex-shrink-0">Unique</span>
                                @elseif($col->is_combo)
                                    <span class="px-2 py-0.5 bg-purple-100 text-purple-600 text-[9px] font-bold uppercase rounded flex-shrink-0">Combo</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 truncate">
                                Type: <span class="capitalize">{{ $col->type }}</span>
                                @if($col->options)
                                    | Options: {{ count($col->options) }}
                                @endif
                            </p>
                        </div>
                        
                        <!-- Toggle Switch -->
                        <div class="flex-shrink-0 ml-2">
                            <button onclick="toggleVisibility({{ $col->id }})" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $col->is_active ? 'bg-indigo-600' : 'bg-gray-200' }}" role="switch" aria-checked="{{ $col->is_active ? 'true' : 'false' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $col->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10 bg-white rounded-2xl border border-slate-100 border-dashed">
                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="layout-list" class="w-8 h-8 text-slate-400"></i>
                        </div>
                        <h4 class="font-bold text-gray-700">No Custom Columns</h4>
                        <p class="text-xs text-gray-500 mt-1">Use AI Setup Wizard to create them automatically.</p>
                        <a href="{{ route('setup.wizard') }}" class="inline-block mt-4 text-indigo-600 font-bold text-sm bg-indigo-50 px-4 py-2 rounded-xl">Go to Wizard</a>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div id="column-modal" class="fixed inset-0 z-50 hidden">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeModal()"></div>
            <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl max-h-[85vh] flex flex-col transform translate-y-full transition-transform duration-300" id="modal-content">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-3xl z-10">
                    <h3 class="font-bold text-[18px] text-gray-800" id="modal-title">Add Column</h3>
                    <button onclick="closeModal()" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center focus:outline-none">
                        <i data-lucide="x" class="w-4 h-4 text-gray-500"></i>
                    </button>
                </div>
                
                <div class="p-5 overflow-y-auto w-full">
                    <form id="column-form" class="space-y-4">
                        <input type="hidden" id="col_id">
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Column Name *</label>
                            <input type="text" id="col_name" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 transition-colors">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Input Type *</label>
                            <select id="col_type" required onchange="toggleFormOptions()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 transition-colors">
                                <option value="text">Short Text</option>
                                <option value="textarea">Long Text</option>
                                <option value="number">Number</option>
                                <option value="select">Dropdown Select</option>
                                <option value="multiselect">Multi-Select</option>
                                <option value="boolean">Yes/No Switch</option>
                            </select>
                        </div>

                        <div id="options-group" class="hidden">
                            <label class="block text-xs font-bold text-gray-700 mb-1">Options (Comma separated)</label>
                            <input type="text" id="col_options" placeholder="e.g. Red, Blue, Green" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 transition-colors">
                        </div>

                        <div id="flags-group" class="space-y-3 bg-slate-50 rounded-2xl p-4 border border-slate-100">
                            <!-- Show in Product List -->
                            <label class="flex items-center justify-between">
                                <span class="text-sm font-bold text-gray-700">Required Field</span>
                                <input type="checkbox" id="col_is_required" class="rounded text-indigo-600 focus:ring-0 w-4 h-4">
                            </label>

                            <!-- Show in Product List -->
                            <label class="flex items-center justify-between">
                                <div>
                                    <span class="text-sm font-bold text-gray-700 block">Show in List</span>
                                    <span class="text-[10px] text-gray-500 block">Display column in main products view</span>
                                </div>
                                <input type="checkbox" id="col_show_on_list" class="rounded text-indigo-600 focus:ring-0 w-4 h-4">
                            </label>

                            <hr class="border-slate-200">

                            <!-- Exclusives -->
                            <div class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2 mt-4">Special Types</div>
                            
                            <label class="flex items-start gap-3">
                                <div class="mt-0.5">
                                    <input type="radio" name="special_type" value="category" id="col_is_category" class="text-orange-500 focus:ring-0 w-4 h-4">
                                </div>
                                <div>
                                    <span class="text-sm font-bold text-gray-800 block">Category Identifier</span>
                                    <span class="text-[10px] text-gray-500 block leading-tight">Used to group products. Options will create categories. Must be 'Select' type.</span>
                                </div>
                            </label>

                            <label class="flex items-start gap-3">
                                <div class="mt-0.5">
                                    <input type="radio" name="special_type" value="unique" id="col_is_unique" class="text-indigo-500 focus:ring-0 w-4 h-4">
                                </div>
                                <div>
                                    <span class="text-sm font-bold text-gray-800 block">Unique Identifier (SKU)</span>
                                    <span class="text-[10px] text-gray-500 block leading-tight">Prevents duplicate imports. Typically Model/SKU number.</span>
                                </div>
                            </label>

                            <label class="flex items-start gap-3">
                                <div class="mt-0.5">
                                    <input type="radio" name="special_type" value="combo" id="col_is_combo" class="text-purple-500 focus:ring-0 w-4 h-4">
                                </div>
                                <div>
                                    <span class="text-sm font-bold text-gray-800 block">Combo / Variant</span>
                                    <span class="text-[10px] text-gray-500 block leading-tight">Generates product variations (e.g. Size, Color).</span>
                                </div>
                            </label>
                            
                            <label class="flex items-start gap-3">
                                <div class="mt-0.5">
                                    <input type="radio" name="special_type" value="title" id="col_is_title" class="text-blue-500 focus:ring-0 w-4 h-4">
                                </div>
                                <div>
                                    <span class="text-sm font-bold text-gray-800 block">Display Title</span>
                                    <span class="text-[10px] text-gray-500 block leading-tight">Used as main display name if no native title exists.</span>
                                </div>
                            </label>
                            
                            <label class="flex items-start gap-3">
                                <div class="mt-0.5">
                                    <input type="radio" name="special_type" value="none" id="col_is_none" class="text-gray-400 focus:ring-0 w-4 h-4" checked>
                                </div>
                                <div>
                                    <span class="text-sm font-bold text-gray-800 block">Normal Regular Field</span>
                                </div>
                            </label>
                        </div>
                        
                        <div class="flex gap-3 pt-2">
                            <button type="button" onclick="deleteColumn()" id="btn-delete" class="hidden w-1/3 bg-red-50 text-red-600 font-bold py-3.5 rounded-xl text-sm">Delete</button>
                            <button type="submit" id="btn-save" class="flex-1 bg-indigo-600 text-white font-bold py-3.5 rounded-xl text-sm shadow-md shadow-indigo-200">Save Column</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
        
        // Initialize dragging
        const el = document.getElementById('sortable-columns');
        if (el && el.children.length > 0) {
            new Sortable(el, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'opacity-50',
                onEnd: function (evt) {
                    const itemEls = el.querySelectorAll('[data-id]');
                    const order = Array.from(itemEls).map(item => item.dataset.id);
                    saveOrder(order);
                }
            });
        }

        // Form submit handler
        document.getElementById('column-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const id = document.getElementById('col_id').value;
            const btn = document.getElementById('btn-save');
            const oldText = btn.innerHTML;
            
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin inline mr-2"></i> Saving...';
            lucide.createIcons();
            btn.disabled = true;

            const url = id ? '/catalogue-columns/' + id : '{{ route("catalogue.columns.store") }}';
            const method = id ? 'PUT' : 'POST';
            
            const specialType = document.querySelector('input[name="special_type"]:checked').value;

            const payload = {
                name: document.getElementById('col_name').value,
                type: document.getElementById('col_type').value,
                options: document.getElementById('col_options').value,
                is_required: document.getElementById('col_is_required').checked,
                show_on_list: document.getElementById('col_show_on_list').checked,
                show_in_ai: true,
                is_category: specialType === 'category',
                is_unique: specialType === 'unique',
                is_title: specialType === 'title',
                is_combo: specialType === 'combo'
            };

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
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
        });
    });

    function openModal() {
        document.getElementById('column-form').reset();
        document.getElementById('col_id').value = '';
        document.getElementById('modal-title').textContent = 'Add Column';
        document.getElementById('col_is_none').checked = true;
        document.getElementById('btn-delete').classList.add('hidden');
        document.getElementById('flags-group').classList.remove('opacity-50', 'pointer-events-none');
        
        toggleFormOptions();
        
        const modal = document.getElementById('column-modal');
        const content = document.getElementById('modal-content');
        modal.classList.remove('hidden');
        // Trigger reflow
        void modal.offsetWidth;
        content.classList.remove('translate-y-full');
    }

    function editColumn(col) {
        document.getElementById('col_id').value = col.id;
        document.getElementById('modal-title').textContent = 'Edit Column';
        document.getElementById('col_name').value = col.name;
        document.getElementById('col_type').value = col.type;
        document.getElementById('col_options').value = col.options ? col.options.join(', ') : '';
        document.getElementById('col_is_required').checked = col.is_required;
        document.getElementById('col_show_on_list').checked = col.show_on_list;
        
        if (col.is_category) document.getElementById('col_is_category').checked = true;
        else if (col.is_unique) document.getElementById('col_is_unique').checked = true;
        else if (col.is_combo) document.getElementById('col_is_combo').checked = true;
        else if (col.is_title) document.getElementById('col_is_title').checked = true;
        else document.getElementById('col_is_none').checked = true;

        if (col.is_system) {
            document.getElementById('btn-delete').classList.add('hidden');
            document.getElementById('flags-group').classList.add('opacity-50', 'pointer-events-none');
        } else {
            document.getElementById('btn-delete').classList.remove('hidden');
            document.getElementById('btn-delete').setAttribute('onclick', `deleteColumn(${col.id})`);
            document.getElementById('flags-group').classList.remove('opacity-50', 'pointer-events-none');
        }

        toggleFormOptions();

        const modal = document.getElementById('column-modal');
        const content = document.getElementById('modal-content');
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        content.classList.remove('translate-y-full');
    }

    function closeModal() {
        const modal = document.getElementById('column-modal');
        const content = document.getElementById('modal-content');
        content.classList.add('translate-y-full');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function toggleFormOptions() {
        const type = document.getElementById('col_type').value;
        const group = document.getElementById('options-group');
        if (['select', 'multiselect'].includes(type)) {
            group.classList.remove('hidden');
        } else {
            group.classList.add('hidden');
        }
    }

    async function toggleVisibility(id) {
        try {
            const response = await fetch(`/catalogue-columns/${id}/toggle`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            if(data.success) {
                // UI updates instantly via blade reactivity / reload
                window.location.reload();
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function deleteColumn(id) {
        if(!confirm('Are you sure? This will delete associated data for all products.')) return;
        
        try {
            const response = await fetch(`/catalogue-columns/${id}`, {
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

    async function saveOrder(orderArr) {
        try {
            const response = await fetch('{{ route("catalogue.columns.reorder") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ order: orderArr })
            });
        } catch (e) {
            console.error(e);
        }
    }
</script>
@endsection
