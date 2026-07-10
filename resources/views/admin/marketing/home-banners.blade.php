@extends('layouts.app')

@section('title', 'Home Banners')

@section('extra_css')
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

.premium-container {
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
    margin-bottom: 1.5rem;
}

.table-panel {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
    overflow: hidden;
    margin-bottom: 1.5rem;
    display: flex;
    flex-direction: column;
}

.table-panel-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: #f8fafc;
}

.table-panel-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.table-icon-wrapper {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    background: #e0e7ff; 
    color: #4338ca;
}

.table-panel-body {
    padding: 1.5rem;
}

/* Drag and Drop Zone */
.drop-zone {
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #f8fafc;
    position: relative;
    overflow: hidden;
}

.drop-zone:hover, .drop-zone.dragover {
    border-color: #4f46e5;
    background: #eff6ff;
}

.drop-zone-icon {
    font-size: 2.5rem;
    color: #94a3b8;
    margin-bottom: 1rem;
    transition: color 0.3s ease;
}

.drop-zone:hover .drop-zone-icon, .drop-zone.dragover .drop-zone-icon {
    color: #4f46e5;
}

.drop-zone-text {
    font-size: 0.95rem;
    color: #64748b;
    font-weight: 500;
}

.drop-zone-subtext {
    font-size: 0.8rem;
    color: #94a3b8;
    margin-top: 0.5rem;
}

.drop-zone input[type="file"] {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    opacity: 0;
    cursor: pointer;
}

/* Image Preview inside Drop Zone */
.image-preview-container {
    display: none;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    width: 100%;
    height: 100%;
}

.image-preview-img {
    max-width: 100%;
    max-height: 150px;
    border-radius: 8px;
    object-fit: contain;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    margin-bottom: 1rem;
}

.upload-btn {
    background: #4f46e5;
    color: white;
    font-weight: 600;
    border: none;
    padding: 0.6rem 1.5rem;
    border-radius: 8px;
    width: 100%;
    margin-top: 1rem;
    transition: background 0.3s ease;
    display: none;
}

.upload-btn:hover {
    background: #4338ca;
}

/* Uploaded Banners Grid */
.banner-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 15px;
    margin-top: 1.5rem;
}

.banner-item {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    cursor: grab;
    transition: transform 0.2s ease;
}

.banner-item:active {
    cursor: grabbing;
    transform: scale(0.98);
}

.banner-item img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    display: block;
}

.delete-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(225, 29, 72, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.8rem;
    transition: background 0.2s ease;
}

.delete-btn:hover {
    background: #be123c;
}

.empty-state {
    text-align: center;
    padding: 2rem 0;
    color: #94a3b8;
    font-size: 0.9rem;
}

.empty-state i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}
</style>
@endsection

@section('content')
<div class="premium-container">
        <h1 class="page-title">Home Page Marquee Banners</h1>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px; font-family: 'Poppins', sans-serif;">
                <i class="fa fa-check-circle mr-2"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px; font-family: 'Poppins', sans-serif;">
                <i class="fa fa-exclamation-circle mr-2"></i> {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px; font-family: 'Poppins', sans-serif;">
                <ul class="mb-0 pl-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="row">
            @for($i = 1; $i <= 3; $i++)
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="table-panel">
                    <div class="table-panel-header">
                        <div class="table-icon-wrapper">
                            <i class="fa fa-image"></i>
                        </div>
                        <h3 class="table-panel-title">Column {{ $i }} Images</h3>
                    </div>
                    
                    <div class="table-panel-body">
                        <form action="{{ route('admin.home_banners.store') }}" method="POST" enctype="multipart/form-data" id="form-col-{{$i}}">
                            @csrf
                            <input type="hidden" name="column_index" value="{{ $i }}">
                            
                            <div class="drop-zone" id="drop-zone-{{$i}}">
                                <div class="drop-zone-content" id="drop-content-{{$i}}">
                                    <i class="fa fa-cloud-upload-alt drop-zone-icon"></i>
                                    <div class="drop-zone-text">Drag & Drop image here</div>
                                    <div class="drop-zone-subtext">or click to browse</div>
                                </div>
                                <div class="image-preview-container" id="preview-container-{{$i}}">
                                    <img src="" class="image-preview-img" id="preview-img-{{$i}}" alt="Preview">
                                    <div class="drop-zone-subtext" id="preview-name-{{$i}}">filename.jpg</div>
                                </div>
                                <input type="file" name="image" id="file-input-{{$i}}" required accept="image/*">
                            </div>
                            
                            <button type="submit" class="upload-btn" id="upload-btn-{{$i}}">
                                <i class="fa fa-upload mr-2"></i> Upload & Convert to WebP
                            </button>
                        </form>

                        <hr style="margin: 1.5rem 0; border-top: 1px solid #f1f5f9;">
                        
                        <h5 style="font-size: 1rem; font-weight: 600; color: #475569; margin-bottom: 0;">Uploaded Banners</h5>
                        
                        @if(isset($banners[$i]) && $banners[$i]->count() > 0)
                            <div class="banner-grid sortable-grid">
                                @foreach($banners[$i] as $banner)
                                    <div class="banner-item" data-id="{{ $banner->id }}">
                                        <img src="{{ asset($banner->image_path) }}" alt="Banner">
                                        <form action="{{ route('admin.home_banners.destroy', $banner->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="delete-btn" onclick="if(confirm('Are you sure you want to delete this banner?')) this.form.submit();">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="empty-state">
                                <i class="fa fa-image"></i><br>
                                No images uploaded.<br>Default static images will be shown.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endfor
        </div>
</div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    for(let i = 1; i <= 3; i++) {
        const dropZone = document.getElementById(`drop-zone-${i}`);
        const fileInput = document.getElementById(`file-input-${i}`);
        const dropContent = document.getElementById(`drop-content-${i}`);
        const previewContainer = document.getElementById(`preview-container-${i}`);
        const previewImg = document.getElementById(`preview-img-${i}`);
        const previewName = document.getElementById(`preview-name-${i}`);
        const uploadBtn = document.getElementById(`upload-btn-${i}`);

        // Handle Drag Events
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
        });

        // Handle Drop
        dropZone.addEventListener('drop', function(e) {
            let dt = e.dataTransfer;
            let files = dt.files;
            
            if (files.length > 0) {
                fileInput.files = files;
                handleFiles(files[0]);
            }
        }, false);

        // Handle Click/Select
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                handleFiles(this.files[0]);
            }
        });

        function handleFiles(file) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                
                reader.onloadend = function() {
                    previewImg.src = reader.result;
                    previewName.textContent = file.name;
                    
                    dropContent.style.display = 'none';
                    previewContainer.style.display = 'flex';
                    uploadBtn.style.display = 'block';
                }
            } else {
                alert('Please select an image file.');
                fileInput.value = '';
            }
        }
    }

    // Initialize Sortable
    $('.sortable-grid').sortable({
        items: '.banner-item',
        cursor: 'grabbing',
        update: function(event, ui) {
            let order = [];
            $(this).children('.banner-item').each(function(index) {
                order[index] = $(this).data('id');
            });

            $.ajax({
                url: "{{ route('admin.home_banners.sort') }}",
                type: "POST",
                data: {
                    order: order,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if(typeof toastr !== 'undefined') {
                        toastr.success('Banner order updated successfully');
                    }
                },
                error: function(xhr) {
                    if(typeof toastr !== 'undefined') {
                        toastr.error('Failed to update banner order');
                    }
                }
            });
        }
    });
});
</script>
@endsection
