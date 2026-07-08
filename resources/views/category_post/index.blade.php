@extends("layouts.app")

@section('extra_css')
<link href="{{ asset('assets/css/frame.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/clean-switch.css')}}">
<style>
    /* Modern Dashboard Styling - Adapted from AI Analytics */
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

    .admin-container {
        font-family: 'Poppins', sans-serif;
        padding: 1.5rem;
        background-color: #f8fafc;
        min-height: 100vh;
    }

    .page-title {
        font-weight: 700;
        color: #1e293b;
        font-size: 1.75rem;
        letter-spacing: -0.025em;
        margin-bottom: 0.25rem;
    }

    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    /* Actions Wrapper */
    .actions-wrapper {
        background: #ffffff;
        padding: 1rem 1.5rem;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        border: 1px solid #e2e8f0;
        margin-bottom: 2rem;
    }

    .filter-group {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    /* Modern Dropdown/Select Styling */
    .custom-select-wrapper .select2-container--default .select2-selection--single {
        height: 42px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background-color: #f8fafc;
        display: flex;
        align-items: center;
        padding: 0 10px;
        transition: all 0.2s ease;
    }

    .custom-select-wrapper .select2-container--default .select2-selection--single:focus,
    .custom-select-wrapper .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        background-color: #ffffff;
    }

    .custom-select-wrapper .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #334155;
        font-weight: 500;
        font-size: 0.9rem;
        line-height: 42px;
    }

    .custom-select-wrapper .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
    }

    /* Buttons */
    .btn-premium-add {
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        color: white;
        border: none;
        padding: 0.6rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(234, 88, 12, 0.3);
    }

    .btn-premium-add:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(234, 88, 12, 0.4);
        color: white;
        text-decoration: none;
    }

    .btn-premium-action {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        padding: 0.6rem 1.25rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    .btn-premium-action:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    /* Bulk Select */
    .bulk-select-wrapper {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: #f8fafc;
        padding: 0.4rem 0.75rem;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }

    .bulk-select-wrapper input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .bulk-select-wrapper label {
        margin: 0;
        font-size: 0.85rem;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
    }

    /* Premium Frame Cards */
    .frame-card {
        background: #ffffff;
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.01);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 1.5rem;
        position: relative;
    }

    .frame-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.03);
    }

    .frame-card-image-wrapper {
        position: relative;
        padding-top: 100%; /* Square aspect ratio by default */
        background: #f1f5f9;
        overflow: hidden;
    }

    .frame-card-image-wrapper img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 10px;
        transition: transform 0.5s ease;
    }

    .frame-card:hover .frame-card-image-wrapper img {
        transform: scale(1.05);
    }

    .frame-card-overlay {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 10;
    }

    .frame-card-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #6366f1;
    }

    .frame-card-content {
        padding: 1.25rem;
    }

    .frame-category-name {
        font-weight: 700;
        color: #1e293b;
        font-size: 1rem;
        margin-bottom: 0.75rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .frame-actions-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 0.75rem;
        border-top: 1px solid #f1f5f9;
    }

    .action-icons {
        display: flex;
        gap: 0.5rem;
    }

    .icon-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    .icon-btn-edit { background: #e0e7ff; color: #4338ca; }
    .icon-btn-edit:hover { background: #4338ca; color: white; }

    .icon-btn-delete { background: #fee2e2; color: #b91c1c; }
    .icon-btn-delete:hover { background: #b91c1c; color: white; }

    .switches-group {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    /* UI Switcher Override */
    .ui-switcher {
        width: 54px !important;
        height: 24px !important;
        border-radius: 12px !important;
        background-color: #cbd5e1 !important;
        border: none !important;
    }

    .ui-switcher[aria-checked=true] {
        background-color: #10b981 !important;
    }

    .ui-switcher:after {
        width: 18px !important;
        height: 18px !important;
        top: 3px !important;
        left: 3px !important;
    }

    .ui-switcher[aria-checked=true]:after {
        left: 33px !important;
    }

    .ui-switcher:before {
        display: none !important;
    }

    /* Tooltip/Label for switches */
    .switch-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: 2px;
        display: block;
    }

    /* Clean Switch Override */
    .cl-switch input[type="checkbox"]:checked + .switcher {
        background-color: #6366f1 !important;
    }

</style>
@endsection

@section('content')
<div class="admin-container">
    <!-- Header Section -->
    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h1 class="page-title">Category Post</h1>
            <p class="page-subtitle">Manage and curate category-specific {{ $tab == 'video' ? 'videos' : 'frame templates' }}</p>
        </div>
        <div class="col-md-5 text-right">
            <a href="{{ $tab == 'video' ? route('category-post.create', ['type' => 'video']) : route('category-post.create') }}" class="btn-premium-add ml-auto" style="width: fit-content;">
                <i class="fa fa-plus"></i> Add New {{ $tab == 'video' ? 'Video' : 'Frame' }}
            </a>
        </div>
    </div>

    <!-- Tabs Section -->
    <ul class="nav nav-tabs mb-4" style="border-bottom: 2px solid #e2e8f0;">
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'image' ? 'active' : '' }}" style="font-weight: 600; {{ $tab == 'image' ? 'color: #6366f1; border-bottom: 2px solid #6366f1;' : 'color: #64748b;' }}" href="{{ url('admin/category-post?tab=image') }}">Images</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'video' ? 'active' : '' }}" style="font-weight: 600; {{ $tab == 'video' ? 'color: #6366f1; border-bottom: 2px solid #6366f1;' : 'color: #64748b;' }}" href="{{ url('admin/category-post?tab=video') }}">Videos</a>
        </li>
    </ul>

    <!-- Actions & Filters Wrapper -->
    <div class="actions-wrapper">
        <div class="filter-group">
            <div class="custom-select-wrapper" style="min-width: 250px;">
                <select class="form-control" id="category_dropdown" name="category_dropdown" onchange="location = this.value;">
                    <option value="{{url('admin/category-post?tab=' . $tab)}}" @if(empty($name)) selected @endif>All Categories</option>
                    @foreach($category as $c)
                        <option value="{{url('admin/category-get/'.$c->id.'?tab=' . $tab)}}" @if(!empty($name) && $name == $c->name) selected @endif>{{$c->name}}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="bulk-select-wrapper">
                <input type="checkbox" id="checkall">
                <label for="checkall">Select All</label>
            </div>
        </div>

        <div class="dropdown">
            <button class="btn btn-premium-action dropdown-toggle" type="button" data-toggle="dropdown">
                Bulk Actions <i class="fa fa-chevron-down ml-2" style="font-size: 0.8rem;"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-right shadow-sm border-0" style="border-radius: 12px; padding: 0.5rem;">
                <a class="dropdown-item py-2 px-3" href="#" data-type="enable" data-toggle="modal" data-target="#enableModal" style="border-radius: 8px; font-weight: 500;">
                    <i class="fa fa-check-circle text-success mr-2"></i> Enable Selected
                </a>
                <a class="dropdown-item py-2 px-3" href="#" data-type="disable" data-toggle="modal" data-target="#disableModal" style="border-radius: 8px; font-weight: 500;">
                    <i class="fa fa-times-circle text-warning mr-2"></i> Disable Selected
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item py-2 px-3 text-danger" href="#" data-type="delete" data-toggle="modal" data-target="#deleteModal" style="border-radius: 8px; font-weight: 500;">
                    <i class="fa fa-trash-alt mr-2"></i> Delete Selected
                </a>
            </div>
            {!! Form::open(['url' => $tab == 'video' ? 'admin/video-action' : 'admin/category-post-action','method'=>'POST','class'=>'form-horizontal','id'=>'form1']) !!}
            <input type="hidden" name="select_post" value="">
            <input type="hidden" name="action_type" value="">
            @if($tab == 'video')
                <input type="hidden" name="redirect_to" value="category-post">
            @endif
            {!! Form::close() !!}
        </div>
    </div>

    @if (count($errors) > 0)
    <div class="alert alert-danger shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Frame Grid -->
    <div class="row" id="frame_data">
        @foreach($data as $frame)
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
            <div class="frame-card">
                <div class="frame-card-overlay">
                    <input type="checkbox" name="post_ids[]" value="{{$frame->id}}" class="post_ids frame-card-checkbox">
                </div>
                
                <div class="frame-card-image-wrapper">
                    @if($tab == 'video')
                        <video width="100%" height="100%" preload="metadata" style="object-fit: cover;">
                            <source src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/video/'.$frame->video)}} @else {{asset('uploads/video/'.$frame->video)}} @endif#t=5">
                        </video>
                    @else
                        <img src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$frame->frame_image)}} @else {{asset('uploads/'.$frame->frame_image)}} @endif" alt="{{$frame->category->name ?? ''}}">
                    @endif
                </div>

                <div class="frame-card-content">
                    <div class="frame-category-name" title="{{$frame->category->name ?? ''}}">
                        {{$frame->category->name ?? ''}}
                    </div>

                    <div class="frame-actions-row">
                        <div class="switches-group">
                            <div class="text-center">
                                <span class="switch-label">Status</span>
                                <label class="cl-switch cl-switch-red mb-0">
                                    <input type="checkbox" class="{{ $tab == 'video' ? 'video-switch' : 'frame-switch' }}" data-id="{{$frame->id}}" value="1" @if($frame->status==1) checked @endif>
                                    <span class="switcher"></span>
                                </label>
                            </div>
                            <div class="text-center">
                                <span class="switch-label">Premium</span>
                                <input class="form-check-input checkbox2" type="checkbox" data-id="{{$frame->id}}" value="1" @if($frame->paid==1) checked @endif>
                            </div>
                            <div class="text-center">
                                <span class="switch-label">AI</span>
                                <input class="form-check-input ai-switch-ajax" type="checkbox" data-id="{{$frame->id}}" value="1" @if($frame->is_ai==1) checked @endif>
                            </div>
                            @if($tab != 'video')
                            <div class="text-center">
                                <span class="switch-label">Landing</span>
                                <input class="form-check-input landing-switch-ajax" type="checkbox" data-id="{{$frame->id}}" value="1" @if($frame->show_on_landing==1) checked @endif>
                            </div>
                            @endif
                        </div>

                        <div class="action-icons">
                            @if($tab == 'video')
                                <a href="{{url('admin/video/'.$frame->id.'/edit?tab=video&redirect_to=category-post')}}" class="icon-btn icon-btn-edit" data-toggle="tooltip" title="Edit Video">
                                    <i class="fa fa-edit"></i>
                                </a>
                            @else
                                <a href="{{url('admin/category-post/'.$frame->id.'/edit')}}" class="icon-btn icon-btn-edit" data-toggle="tooltip" title="Edit Frame">
                                    <i class="fa fa-edit"></i>
                                </a>
                            @endif
                            <a href="#" data-id="{{$frame->id}}" class="icon-btn icon-btn-delete btn_delete_a" data-toggle="modal" data-target="#myModal" title="Delete">
                                <i class="fa fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>

                @if($tab == 'video')
                    {!! Form::open(['url' => 'admin/video/'.$frame->id,'method'=>'DELETE','class'=>'form-horizontal','id'=>'form_'.$frame->id]) !!}
                    {!! Form::hidden("redirect_to", "category-post") !!}
                @else
                    {!! Form::open(['url' => 'admin/category-post/'.$frame->id,'method'=>'DELETE','class'=>'form-horizontal','id'=>'form_'.$frame->id]) !!}
                @endif
                {!! Form::hidden("id",$frame->id) !!}
                {!! Form::close() !!}
            </div>
        </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-4">
        {{ $data->links() }}
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
@endsection

