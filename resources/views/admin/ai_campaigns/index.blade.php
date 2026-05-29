@extends('layouts.app')

@section('extra_css')
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

.campaign-container {
    font-family: 'Poppins', sans-serif;
    padding: 1.5rem;
    background-color: #f8fafc;
    min-height: 100vh;
}

.table-panel {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
    padding: 24px;
    margin-top: 20px;
}

.drag-drop-zone {
    border: 2px dashed #cbd5e1;
    border-radius: 10px;
    padding: 30px 20px;
    text-align: center;
    background-color: #f8fafc;
    position: relative;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Poppins', sans-serif;
}
.drag-drop-zone.dragover {
    background-color: #e2e8f0;
    border-color: #a855f7;
}
.drag-drop-zone input[type="file"] {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    opacity: 0;
    cursor: pointer;
    z-index: 2;
}
.drag-drop-text {
    color: #64748b;
    font-size: 14px;
    font-weight: 500;
    pointer-events: none;
    position: relative;
    z-index: 1;
}

.page-title {
    font-weight: 700;
    color: #1e293b;
    font-size: 1.5rem;
    letter-spacing: -0.025em;
    margin-bottom: 20px;
}

.panel {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
    padding: 24px;
    margin-bottom: 20px;
}

.panel-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-magic {
    background: linear-gradient(135deg, #a855f7 0%, #7e22ce 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s;
}

.btn-magic:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(126, 34, 206, 0.3);
    color: white;
}

.preview-box {
    background: #f1f5f9;
    border-radius: 12px;
    padding: 16px;
    margin-top: 20px;
    border: 1px dashed #cbd5e1;
}

.mock-notification {
    background: white;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    display: flex;
    gap: 15px;
    max-width: 400px;
    margin: 0 auto;
}

.mock-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
}

