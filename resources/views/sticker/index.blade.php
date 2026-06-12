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

    .select2-container--default .select2-selection--single {
      background-color: #007bff;
      border-color: #007bff;
      height: 38px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: white;
      line-height: 35px;
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
      <div class="card card-primary">
        <div class="card-header">
          <h3 class="card-title float-left">
            Sticker
          </h3>
          <div class="float-right">
            <button type="button" class="btn btn-primary mr-2" data-toggle="modal" data-target="#aiGenerateModal">
              <i class="fas fa-magic"></i> AI Auto Generate
            </button>
            <a href="{{ route('sticker.create')}}" class="btn btn-success">Add New</a>
          </div>
        </div>

        <div class="card-body">
          <div class="row d-flex justify-content-between mb-3 px-3">
            <div class="d-flex align-items-center">
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
                {!! Form::open(['url' => 'admin/sticker-action', 'method' => 'POST', 'class' => 'form-horizontal', 'id' => 'form1']) !!}
                <input type="hidden" name="select_post" value="">
                <input type="hidden" name="action_type" value="">
                {!! Form::close() !!}
              </div>
            </div>
            <div class="d-flex align-items-center">
              <select class="form-control" id="sticker_dropdown" name="sticker_dropdown"
                onchange="location = this.value;">
                <option value="{{url('admin/sticker')}}" @if(empty($name)) selected @endif>Select Sticker Category
                </option>
                @foreach($category as $c)
                  <option value="{{url('admin/sticker-category-get/' . $c->id)}}" @if(!empty($name) && $name == $c->name)
                  selected @endif>{{$c->name}}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="table-responsive p-0">
            <table class="table table-hover text-nowrap">
              <thead>
                <tr>
                  <th width="50px"></th>
                  <th>ID</th>
                  <th>Thumb</th>
                  <th>Category</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="frame_data">
                @foreach($data as $sticker)
                  <tr>
                    <td class="align-middle">
                      <input type="checkbox" name="post_ids[]" value="{{$sticker->id}}" class="post_ids"
                        style="width: 16px;height: 16px;">
                    </td>
                    <td class="align-middle">#{{$sticker->id}}</td>
                    <td class="align-middle">
                      <img
                        src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/' . $sticker->image)}} @else {{asset('uploads/' . $sticker->image)}} @endif"
                        class="img-preview-trigger shadow-sm"
                        data-url="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/' . $sticker->image)}} @else {{asset('uploads/' . $sticker->image)}} @endif"
                        alt="Thumb" />
                    </td>
                    <td class="align-middle">
                      <span class="font-weight-bold">{{$sticker->sticker_category->name}}</span>
                    </td>
                    <td class="align-middle">
                      <input class="frame-switch" type="checkbox" data-id="{{$sticker->id}}" value="1"
                        @if($sticker->status == 1) checked @endif>
                    </td>
                    <td class="align-middle">
                      <div class="btn-group">
                        <a href="{{url('admin/sticker/' . $sticker->id . '/edit')}}" class="btn btn-sm btn-success"
                          data-toggle="tooltip" title="Edit">
                          <i class="fa fa-edit"></i>
                        </a>
                        <button type="button" data-id="{{$sticker->id}}" class="btn btn-sm btn-danger ml-2 btn_delete_a"
                          data-toggle="modal" data-target="#myModal" title="Delete">
                          <i class="fa fa-trash"></i>
                        </button>
                      </div>
                      {!! Form::open(['url' => 'admin/sticker/' . $sticker->id, 'method' => 'DELETE', 'class' => 'form-horizontal', 'id' => 'form_' . $sticker->id]) !!}
                      {!! Form::hidden("id", $sticker->id) !!}
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

  <!-- AI Generate Modal -->
  <div class="modal fade" id="aiGenerateModal" tabindex="-1" role="dialog" aria-labelledby="aiGenerateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <form id="aiGenerateForm">
          <div class="modal-header">
            <h5 class="modal-title" id="aiGenerateModalLabel"><i class="fas fa-magic"></i> AI Auto Generate Stickers</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>Category Name</label>
              <input type="text" class="form-control" name="category_name" required placeholder="e.g. Space, Animals, Food">
              <small class="form-text text-muted">AI will automatically generate related emojis and add them to this category.</small>
            </div>
            <div id="aiLoading" style="display:none;" class="text-center mt-3">
              <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
              </div>
              <p class="mt-2 text-primary font-weight-bold">AI is generating stickers... Please wait.</p>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary" id="aiGenerateBtn">Generate</button>
          </div>
        </form>
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
            <button id="bulk_delete_btn" class="btn btn-danger" type="button">Yes</button>
          @endif
          <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
        </div>
      </div>
    </div>
  </div>
  <!-- deleteModal -->

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
@endsection

@section('script')
  <script src="{{ asset('assets/js/jquery.switcher.js')}}"></script>
  <script type="text/javascript">
    $(document).ready(function () {
      $.switcher('.frame-switch');
      $('#sticker_dropdown').select2();
    });

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

    $(document).on('click', '.img-preview-trigger', function () {
      var url = $(this).data('url');
      $('#previewImageFull').attr('src', url);
      $('#previewModal').modal('show');
    });

    $("#del_btn").on("click", function () {
      var id = $(this).data("submit");
      $("#form_" + id).submit();
    });

    $('#myModal').on('show.bs.modal', function (e) {
      var id = e.relatedTarget.dataset.id;
      $("#del_btn").attr("data-submit", id);
    });

    $(document).on('change', ".frame-switch", function () {
      var checked = $(this).is(':checked');
      var id = $(this).data("id");

      $.ajax({
        type: "POST",
        url: "{{url('admin/sticker-status')}}",
        data: { checked: checked, id: id },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
          new PNotify({
            title: 'Success!',
            text: "Sticker Status Has Been Changed.",
            type: 'success'
          });
        },
      });
    });

    $("#aiGenerateForm").submit(function(e) {
      e.preventDefault();
      var categoryName = $(this).find('input[name="category_name"]').val();
      var btn = $("#aiGenerateBtn");
      
      btn.prop('disabled', true);
      $("#aiLoading").show();
      
      $.ajax({
        type: "POST",
        url: "{{url('admin/sticker/generate-ai')}}",
        data: {
          category_name: categoryName,
          _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          if (response.success) {
            new PNotify({
              title: 'Success!',
              text: response.message,
              type: 'success'
            });
            setTimeout(function() {
              location.reload();
            }, 1500);
          } else {
            new PNotify({
              title: 'Error!',
              text: response.message,
              type: 'error'
            });
            btn.prop('disabled', false);
            $("#aiLoading").hide();
          }
        },
        error: function(err) {
          new PNotify({
            title: 'Error!',
            text: 'Something went wrong. Please try again.',
            type: 'error'
          });
          btn.prop('disabled', false);
          $("#aiLoading").hide();
        }
      });
    });
  </script>
@endsection