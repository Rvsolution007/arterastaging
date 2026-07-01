import re

filepath = r"c:\xampp\htdocs\Artera\resources\views\business_frame\index.blade.php"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

# 1. Expand table layout by removing the col-md-4 form entirely and renaming col-md-8
pattern_col4 = r'<div class="col-md-4">\s*<div class="cf-panel">\s*<div class="cf-panel-header">\s*<div class="cf-panel-icon rose"><i class="fa-solid fa-file-zipper"></i></div>\s*<h5 class="cf-panel-title">Upload Frame Template</h5>\s*</div>\s*<div class="cf-panel-body">\s*<form action="{{ url\(\'admin/business-custom-frame-zip\'\) }}" method="POST" enctype="multipart/form-data">.*?</form>\s*</div>\s*</div>\s*</div>\s*<div class="col-md-8">'
content = re.sub(pattern_col4, '<div class="col-md-12">', content, flags=re.DOTALL)

# 2. Add header with filter and upload button
old_header = r'''<div class="cf-panel-header">
                                <div class="cf-panel-icon blue"><i class="fa-solid fa-box-archive"></i></div>
                                <h5 class="cf-panel-title">Uploaded Custom Posts</h5>
                            </div>'''
new_header = r'''<div class="cf-panel-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <div style="display: flex; align-items: center;">
        <div class="cf-panel-icon blue"><i class="fa-solid fa-box-archive"></i></div>
        <h5 class="cf-panel-title">Uploaded Custom Posts</h5>
    </div>
    <div style="display: flex; align-items: center; gap: 10px;">
        <form method="GET" action="" style="display: flex; gap: 5px; margin: 0;">
            <select name="filter_purpose_id" class="form-control" style="border-radius: 8px; width: 200px; height: 38px;" onchange="this.form.submit()">
                <option value="">All Purposes</option>
                @foreach($purposes as $p)
                    <option value="{{$p->id}}" {{ request('filter_purpose_id') == $p->id ? 'selected' : '' }}>{{$p->name}}</option>
                @endforeach
            </select>
        </form>
        <button type="button" class="cf-btn-primary" data-toggle="modal" data-target="#uploadZipModal" style="border-radius: 8px; padding: 8px 16px;"><i class="fa-solid fa-upload"></i> Upload Template</button>
    </div>
</div>'''
# Using string replace instead of re to avoid backslash escaping issues
content = content.replace(old_header, new_header)

# 3. Add WebP preview support
old_preview = r'''@php
                                            $zipFolder = str_replace('.zip', '', $frame->zip_file_path);
                                            $previewUrl = (App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') 
                                                ? Storage::disk('spaces')->url('uploads/template/'.$zipFolder.'/preview.jpg') 
                                                : asset('uploads/template/'.$zipFolder.'/preview.jpg');
                                        @endphp'''
new_preview = r'''@php
                                            $zipFolder = str_replace('.zip', '', $frame->zip_file_path);
                                            $previewUrl = asset('assets/images/placeholder.png'); // Default fallback
                                            
                                            if (App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                                                if (Storage::disk('spaces')->exists('uploads/template/'.$zipFolder.'/preview.webp')) {
                                                    $previewUrl = Storage::disk('spaces')->url('uploads/template/'.$zipFolder.'/preview.webp');
                                                } elseif (Storage::disk('spaces')->exists('uploads/template/'.$zipFolder.'/preview.jpg')) {
                                                    $previewUrl = Storage::disk('spaces')->url('uploads/template/'.$zipFolder.'/preview.jpg');
                                                } elseif (Storage::disk('spaces')->exists('uploads/template/'.$zipFolder.'/preview.png')) {
                                                    $previewUrl = Storage::disk('spaces')->url('uploads/template/'.$zipFolder.'/preview.png');
                                                } else {
                                                    $previewUrl = Storage::disk('spaces')->url('uploads/template/'.$zipFolder.'/preview.jpg');
                                                }
                                            } else {
                                                $localDir = public_path('uploads/template/'.$zipFolder.'/');
                                                if (file_exists($localDir . 'preview.webp')) {
                                                    $previewUrl = asset('uploads/template/'.$zipFolder.'/preview.webp');
                                                } elseif (file_exists($localDir . 'preview.jpg')) {
                                                    $previewUrl = asset('uploads/template/'.$zipFolder.'/preview.jpg');
                                                } elseif (file_exists($localDir . 'preview.png')) {
                                                    $previewUrl = asset('uploads/template/'.$zipFolder.'/preview.png');
                                                } else {
                                                    $files = glob($localDir . '*.{webp,jpg,jpeg,png}', GLOB_BRACE);
                                                    if (!empty($files)) {
                                                        $previewUrl = asset('uploads/template/'.$zipFolder.'/'.basename($files[0]));
                                                    } else {
                                                        $previewUrl = asset('uploads/template/'.$zipFolder.'/preview.jpg');
                                                    }
                                                }
                                            }
                                        @endphp'''
