@extends("layouts.app")

@section('extra_css')
<link href="{{ asset('assets/css/frame.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/clean-switch.css')}}">

<!-- <link href="{{ asset('assets/css/switcher.css') }}" rel="stylesheet"> -->
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
  background-image: url('searchicon.png');
  background-position: 14px 12px;
  background-repeat: no-repeat;
  font-size: 16px;
  padding: 14px 20px 12px 10px;
  border: none;
  width: 100%;
  border-bottom: 1px solid #056fed;
}

#myInput:focus {outline: 1px solid #056fed;}

.dropdown {
  position: relative;
  display: inline-block;
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

.dropdown-content a:hover {background-color: #056fed; color: white;}

.show {display: block;}

.select2-container--default .select2-selection--single {
  background-color:#007bff;
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
    <div class="card card-primary">
      <div class="card-header">
          <h3 class="card-title">
              Custom Frame
          </h3>
      </div> 
      
      <div class="card-body">
        <div style="float: left;">
          <select class="form-control" id="greeting_category_dropdown" name="greeting_category_dropdown" onchange="location = this.value;">
              <option value="{{url('admin/greeting')}}" @if(empty($name)) selected @endif>Select Custom Category</option>
              @foreach($greetingCategory as $c)
                  <option value="{{url('admin/custom-post-get/'.$c->id)}}" @if(!empty($name) && $name == $c->name) selected @endif>{{$c->name}}</option>
              @endforeach
          </select>
        </div>
      
        <!-- <div class="dropdown" style="float: left;">
          <button class="btn btn-primary dropdown-toggle dropbtn" onclick="myFunction()" type="button" id="dropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            @if(!empty($name)) {{$name}} @else Select Custom Category @endif
          </button>
          <div class="dropdown-menu dropdown-content" aria-labelledby="dropdownMenu" id="myDropdown"> 
            <input type="text" placeholder="Search.." id="myInput" onkeyup="filterFunction()">
              @foreach($greetingCategory as $c)
              <a class="dropdown-item" href="{{url('admin/custom-post-get/'.$c->id)}}">{{$c->name}}</a>
              @endforeach
          </div>
        </div> -->

        <div class="row d-flex justify-content-end mb-3">
          <div class="col-md-3 col-xs-12 text-right" style="float: right;">
            <div class="checkbox" style="width: 150px;margin-top: 5px;margin-left: 10px;float: left;right: 110px;position: absolute;">
              <input type="checkbox" id="checkall" style="width: 16px;height: 16px;">
              <label for="checkall">Select All</label>
            </div>
            <div class="dropdown" style="float:right">
              <button class="btn btn-primary dropdown-toggle btn_cust" type="button" data-toggle="dropdown">Action<span class="caret"></span></button>
              <ul class="dropdown-menu" style="right:0;left:auto;">
                <li><a class="dropdown-item" href="#" data-type="enable" data-toggle="modal" data-target="#enableModal">Enable</a></li>
                <li><a class="dropdown-item" href="#" data-type="enable" data-toggle="modal" data-target="#disableModal">Disable</a></li>
                <li><a class="dropdown-item" href="#" data-type="enable" data-toggle="modal" data-target="#deleteModal">Delete</a></li>
              </ul>
              {!! Form::open(['url' => 'admin/greeting-action','method'=>'POST','class'=>'form-horizontal','id'=>'form1']) !!}
              <input type="hidden" name="select_post" value="">
              <input type="hidden" name="action_type" value="">
              {!! Form::close() !!}
            </div>
          </div>
          <div>
            <a href="{{ route('greeting.create')}}" class="btn btn-success ml-2">Add New</a>
          </div>
        </div>

        <div class="row" id="frame_data">
          @foreach($data as $frame)
          <div class="col-lg-3 col-sm-6 col-xs-12">
            <div class="block_wallpaper add_wall_category" style="box-shadow:0px 3px 8px rgba(0, 0, 0, 0.3)">
            <div class="wall_category_block" style="z-index: 1">
                <div class="checkbox" style="float: right;z-index: 1">
                  <input type="checkbox" name="post_ids[]" id="checkbox0" value="{{$frame->id}}" class="post_ids mt-1" style="width: 16px;height: 16px;">
                </div>
              </div>
              <div class="wall_image_title">
                <h2 style="font-size: 20px">
                  {{$frame->greeting_category->name}} 
                </h2>
                <ul>
                  <li><a href="{{url('admin/greeting/'.$frame->id.'/edit')}}" data-toggle="tooltip" data-tooltip="Edit"><i class="fa fa-edit"></i></a></li>
                  <li><a href="#" data-id="{{$frame->id}}" class="btn_delete_a" data-toggle="modal" data-target="#myModal"><i class="fa fa-trash"></i></a></li>
                  <li>
                    <label class="cl-switch cl-switch-red">
                      <input type="checkbox" class="frame-switch" data-id="{{$frame->id}}" value="1" @if($frame->status==1) checked @endif>
                      <span class="switcher"></span>
                    </label>
                  </li>
                  <li>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input checkbox2" type="checkbox" data-id="{{$frame->id}}" value="1" @if($frame->paid==1) checked @endif>
                    </div>
                  </li>
                </ul>
                {!! Form::open(['url' => 'admin/greeting/'.$frame->id,'method'=>'DELETE','class'=>'form-horizontal','id'=>'form_'.$frame->id]) !!}
                {!! Form::hidden("id",$frame->id) !!}
                {!! Form::close() !!}
              </div>
              <span>
                <img src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$frame->frame_image)}}@else{{asset('uploads/'.$frame->frame_image)}}@endif"/>
              </span>
            </div>
          </div>
          @endforeach
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
@endsection

@section('script')
<script src="{{ asset('assets/js/jquery.switcher.js')}}"></script>
<script type="text/javascript">
    $('#greeting_category_dropdown').select2();

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

    function myFunction() {
      document.getElementById("myDropdown").classList.toggle("show");
    }

    function filterFunction() {
      var input, filter, ul, li, a, i;
      input = document.getElementById("myInput");
      filter = input.value.toUpperCase();
      div = document.getElementById("myDropdown");
      a = div.getElementsByTagName("a");
      for (i = 0; i < a.length; i++) {
        txtValue = a[i].textContent || a[i].innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
          a[i].style.display = "";
        } else {
          a[i].style.display = "none";
        }
      }
    }

    $(function(){
      $('#category').select2();
      $.switcher('.checkbox2');
    
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

        $.ajax({
          type: "POST",
          url: "{{url('admin/greeting-type')}}",
          data: { checked : checked , id : id},
          headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
          success: function(data) {
            if(data == 1)
            {
              new PNotify({
                title: 'Success!',
                text: "Custom Frame Set Paid",
                type: 'success'
              });
            }
            else
            {
              new PNotify({
                title: 'Success!',
                text: "Custom Frame Set Free",
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
          url: "{{url('admin/greeting-status')}}",
          data: { checked : checked , id : id},
          headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
          success: function(data) {
            new PNotify({
              title: 'Success!',
              text: "Custom Frame Status Has Been Changed.",
              type: 'success'
            });
          },
        });
      });
    });
</script>
@endsection
