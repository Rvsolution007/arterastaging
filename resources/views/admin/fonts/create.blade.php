@extends('layouts.app')
@section('title', 'AI Font Uploader')
@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

    .ai-upload-wrapper { 
        font-family: 'Poppins', sans-serif; 
        background-color: #f8fafc; 
        min-height: calc(100vh - 60px); 
        padding: 2rem; 
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .upload-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        padding: 3rem 2rem;
        width: 100%;
        max-width: 650px;
    }

    .ai-header { text-align: center; margin-bottom: 2rem; }
    .ai-header h1 { font-weight: 700; font-size: 2rem; color: #1e293b; letter-spacing: -0.025em; margin-bottom: 0.5rem; }
    .ai-header p { color: #64748b; font-size: 0.95rem; line-height: 1.5; }
    
    .dropzone-container {
        background: #f8fafc;
        border: 2px dashed #cbd5e1;
        border-radius: 16px;
        padding: 3rem 2rem;
        text-align: center;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .dropzone-container.dragover {
        border-color: #6366f1;
        background: #eef2ff;
    }
    .drop-icon { font-size: 3.5rem; color: #818cf8; margin-bottom: 1rem; }
    .drop-text { font-size: 1.25rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem; }
    .drop-subtext { color: #64748b; font-size: 0.85rem; margin-bottom: 1.5rem; }
    
    .ai-btn-browse {
        background: #6366f1;
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.2);
    }
    .ai-btn-browse:hover { background: #4f46e5; transform: translateY(-1px); box-shadow: 0 6px 8px -1px rgba(99, 102, 241, 0.3); }

    .file-input { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }

    .upload-list { margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.75rem; }
    .upload-item {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        position: relative;
        overflow: hidden;
    }
    .upload-info { display: flex; align-items: center; gap: 15px; z-index: 1; }
    .font-icon { background: #eef2ff; padding: 12px; border-radius: 8px; color: #6366f1; font-size: 1.2rem; }
    .font-details { display: flex; flex-direction: column; }
    .font-name { font-weight: 600; font-size: 0.95rem; color: #1e293b; }
    .font-size { font-size: 0.75rem; color: #64748b; }
    
    .upload-status { font-weight: 500; font-size: 0.85rem; z-index: 1; display: flex; align-items: center; gap: 6px; }
    .status-success { color: #10b981; }
    .status-error { color: #ef4444; }
    .status-uploading { color: #6366f1; }
    
    .progress-bar-bg {
        position: absolute; left: 0; bottom: 0; height: 3px; background: transparent; width: 100%;
    }
    .progress-bar-fill {
        height: 100%; background: #6366f1; width: 0%; transition: width 0.3s ease;
    }
    
    .back-link { 
        display: inline-flex; 
        align-items: center; 
        gap: 6px; 
        color: #64748b; 
        text-decoration: none; 
        margin-bottom: 1.5rem; 
        font-size: 0.9rem;
        font-weight: 500;
        transition: color 0.2s; 
    }
    .back-link:hover { color: #1e293b; }
</style>

<div class="content-wrapper ai-upload-wrapper">
    <div class="upload-card">
        <a href="{{ route('admin.fonts.index') }}" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Fonts Manager</a>
        
        <div class="ai-header">
            <h1>Neural Font Injector</h1>
            <p>Drag and drop multiple font files to train the system.<br>The exact filename will be auto-assigned as the font name.</p>
        </div>

        <div class="dropzone-container" id="dropzone">
            <input type="file" id="file-input" class="file-input" multiple accept=".ttf,.otf,.woff,.woff2">
            <div class="drop-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
            <div class="drop-text">Drag & Drop Fonts Here</div>
            <div class="drop-subtext">Supports .ttf, .otf, .woff, .woff2 (Max 20MB per file)</div>
            <button class="ai-btn-browse">Browse Files</button>
        </div>

        <div class="upload-list" id="upload-list">
            <!-- Upload items will appear here -->
        </div>
    </div>
</div>

<script>
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('file-input');
    const uploadList = document.getElementById('upload-list');
    const uploadUrl = "{{ route('admin.fonts.store') }}";
    const csrfToken = "{{ csrf_token() }}";

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Highlight dropzone when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => dropzone.classList.add('dragover'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => dropzone.classList.remove('dragover'), false);
    });

    // Handle dropped files
    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    });

    // Handle selected files
    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
        this.value = ''; // Reset input
    });

    // Handle browse button click
    document.querySelector('.ai-btn-browse').addEventListener('click', () => {
        fileInput.click();
    });

    let totalFiles = 0;
    let processedFiles = 0;

    function handleFiles(files) {
        totalFiles += files.length;
        [...files].forEach(uploadFile);
    }

    function checkCompletion() {
        processedFiles++;
        if (processedFiles === totalFiles) {
            // All files processed, redirect after 1.5 seconds
            setTimeout(() => {
                window.location.href = "{{ route('admin.fonts.index') }}";
            }, 1500);
        }
    }

    function uploadFile(file) {
        // Validate file type
        const validExts = ['.ttf', '.otf', '.woff', '.woff2'];
        const isValid = validExts.some(ext => file.name.toLowerCase().endsWith(ext));
        
        if (!isValid) {
            alert('Invalid file type: ' + file.name);
            checkCompletion();
            return;
        }

        // Create UI element
        const fileId = 'file-' + Math.random().toString(36).substr(2, 9);
        const fileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
        const fontName = file.name.split('.').slice(0, -1).join('.'); // Extract name without extension

        const itemHtml = `
            <div class="upload-item" id="${fileId}">
                <div class="upload-info">
                    <div class="font-icon"><i class="fa-solid fa-font"></i></div>
                    <div class="font-details">
                        <span class="font-name">${fontName}</span>
                        <span class="font-size">${fileSize}</span>
                    </div>
                </div>
                <div class="upload-status status-uploading" id="status-${fileId}">
                    <i class="fa-solid fa-circle-notch fa-spin"></i> Processing...
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" id="progress-${fileId}"></div>
                </div>
            </div>
        `;
        
        uploadList.insertAdjacentHTML('afterbegin', itemHtml);

        // Upload via XMLHttpRequest for progress tracking
        const formData = new FormData();
        formData.append('file', file);
        formData.append('_token', csrfToken);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', uploadUrl, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                document.getElementById(`progress-${fileId}`).style.width = percentComplete + '%';
            }
        };

        xhr.onload = function() {
            const statusEl = document.getElementById(`status-${fileId}`);
            try {
                const res = JSON.parse(xhr.responseText);
                if (xhr.status === 200 && res.success) {
                    statusEl.className = 'upload-status status-success';
                    statusEl.innerHTML = '<i class="fa-solid fa-circle-check"></i> Injected';
                    document.getElementById(`progress-${fileId}`).style.background = '#10b981';
                } else {
                    let errMsg = res.message || 'Error';
                    if (res.errors) {
                        errMsg = Object.values(res.errors)[0][0]; // Get first validation error
                    }
                    statusEl.className = 'upload-status status-error';
                    statusEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> ' + errMsg;
                    document.getElementById(`progress-${fileId}`).style.background = '#ef4444';
                }
            } catch(e) {
                statusEl.className = 'upload-status status-error';
                statusEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Server Error';
                document.getElementById(`progress-${fileId}`).style.background = '#ef4444';
            }
            checkCompletion();
        };

        xhr.onerror = function() {
            const statusEl = document.getElementById(`status-${fileId}`);
            statusEl.className = 'upload-status status-error';
            statusEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Network Error';
            document.getElementById(`progress-${fileId}`).style.background = '#ef4444';
            checkCompletion();
        };

        xhr.send(formData);
    }
</script>
@endsection
