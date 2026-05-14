@extends("layouts.app")

@section('extra_css')
    <link href="{{ asset('assets/css/frame.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/clean-switch.css')}}">
    <style>
        .ui-switcher {
            background-color: #bdc1c2;
            display: inline-block;
            top: 7px;
            height: 25px;
            width: 70px;
            border-radius: 15px;
            box-sizing: border-box;
            vertical-align: middle;
            position: relative;
            cursor: pointer;
            transition: border-color 0.25s;
            box-shadow: inset 1px 1px 1px rgba(0, 0, 0, 0.15);
        }

        .ui-switcher:before {
            font-family: sans-serif;
            font-size: 13px;
            font-weight: 400;
            color: #ffffff;
            line-height: 1;
            display: inline-block;
            position: absolute;
            top: 6px;
            height: 15px;
            width: 27px;
            text-align: center;
        }

        .ui-switcher[aria-checked=false]:before {
            content: 'Free';
            right: 10px;
        }

        .ui-switcher[aria-checked=true]:before {
            content: 'Paid';
            left: 10px;
        }

        .ui-switcher[aria-checked=true] {
            background-color: #e91e63;
        }

        .ui-switcher:after {
            background-color: #ffffff;
            content: '\0020';
            display: inline-block;
            position: absolute;
            top: 2px;
            height: 20px;
            width: 20px;
            border-radius: 50%;
            transition: left 0.25s;
        }

        .ui-switcher[aria-checked=false]:after {
            left: 5px;
        }

        .ui-switcher[aria-checked=true]:after {
            left: 45px;
        }

        .dropbtn {
            color: white;
            font-size: 16px;
            border: none;
            cursor: pointer;
        }

        #myInput {
            box-sizing: border-box;
            background-image: url('{{asset("searchicon.png")}}');
            background-position: 14px 12px;
            background-repeat: no-repeat;
            font-size: 16px;
            padding: 14px 20px 12px 10px;
            border: none;
            width: 100%;
            border-bottom: 1px solid #056fed;
        }

        #myInput:focus {
            outline: 1px solid #056fed;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .text-element {
            display: flex;
            /* Justification handled dynamically in JS */
            white-space: pre-wrap;
            overflow-wrap: break-word;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f6f6f6;
            min-width: 20px;
            overflow: auto;
            padding: 0 0;
            border: 1px solid #ddd;
            z-index: 1;
        }

        .dropdown-content a {
            color: black;
            padding: 7px 7px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #056fed;
            color: white;
        }

        .show {
            display: block;
        }

        .select2-container--default .select2-selection--single {
            background-color: #007bff;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: white;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: white transparent transparent transparent;
        }

        .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent white transparent;
        }
    </style>
@endsection