content = content.replace(old_preview, new_preview)

# 4. Add Tags
old_sys_path = r'''<div style="font-size: 12px; color: #6b7280;">Original: <strong style="color: #111;">{{ $frame->original_zip_name ?? 'N/A' }}</strong></div>
                                                    <div class="font-math" style="font-size: 11px; word-break: break-all; max-width: 250px;">System: {{ $frame->zip_file_path }}</div>'''
new_sys_path = r'''<div style="font-size: 12px; color: #6b7280;">Original: <strong style="color: #111;">{{ $frame->original_zip_name ?? 'N/A' }}</strong></div>
                                                    <div class="font-math" style="font-size: 11px; word-break: break-all; max-width: 250px;">System: {{ $frame->zip_file_path }}</div>
                                                    @if(is_array($frame->tags) && count($frame->tags) > 0)
                                                    <div class="mt-1" style="display:flex; flex-wrap:wrap; gap:4px;">
                                                        @foreach($frame->tags as $tag)
                                                            <span class="badge badge-info" style="font-size: 10px; background-color: #e0f2fe; color: #0284c7;">{{ $tag }}</span>
                                                        @endforeach
                                                    </div>
                                                    @endif'''
content = content.replace(old_sys_path, new_sys_path)

# 5. Action Edit button
old_action = r'''<form action="{{ url('admin/business-custom-frame-zip/'.$frame->id) }}" method="POST" style="display:inline-block">'''
new_action = r'''<button type="button" class="cf-btn-primary" onclick="openEditZipModal({{ $frame->id }}, {{ $frame->custom_frame_purpose_id }}, {{ $frame->custom_frame_image_type_id }}, '{{ addslashes(json_encode($frame->tags ?? [])) }}')" style="background-color: #6366f1; padding: 6px 12px; margin-right: 5px;"><i class="fa-solid fa-edit"></i></button>
                                                <form action="{{ url('admin/business-custom-frame-zip/'.$frame->id) }}" method="POST" style="display:inline-block">'''
content = content.replace(old_action, new_action)

# 6. Pagination Footer
old_footer = r'''</table>
                            </div>
                        </div>
                    </div>'''
new_footer = r'''</table>
                            </div>
                            <div class="cf-panel-footer mt-3" style="display: flex; justify-content: flex-end; padding: 15px;">
                                {{ $business_custom_frames->appends(request()->query())->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    </div>'''
content = content.replace(old_footer, new_footer, 1)

