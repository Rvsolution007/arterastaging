@extends("layouts.app")

@section('extra_css')
    <style>
        .subcategory-pane {
            border: 1px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
            display: flex;
            flex-direction: column;
            max-height: 520px;
            /* Perfectly sized for ~5 dense rows */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .subcategory-header-sticky {
            padding: 12px 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            z-index: 20;
        }

        .subcategory-scroll-area {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 10px 15px;
            background: #fff;
        }

        .subcategory-scroll-area::-webkit-scrollbar {
            width: 6px;
        }

        .subcategory-scroll-area::-webkit-scrollbar-thumb {
            background: #ddd;
            border-radius: 10px;
        }

        .subcategory-card {
            transition: all 0.3s;
        }

        .subcategory-card .card {
            border: 1px solid #e0e0e0 !important;
            border-radius: 10px !important;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02) !important;
        }

        .subcategory-card:hover .card {
            border-color: #007bff !important;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.08) !important;
        }



        /* Task image upload area */
        .task-upload-zone {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .task-upload-btn {
            width: 60px;
            height: 60px;
            border: 2px dashed #007bff;
            border-radius: 6px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            background: #f0f7ff;
            color: #007bff;
            font-size: 10px;
            font-weight: 600;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .task-upload-btn:hover {
            background: #007bff;
            color: #fff;
        }

        .task-img-thumb {
            width: 60px;
            height: 60px;
            border: 2px solid #28a745;
            border-radius: 6px;
            overflow: hidden;
            position: relative;
            flex-shrink: 0;
        }

        .task-img-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .task-img-thumb .remove-task-img {
            position: absolute;
            top: 0;
            right: 0;
            background: rgba(220, 53, 69, 0.9);
            color: #fff;
            padding: 1px 5px;
            font-size: 10px;
            cursor: pointer;
            border-bottom-left-radius: 4px;
            display: none;
        }

        .task-img-thumb:hover .remove-task-img {
            display: block;
        }


        .category-dropdown-wrapper {
            position: relative;
        }

        .category-dropdown-menu {
            width: 100%;
            padding: 10px;
            max-height: 350px;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            margin-top: 5px;
        }

        .category-search-box {
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 10;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            margin-bottom: 10px;
        }

        .category-list-container {
            max-height: 200px;
            overflow-y: auto;
        }

        .selected-category-chip {
            display: inline-block;
            background: #212529;
            color: #fff;
            padding: 2px 10px;
            border-radius: 4px;
            margin-right: 4px;
            margin-bottom: 2px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        #selectedCategoriesCount {
            display: flex;
            flex-wrap: wrap;
            gap: 2px;
            align-items: center;
        }

        .category-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .category-item:hover {
            background: #f4f8ff;
        }

        .category-item input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .category-item label {
            margin-bottom: 0;
            margin-left: 12px;
            cursor: pointer;
            font-weight: 500;
            color: #444;
            flex: 1;
            user-select: none;
        }

        .subcategory-img-preview.has-image:hover .remove-img {
            display: block;
        }

        .view-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            display: none;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
        }

        .subcategory-img-preview:hover .view-overlay {
            display: flex;
        }

        .sub-images-grid::-webkit-scrollbar {
            height: 4px;
        }

        .sub-images-grid::-webkit-scrollbar-thumb {
            background: #ced4da;
            border-radius: 4px;
        }

        .sub-images-grid {
            padding-bottom: 5px;
        }

        /* Zip Library Styles */
        .zip-item-card {
            border: 2px solid transparent;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
            height: 100%;
        }

        .zip-item-card:hover {
            border-color: #ced4da;
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .zip-item-card.selected {
            border-color: #007bff;
            background: #e7f1ff;
        }

        .zip-icon {
            font-size: 40px;
            color: #ffc107;
            margin-bottom: 5px;
        }

        .zip-name {
            font-size: 12px;
            font-weight: 600;
            color: #333;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            height: 36px;
        }

        /* Layout Optimizations */
        .content-header {
            padding: 8px .5rem !important;
        }

        .content-header h4 {
            font-size: 1.2rem !important;
        }

        .card-header {
            padding: .5rem 1rem !important;
        }

        .card-title {
            font-size: 1.1rem !important;
        }

        .main-card>.card-body {
            padding: 0.8rem 1rem !important;
        }

        hr {
            margin-top: 0.4rem !important;
            margin-bottom: 0.4rem !important;
        }
    </style>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            @if (count($errors) > 0)
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="card card-primary main-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Add General Post</h3>

                </div>
                {!! Form::open(['route' => 'general-post.store', 'method' => 'POST', 'files' => true, 'id' => 'postSubmitForm']) !!}
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-weight: 700; color: #333;">Task Name</label>
                                <input type="text" name="task_name" class="form-control" placeholder="Enter Task Name"
                                    style="height: 45px; border-radius: 8px;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-weight: 700; color: #333;">Post Purpose</label>
                                <select name="post_purpose_id" class="form-control"
                                    style="height: 45px; border-radius: 8px;">
                                    <option value="">Select Purpose (Option)</option>
                                    @foreach($purposes as $purpose)
                                        <option value="{{ $purpose->id }}">{{ $purpose->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 700; color: #333;">Business Category</label>
                        <div class="input-group">
                            <div class="dropdown flex-grow-1" id="categoryDropdownContainer">
                                <button
                                    class="btn btn-outline-secondary dropdown-toggle btn-block text-left d-flex justify-content-between align-items-center"
                                    type="button" id="categorySelectionBtn" data-toggle="dropdown" aria-haspopup="true"
                                    aria-expanded="false"
                                    style="background: #fff; border: 1px solid #ced4da; color: #495057; min-height: 45px; border-radius: 8px 0 0 8px; padding: 5px 12px;">
                                    <span id="selectedCategoriesCount" style="margin-right: 15px;">Select Categories</span>
                                </button>
                                <div class="dropdown-menu category-dropdown-menu" aria-labelledby="categorySelectionBtn">
                                    <div class="category-search-box">
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-search"></i></span>
                                            </div>
                                            <input type="text" id="cat_search_input" class="form-control"
                                                placeholder="Search categories...">
                                        </div>
                                        <div class="mt-2 pl-2">
                                            <div class="checkbox">
                                                <input type="checkbox" id="select_all_categories"
                                                    style="width: 16px; height: 16px; vertical-align: middle;">
                                                <label for="select_all_categories" class="mb-0 ml-1"
                                                    style="font-weight: 700; font-size: 13px; cursor: pointer;">SELECT
                                                    ALL</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="category-list-container">
                                        @foreach($category as $row)
                                            <div class="category-item-wrapper" data-name="{{strtolower($row->name)}}">
                                                <div class="category-item">
                                                    <input type="checkbox" name="business_category_id[]" value="{{$row->id}}"
                                                        class="category-checkbox" id="cat_{{$row->id}}">
                                                    <label for="cat_{{$row->id}}">{{$row->name}}</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="input-group-append">
                                <button type="button" id="btn_done_categories" class="btn btn-info"
                                    style="font-weight: 700; width: 100px; border-radius: 0 8px 8px 0;">
                                    DONE
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="subcategory_grid_wrapper" style="display: none;">
                        <hr>
                        <div class="subcategory-pane">
                            <div class="subcategory-header-sticky">
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <h5 style="font-weight: 600; color: #333; margin-bottom: 0;">
                                            <i class="fa fa-list-alt mr-1"></i> Subcategories
                                        </h5>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="checkbox">
                                            <input type="checkbox" id="select_all_subcategories"
                                                style="width: 16px; height: 16px; vertical-align: middle;">
                                            <label for="select_all_subcategories" class="mb-0 ml-1"
                                                style="font-weight: 600; cursor: pointer;">Select All</label>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-white border-right-0"><i
                                                        class="fa fa-search text-muted"></i></span>
                                            </div>
                                            <input type="text" id="subcategory_search" class="form-control border-left-0"
                                                placeholder="Search subcategories...">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="subcategory-scroll-area">
                                <div class="row" id="subcategory_grid">
                                    <!-- Dynamic Content -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="zip_file_display">Select Zip</label>
                                <div class="input-group">
                                    <input type="text" id="zip_file_display" class="form-control"
                                        placeholder="No ZIP selected" readonly>
                                    <input type="hidden" name="zip_file_id" id="zip_file_id_input">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-info" id="btn_browse_zips">
                                            <i class="fa fa-folder-open mr-1"></i> Browse Library
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ai_content_subject">Add Content Subject for AI</label>
                                <input type="text" name="ai_content_subject" id="ai_content_subject" class="form-control"
                                    placeholder="Enter subject for AI content generation">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer py-2">
                    <button type="submit" id="btnSubmitPost" class="btn btn-primary float-right px-4">Submit</button>
                </div>
                {!! Form::close() !!}
            </div>



            <!-- Zip Library Modal -->
            <div id="zipLibraryModal" class="modal fade" role="dialog">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header d-flex justify-content-between align-items-center">
                            <h4 class="modal-title">Select Zip Library</h4>
                            <div>
                                <button type="button" class="btn btn-success btn-sm mr-2" id="btn_refresh_zips">
                                    <i class="fa fa-sync-alt"></i> Refresh
                                </button>
                                <button type="button" class="btn btn-primary btn-sm" id="btn_modal_upload_zip">
                                    <i class="fa fa-upload"></i> Upload New ZIP
                                </button>
                                <input type="file" id="modal_zip_upload_input" style="display: none;" accept=".zip">
                            </div>
                        </div>
                        <div class="modal-body">
                            <div class="row" id="zip_library_container" style="max-height: 400px; overflow-y: auto;">
                                <!-- Dynamic Content -->
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" id="btn_select_zip">Select</button>
                            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection

    @section('script')
        <script type="text/javascript">
            $(document).ready(function () {
                // $('#business_category_id').select2(); // Removed as category selection is now checkboxes
            });

            // Prevent dropdown from closing when clicking inside
            $(document).on('click', '.category-dropdown-menu', function (e) {
                e.stopPropagation();
            });

            // Search functionality for categories
            $('#cat_search_input').on('keyup', function () {
                var value = $(this).val().toLowerCase();
                $('.category-item-wrapper').filter(function () {
                    $(this).toggle($(this).data('name').indexOf(value) > -1);
                });
            });

            // Update button text on checkbox change
            $(document).on('change', '.category-checkbox, #select_all_categories', function () {
                var selectedNames = [];
                $('.category-checkbox:checked').each(function () {
                    selectedNames.push($(this).next('label').text());
                });

                if (selectedNames.length > 0) {
                    var chipsHtml = '';
                    $.each(selectedNames, function (i, name) {
                        chipsHtml += '<span class="selected-category-chip">' + name + '</span>';
                    });
                    $('#selectedCategoriesCount').html(chipsHtml);
                    $('#categorySelectionBtn').addClass('btn-primary').removeClass('btn-outline-secondary').css('color', '#fff');
                } else {
                    $('#selectedCategoriesCount').text('Select Categories');
                    $('#categorySelectionBtn').addClass('btn-outline-secondary').removeClass('btn-primary').css('color', '#495057');
                }
            });

            $('#select_all_categories').on('change', function () {
                $('.category-checkbox:visible').prop('checked', $(this).is(':checked')).trigger('change');
            });

            $('#btn_done_categories').on('click', function () {
                var ids = [];
                $('.category-checkbox:checked').each(function () {
                    ids.push($(this).val());
                });

                if (ids.length > 0) {
                    var $btn = $(this);
                    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

                    $.ajax({
                        url: "{{ url('admin/get-business-sub-category') }}",
                        type: "GET",
                        data: { id: ids },
                        success: function (data) {
                            $btn.prop('disabled', false).html('DONE');
                            $('#subcategory_grid').empty();
                            if (data.length > 0) {
                                $('#subcategory_grid_wrapper').show();
                                $.each(data, function (key, value) {
                                    var iconHtml = '';
                                    if (value.icon) {
                                        var iconUrl = "{{ App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean' ? \Storage::disk('spaces')->url('uploads/') : asset('uploads/') }}/" + value.icon;
                                        iconHtml = '<img class="rounded-circle mr-2" src="' + iconUrl + '" width="30" height="30" style="object-fit: cover;">';
                                    }


                                    // Upload zone — new images for this task only
                                    var taskUploadArea = '<div class="task-upload-zone" id="task-zone-' + value.id + '">' +
                                        '<div class="task-upload-btn" data-subcat-id="' + value.id + '" title="Add image(s) for this task">' +
                                        '<i class="fa fa-plus" style="font-size: 18px;"></i>' +
                                        '<span style="font-size: 9px; margin-top: 3px;">Add Image</span>' +
                                        '</div>' +
                                        '<div class="task-thumbs-container d-flex" style="gap:4px; flex-wrap: nowrap;"></div>' +
                                        '</div>';

                                    var block = '<div class="col-12 mb-2 subcategory-card">' +
                                        '<div class="card shadow-none" style="margin-bottom: 0; background: #fcfcfc;">' +
                                        '<div class="card-body p-2">' +
                                        '<div class="row align-items-center">' +
                                        '<div class="col-md-4 d-flex align-items-center">' +
                                        '<input type="checkbox" name="business_sub_category_id[]" value="' + value.id + '" class="sub_category_checkbox mr-3" style="width: 18px; height: 18px; cursor: pointer;">' +
                                        iconHtml +
                                        '<div style="line-height: 1.1;">' +
                                        '<span class="sub-name" style="font-size: 14px; font-weight: 600; color: #444;">' + value.name + '</span>' +
                                        '</div>' +
                                        '</div>' +
                                        '<div class="col-md-8">' +
                                        '<div class="sub-images-grid d-flex align-items-center" style="overflow-x: auto; flex-wrap: nowrap; gap: 6px; padding: 0;">' +
                                        taskUploadArea +
                                        '</div>' +
                                        '</div>' +
                                        '</div>' +
                                        '<input type="file" class="task-image-file-input" accept="image/*" multiple data-subcat-id="' + value.id + '" style="display: none;">' +
                                        '</div>' +
                                        '</div>' +
                                        '</div>';
                                    $('#subcategory_grid').append(block);
                                });

                                // Scroll to grid
                                $('html, body').animate({
                                    scrollTop: $("#subcategory_grid_wrapper").offset().top - 100
                                }, 500);
                            } else {
                                $('#subcategory_grid_wrapper').hide();
                                PNotify.error({ title: 'No Subcategories', text: 'No subcategories found for selected categories.' });
                            }
                        },
                        error: function () {
                            $btn.prop('disabled', false).html('DONE');
                        }
                    });
                } else {
                    PNotify.notice({ title: 'Attention', text: 'Please select at least one category.' });
                    $('#subcategory_grid_wrapper').hide();
                    $('#subcategory_grid').empty();
                }
            });

            $(document).on('keyup', '#subcategory_search', function () {
                var value = $(this).val().toLowerCase();
                $('.subcategory-card').filter(function () {
                    $(this).toggle($(this).find('.sub-name').text().toLowerCase().indexOf(value) > -1)
                });
            });

            // Click the task-upload-btn => trigger its hidden file input
            $(document).on('click', '.task-upload-btn', function () {
                var subcatId = $(this).data('subcat-id');
                $(this).closest('.card-body').find('.task-image-file-input[data-subcat-id="' + subcatId + '"]').click();
            });

            $(document).on('change', '#select_all_subcategories', function () {
                $('.sub_category_checkbox:visible').prop('checked', $(this).prop('checked'));
            });

            // Task-specific image upload: preview locally, attach to form on submit
            // We store a DataTransfer per subcategory to accumulate files
            var taskImageStore = {}; // { subcatId: DataTransfer }

            $(document).on('change', '.task-image-file-input', function () {
                var input = this;
                var subcatId = $(this).data('subcat-id');
                var files = input.files;
                if (!files.length) return;

                // Initialize DataTransfer for this subcategory
                if (!taskImageStore[subcatId]) {
                    taskImageStore[subcatId] = new DataTransfer();
                }

                var thumbsContainer = $('#task-zone-' + subcatId).find('.task-thumbs-container');
                var numAdded = files.length;

                for (var i = 0; i < files.length; i++) {
                    (function(file, sId) {
                        taskImageStore[sId].items.add(file);
                        var fileIndex = taskImageStore[sId].files.length - 1;

                        var reader = new FileReader();
                        reader.onload = function(e) {
                            var thumb = $('<div class="task-img-thumb" data-subcat-id="' + sId + '" data-file-index="' + fileIndex + '">' +
                                '<img src="' + e.target.result + '">' +
                                '<span class="remove-task-img" title="Remove"><i class="fa fa-times"></i></span>' +
                                '</div>');
                            thumbsContainer.append(thumb);
                        };
                        reader.readAsDataURL(file);
                    })(files[i], subcatId);
                }

                // Reset the file input so user can re-select same files
                $(this).val('');

                console.log("Subcategory " + subcatId + " now has " + taskImageStore[subcatId].files.length + " files queued.");

                new PNotify({
                    title: 'Images Added',
                    text: numAdded + ' image(s) queued for this task.',
                    type: 'success'
                });
            });

            // Remove a task-specific image preview
            $(document).on('click', '.remove-task-img', function (e) {
                e.stopPropagation();
                var thumb = $(this).closest('.task-img-thumb');
                var subcatId = thumb.data('subcat-id');
                var fileIndex = thumb.data('file-index');

                // Rebuild DataTransfer without this file
                if (taskImageStore[subcatId]) {
                    var newDT = new DataTransfer();
                    var oldFiles = taskImageStore[subcatId].files;
                    for (var i = 0; i < oldFiles.length; i++) {
                        if (i !== fileIndex) newDT.items.add(oldFiles[i]);
                    }
                    taskImageStore[subcatId] = newDT;

                    // Re-index remaining thumbs for this subcategory
                    var newIdx = 0;
                    thumb.closest('.task-thumbs-container').find('.task-img-thumb').each(function() {
                        if (!$(this).is(thumb)) {
                            $(this).data('file-index', newIdx).attr('data-file-index', newIdx);
                            newIdx++;
                        }
                    });
                }

                thumb.remove();
            });

            // Submit form via AJAX with FormData so task images are reliably included
            $('#postSubmitForm').on('submit', function (e) {
                e.preventDefault();

                var form     = $(this);
                var formEl   = this;
                var actionUrl = form.attr('action');
                var btn = $('#btnSubmitPost');
                btn.attr('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Generating Posts, please wait...');

                // Build FormData from the form (captures all regular fields + CSRF token)
                var formData = new FormData(formEl);

                // Append task-specific images from each subcategory's DataTransfer store
                var subcatCount = 0;
                var totalFiles = 0;
                $.each(taskImageStore, function(subcatId, dt) {
                    if (dt.files.length > 0) {
                        subcatCount++;
                        for (var i = 0; i < dt.files.length; i++) {
                            formData.append('task_images[' + subcatId + '][]', dt.files[i]);
                            totalFiles++;
                        }
                    }
                });

                console.log("Submitting form with " + totalFiles + " task images across " + subcatCount + " subcategories.");
                // Diagnostic: Log all FormData keys
                for (var key of formData.keys()) {
                   console.log("FormData Key: " + key);
                }

                $.ajax({
                    url: actionUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        var notifyType = response.warning ? 'notice' : 'success';
                        var msg = response.message || 'Posts created successfully.';
                        new PNotify({ title: response.warning ? 'Partial Success' : 'Done!', text: msg, type: notifyType });
                        setTimeout(function() {
                            window.location.href = "{{ route('general-post.index') }}";
                        }, 2000);
                    },
                    error: function (xhr) {
                        btn.attr('disabled', false).html('Submit');
                        var msg = 'An error occurred. Please try again.';
                        try {
                            var resp = JSON.parse(xhr.responseText);
                            if (resp.message) msg = resp.message;
                        } catch(ex) {}
                        new PNotify({ title: 'Error', text: msg, type: 'error' });
                    }
                });
            });


            // Zip Library Logic
            $('#btn_browse_zips').on('click', function () {
                loadZipLibrary();
                $('#zipLibraryModal').modal('show');
            });

            function loadZipLibrary() {
                $('#zip_library_container').html('<div class="col-12 text-center p-5"><i class="fa fa-spinner fa-spin fa-2x"></i><p class="mt-2 text-muted">Loading zip library...</p></div>');
                $.ajax({
                    url: "{{ url('admin/get-zip-library') }}",
                    type: "GET",
                    success: function (data) {
                        var html = '';
                        if (data.length > 0) {
                            $.each(data, function (key, value) {
                                html += '<div class="col-md-3 col-sm-4 mb-3">' +
                                    '<div class="zip-item-card" data-id="' + value.id + '" data-name="' + value.file_name + '">' +
                                    '<div class="zip-icon"><i class="fa fa-file-archive"></i></div>' +
                                    '<div class="zip-name" title="' + value.file_name + '">' + value.file_name + '</div>' +
                                    '</div>' +
                                    '</div>';
                            });
                        } else {
                            html = '<div class="col-12 text-center p-5"><p class="text-muted">No ZIP files found in library.</p></div>';
                        }
                        $('#zip_library_container').html(html);
                    }
                });
            }

            $('#btn_refresh_zips').on('click', function () {
                loadZipLibrary();
            });

            $(document).on('click', '.zip-item-card', function () {
                $('.zip-item-card').removeClass('selected text-primary').css('border-color', 'transparent');
                $(this).addClass('selected text-primary').css('border-color', '#007bff');
            });

            $('#btn_select_zip').on('click', function () {
                var selected = $('.zip-item-card.selected');
                if (selected.length > 0) {
                    $('#zip_file_id_input').val(selected.data('id'));
                    $('#zip_file_display').val(selected.data('name'));
                    $('#zipLibraryModal').modal('hide');
                } else {
                    alert('Please select a ZIP file.');
                }
            });

            // Modal direct upload
            $('#btn_modal_upload_zip').on('click', function () {
                $('#modal_zip_upload_input').click();
            });

            $('#modal_zip_upload_input').on('change', function () {
                var file = this.files[0];
                if (!file) return;

                var formData = new FormData();
                formData.append('zip_file', file);

                $('#zip_library_container').html('<div class="col-12 text-center p-5"><i class="fa fa-spinner fa-spin fa-2x"></i><p class="mt-2">Uploading and processing ZIP...</p></div>');

                $.ajax({
                    url: "{{ url('admin/ajax-zip-upload') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (data) {
                        if (data.success) {
                            new PNotify({
                                title: 'Success!',
                                text: data.message,
                                type: 'success'
                            });
                            loadZipLibrary(); // Refresh grid
                        } else {
                            alert(data.message);
                            loadZipLibrary();
                        }
                    },
                    error: function () {
                        alert('Failed to upload ZIP.');
                        loadZipLibrary();
                    }
                });
            });
        </script>
    @endsection