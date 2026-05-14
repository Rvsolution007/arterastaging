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

    .ui-switcher[aria-checked=false]:before {
      content: 'Off';
      right: 10px;
    }

    .ui-switcher[aria-checked=true]:before {
      content: 'On';
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

/* Minimalist Dashboard UI Port */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

.poppins-font { font-family: 'Poppins', sans-serif; }

.dash-panel {
    background-color: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
    margin-bottom: 24px;
    overflow: hidden;
}
.dash-panel-body { padding: 0; }
.dash-panel-body-filters { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; }
.dash-panel-body .table { margin-bottom: 0; font-family: 'Poppins', sans-serif; }
.dash-panel-body .table thead th {
    border-top: none;
    border-bottom: 1px solid #e2e8f0;
    background-color: #f8fafc;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 13px;
    padding: 12px 20px;
}
.dash-panel-body .table tbody td {
    padding: 12px 20px;
    vertical-align: middle;
    border-top: 1px solid #f1f5f9;
    font-size: 14px;
    color: #334155;
}
.btn-action {
    background: transparent;
    border: none;
    padding: 6px;
    margin-right: 4px;
    border-radius: 6px;
    transition: background 0.2s;
}
.btn-action-edit { color: #10B981; }
.btn-action-edit:hover { background: rgba(16, 185, 129, 0.1); }
.btn-action-delete { color: #EF4444; }
.btn-action-delete:hover { background: rgba(239, 68, 68, 0.1); }
</style>
@endsection

@section('content')
<div class="row poppins-font mt-4">
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
    <div class="dash-panel">
      <!-- Old Design Header Reinstated -->
      <div class="d-flex justify-content-between align-items-center" style="background-color: #007bff; padding: 14px 24px; border-radius: 12px 12px 0 0;">
          <span style="color: #ffffff; font-size: 18px; font-weight: 500; letter-spacing: 0.5px;">Festivals</span>
          <a href="{{ route('festivals.create')}}" style="background-color: #28a745; color: #ffffff; text-decoration: none; padding: 6px 16px; border-radius: 4px; font-size: 14px; font-weight: 500;">Add New</a>
      </div>
      <div class="dash-panel-body">
        <div class="dash-panel-body-filters d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
              <div class="checkbox mr-3 mt-1">
                <input type="checkbox" id="checkall" style="width: 16px;height: 16px; vertical-align: middle;">
                <label for="checkall" class="mb-0 ml-1" style="font-size:14px;">Select All</label>
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
                {!! Form::open(['url' => 'admin/festivals-action', 'method' => 'POST', 'class' => 'form-horizontal', 'id' => 'form1']) !!}
                <input type="hidden" name="select_post" value="">
                <input type="hidden" name="action_type" value="">
                {!! Form::close() !!}
              </div>
            </div>
            <div class="d-flex">
              <form class="form-inline" action="{{url('admin/festivals-search')}}" method="GET">
                <input class="form-control mr-sm-2 shadow-sm" style="border-radius:6px; font-size:14px; border:1px solid #e2e8f0;" type="search" name="search"
                  value="@if(!empty($name)) {{$name}} @endif" placeholder="Search" aria-label="Search">
                <button class="btn btn-primary my-2 my-sm-0" type="submit" style="background:#0EA5E9; border:none; border-radius:6px; font-size:14px;">Search</button>
              </form>
            </div>
          </div>

          <div class="table-responsive p-0">
            <table class="table table-hover text-nowrap">
              <thead>
                <tr>
                  <th width="50px"></th>
                  <th>ID</th>
                  <th>Thumb</th>
                  <th>Title</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Feature</th>
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
                    <td class="align-middle" style="color:#64748b;">#{{$frame->id}}</td>
                    <td class="align-middle">
                      <img
                        src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{Storage::disk('spaces')->url('uploads/' . $frame->image)}} @else {{asset('uploads/' . $frame->image)}} @endif"
                        class="img-preview-trigger shadow-sm rounded"
                        style="width:36px; height:36px; object-fit:cover; cursor:pointer;"
                        data-url="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{Storage::disk('spaces')->url('uploads/' . $frame->image)}} @else {{asset('uploads/' . $frame->image)}} @endif"
                        alt="Thumb" />
                    </td>
                    <td class="align-middle">
                      <span class="font-weight-bold" style="color:#1e293b; font-size:14px;">{{$frame->title}}</span>
                    </td>
                    <td class="align-middle flex align-items-center" style="color:#64748b; font-size:14px;">
                      <i class="fa fa-calendar-alt mr-2" style="color:#94a3b8;"></i>
                      {{ date_format(date_create(implode("", preg_split("/[-\s:,]/", $frame->festivals_date))), "d M, y") }}
                    </td>
                    <td class="align-middle">
                      <input class="festivals-switch-ajax" type="checkbox" data-id="{{$frame->id}}" value="1"
                        @if($frame->status == 1) checked @endif>
                    </td>
                    <td class="align-middle">
                      @php $post = App\Models\FeaturePost::where("festival_id", $frame->id)->get(); @endphp
                      <input class="feature-switch-ajax" type="checkbox" data-id="{{$frame->id}}" value="1"
                        @if(!$post->isEmpty()) checked @endif>
                    </td>
                    <td class="align-middle">
                      <div class="d-flex align-items-center">
                        <a href="{{url('admin/festivals/' . $frame->id . '/edit')}}" class="btn-action btn-action-edit"
                          data-toggle="tooltip" title="Edit">
                          <i class="fa fa-edit"></i>
                        </a>
                        <button type="button" data-id="{{$frame->id}}" class="btn-action btn-action-delete btn_delete_a"
                          data-toggle="modal" data-target="#myModal" title="Delete">
                          <i class="fa fa-trash"></i>
                        </button>
                      </div>
                      {!! Form::open(['url' => 'admin/festivals/' . $frame->id, 'method' => 'DELETE', 'class' => 'form-horizontal', 'id' => 'form_' . $frame->id]) !!}
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
              {{ $data->appends(request()->input())->links() }}
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
  <script src="{{ asset('assets/js/jquery.switcher.js') }}"></script>
  <script type="text/javascript">
    $.switcher('.festivals-switch-ajax');
    $.switcher('.feature-switch-ajax');

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
        url: "{{url('admin/festivals-status')}}",
        data: { checked: checked, id: id },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
          new PNotify({
            title: 'Success!',
            text: "Festivals Status Has Been Changed.",
            type: 'success'
          });
        },
      });
    });

    $(document).on('change', ".feature-switch-ajax", function () {
      var checked = $(this).is(':checked');
      var id = $(this).data("id");

      $.ajax({
        type: "POST",
        url: "{{url('admin/festivals-feature-status')}}",
        data: { checked: checked, id: id },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
          if (data == 1) {
            new PNotify({
              title: 'Success!',
              text: "Festival Feature Set!.",
              type: 'success'
            });
          }
          else {
            new PNotify({
              title: 'Success!',
              text: "Festival Feature Unset!.",
              type: 'success'
            });
          }
        },
      });
    });
  </script>
@endsection