@section('script')
<script src="{{ asset('assets/js/jquery.switcher.js')}}"></script>
<script type="text/javascript">
    $('#category_dropdown').select2();
    var checkarray = [];
    $("#checkall").click(function() {
      checkarray = [];
      $("input[name='post_ids[]']").not(this).prop('checked', this.checked);
      $.each($("input[name='post_ids[]']:checked"), function() {
        checkarray.push($(this).val());
      });
      $("input[name='select_post']").val(checkarray);
    });
    
    $(".post_ids").click(function(e) {
      if ($(this).prop("checked") == true) {
        checkarray.push($(this).val());
      } else if ($(this).prop("checked") == false) {
        checkarray.splice($.inArray($(this).val(),checkarray), 1);
      }
      $("input[name='select_post']").val(checkarray);
    });

    $("#enable_btn").on("click",function(){
        $("#form1").submit();
    });

    $('#enableModal').on('show.bs.modal', function(e) {
        var id = e.relatedTarget.dataset.id;
        $("input[name='action_type']").val("enable");
    });

    $("#disable_btn").on("click",function(){
        $("#form1").submit();
    });

    $('#disableModal').on('show.bs.modal', function(e) {
        var id = e.relatedTarget.dataset.id;
        $("input[name='action_type']").val("disable");
    });

    $("#delete_btn").on("click",function(){
        $("#form1").submit();
    });

    $('#deleteModal').on('show.bs.modal', function(e) {
        var id = e.relatedTarget.dataset.id;
        $("input[name='action_type']").val("delete");
    });

    $(function(){
      $('[data-toggle="tooltip"]').tooltip();
      $('#category').select2();
      $.switcher('.checkbox2');
      $.switcher('.ai-switch-ajax');
      $.switcher('.landing-switch-ajax');
    
      // $('#category').on('change', function () {
      //   var id = $(this).val();
        
      //   $.ajax({
      //     type: "GET",
      //     url: "{{url('admin/get-category-post')}}",
      //     data: {id : id},
      //     headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      //     success: function(data) {
      //         //console.log(data);
      //         $('#frame_data').html(data);
      //     },
      //   });
      // });

      $(".checkbox2").change(function(){
        var checked = $(this).is(':checked');
        var id = $(this).data("id");
        var isVideo = '{{ $tab }}' === 'video';

        $.ajax({
          type: "POST",
          url: isVideo ? "{{url('admin/video-type')}}" : "{{url('admin/category-post-type')}}",
          data: { checked : checked , id : id},
          headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
          success: function(data) {
            if(data == 1)
            {
              new PNotify({
                title: 'Success!',
                text: isVideo ? "Video Set Paid" : "Category Post Set Paid",
                type: 'success'
              });
            }
            else
            {
              new PNotify({
                title: 'Success!',
                text: isVideo ? "Video Set Free" : "Category Post Set Free",
                type: 'success'
              });
            }
          },
        });
      });
    
      $("#del_btn").on("click",function(){
          var id=$(this).data("submit");
          $("#form_"+id).submit();
      });

      $('#myModal').on('show.bs.modal', function(e) {
          var id = e.relatedTarget.dataset.id;
          $("#del_btn").attr("data-submit",id);
      });

      $(".frame-switch").change(function(){
        var checked = $(this).is(':checked');
        var id = $(this).data("id");
        
        $.ajax({
          type: "POST",
          url: "{{url('admin/category-post-status')}}",
          data: { checked : checked , id : id},
          headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
          success: function(data) {
            new PNotify({
              title: 'Success!',
              text: "Category Post Status Has Been Changed.",
              type: 'success'
            });
          },
        });
      });

      $(".video-switch").change(function(){
        var checked = $(this).is(':checked');
        var id = $(this).data("id");
        
        $.ajax({
          type: "POST",
          url: "{{url('admin/video-status')}}",
          data: { checked : checked , id : id},
          headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
          success: function(data) {
            new PNotify({
              title: 'Success!',
              text: "Video Status Has Been Changed.",
              type: 'success'
            });
          },
        });
      });

      $(".ai-switch-ajax").change(function(){
        var checked = $(this).is(':checked');
        var id = $(this).data("id");
        
        $.ajax({
          type: "POST",
          url: "{{url('admin/category-post-ai')}}",
          data: { checked : checked , id : id},
          headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
          success: function(data) {
            new PNotify({
              title: 'Success!',
              text: "AI Status Has Been Changed.",
              type: 'success'
            });
          },
        });
      });

      $(".landing-switch-ajax").change(function(){
        var checked = $(this).is(':checked');
        var id = $(this).data("id");
        
        $.ajax({
          type: "POST",
          url: "{{url('admin/category-post-landing')}}",
          data: { checked : checked , id : id},
          headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
          success: function(data) {
            new PNotify({
              title: 'Success!',
              text: "Landing Visibility Has Been Changed.",
              type: 'success'
            });
          },
        });
      });
    });
</script>
@endsection
