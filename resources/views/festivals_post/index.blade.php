@extends("layouts.app")

@section('extra_css')
  <link href="{{ asset('assets/css/frame.css') }}" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/css/clean-switch.css')}}">
  <style>
    .ui-switcher {
      background-color: #bdc1c2;
      display: inline-block;
      top: 0;
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

    /* Status Column (5th column) */
    td:nth-child(5) .ui-switcher[aria-checked=false]:before {
      content: 'Hide';
      right: 10px;
    }

    td:nth-child(5) .ui-switcher[aria-checked=true]:before {
      content: 'Show';
      left: 10px;
    }

    td:nth-child(5) .ui-switcher[aria-checked=true] {
      background-color: #4CAF50; /* Green for Active */
    }

    /* Type Column (6th column) */
    td:nth-child(6) .ui-switcher[aria-checked=false]:before {
      content: 'Free';
      right: 10px;
    }

    td:nth-child(6) .ui-switcher[aria-checked=true]:before {
      content: 'Paid';
      left: 10px;
    }

    .ui-switcher[aria-checked=true] {
      background-color: #e91e63;
    }

    /* AI Column (7th column) */
    td:nth-child(7) .ui-switcher[aria-checked=false]:before {
      content: 'No';
      right: 10px;
    }

    td:nth-child(7) .ui-switcher[aria-checked=true]:before {
      content: 'AI';
      left: 10px;
    }

    td:nth-child(7) .ui-switcher[aria-checked=true] {
      background-color: #9c27b0; /* Purple for AI */
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

    .table img {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      cursor: pointer;
      transition: transform 0.2s;
    }

    .table img:hover {
      transform: scale(1.1);
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
      <div class="card card-primary">
        <div class="card-header">
          <h3 class="card-title float-left">
            Festivals Post
          </h3>
          <a href="{{ $tab == 'video' ? route('festivals-post.create', ['type' => 'video']) : route('festivals-post.create') }}" class="btn btn-success float-right">Add New</a>
        </div>

        <!-- Tabs Section -->
        <ul class="nav nav-tabs mt-3 px-3" style="border-bottom: 2px solid #e2e8f0;">
            <li class="nav-item">
                <a class="nav-link {{ $tab == 'image' ? 'active' : '' }}" style="font-weight: 600; {{ $tab == 'image' ? 'color: #6366f1; border-bottom: 2px solid #6366f1;' : 'color: #64748b;' }}" href="{{ url('admin/festivals-post?tab=image') }}">Images</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab == 'video' ? 'active' : '' }}" style="font-weight: 600; {{ $tab == 'video' ? 'color: #6366f1; border-bottom: 2px solid #6366f1;' : 'color: #64748b;' }}" href="{{ url('admin/festivals-post?tab=video') }}">Videos</a>
            </li>
        </ul>

        <div class="card-body">
          <div class="row d-flex justify-content-between mb-3 px-3">
            <div class="d-flex align-items-center">
              <div class="mr-3">
                <select class="form-control select2" id="festival_dropdown" name="festival_dropdown"
                  onchange="location = this.value;">
                  <option value="{{url('admin/festivals-post?tab=' . $tab)}}" @if(empty($name)) selected @endif>Select Festivals
                  </option>
                  @foreach($festivals as $f)
                    <option value="{{url('admin/festival/' . $f->id . '?tab=' . $tab)}}" @if(!empty($name) && $name == $f->title) selected
                    @endif>{{$f->title}}</option>
                  @endforeach
                </select>
              </div>

              <div class="checkbox mr-3">
                <input type="checkbox" id="checkall" style="width: 18px;height: 18px; vertical-align: middle;">
                <label for="checkall" class="mb-0 ml-1">Select All</label>
              </div>

              <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle btn_cust" type="button" data-toggle="dropdown">Action<span
                    class="caret"></span></button>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="#" data-type="enable" data-toggle="modal"
                      data-target="#enableModal">Enable</a></li>
                  <li><a class="dropdown-item" href="#" data-type="disable" data-toggle="modal"
                      data-target="#disableModal">Disable</a></li>
                  <li><a class="dropdown-item" href="#" data-type="delete" data-toggle="modal"
                      data-target="#deleteModal">Delete</a></li>
                </ul>
                {!! Form::open(['url' => $tab == 'video' ? 'admin/video-action' : 'admin/festivals-post-action', 'method' => 'POST', 'class' => 'form-horizontal', 'id' => 'form1']) !!}
                <input type="hidden" name="select_post" value="">
                <input type="hidden" name="action_type" value="">
                @if($tab == 'video')
                    <input type="hidden" name="redirect_to" value="festivals-post">
                @endif
                {!! Form::close() !!}
              </div>
            </div>
          </div>

          <div class="table-responsive p-0">
            <table class="table table-hover text-nowrap">
              <thead>
                <tr>
                  <th width="50px"></th>
                  <th>ID</th>
                  <th>Thumb</th>
                  <th>Festival</th>
                  <th>Status</th>
                  <th>Type</th>
                  <th>AI</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach($data as $frame)
                  <tr>
                    <td class="align-middle">
                      <input type="checkbox" name="post_ids[]" value="{{$frame->id}}" class="post_ids"
                        style="width: 16px;height: 16px;">
                    </td>
                    <td class="align-middle">#{{$frame->id}}</td>
                    <td class="align-middle">
                      @if($tab == 'video')
                        <video width="60" height="60" preload="metadata" style="object-fit: cover; border-radius: 8px;">
                          <source src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/video/' . $frame->video)}} @else {{asset('uploads/video/' . $frame->video)}} @endif#t=5">
                        </video>
                      @else
                        <img
                          src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/' . $frame->frame_image)}} @else {{asset('uploads/' . $frame->frame_image)}} @endif"
                          class="img-preview-trigger shadow-sm"
                          data-url="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/' . $frame->frame_image)}} @else {{asset('uploads/' . $frame->frame_image)}} @endif"
                          alt="Thumb" />
                      @endif
                    </td>
                    <td class="align-middle">
                      <span class="font-weight-bold">
                        @if($tab == 'video')
                            {{$frame->festival->title ?? ''}}
                        @else
                            {{$frame->festivals->title ?? ''}}
                        @endif
                      </span>
                    </td>
                    <td class="align-middle">
                      <input class="{{ $tab == 'video' ? 'video-switch-ajax' : 'festivals-switch-ajax' }}" type="checkbox" data-id="{{$frame->id}}" value="1"
                        @if($frame->status == 1) checked @endif>
                    </td>
                    <td class="align-middle">
                      <input class="{{ $tab == 'video' ? 'video-type-switch-ajax' : 'type-switch-ajax' }}" type="checkbox" data-id="{{$frame->id}}" value="1"
                        @if($frame->paid == 1) checked @endif>
                    </td>
                    <td class="align-middle">
                      <input class="ai-switch-ajax" type="checkbox" data-id="{{$frame->id}}" value="1"
                        @if($frame->is_ai == 1) checked @endif>
                    </td>
                    <td class="align-middle">
                      <div class="btn-group">
                        @if($tab == 'video')
                            <a href="{{url('admin/video/' . $frame->id . '/edit?tab=video&redirect_to=festivals-post')}}" class="btn btn-sm btn-success" data-toggle="tooltip" title="Edit">
                              <i class="fa fa-edit"></i>
                            </a>
                        @else
                            <a href="{{url('admin/festivals-post/' . $frame->id . '/edit')}}" class="btn btn-sm btn-success" data-toggle="tooltip" title="Edit">
                              <i class="fa fa-edit"></i>
                            </a>
                        @endif
                        <button type="button" data-id="{{$frame->id}}" class="btn btn-sm btn-danger ml-2 btn_delete_a"
                          data-toggle="modal" data-target="#myModal" title="Delete">
                          <i class="fa fa-trash"></i>
                        </button>
                      </div>
                      @if($tab == 'video')
                          {!! Form::open(['url' => 'admin/video/' . $frame->id, 'method' => 'DELETE', 'class' => 'form-horizontal', 'id' => 'form_' . $frame->id]) !!}
                          {!! Form::hidden("redirect_to", "festivals-post") !!}
                      @else
                          {!! Form::open(['url' => 'admin/festivals-post/' . $frame->id, 'method' => 'DELETE', 'class' => 'form-horizontal', 'id' => 'form_' . $frame->id]) !!}
                      @endif
                      {!! Form::hidden("id", $frame->id) !!}
                      {!! Form::close() !!}
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="card-footer clearfix">
            <div class="float-right">
              {{ $data->links() }}
            </div>
          </div>
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

  <!-- Image Preview Modal -->
  <div id="previewModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Image Preview</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body text-center" style="background: #f8fafc;">
          <img id="previewImageFull" src=""
            style="max-width: 100%; height: auto; border-radius: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        </div>
      </div>
    </div>
  </div>

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
            <button id="bulk_delete_btn" class="btn btn-danger" type="button">Yes</button>
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
    $('#festival_dropdown').select2();
    $.switcher('.festivals-switch-ajax');
    $.switcher('.type-switch-ajax');
    $.switcher('.ai-switch-ajax');
    $.switcher('.video-switch-ajax');
    $.switcher('.video-type-switch-ajax');

    var checkarray = [];
    $("#checkall").click(function () {
      checkarray = [];
      $("input[name='post_ids[]']").not(this).prop('checked', this.checked);
      $.each($("input[name='post_ids[]']:checked"), function () {
        checkarray.push($(this).val());
      });
      $("input[name='select_post']").val(checkarray);
    });

    $(document).on('click', ".post_ids", function (e) {
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
      $("input[name='action_type']").val("enable");
    });

    $("#disable_btn").on("click", function () {
      $("#form1").submit();
    });

    $('#disableModal').on('show.bs.modal', function (e) {
      $("input[name='action_type']").val("disable");
    });

    $("#bulk_delete_btn").on("click", function () {
      $("#form1").submit();
    });

    $('#deleteModal').on('show.bs.modal', function (e) {
      $("input[name='action_type']").val("delete");
    });

    $("#del_btn").on("click", function () {
      var id = $(this).data("submit");
      $("#form_" + id).submit();
    });

    $('#myModal').on('show.bs.modal', function (e) {
      var id = e.relatedTarget.dataset.id;
      $("#del_btn").attr("data-submit", id);
    });

    $(document).on('click', '.img-preview-trigger', function () {
      var url = $(this).data('url');
      $('#previewImageFull').attr('src', url);
      $('#previewModal').modal('show');
    });

    $(document).on('change', ".festivals-switch-ajax", function () {
      var checked = $(this).is(':checked');
      var id = $(this).data("id");

      $.ajax({
        type: "POST",
        url: "{{url('admin/festivals-post-status')}}",
        data: { checked: checked, id: id },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
          new PNotify({
            title: 'Success!',
            text: "Festivals Post Status Has Been Changed.",
            type: 'success'
          });
        },
      });
    });

    $(document).on('change', ".type-switch-ajax", function () {
      var checked = $(this).is(':checked');
      var id = $(this).data("id");

      $.ajax({
        type: "POST",
        url: "{{url('admin/festivals-post-type')}}",
        data: { checked: checked, id: id },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
          if (data == 1) {
            new PNotify({
              title: 'Success!',
              text: "Festivals Post Set Paid",
              type: 'success'
            });
          }
          else {
            new PNotify({
              title: 'Success!',
              text: "Festivals Post Set Free",
              type: 'success'
            });
          }
        },
      });
    });

    $(document).on('change', ".video-switch-ajax", function () {
      var checked = $(this).is(':checked');
      var id = $(this).data("id");
      $.ajax({
        type: "POST",
        url: "{{url('admin/video-status')}}",
        data: { checked: checked, id: id },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
          new PNotify({title: 'Success!', text: "Video Status Changed.", type: 'success'});
        },
      });
    });

    $(document).on('change', ".video-type-switch-ajax", function () {
      var checked = $(this).is(':checked');
      var id = $(this).data("id");
      $.ajax({
        type: "POST",
        url: "{{url('admin/video-type')}}",
        data: { checked: checked, id: id },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
          if (data == 1) {
            new PNotify({title: 'Success!', text: "Video Set Paid", type: 'success'});
          } else {
            new PNotify({title: 'Success!', text: "Video Set Free", type: 'success'});
          }
        },
      });
    });

    $(document).on('change', ".ai-switch-ajax", function () {
      var checked = $(this).is(':checked');
      var id = $(this).data("id");

      $.ajax({
        type: "POST",
        url: "{{url('admin/festivals-post-ai')}}",
        data: { checked: checked, id: id },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
          new PNotify({
            title: 'Success!',
            text: "AI Status Has Been Changed.",
            type: 'success'
          });
        },
      });
    });
  </script>
@endsection