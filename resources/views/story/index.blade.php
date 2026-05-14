@extends("layouts.app")

@section('extra_css')
<link href="{{ asset('assets/css/frame.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/clean-switch.css')}}">
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
              Story
          </h3>
      </div> 
      
      <div class="card-body">
        <div class="row d-flex justify-content-end mb-3">
          <!-- <div class="col-md-3">
            <label style="font-weight: 400;font-size: 1em;">Filter by Date Range</label>
            <input type="text" value="" name="date" class="form-control filter" id="date_range_filter" placeholder="mm/dd/YYYY - mm/dd/YYYY">
          </div> -->
          <div>
            <a href="{{ route('story.create')}}" class="btn btn-success ml-2">Add New</a>
          </div>
        </div>

        <div class="row mt-3">
          @foreach($data as $story)
            <?php
            $title = '';
            if($story->story_type == "festival" && $story->festival)
            {
              $title = $story->festival->title;
            }
            if($story->story_type == "category" && $story->category)
            {
              $title = $story->category->name;
            }
            if($story->story_type == "custom" && $story->custom)
            {
              $title = $story->custom->name;
            }
            if($story->story_type == "externalLink")
            {
              $title = $story->external_link_title;
            }
            if($story->story_type == "subscriptionPlan" && $story->subscription)
            {
              $title = $story->subscription->plan_name;
            }
            $imageCount = $story->story_images ? count($story->story_images) : ($story->image ? 1 : 0);
            ?>
          <div class="col-lg-3 col-sm-6 col-xs-12">
            <div class="block_wallpaper add_wall_category" style="box-shadow:0px 3px 8px rgba(0, 0, 0, 0.3)">
              <div class="wall_image_title" style="z-index: 1;background: rgba(0, 0, 0, 0.4);">
                <p class="my-auto">{{$title}}</p>
                <ul>
                  <li><a href="{{url('admin/story/'.$story->id.'/edit')}}" data-toggle="tooltip" data-tooltip="Edit"><i class="fa fa-edit"></i></a></li>
                  <li><a href="#" data-id="{{$story->id}}" class="btn_delete_a" data-toggle="modal" data-target="#myModal"><i class="fa fa-trash"></i></a></li>
                  <li>
                    <label class="cl-switch cl-switch-red">
                      <input type="checkbox" class="story-switch" data-id="{{$story->id}}" value="1" @if($story->status==1) checked @endif>
                      <span class="switcher"></span>
                    </label>
                  </li>
                </ul>
                {!! Form::open(['url' => 'admin/story/'.$story->id,'method'=>'DELETE','class'=>'form-horizontal','id'=>'form_'.$story->id]) !!}
                {!! Form::hidden("id",$story->id) !!}
                {!! Form::close() !!}
              </div>
              <span>
                <img src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$story->image)}} @else {{asset('uploads/'.$story->image)}} @endif"/>
              </span>
              {{-- Sort Order & Info Bar --}}
              <div class="d-flex align-items-center justify-content-between px-2 py-1" style="background:#f8f9fa;">
                <div class="d-flex align-items-center">
                  <small class="mr-1 text-muted" style="white-space:nowrap;">Order:</small>
                  <input type="number" class="form-control form-control-sm sort-order-input" 
                    data-id="{{$story->id}}" value="{{$story->sort_order}}" 
                    style="width:60px;height:28px;font-size:12px;">
                </div>
                <div>
                  @if($imageCount > 1)
                    <span class="badge badge-info" title="{{$imageCount}} slides"><i class="fa fa-images"></i> {{$imageCount}}</span>
                  @endif
                  @if($story->expire_type == 'never')
                    <span class="badge badge-success">∞</span>
                  @else
                    <span class="badge badge-warning">{{$story->expire_value}} {{$story->expire_type}}</span>
                  @endif
                </div>
              </div>
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
@endsection

@section('script')
<script type="text/javascript">
    $("#del_btn").on("click",function(){
        var id=$(this).data("submit");
        $("#form_"+id).submit();
    });

    $('#myModal').on('show.bs.modal', function(e) {
        var id = e.relatedTarget.dataset.id;
        $("#del_btn").attr("data-submit",id);
    });

    $(".story-switch").change(function(){
      var checked = $(this).is(':checked');
      var id = $(this).data("id");
     
      $.ajax({
        type: "POST",
        url: "{{url('admin/story-status')}}",
        data: { checked : checked , id : id},
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
          new PNotify({
            title: 'Success!',
            text: "Story Status Has Been Changed.",
            type: 'success'
          });
        },
      });
    });

    $(".sort-order-input").change(function(){
      var id = $(this).data("id");
      var sort_order = $(this).val();
      $.ajax({
        type: "POST",
        url: "{{url('admin/story-sort-order')}}",
        data: { id : id, sort_order : sort_order },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
          new PNotify({
            title: 'Success!',
            text: "Story Order Updated.",
            type: 'success'
          });
        },
      });
    });
</script>
@endsection