# 7. Modals
modals = r'''
<!-- Upload Custom Post Modal -->
<div id="uploadZipModal" class="modal fade" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
      <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; border-radius: 12px 12px 0 0;">
        <h4 class="modal-title" style="font-weight: 600; color: #1e293b;">Upload Frame Template</h4>
        <button type="button" class="close" data-dismiss="modal" style="color: #64748b;">&times;</button>
      </div>
      <form action="{{ url('admin/business-custom-frame-zip') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body" style="padding: 20px;">
            <div class="form-group mb-3">
                <label style="font-weight: 600; color: #475569; font-size: 13px;">Linked Purpose <span class="text-danger">*</span></label>
                <select name="custom_frame_purpose_id" class="form-control" style="border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: none;" required>
                    <option value="">Select Purpose</option>
                    @foreach($purposes as $p)
                        <option value="{{$p->id}}">{{$p->name}}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="form-group mb-3">
                <label style="font-weight: 600; color: #475569; font-size: 13px;">Linked Image Type <span class="text-danger">*</span></label>
                <select name="custom_frame_image_type_id" class="form-control" style="border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: none;" required>
                    <option value="">Select Image Type</option>
                    @foreach($image_types as $it)
                        <option value="{{$it->id}}">{{$it->name}}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="form-group mb-3">
                <label style="font-weight: 600; color: #475569; font-size: 13px;">Template Tags (Optional)</label>
                <select name="tags[]" class="form-control select2" multiple="multiple" style="width: 100%;" data-placeholder="Select tags (e.g. {col_is_category})">
                    @foreach($dynamic_tags as $tag)
                        <option value="{{$tag}}">{{$tag}}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group mb-3">
                <label style="font-weight: 600; color: #475569; font-size: 13px;">Template Zip File <span class="text-danger">*</span></label>
                <div class="cf-file-upload" id="zipUploadArea">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <p id="zipUploadText">Click or drag ZIP file(s) here</p>
                    <input type="file" name="zip_file[]" id="zipFileInput" accept=".zip" multiple required>
                </div>
            </div>
        </div>
        <div class="modal-footer" style="border-top: 1px solid #e2e8f0; padding: 15px 20px;">
            <button type="submit" class="cf-btn-primary w-100 justify-content-center" style="border-radius: 8px; padding: 10px;"><i class="fa-solid fa-upload"></i> Process & Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Custom Post Modal -->
<div id="editZipModal" class="modal fade" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
      <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; border-radius: 12px 12px 0 0;">
        <h4 class="modal-title" style="font-weight: 600; color: #1e293b;">Edit Custom Post Template</h4>
        <button type="button" class="close" data-dismiss="modal" style="color: #64748b;">&times;</button>
      </div>
      <form id="editZipForm" method="POST" action="">
        @csrf
        @method('PUT')
        <div class="modal-body" style="padding: 20px;">
            <div class="form-group mb-3">
                <label style="font-weight: 600; color: #475569; font-size: 13px;">Purpose <span class="text-danger">*</span></label>
                <select name="custom_frame_purpose_id" id="edit_purpose" class="form-control" style="border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: none;" required>
                    <option value="">Select Purpose</option>
                    @foreach($purposes as $purpose)
                        <option value="{{ $purpose->id }}">{{ $purpose->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="form-group mb-3">
                <label style="font-weight: 600; color: #475569; font-size: 13px;">Image Type <span class="text-danger">*</span></label>
                <select name="custom_frame_image_type_id" id="edit_image_type" class="form-control" style="border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: none;" required>
                    <option value="">Select Image Type</option>
                    @foreach($image_types as $image_type)
                        <option value="{{ $image_type->id }}">{{ $image_type->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="form-group mb-3">
                <label style="font-weight: 600; color: #475569; font-size: 13px;">Tags (Optional)</label>
                <select name="tags[]" id="edit_tags" class="form-control tag-select" multiple="multiple" style="width: 100%;">
                    @foreach($dynamic_tags as $tag)
                        <option value="{{ $tag }}">{{ $tag }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="modal-footer" style="border-top: 1px solid #e2e8f0; padding: 15px 20px;">
            <button type="submit" class="cf-btn-primary w-100 justify-content-center" style="border-radius: 8px; padding: 10px;"><i class="fa-solid fa-save"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
    function openEditZipModal(id, purpose_id, image_type_id, tagsStr) {
        var form = document.getElementById('editZipForm');
        form.action = '{{ url("admin/business-custom-frame-zip") }}/' + id;
        
        document.getElementById('edit_purpose').value = purpose_id;
        document.getElementById('edit_image_type').value = image_type_id;
        
        var tagsSelect = $('#edit_tags');
        tagsSelect.val(null).trigger('change');
        
        try {
            var tags = JSON.parse(tagsStr);
            if (tags && Array.isArray(tags)) {
                tagsSelect.val(tags).trigger('change');
            }
        } catch (e) {
            console.error("Error parsing tags", e);
        }
        
        $('#editZipModal').modal('show');
    }
</script>
@endsection
'''

if "uploadZipModal" not in content:
    content = content.replace('@endsection', modals)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)
print("Patch applied successfully.")