@section('content')
    <div class="row" style="margin-right: 0px;">
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
            @if (Session::has('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>Success!</strong> {{ Session::get('success') }}
                </div>
            @endif
            @if (Session::has('warning'))
                <div class="alert alert-warning alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>Warning!</strong> {{ Session::get('warning') }}
                </div>
            @endif
            @if (Session::has('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>Error!</strong> {{ Session::get('error') }}
                </div>
            @endif
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        General Posts
                    </h3>
                </div>

                <div class="card-body">
                    <div style="float: left;" class="d-flex">
                        <select class="form-control" id="business_category_dropdown" name="business_category_dropdown"
                            onchange="location = this.value;">
                            <option value="{{url('admin/general-post')}}" @if(empty($name)) selected @endif>Select Business
                                Category</option>
                            @foreach($category as $c)
                                <option value="{{url('admin/business-category-get-general/' . $c->id)}}" @if(!empty($name) && $name == $c->name) selected @endif>{{$c->name}}</option>
                            @endforeach
                        </select>
                        <a href="{{ route('zip-file-manager.index')}}" class="btn btn-primary ml-2"
                            style="white-space: nowrap;">Zip File manager</a>
                        <a href="{{ route('post-purpose.index') }}" class="btn btn-info ml-2"
                            style="white-space: nowrap;">Manage Post Purposes</a>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                    Bulk Actions <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" data-type="enable" data-toggle="modal"
                                            data-target="#enableModal">Enable</a></li>
                                    <li><a class="dropdown-item" href="#" data-type="disable" data-toggle="modal"
                                            data-target="#disableModal">Disable</a></li>
                                    <li><a class="dropdown-item" href="#" data-type="delete" data-toggle="modal"
                                            data-target="#deleteModal">Delete</a></li>
                                </ul>
                                {!! Form::open(['url' => 'admin/general-post-action', 'method' => 'POST', 'id' => 'form1']) !!}
                                <input type="hidden" name="select_post" value="">
                                <input type="hidden" name="action_type" value="">
                                {!! Form::close() !!}
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="{{ route('general-post.create')}}" class="btn btn-success">
                                <i class="fa fa-plus mr-1"></i> Add New Post
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive" id="post_table_wrapper">
                        <table class="table table-hover table-striped">
                            <thead class="bg-light">
                                <tr>
                                    <th width="30"><input type="checkbox" id="checkall" style="width: 16px; height: 16px;">
                                    </th>
                                    <th>ID</th>
                                    <th>Category</th>
                                    <th>Zip Name</th>
                                    <th>Task Name</th>
                                    <th>Purpose</th>
                                    <th>Process Status</th>
                                    <th>Preview</th>
                                    <th>Date</th>
                                    <th width="80">Status</th>
                                    <th width="80">Type</th>
                                    <th width="100">Action</th>
                                </tr>
                            </thead>
                            <tbody id="frame_data">
                                @forelse($data as $frame)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="post_ids[]" value="{{$frame->id}}" class="post_ids"
                                                style="width: 16px; height: 16px;">
                                        </td>
                                        <td>
                                            <span class="badge badge-dark">{{$frame->id}}</span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge badge-info">{{$frame->business_category->name ?? 'General'}}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{$frame->zip_name ?? '-'}}</small>
                                        </td>
                                        <td>
                                            <span style="color: #6610f2; font-weight: 600;">{{$frame->task_name ?? '-'}}</span>
                                        </td>
                                        <td>
                                            @if($frame->post_purpose)
                                                <span class="badge badge-primary" style="background-color: #6610f2; padding: 5px 10px;">
                                                    {{$frame->post_purpose->name}}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>



                                        <td>
                                            @if($frame->process_status == 'success')
                                                <span class="badge badge-success px-2 py-1"><i class="fa fa-check-circle mr-1"></i>
                                                    Success</span>
                                            @elseif($frame->process_status == 'pending')
                                                <span class="badge badge-warning px-2 py-1 text-white"><i
                                                        class="fa fa-clock-o mr-1"></i> Pending</span>
                                            @else
                                                <span class="badge badge-danger px-2 py-1" title="{{$frame->failure_reason}}"
                                                    data-toggle="tooltip">
                                                    <i class="fa fa-times-circle mr-1"></i> Failed
                                                </span>
                                                <br><small class="text-danger"
                                                    style="font-size: 10.5px; display:inline-block; max-width: 160px; line-height:1.2; margin-top:4px;">{{Str::limit($frame->failure_reason ?? 'Unknown Error', 50)}}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                // Priority: Subcategory image (per user request to match client editor), then frame_image
                                                $previewImg = null;
                                                if ($frame->business_sub_category && ($frame->business_sub_category->image_1 ?: $frame->business_sub_category->image_2)) {
                                                    $previewImg = $frame->business_sub_category->image_1 ?: $frame->business_sub_category->image_2;
                                                } elseif ($frame->product && $frame->product->image) {
                                                    $previewImg = $frame->product->image;
                                                } else {
                                                    $previewImg = $frame->frame_image;
                                                }
                                                
                                                $isAiPost = ($frame->ai_generated_content && $frame->process_status == 'success' && $frame->frame_image);
                                                
                                                $imgUrl = '';
                                                $aiData = null;
                                                $previewText = '';

                                                if($previewImg) {
                                                    if(\App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                                                        $imgUrl = \Storage::disk('spaces')->url('uploads/' . $previewImg);
                                                    } else {
                                                        $imgUrl = asset('uploads/' . $previewImg);
                                                    }
                                                }

                                                if($frame->ai_generated_content) {
                                                    $aiData = json_decode($frame->ai_generated_content, true);
                                                    $previewText = $aiData['heading_main'] ?? ($aiData['list_heading'] ?? ($aiData['heading_script'] ?? ''));
                                                    if (is_array($previewText)) $previewText = implode(' ', $previewText);
                                                    $previewText = str_replace(['\n', "\n"], ' ', $previewText);
                                                }
                                            @endphp
                                            
                                            @if($previewImg)
                                            @php
                                                $templateConfig = null;
                                                if($frame->ai_generated_content) {
                                                    $templateConfig = $frame->getTemplateConfig();
                                                }
                                            @endphp
                                            <a href="javascript:void(0)" 
                                               onclick="openGenPostPreviewModal('{{ $imgUrl }}', {{ json_encode($aiData) }}, {{ json_encode($templateConfig) }})" 
                                               title="Click to View Full Preview">
                                                <div class="gen-post-preview-container-root" style="position: relative; width: 60px; height: 60px; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                                    <img src="{{ $imgUrl }}" id="thumb-base-{{ $frame->id }}"
                                                        style="width: 100%; height: 100%; object-fit: cover;"
                                                        onerror="this.onerror=null; this.src='{{asset('assets/images/placeholder-frame.png')}}';">
                                                    
                                                    @if($isAiPost)
                                                        <div class="ai-thumbnail-generator" 
                                                             data-img-url="{{ $imgUrl }}"
                                                             data-template-config="{{ json_encode($templateConfig) }}"
                                                             style="position: absolute; inset: 0; pointer-events: none;">
                                                        </div>
                                                     @endif
                                                </div>
                                            </a>
                                            @else
                                            <div style="position: relative; width: 60px; height: 60px; border-radius: 4px; overflow: hidden; border: 1px solid #ddd; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                                <span class="text-muted" style="font-size: 9px;">No Image</span>
                                            </div>
                                            @endif
                                        </td>
                                        <td>
                                            <small>{{$frame->created_at->format('d M, Y')}}<br>{{$frame->created_at->format('h:i A')}}</small>
                                        </td>
                                        <td>
                                            <label class="cl-switch cl-switch-red mb-0">
                                                <input type="checkbox" class="frame-switch" data-id="{{$frame->id}}" value="1"
                                                    @if($frame->status == 1) checked @endif>
                                                <span class="switcher"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input checkbox2" type="checkbox"
                                                    data-id="{{$frame->id}}" value="1" @if($frame->paid == 1) checked @endif>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{url('admin/general-post/' . $frame->id . '/edit')}}"
                                                    class="btn btn-sm btn-info mr-1" data-toggle="tooltip" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <button type="button" data-id="{{$frame->id}}"
                                                    class="btn btn-sm btn-danger btn_delete_a" data-toggle="modal"
                                                    data-target="#myModal">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                            {!! Form::open(['url' => 'admin/general-post/' . $frame->id, 'method' => 'DELETE', 'class' => 'd-none', 'id' => 'form_' . $frame->id]) !!}
                                            {!! Form::hidden("id", $frame->id) !!}
                                            {!! Form::close() !!}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4">No data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center">{{ $data->links() }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Delete</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to Delete ?</p>
                </div>
                <div class="modal-footer">
                    @if(optional(Auth::user())->user_type == "Demo")
                        <button type="button" class="btn btn-danger ToastrButton">Delete</button>
                    @else
                        <button id="del_btn" class="btn btn-danger" type="button" data-submit="">Delete</button>
                    @endif
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->

    <!-- enableModal -->
    <div id="enableModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Enable</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Do you really want to perform?</p>
                </div>
                <div class="modal-footer">
                    @if(optional(Auth::user())->user_type == "Demo")
                        <button type="button" class="btn btn-danger ToastrButton">Yes</button>
                    @else
                        <button id="enable_btn" class="btn btn-danger" type="button">Yes</button>
                    @endif
                    <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
                </div>
            </div>
        </div>
    </div>
    <!-- enableModal -->

    <!-- disableModal -->
    <div id="disableModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Disable</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Do you really want to perform?</p>
                </div>
                <div class="modal-footer">
                    @if(optional(Auth::user())->user_type == "Demo")
                        <button type="button" class="btn btn-danger ToastrButton">Yes</button>
                    @else
                        <button id="disable_btn" class="btn btn-danger" type="button">Yes</button>
                    @endif
                    <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
                </div>
            </div>
        </div>
    </div>
    <!-- disableModal -->

    <!-- deleteModal -->
    <div id="deleteModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Delete</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Do you really want to perform?</p>
                </div>
                <div class="modal-footer">
                    @if(optional(Auth::user())->user_type == "Demo")
                        <button type="button" class="btn btn-danger ToastrButton">Yes</button>
                    @else
                        <button id="delete_btn" class="btn btn-danger" type="button">Yes</button>
                    @endif
                    <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
                </div>
            </div>
        </div>
    </div>
    <!-- deleteModal -->

    <!-- High-Fidelity AI Post Preview Modal -->
    <div id="genPostPreviewModalFinal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 9999;">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 12px; overflow: hidden; border: none; background: #f8fafc;">
                <div class="modal-header" style="background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 15px 20px;">
                    <h4 class="modal-title" style="font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                        <i class="fa fa-magic text-primary"></i> AI Post Preview
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" style="font-size: 24px;">&times;</button>
                </div>
                <div class="modal-body" style="padding: 20px; display: flex; justify-content: center; background: #f1f5f9;">
                    <div id="genPostPreviewContainer" style="position: relative; background: #fff; box-shadow: 0 10px 40px -10px rgba(0,0,0,0.2); border-radius: 8px; overflow: hidden; width: 100%; max-width: 600px; aspect-ratio: 1/1;">
                        <img id="genPostPreviewImage" src="" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block;">
                        
                        <!-- Dynamic Overlay Layer -->
                        <div id="genPostAdvancedOverlay" style="position: absolute; inset: 0; pointer-events: none; display: none; width: 100%; height: 100%;">
                            <!-- Template layers will be injected here -->
                        </div>

                        <!-- Basic Fallback Overlay -->
                        <div id="genPostAiOverlay" style="position: absolute; inset: 0; padding: 40px; display: none; flex-direction: column; justify-content: center; align-items: center; text-align: center; background: rgba(0,0,0,0.15); pointer-events: none;">
                            <h2 id="genPostAiHeading" style="color: #ffffff; font-size: 32px; font-weight: 900; text-shadow: 0 2px 10px rgba(0,0,0,0.5); line-height: 1.2; margin-bottom: 15px; width: 100%;"></h2>
                            <p id="genPostAiSubheading" style="color: #ffffff; font-size: 18px; font-weight: 700; text-shadow: 0 1px 5px rgba(0,0,0,0.5); width: 100%;"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background: #ffffff; border-top: 1px solid #e2e8f0; padding: 15px 20px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal" style="font-weight: 600; padding: 8px 20px; border-radius: 6px;">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Preview Modal -->
@endsection

@section('script')
    <script src="{{ asset('assets/js/jquery.switcher.js')}}"></script>
    <script type="text/javascript">
        var checkarray = [];
        $("#checkall").click(function () {
            checkarray = [];
            $("input[name='post_ids[]']").not(this).prop('checked', this.checked);
            $.each($("input[name='post_ids[]']:checked"), function () {
                checkarray.push($(this).val());
            });
            $("input[name='select_post']").val(checkarray);
        });

        $(".post_ids").click(function (e) {
            if ($(this).prop("checked") == true) {
                checkarray.push($(this).val());
            } else if ($(this).prop("checked") == false) {
                checkarray.splice($.inArray($(this).val(), checkarray), 1);
            }
            $("input[name='select_post']").val(checkarray);
        });

        $("#enable_btn").on("click", function () {
            $("#form1").submit();
        });

        $('#enableModal').on('show.bs.modal', function (e) {
            var id = e.relatedTarget.dataset.id;
            $("input[name='action_type']").val("enable");
        });

        $("#disable_btn").on("click", function () {
            $("#form1").submit();
        });

        $('#disableModal').on('show.bs.modal', function (e) {
            var id = e.relatedTarget.dataset.id;
            $("input[name='action_type']").val("disable");
        });

        $("#delete_btn").on("click", function () {
            $("#form1").submit();
        });

        $('#deleteModal').on('show.bs.modal', function (e) {
            var id = e.relatedTarget.dataset.id;
            $("input[name='action_type']").val("delete");
        });

        $(function () {
            $.switcher('.checkbox2');

            $(".checkbox2").change(function () {
                var checked = $(this).is(':checked');
                var id = $(this).data("id");

                $.ajax({
                    type: "POST",
                    url: "{{url('admin/general-post-type')}}",
                    data: { checked: checked, id: id },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (data) {
                        if (data == 1) {
                            new PNotify({
                                title: 'Success!',
                                text: "General Post Set Paid",
                                type: 'success'
                            });
                        }
                        else {
                            new PNotify({
                                title: 'Success!',
                                text: "General Post Set Free",
                                type: 'success'
                            });
                        }
                    },
                });
            });

            $("#del_btn").on("click", function () {
                var id = $(this).data("submit");
                $("#form_" + id).submit();
            });

            $('#myModal').on('show.bs.modal', function (e) {
                var id = e.relatedTarget.dataset.id;
                $("#del_btn").attr("data-submit", id);
            });

            $(".frame-switch").change(function () {
                var checked = $(this).is(':checked');
                var id = $(this).data("id");

                $.ajax({
                    type: "POST",
                    url: "{{url('admin/general-post-status')}}",
                    data: { checked: checked, id: id },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (data) {
                        new PNotify({
                            title: 'Success!',
                            text: "General Post Status Has Been Changed.",
                            type: 'success'
                        });
                    },
                });
            });

            $("#del_btn").on("click", function () {
                var id = $(this).data("submit");
                $("#form_" + id).submit(function(e) {
                    e.preventDefault();
                });
            });

            $('#business_category_dropdown').select2();
        });
    let currentPreviewData = null;

    function openGenPostPreviewModal(imgUrl, aiData, templateConfig) {
        $('#genPostPreviewImage').attr('src', imgUrl);
        
        const overlay = document.getElementById('genPostAiOverlay');
        const advancedOverlay = document.getElementById('genPostAdvancedOverlay');
        const container = document.getElementById('genPostPreviewContainer');
        
        // Store data for the "shown" event
        currentPreviewData = { 
            imgUrl, 
            aiData, 
            templateConfig,
            uploadsDir: "{{ asset('uploads') }}"
        };

        // Reset state
        overlay.style.display = 'none';
        advancedOverlay.style.display = 'none';
        advancedOverlay.innerHTML = '';
        advancedOverlay.setAttribute('data-rendered', 'false');
        container.style.aspectRatio = '1/1'; 
        container.style.background = '#ffffff'; // White background
        $('#genPostPreviewImage').hide(); // Hide the base image by default
        
        $('#genPostPreviewModalFinal').modal('show');
    }

    // Trigger rendering ONLY after modal is visible to get correct clientWidth
    $('#genPostPreviewModalFinal').on('shown.bs.modal', function () {
        if (!currentPreviewData) return;
        
        const { aiData, templateConfig } = currentPreviewData;
        const overlay = document.getElementById('genPostAiOverlay');
        const advancedOverlay = document.getElementById('genPostAdvancedOverlay');

        if (templateConfig && templateConfig.config) {
            if (advancedOverlay.getAttribute('data-rendered') === 'true') return;
            renderAdvancedAiPost(templateConfig);
            advancedOverlay.style.display = 'block';
            advancedOverlay.setAttribute('data-rendered', 'true');
        } else {
            // If No advanced template, MUST show the base image!
            $('#genPostPreviewImage').show();
            if (aiData) {
                const heading = document.getElementById('genPostAiHeading');
                const sub = document.getElementById('genPostAiSubheading');
                heading.innerText = (aiData.heading_main || aiData.list_heading || aiData.heading_script || '').replace(/\\n/g, '\n');
                sub.innerText = (aiData.subheading || aiData.sub_heading || aiData.heading_main_2 || '').replace(/\\n/g, '\n');
                overlay.style.display = 'flex';
            }
        }
    });

    async function renderAdvancedAiPost(data, targetContainer = null) {
        const config = data.config;
        const aiData = data.ai_data;
        // Default to the modal's advanced overlay if no target provided
        const advancedOverlay = targetContainer || document.getElementById('genPostAdvancedOverlay');
        const container = advancedOverlay.closest('.gen-post-preview-container-root') || document.getElementById('genPostPreviewContainer');
        
        if (!config) return;
        advancedOverlay.innerHTML = ''; // Clear previous

        // HIDE base image if it's an AI thumbnail to show white background
        if (targetContainer) {
            const baseImg = targetContainer.parentElement.querySelector('img[id^="thumb-base-"]');
            if (baseImg) baseImg.style.display = 'none';
        }

        // Resolve design resolution
        let designW = (config.info && config.info.width) ? config.info.width : 1024;
        let designH = (config.info && config.info.height) ? config.info.height : 1024;
        
        if (container) {
            container.style.aspectRatio = `${designW} / ${designH}`;
        }
        
        const areaW = advancedOverlay.clientWidth || (container ? container.clientWidth : 0);
        if (areaW === 0) return; // Can't render without width

        const scale = areaW / designW;

        // Load fonts
        if (config.layers) {
            config.layers.forEach(layer => {
                if (layer.type === 'text' && layer.font) {
                    const fontName = layer.font;
                    const fontUrl = `${data.fonts_dir}/${encodeURIComponent(fontName)}.ttf`;
                    const styleId = 'font-' + fontName.replace(/\s+/g, '-');
                    if (!document.getElementById(styleId)) {
                        const style = document.createElement('style');
                        style.id = styleId;
                        style.textContent = `@font-face { font-family: "${fontName}"; src: url("${fontUrl}"); }`;
                        document.head.appendChild(style);
                    }
                }
            });
        }

        // Render layers
        config.layers.forEach((layer, idx) => {
            if (layer.name === 'bg' || layer.name === 'background') {
                if (layer.type === 'image') {
                    let bgSrc = layer.src;
                    if (bgSrc.includes('../skins/')) bgSrc = bgSrc.split('/').pop();
                    const bgImg = document.createElement('img');
                    bgImg.src = `${data.skins_dir}/${bgSrc}`;
                    bgImg.style.position = 'absolute';
                    bgImg.style.inset = '0';
                    bgImg.style.width = '100%';
                    bgImg.style.height = '100%';
                    bgImg.style.objectFit = 'cover';
                    bgImg.style.zIndex = layer.z_index || 0;
                    advancedOverlay.appendChild(bgImg);
                }
                return;
            }

            const el = document.createElement('div');
            el.style.position = 'absolute';
            el.style.left = (layer.x * scale) + 'px';
            el.style.top = (layer.y * scale) + 'px';
            el.style.width = ((layer.w || layer.width || 0) * scale) + 'px';
            el.style.height = ((layer.h || layer.height || 0) * scale) + 'px';
            el.style.zIndex = layer.z_index || (idx + 10);
            el.style.pointerEvents = 'none';

            if (layer.type === 'text') {
                const text = (aiData && aiData[layer.name]) ? aiData[layer.name] : (layer.text || '');
                el.innerText = text.replace(/\\n/g, '\n');
                el.style.color = (layer.color || '#000000').replace('0x', '#');
                el.style.fontSize = (layer.size * scale) + 'px';
                el.style.fontFamily = layer.font || 'sans-serif';
                const isBold = (layer.weight === 'bold' || (layer.font && layer.font.toLowerCase().includes('bold')));
                el.style.fontWeight = isBold ? '700' : (layer.weight || '400');
                
                if (layer.uppercase) {
                    el.style.textTransform = 'uppercase';
                }
                
                if (layer.char_spacing) {
                    // Convert Fabric.js char spacing (in 1000ths of an em) to pixels approx.
                    // For DOM, letter-spacing is applied as pixels based on font size.
                    // Fabric uses (char_spacing / 1000) * fontSize
                    el.style.letterSpacing = ((layer.char_spacing / 1000) * (layer.size * scale)) + 'px';
                }
                
                if (layer.shadow) {
                    const ox = (layer.shadow.offsetX || 0) * scale;
                    const oy = (layer.shadow.offsetY || 0) * scale;
                    const bl = (layer.shadow.blur || 0) * scale;
                    const c = layer.shadow.color || 'rgba(0,0,0,0.5)';
                    el.style.textShadow = `${ox}px ${oy}px ${bl}px ${c}`;
                }

                el.style.textAlign = layer.justification || 'left';
                el.style.lineHeight = layer.line_height || 1.1;
                el.style.overflow = 'hidden';
                el.style.whiteSpace = 'pre-wrap';
                el.style.overflowWrap = 'break-word';
                el.style.display = 'block';
            } else if (layer.type === 'image') {
                const img = document.createElement('img');
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'contain';
                
                let src = layer.src;
                const lname = (layer.name || '').toLowerCase();

                let mappedImg = null;
                if (aiData && aiData._image_mappings) {
                    const cleanLName = lname.replace(/[\s\-_]/g, '').toLowerCase();
                    if (aiData._image_mappings[lname]) {
                        mappedImg = aiData._image_mappings[lname];
                    } else {
                        for (let key in aiData._image_mappings) {
                            const cleanKey = key.replace(/[\s\-_]/g, '').toLowerCase();
                            if (cleanLName === cleanKey) {
                                mappedImg = aiData._image_mappings[key];
                                break;
                            }
                        }
                    }
                    if (!mappedImg && (cleanLName === 'image1' || cleanLName === 'mainimage')) {
                        mappedImg = aiData._image_mappings['image1'] || aiData._image_mappings['main_image'] || aiData._image_mappings['image 1'];
                    }
                }

                if (mappedImg) {
                    let mapUrl = mappedImg;
                    const uploadsDir = "{{ asset('uploads') }}";
                    if (!mapUrl.startsWith('http') && !mapUrl.startsWith('/') && !mapUrl.startsWith('data:')) {
                        mapUrl = `${uploadsDir}/${mapUrl}`;
                    }
                    img.src = mapUrl;
                    img.style.objectFit = 'cover';
                } else if (lname === 'image1' || lname === 'main_image' || lname.startsWith('image')) {
                    // Use the thumbnail's base image or the modal's current image
                    const baseImg = advancedOverlay.dataset.imgUrl || (currentPreviewData ? currentPreviewData.imgUrl : '');
                    img.src = baseImg;
                    img.style.objectFit = 'cover';
                } else {
                    if (src.includes('../skins/')) src = src.split('/').pop();
                    img.src = `${data.skins_dir}/${src}`;
                    img.style.objectFit = 'contain';
                }

                if (lname.includes('sign')) {
                    const minSize = 2; // Smaller for thumbnails
                    const w = (layer.w || layer.width || 0) * scale;
                    if (w < minSize) el.style.width = minSize + 'px';
                }

                if (lname.startsWith('image')) {
                    const radius = (layer.radius || 40) * scale;
                    img.style.borderRadius = radius + 'px';
                    el.style.borderRadius = radius + 'px';
                }
                el.appendChild(img);
            }
            advancedOverlay.appendChild(el);
        });
    }

    // Auto-render AI thumbnails
    document.addEventListener('DOMContentLoaded', () => {
        const thumbnails = document.querySelectorAll('.ai-thumbnail-generator');
        thumbnails.forEach(async el => {
            const config = JSON.parse(el.dataset.templateConfig || 'null');
            if (config && config.config) {
                // Give a small delay to ensure clientWidth is available
                setTimeout(() => renderAdvancedAiPost(config, el), 100);
            }
        });
    });
    </script>
@endsection