.mock-content { flex: 1; }
.mock-title { font-weight: 600; color: #1e293b; font-size: 14px; margin-bottom: 4px; }
.mock-text { font-size: 13px; color: #64748b; line-height: 1.4; }

.form-control { border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 15px; font-family: 'Poppins', sans-serif;}
.form-control:focus { border-color: #a855f7; box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.1); }
</style>
@endsection

@section('content')
<div class="campaign-container">
    <div class="row align-items-center">
        <div class="col-md-12">
            <h4 class="page-title"><i class="fa-solid fa-wand-magic-sparkles text-primary mr-2"></i> AI Smart Campaigns Engine</h4>
        </div>
    </div>

    @if(session('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <!-- AI Generator Panel -->
        <div class="col-lg-6">
            <div class="panel">
                <h5 class="panel-title"><i class="fa-solid fa-robot text-purple"></i> Generate AI Copy</h5>
                <p class="text-muted text-sm">Let AI write highly engaging push notifications based on a simple prompt.</p>
                
                <div class="form-group">
                    <label>What's the campaign about?</label>
                    <textarea id="ai-prompt" class="form-control" rows="3" placeholder="e.g. Offer 20% discount on Diwali custom frames..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Tone of Voice</label>
                    <select id="ai-tone" class="custom-select" style="font-family: 'Poppins', sans-serif; height: calc(2.25rem + 2px);">
                        <option value="persuasive">Persuasive & Urgent</option>
                        <option value="friendly">Friendly & Casual</option>
                        <option value="professional">Professional</option>
                        <option value="funny">Funny & Witty</option>
                    </select>
                </div>
                
                <button type="button" class="btn-magic" id="btn-generate">
                    <i class="fa-solid fa-bolt"></i> Generate Copy
                </button>
            </div>
            
            <div class="panel">
                <h5 class="panel-title"><i class="fa-solid fa-mobile-screen"></i> Live Preview</h5>
                <div class="preview-box">
                    <div class="mock-notification">
                        <div class="mock-icon"><i class="fa-solid fa-bell"></i></div>
                        <div class="mock-content">
                            <div class="mock-title" id="prev-title">Your Title Here</div>
                            <div class="mock-text" id="prev-msg">Your engaging notification message will appear here...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sender Panel -->
        <div class="col-lg-6">
            <div class="panel">
                <h5 class="panel-title"><i class="fa-solid fa-paper-plane text-success"></i> Broadcast Campaign</h5>
                <form action="{{ route('admin.manual_notification.send') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>Notification Title</label>
                        <input type="text" name="title" id="final-title" class="form-control" required placeholder="Title">
                    </div>
                    
                    <div class="form-group">
                        <label>Notification Message</label>
                        <textarea name="message" id="final-message" class="form-control" rows="3" required placeholder="Message..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Type</label>
                        <select id="type" name="type" class="form-control" required style="font-family: 'Poppins', sans-serif; height: calc(2.25rem + 2px);">
                            <option value="">Select Type</option>
                            <option value="category">Category</option>
                            <option value="festival">Festival</option>
                            <option value="custom">Custom</option>
                            <option value="externalLink">External Link</option>
                            <option value="subscriptionPlan">Subscription Plan</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="otherText">
                    </div>
                    
                    <div class="form-group">
                        <label>Attach Image (Optional)</label>
                        <div class="drag-drop-zone" id="dragDropZone">
                            <div class="drag-drop-text" id="dragDropText">
                                <i class="fa-solid fa-cloud-arrow-up" style="font-size: 30px; color: #a855f7; margin-bottom: 10px; display: block;"></i>
                                <span>Drag and drop an image here or click to select</span>
                            </div>
                            <input type="file" id="campaignImage" name="image" accept="image/*">
                            <div id="preview" style="display: none; margin-top: 15px; position: relative; z-index: 1;">
                                <img class="shadow bg-white rounded" src="" alt="Image Preview" style="max-height: 150px; max-width: 100%; border-radius: 8px;" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-success btn-lg w-100" style="border-radius: 8px; font-weight: 600;">
                            <i class="fa-solid fa-satellite-dish"></i> Send AI Campaign Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- History Panel -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="table-panel">
                <h5 class="panel-title"><i class="fa-solid fa-clock-rotate-left text-info"></i> Recent Campaigns History</h5>
                <p class="text-muted text-sm mb-4">View and manage your previously sent AI and standard broadcast campaigns.</p>
                
                <form action="{{ route('admin.manual_notification.bulk_delete') }}" method="POST" id="bulk-delete-form">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="historyTable" style="width: 100%;">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 40px; text-align: center;">
                                        <input type="checkbox" id="selectAll">
                                    </th>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Message</th>
                                    <th>Type</th>
                                    <th>Sent Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($notifications as $n)
                                <tr>
                                    <td style="text-align: center;">
                                        <input type="checkbox" name="ids[]" class="row-checkbox" value="{{ $n->id }}">
                                    </td>
                                    <td>
                                        @if($n->image)
                                            <img src="{{ asset('uploads/' . $n->image) }}" width="45" height="45" class="rounded shadow-sm" style="object-fit: cover;">
                                        @else
                                            <div style="width:45px; height:45px; background:#e2e8f0; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#94a3b8;">
                                                <i class="fa-solid fa-image"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td style="font-weight: 500;">{{ Str::limit($n->title, 30) }}</td>
                                    <td class="text-muted" style="font-size: 0.9rem;">{{ Str::limit($n->message, 50) }}</td>
                                    <td>
                                        <span class="badge badge-light border">{{ ucfirst($n->type) }}</span>
                                    </td>
                                    <td style="font-size: 0.9rem;">{{ $n->created_at->format('d M, Y h:i A') }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger single-delete" data-id="{{ $n->id }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <button type="button" class="btn btn-danger" id="btn-bulk-delete" disabled>
                            <i class="fa-solid fa-trash-can mr-1"></i> Delete Selected
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(document).ready(function() {
    // Live preview update
    $('#final-title').on('input', function() {
        $('#prev-title').text($(this).val() || 'Your Title Here');
    });
    $('#final-message').on('input', function() {
        $('#prev-msg').text($(this).val() || 'Your engaging notification message will appear here...');
    });

    $('#btn-generate').click(function() {
        let prompt = $('#ai-prompt').val();
        let tone = $('#ai-tone').val();
        
        if(!prompt) {
            toastr.warning('Please enter a prompt first.');
            return;
        }
        
        let btn = $(this);
        let originalText = btn.html();
        btn.html('<i class="fa-solid fa-spinner fa-spin"></i> Generating...').prop('disabled', true);
        
        $.post('{{ route("admin.manual_notification.generate") }}', {
            _token: '{{ csrf_token() }}',
            prompt: prompt,
            tone: tone
        }, function(res) {
            if(res.success) {
                $('#final-title').val(res.title).trigger('input');
                $('#final-message').val(res.message).trigger('input');
                toastr.success('AI Copy generated!');
            } else {
                toastr.error('Generation failed: ' + res.error);
            }
        }).fail(function() {
            toastr.error('Server error during AI generation.');
        }).always(function() {
            btn.html(originalText).prop('disabled', false);
        });
    });

    // Image Preview and Drag/Drop Handlers
    function imagePreview(fileInput) {
        if (fileInput.files && fileInput.files[0]) {
            var fileReader = new FileReader();
            fileReader.onload = function (event) {
                $('#preview').show();
                $('#preview img').attr('src', event.target.result);
                $('#dragDropText').hide();
            };
            fileReader.readAsDataURL(fileInput.files[0]);
        } else {
            $('#preview').hide();
            $('#dragDropText').show();
        }
    }

    $("#campaignImage").change(function () {
        imagePreview(this);
    });

    var dragDropZone = document.getElementById('dragDropZone');
    dragDropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    dragDropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });
    dragDropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        if(e.dataTransfer.files.length) {
            document.getElementById('campaignImage').files = e.dataTransfer.files;
            imagePreview(document.getElementById('campaignImage'));
        }
    });

    // Handle Type Selection
    $('#type').select2();
    $("#type").change(function () {
        $('#otherText').empty();
        if ($(this).find("option:selected").text() == "Category") {
            $('#otherText').append('<label class="col-form-label">Select Category</label><select id="category_id" name="category_id" class="form-control" required style="font-family: \'Poppins\', sans-serif;"><option value="">Select Category</option>@foreach($category as $c)<option value="{{$c->id}}">{{$c->name}}</option>@endforeach</select>');
        }
        if ($(this).find("option:selected").text() == "Festival") {
            $('#otherText').append('<label class="col-form-label">Select Festival</label><select id="festival_id" name="festival_id" class="form-control" required style="font-family: \'Poppins\', sans-serif;"><option value="">Select Festival</option>@foreach($festival as $f)<option value="{{$f->id}}">{{$f->title}}</option>@endforeach</select>');
        }
        if ($(this).find("option:selected").text() == "Custom") {
            $('#otherText').append('<label class="col-form-label">Select Custom Category</label><select id="custom_category_id" name="custom_category_id" class="form-control" required style="font-family: \'Poppins\', sans-serif;"><option value="">Select Custom Category</option>@foreach($custom as $c)<option value="{{$c->id}}">{{$c->name}}</option>@endforeach</select>');
        }
        if ($(this).find("option:selected").text() == "External Link") {
            $('#otherText').append('<label class="col-form-label">External Link (Optional)</label><input type="text" id="external_link" class="form-control" name="external_link" placeholder="http://www.google.com" style="font-family: \'Poppins\', sans-serif;">');
        }
        if ($(this).find("option:selected").text() == "Subscription Plan") {
            $('#otherText').append('<label class="col-form-label">Subscription Plan</label><select id="plan_id" name="subscription_id" class="form-control" required style="font-family: \'Poppins\', sans-serif;"><option value="">Select Subscription Plan</option>@foreach($plan as $p)<option value="{{$p->id}}">{{$p->plan_name}}</option>@endforeach</select>');
        }
        if($('#category_id').length) $('#category_id').select2();
        if($('#festival_id').length) $('#festival_id').select2();
        if($('#custom_category_id').length) $('#custom_category_id').select2();
        if($('#plan_id').length) $('#plan_id').select2();
    });

    // History Table JS
    let historyTable = $('#historyTable').DataTable({
        "order": [[ 5, "desc" ]],
        "columnDefs": [
            { "orderable": false, "targets": [0, 1, 6] }
        ]
    });

    // Select All
    $('#selectAll').change(function() {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
        toggleBulkDeleteBtn();
    });

    $('.row-checkbox').change(function() {
        if (!$(this).prop('checked')) {
            $('#selectAll').prop('checked', false);
        }
        if ($('.row-checkbox:checked').length === $('.row-checkbox').length) {
            $('#selectAll').prop('checked', true);
        }
        toggleBulkDeleteBtn();
    });

    function toggleBulkDeleteBtn() {
        if ($('.row-checkbox:checked').length > 0) {
            $('#btn-bulk-delete').prop('disabled', false);
        } else {
            $('#btn-bulk-delete').prop('disabled', true);
        }
    }

    // Bulk Delete Action
    $('#btn-bulk-delete').click(function() {
        if(confirm('Are you sure you want to delete all selected notifications?')) {
            $('#bulk-delete-form').submit();
        }
    });

    // Single Delete Action via Bulk Form
    $('.single-delete').click(function() {
        if(confirm('Are you sure you want to delete this notification?')) {
            // Uncheck all first
            $('.row-checkbox').prop('checked', false);
            // Check only this one
            let id = $(this).data('id');
            $('.row-checkbox[value="' + id + '"]').prop('checked', true);
            // Submit form
            $('#bulk-delete-form').submit();
        }
    });
});
</script>
@endsection
