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
                <h1 class="text-[18px] font-bold text-gray-800 tracking-tight">AI Setup Wizard</h1>
            </div>
            @if(empty($isConfigured))
            <div class="px-3 py-1 bg-red-100 text-red-600 rounded-full text-xs font-bold">
                Not Configured
            </div>
            @endif
        </div>

        <div class="px-4">
            <!-- Progress Bar -->
            <div class="relative flex justify-between items-center mb-8 px-2 mt-4">
                <div class="absolute inset-0 top-1/2 -mt-[1px] h-[2px] bg-gray-200 z-0"></div>
                <!-- Line Fill -->
                <div id="progress-line" class="absolute inset-0 top-1/2 -mt-[1px] h-[2px] bg-indigo-600 z-0 transition-all duration-500 w-0"></div>

                <div class="step-indicator active flex flex-col items-center gap-2 relative z-10" data-step="1">
                    <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-md transition-colors ring-4 ring-[#f8fafc]">1</div>
                    <span class="text-[10px] font-bold text-indigo-600 uppercase">Upload</span>
                </div>
                <div class="step-indicator flex flex-col items-center gap-2 relative z-10" data-step="2">
                    <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold text-sm transition-colors ring-4 ring-[#f8fafc]">2</div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase">Columns</span>
                </div>
                <div class="step-indicator flex flex-col items-center gap-2 relative z-10" data-step="3">
                    <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold text-sm transition-colors ring-4 ring-[#f8fafc]">3</div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase">Products</span>
                </div>
            </div>

            @if(!$isConfigured)
            <div class="bg-red-50 border border-red-100 rounded-2xl p-4 mb-4 flex items-start gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 shrink-0 mt-0.5"></i>
                <div>
                    <h4 class="font-bold text-red-800 text-sm">AI Not Configured</h4>
                    <p class="text-xs text-red-600 mt-1">Please ask the administrator to configure Vertex AI credentials in the AI Settings panel.</p>
                </div>
            </div>
            @endif

            <!-- Step 1: Upload -->
            <div id="step-1" class="step-content">
                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 mb-6">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center">
                            <i data-lucide="file-text" class="w-5 h-5 text-orange-500"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-[16px] text-gray-800">Upload Catalogue</h2>
                            <p class="text-[12px] text-gray-500">PDF or Website URL</p>
                        </div>
                    </div>

                    <div class="flex bg-slate-100 rounded-xl p-1 mb-5">
                        <button class="flex-1 py-2 text-sm font-bold rounded-lg bg-white shadow-sm text-gray-800" onclick="setSource('pdf')" id="btn-source-pdf">PDF Document</button>
                        <button class="flex-1 py-2 text-sm font-bold rounded-lg text-gray-500" onclick="setSource('website')" id="btn-source-website">Website URL</button>
                    </div>

                    <div id="source-pdf-section">
                        <input type="file" id="pdf-upload" accept=".pdf" class="hidden" onchange="handleFileSelect(event)">
                        <div class="border-2 border-dashed border-indigo-200 bg-indigo-50/30 rounded-2xl p-8 flex flex-col items-center justify-center text-center cursor-pointer active:scale-[0.98] transition-transform" onclick="document.getElementById('pdf-upload').click()">
                            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-sm mb-4">
                                <i data-lucide="upload-cloud" class="w-8 h-8 text-indigo-500"></i>
                            </div>
                            <h4 class="font-bold text-gray-700 mb-1">Tap to Upload PDF</h4>
                            <p class="text-xs text-gray-500 px-4">AI will scan your PDF and understand your product structure automatically.</p>
                            <p class="text-[10px] text-indigo-400 font-bold mt-4" id="file-name-display"></p>
                        </div>
                    </div>

                    <div id="source-website-section" class="hidden">
                        <label class="block text-xs font-bold text-gray-700 mb-2">Website URL</label>
                        <input type="url" id="website-url" placeholder="https://example.com/products" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 mb-4 transition-colors">
                        <p class="text-xs text-gray-500 px-1">AI will crawl the page to understand your products.</p>
                    </div>

                    <button onclick="analyzeCatalogue()" id="btn-analyze" class="w-full mt-6 bg-indigo-600 text-white font-bold text-[15px] py-4 rounded-2xl shadow-lg shadow-indigo-200 active:scale-95 transition-all @if(empty($isConfigured)) opacity-50 pointer-events-none @endif block">
                        Start AI Analysis
                    </button>
                </div>
                
            </div>

            <!-- AI Processing Overlay -->
            <div id="ai-processing" class="hidden fixed inset-0 z-50 bg-white/90 backdrop-blur-sm flex-col items-center justify-center px-6 text-center">
                <div class="w-24 h-24 relative mb-6">
                    <!-- SVG Ring Animation -->
                    <svg class="animate-spin w-full h-full text-indigo-100" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="currentColor" stroke-width="8"></circle>
                    </svg>
                    <svg class="absolute inset-0 w-full h-full text-indigo-600" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="currentColor" stroke-width="8" stroke-dasharray="283" stroke-dashoffset="75" stroke-linecap="round"></circle>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <i data-lucide="bot" class="w-10 h-10 text-indigo-600 animate-pulse"></i>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">AI is Thinking...</h3>
                <p id="ai-status-text" class="text-sm text-gray-500 mb-2">Reading catalogue content...</p>
                <div id="ai-timer" class="text-sm font-bold text-indigo-600 mb-6 hidden">00:00</div>
                
                <div class="flex gap-2 bg-indigo-50 text-indigo-700 px-4 py-2 rounded-full text-xs font-bold items-center">
                    <div class="w-2 h-2 rounded-full bg-indigo-500 animate-ping"></div>
                    This may take up to 2-3 minutes
                </div>
            </div>

            <!-- Step 2: Columns -->
            <div id="step-2" class="step-content hidden">
                <div class="bg-indigo-50 rounded-2xl p-4 mb-4 flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
                        <i data-lucide="sparkles" class="w-5 h-5 text-indigo-600"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm">AI Found Data Structure</h3>
                        <p class="text-[11px] text-gray-600 mt-1 leading-relaxed">Review the columns AI found. Check <b>"Is Category"</b> for grouping, <b>"Unique"</b> for codes (SKU), and <b>"Combo"</b> for variants (Size/Color).</p>
                    </div>
                </div>

                <div id="columns-container" class="space-y-3 mb-6">
                    <!-- Columns rendered via JS -->
                </div>

                <button onclick="addNewColumnRow()" class="w-full py-3 border-2 border-dashed border-indigo-200 text-indigo-600 font-bold text-sm rounded-xl mb-6 active:bg-indigo-50 transition-colors">
                    + Add Missing Column
                </button>

                <div class="flex gap-3">
                    <button onclick="goToStep(1)" class="w-1/3 bg-slate-100 text-gray-600 font-bold py-3.5 rounded-xl text-sm">Back</button>
                    <button onclick="importColumns()" id="btn-import-cols" class="flex-1 bg-indigo-600 text-white font-bold py-3.5 rounded-xl text-sm shadow-md shadow-indigo-200">Save & Continue</button>
                </div>
            </div>

            <!-- Step 3: Products -->
            <div id="step-3" class="step-content hidden">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 mx-auto bg-indigo-50 rounded-full flex items-center justify-center mb-4">
                        <i data-lucide="package-search" class="w-8 h-8 text-indigo-500"></i>
                    </div>
                    <h2 class="font-bold text-lg text-gray-800">Extract Products</h2>
                    <p class="text-xs text-gray-500 mt-2 px-6">AI will now read every page and extract all individual products matching your defined columns.</p>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-sm font-bold text-gray-700">Products Extracted</span>
                        <span id="product-count" class="bg-indigo-100 text-indigo-700 text-xs font-bold px-2.5 py-1 rounded-full">0</span>
                    </div>
                    
                    <div id="products-preview" class="max-h-[300px] overflow-y-auto w-full overflow-x-auto scrollbar-hide border border-slate-100 rounded-xl hidden">
                        <!-- Table rendered via JS -->
                        <table class="w-full text-left text-xs whitespace-nowrap">
                            <thead class="bg-slate-50 sticky top-0" id="preview-thead"></thead>
                            <tbody class="divide-y divide-slate-100" id="preview-tbody"></tbody>
                        </table>
                    </div>

                    <div id="extract-empty-state" class="text-center py-6">
                        <p class="text-xs text-gray-400">Ready to extract</p>
                    </div>
                </div>

                <div class="flex gap-3" id="product-actions-1">
                    <button onclick="extractProducts()" id="btn-extract" class="w-full bg-indigo-600 text-white font-bold py-4 rounded-xl text-[15px] shadow-md shadow-indigo-200 active:scale-95 transition-all">
                        Extract Products Now
                    </button>
                </div>
                
                <div class="flex gap-3 hidden" id="product-actions-2">
                    <button onclick="extractProducts()" class="w-1/3 bg-slate-100 text-gray-600 font-bold py-4 rounded-xl text-sm">Retry</button>
                    <button onclick="importProductsToSystem()" id="btn-import-prods" class="flex-1 bg-green-500 text-white font-bold py-4 rounded-xl text-[15px] shadow-md shadow-green-200 active:scale-95 transition-all">
                        Import to Database
                    </button>
                </div>
            </div>

            <!-- Step 4: Complete -->
            <div id="step-4" class="step-content hidden text-center pt-8">
                <!-- Confetti container -->
                <div id="confetti-container" class="fixed inset-0 pointer-events-none z-50"></div>
                
                <div class="w-24 h-24 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-6">
                    <i data-lucide="check" class="w-12 h-12 text-green-500"></i>
                </div>
                <h2 class="font-bold text-2xl text-gray-800 mb-2">Setup Complete!</h2>
                <p class="text-sm text-gray-500 mb-8 px-6">Your product catalogue has been fully digitized. The AI Content Engine can now create daily posts for your products.</p>
                
                <div class="bg-slate-50 rounded-2xl p-4 mb-8 text-left">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm text-gray-600">Columns Created</span>
                        <span class="font-bold text-gray-800" id="stat-cols">0</span>
                    </div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm text-gray-600">Categories Created</span>
                        <span class="font-bold text-gray-800" id="stat-cats">0</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Products Imported</span>
                        <span class="font-bold text-green-600" id="stat-prods">0</span>
                    </div>
                </div>

                <a href="{{ route('products') }}" class="block w-full bg-indigo-600 text-white font-bold py-4 rounded-xl text-[15px] shadow-lg shadow-indigo-200 mb-3 active:scale-95 transition-all">
                    View My Products
                </a>
                <button onclick="resetWizard()" class="text-sm font-bold text-gray-400 py-2 active:text-gray-600">
                    Restart Setup Wizard
                </button>
            </div>

        </div>
    </div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script>
    let currentStep = 1;
    let sourceType = 'pdf';
    let selectedFile = null;
    let aiColumns = @json($cachedColumns ?? []);
    let extractedProducts = @json($cachedProducts ?? []);

    // Initialize lucide icons
    lucide.createIcons();

    // Check if we need to resume a step based on cached data
    document.addEventListener('DOMContentLoaded', () => {
        if (extractedProducts.length > 0) {
            goToStep(3);
            renderProductsPreview(extractedProducts);
        } else if (aiColumns.length > 0) {
            goToStep(2);
            renderColumnEditor();
        }
    });

    function setSource(type) {
        sourceType = type;
        if (type === 'pdf') {
            document.getElementById('btn-source-pdf').classList.replace('text-gray-500', 'bg-white');
            document.getElementById('btn-source-pdf').classList.add('shadow-sm', 'text-gray-800');
            document.getElementById('btn-source-website').classList.replace('bg-white', 'text-gray-500');
            document.getElementById('btn-source-website').classList.remove('shadow-sm', 'text-gray-800');
            
            document.getElementById('source-pdf-section').classList.remove('hidden');
            document.getElementById('source-website-section').classList.add('hidden');
        } else {
            document.getElementById('btn-source-website').classList.replace('text-gray-500', 'bg-white');
            document.getElementById('btn-source-website').classList.add('shadow-sm', 'text-gray-800');
            document.getElementById('btn-source-pdf').classList.replace('bg-white', 'text-gray-500');
            document.getElementById('btn-source-pdf').classList.remove('shadow-sm', 'text-gray-800');

            document.getElementById('source-website-section').classList.remove('hidden');
            document.getElementById('source-pdf-section').classList.add('hidden');
        }
    }

    function handleFileSelect(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        if (file.type !== 'application/pdf') {
            alert('Please select a PDF file.');
            return;
        }
        
        if (file.size > 60 * 1024 * 1024) {
            alert('File too large. Maximum size is 60MB.');
            return;
        }

        selectedFile = file;
        document.getElementById('file-name-display').textContent = file.name + ' (' + (file.size/1024/1024).toFixed(1) + ' MB)';
        document.getElementById('file-name-display').classList.remove('hidden');
    }

    function goToStep(step) {
        // Update UI logic for steps 1-4
        document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
        document.getElementById('step-' + step).classList.remove('hidden');
        
        // Update progress bar
        const width = step === 1 ? '0%' : (step === 2 ? '50%' : '100%');
        document.getElementById('progress-line').style.width = width;
        
        document.querySelectorAll('.step-indicator').forEach((el, index) => {
            const numEl = el.querySelector('div');
            const textEl = el.querySelector('span');
            
            if (index + 1 < step) {
                // Completed
                numEl.className = 'w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-md transition-colors ring-4 ring-[#f8fafc]';
                numEl.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>';
                textEl.className = 'text-[10px] font-bold text-indigo-600 uppercase';
            } else if (index + 1 === step) {
                // Active
                numEl.className = 'w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-md transition-colors ring-4 ring-[#f8fafc]';
                numEl.innerHTML = (index + 1).toString();
                textEl.className = 'text-[10px] font-bold text-indigo-600 uppercase';
            } else {
                // Pending
                numEl.className = 'w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold text-sm transition-colors ring-4 ring-[#f8fafc]';
                numEl.innerHTML = (index + 1).toString();
                textEl.className = 'text-[10px] font-bold text-gray-400 uppercase';
            }
        });
        
        currentStep = step;
        window.scrollTo(0, 0);
    }

    async function analyzeCatalogue() {
        if (sourceType === 'pdf' && !selectedFile) {
            alert('Please select a PDF file first.');
            return;
        }
        if (sourceType === 'website' && !document.getElementById('website-url').value) {
            alert('Please enter a website URL.');
            return;
        }

        const formData = new FormData();
        formData.append('source_type', sourceType);
        if (sourceType === 'pdf') {
            formData.append('catalogue_pdf', selectedFile);
        } else {
            formData.append('website_url', document.getElementById('website-url').value);
        }

        // Show loading overlay
        document.getElementById('ai-processing').classList.remove('hidden');
        document.getElementById('ai-processing').classList.add('flex');
        
        let timerEl = document.getElementById('ai-timer');
        timerEl.classList.remove('hidden');
        timerEl.textContent = '00:00';
        let analyzeStartTime = Date.now();
        
        if (extractTimerInterval) clearInterval(extractTimerInterval);
        extractTimerInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - analyzeStartTime) / 1000);
            const mins = Math.floor(elapsed / 60).toString().padStart(2, '0');
            const secs = (elapsed % 60).toString().padStart(2, '0');
            timerEl.textContent = `Elapsed time: ${mins}:${secs}`;
        }, 1000);
        
        const statuses = ['Uploading file...', 'Extracting text contents...', 'Sending to Vertex AI...', 'AI is analyzing structure...'];
        let statusIdx = 0;
        const statusInterval = setInterval(() => {
            statusIdx = (statusIdx + 1) % statuses.length;
            document.getElementById('ai-status-text').textContent = statuses[statusIdx];
        }, 3000);

        try {
            const response = await fetch('{{ route("setup.analyze") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            });

            clearInterval(statusInterval);
            clearInterval(extractTimerInterval);
            timerEl.classList.add('hidden');
            const data = await response.json();

            if (data.success) {
                aiColumns = data.columns;
                renderColumnEditor();
                goToStep(2);
            } else {
                alert(data.message || 'Analysis failed.');
            }
        } catch (error) {
            clearInterval(statusInterval);
            clearInterval(extractTimerInterval);
            timerEl.classList.add('hidden');
            alert('An error occurred during analysis.');
            console.error(error);
        } finally {
            document.getElementById('ai-processing').classList.add('hidden');
            document.getElementById('ai-processing').classList.remove('flex');
        }
    }

    function renderColumnEditor() {
        const container = document.getElementById('columns-container');
        container.innerHTML = '';
        
        if (aiColumns.length === 0) {
            container.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">No columns found. Please try adding manually.</p>';
            return;
        }

        aiColumns.forEach((col, idx) => {
            const optionsStr = col.options ? col.options.join(', ') : '';
            
            const html = `
                <div class="bg-white border ${col.is_category ? 'border-orange-300 shadow-sm' : 'border-slate-200'} rounded-2xl p-4 transition-all">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex-1">
                            <input type="text" class="col-name w-full font-bold text-gray-800 text-[15px] border-none p-0 focus:ring-0" value="${col.name}" placeholder="Column Name">
                        </div>
                        <select class="col-type text-xs bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 outline-none text-gray-600" onchange="toggleOptionsField(this)">
                            <option value="text" ${col.type==='text'?'selected':''}>Text</option>
                            <option value="number" ${col.type==='number'?'selected':''}>Number</option>
                            <option value="select" ${col.type==='select'?'selected':''}>Select</option>
                            <option value="multiselect" ${col.type==='multiselect'?'selected':''}>Multi-Select</option>
                        </select>
                        <button onclick="this.closest('.bg-white').remove()" class="text-gray-300 hover:text-red-500"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </div>
                    
                    <div class="col-options-container ${['select','multiselect'].includes(col.type) ? '' : 'hidden'} mb-3">
                        <input type="text" class="col-options w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-gray-600" value="${optionsStr}" placeholder="Options (comma separated)">
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <label class="flex items-center gap-1.5 bg-slate-50 px-2 py-1 rounded-md text-[10px] font-bold text-gray-600 border border-slate-100 ${col.is_category ? 'bg-orange-50 border-orange-200 text-orange-700' : ''}">
                            <input type="checkbox" class="col-category rounded text-orange-500 focus:ring-0 w-3 h-3" ${col.is_category?'checked':''} onchange="handleExclusiveFlag(this, 'col-category')"> Category
                        </label>
                        <label class="flex items-center gap-1.5 bg-slate-50 px-2 py-1 rounded-md text-[10px] font-bold text-gray-600 border border-slate-100">
                            <input type="checkbox" class="col-unique rounded text-indigo-500 focus:ring-0 w-3 h-3" ${col.is_unique?'checked':''} onchange="handleExclusiveFlag(this, 'col-unique')"> Unique
                        </label>
                        <label class="flex items-center gap-1.5 bg-slate-50 px-2 py-1 rounded-md text-[10px] font-bold text-gray-600 border border-slate-100">
                            <input type="checkbox" class="col-combo rounded text-purple-500 focus:ring-0 w-3 h-3" ${col.is_combo?'checked':''}> Combo
                        </label>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        });
        lucide.createIcons();
    }

    function toggleOptionsField(selectEl) {
        const container = selectEl.closest('.bg-white').querySelector('.col-options-container');
        if (['select', 'multiselect'].includes(selectEl.value)) {
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
        }
    }

    function handleExclusiveFlag(checkbox, className) {
        if (!checkbox.checked) return;
        // Uncheck all others of this class
        document.querySelectorAll('.' + className).forEach(el => {
            if (el !== checkbox) el.checked = false;
        });
        
        // Highlight category container
        if (className === 'col-category') {
            document.querySelectorAll('.' + className).forEach(el => {
                const container = el.closest('.bg-white');
                const label = el.closest('label');
                if (el === checkbox) {
                    container.classList.add('border-orange-300', 'shadow-sm');
                    label.classList.add('bg-orange-50', 'border-orange-200', 'text-orange-700');
                } else {
                    container.classList.remove('border-orange-300', 'shadow-sm');
                    label.classList.remove('bg-orange-50', 'border-orange-200', 'text-orange-700');
                }
            });
        }
    }

    function addNewColumnRow() {
        const container = document.getElementById('columns-container');
        aiColumns.push({name: '', type: 'text'}); // Add dummy to keep state kinda mapped
        renderColumnEditor();
    }

    async function importColumns() {
        const rows = document.querySelectorAll('#columns-container > .bg-white');
        const columnsData = [];
        
        let hasCategory = false;
        
        rows.forEach((row, i) => {
            const name = row.querySelector('.col-name').value.trim();
            if(!name) return;
            
            const isCat = row.querySelector('.col-category').checked;
            if(isCat) hasCategory = true;
            
            columnsData.push({
                name: name,
                type: row.querySelector('.col-type').value,
                options: row.querySelector('.col-options').value ? row.querySelector('.col-options').value.split(',').map(s=>s.trim()) : null,
                is_category: isCat,
                is_unique: row.querySelector('.col-unique').checked,
                is_combo: row.querySelector('.col-combo').checked,
                is_title: false,
                sort_order: i + 1
            });
        });

        if (columnsData.length === 0) {
            alert('Please add at least one column.');
            return;
        }

        const btn = document.getElementById('btn-import-cols');
        const oldText = btn.innerHTML;
        btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin inline mr-2"></i> Saving...';
        lucide.createIcons();
        btn.disabled = true;

        try {
            const response = await fetch('{{ route("setup.import.columns") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ columns: columnsData })
            });

            const data = await response.json();
            if (data.success) {
                document.getElementById('stat-cols').textContent = data.created;
                document.getElementById('stat-cats').textContent = data.categories_created.length;
                goToStep(3);
            } else {
                alert(data.message || 'Import failed.');
            }
        } catch (error) {
            alert('An error occurred while saving columns.');
            console.error(error);
        } finally {
            btn.innerHTML = oldText;
            btn.disabled = false;
        }
    }

    let extractTimerInterval = null;

    async function extractProducts() {
        document.getElementById('ai-processing').classList.remove('hidden');
        document.getElementById('ai-processing').classList.add('flex');
        document.getElementById('ai-status-text').textContent = "Scanning catalogue to extract products...";
        
        let timerEl = document.getElementById('ai-timer');
        timerEl.classList.remove('hidden');
        timerEl.textContent = '00:00';
        let extractStartTime = Date.now();
        
        if (extractTimerInterval) clearInterval(extractTimerInterval);
        extractTimerInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - extractStartTime) / 1000);
            const mins = Math.floor(elapsed / 60).toString().padStart(2, '0');
            const secs = (elapsed % 60).toString().padStart(2, '0');
            timerEl.textContent = `Elapsed time: ${mins}:${secs}`;
        }, 1000);
        
        const extStatuses = ['Scanning PDF chunks...', 'Extracting data with AI...', 'Matching constraints...', 'Finalizing product list...'];
        let extStatusIdx = 0;
        const extStatusInterval = setInterval(() => {
            extStatusIdx = (extStatusIdx + 1) % extStatuses.length;
            document.getElementById('ai-status-text').textContent = extStatuses[extStatusIdx];
        }, 4000);
        
        try {
            const response = await fetch('{{ route("setup.extract.products") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            clearInterval(extStatusInterval);
            clearInterval(extractTimerInterval);
            timerEl.classList.add('hidden');
            
            const data = await response.json();
            if (data.success) {
                extractedProducts = data.products;
                renderProductsPreview(extractedProducts);
            } else {
                showExtractError(data.message || 'Extraction failed.');
            }
        } catch (error) {
            clearInterval(extStatusInterval);
            clearInterval(extractTimerInterval);
            timerEl.classList.add('hidden');
            
            console.error(error);
            showExtractError('The server took too long to respond or returned an error (likely a timeout). This usually happens with very large catalogues. Please try again or extract a smaller PDF.');
        } finally {
            document.getElementById('ai-processing').classList.add('hidden');
            document.getElementById('ai-processing').classList.remove('flex');
        }
    }

    function showExtractError(msg) {
        document.getElementById('extract-empty-state').innerHTML = `
            <div class="bg-red-50 text-red-600 p-4 rounded-xl border border-red-100 text-left">
                <h4 class="font-bold text-sm mb-1 flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Task Failed
                </h4>
                <p class="text-[11px] leading-relaxed">${msg}</p>
            </div>
        `;
        document.getElementById('extract-empty-state').classList.remove('hidden');
        document.getElementById('product-actions-1').classList.remove('hidden');
    }

    function renderProductsPreview(products) {
        if (products.length === 0) return;
        
        document.getElementById('product-count').textContent = products.length;
        document.getElementById('extract-empty-state').classList.add('hidden');
        document.getElementById('products-preview').classList.remove('hidden');
        
        document.getElementById('product-actions-1').classList.add('hidden');
        document.getElementById('product-actions-2').classList.remove('hidden');

        // Render table
        const thead = document.getElementById('preview-thead');
        const tbody = document.getElementById('preview-tbody');
        
        // Get keys from first item
        const keys = Object.keys(products[0]).slice(0, 5); // Max 5 cols for preview
        
        let ths = '<tr>';
        keys.forEach(k => { ths += `<th class="px-4 py-2 font-bold text-gray-600">${k}</th>`; });
        ths += '</tr>';
        thead.innerHTML = ths;
        
        let trs = '';
        products.slice(0, 10).forEach(p => {
            trs += '<tr>';
            keys.forEach(k => {
                const val = p[k] || '-';
                trs += `<td class="px-4 py-2 text-gray-800 text-ellipsis overflow-hidden max-w-[120px]">${val}</td>`;
            });
            trs += '</tr>';
        });
        if(products.length > 10) {
            trs += `<tr><td colspan="${keys.length}" class="px-4 py-2 text-center text-gray-500 italic">... and ${products.length - 10} more</td></tr>`;
        }
        tbody.innerHTML = trs;
    }

    async function importProductsToSystem() {
        const btn = document.getElementById('btn-import-prods');
        const oldText = btn.innerHTML;
        btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin inline mr-2"></i> Importing...';
        lucide.createIcons();
        btn.disabled = true;

        try {
            const response = await fetch('{{ route("setup.import.products") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();
            if (data.success) {
                document.getElementById('stat-prods').textContent = data.created;
                goToStep(4);
                triggerConfetti();
            } else {
                alert(data.message || 'Import failed.');
            }
        } catch (error) {
            alert('An error occurred during import.');
            console.error(error);
        } finally {
            btn.innerHTML = oldText;
            btn.disabled = false;
        }
    }

    function triggerConfetti() {
        var duration = 3 * 1000;
        var end = Date.now() + duration;

        (function frame() {
            confetti({
                particleCount: 5,
                angle: 60,
                spread: 55,
                origin: { x: 0 },
                colors: ['#4f46e5', '#10b981', '#f59e0b']
            });
            confetti({
                particleCount: 5,
                angle: 120,
                spread: 55,
                origin: { x: 1 },
                colors: ['#4f46e5', '#10b981', '#f59e0b']
            });

            if (Date.now() < end) {
                requestAnimationFrame(frame);
            }
        }());
    }

    async function resetWizard() {
        if(!confirm('Are you sure you want to reset the wizard? This will clear cached AI data.')) return;
        
        await fetch('{{ route("setup.reset") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        
        window.location.reload();
    }
</script>
@endsection
