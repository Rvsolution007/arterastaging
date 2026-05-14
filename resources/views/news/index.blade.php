@extends("layouts.app")

@section('extra_css')
<link href="{{ asset('assets/css/frame.css') }}" rel="stylesheet">
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
              News
          </h3>
      </div> 
      
      <div class="card-body">
        <div class="row d-flex justify-content-end mb-3">
          <!-- <div class="col-md-3">
            <label style="font-weight: 400;font-size: 1em;">Filter by Date Range</label>
            <input type="text" value="" name="date" class="form-control filter" id="date_range_filter" placeholder="mm/dd/YYYY - mm/dd/YYYY">
          </div> -->
          <div>
            <a href="{{ route('news.create')}}" class="btn btn-success ml-2">Add New</a>
          </div>
        </div>

        <div class="row mt-3">
          @foreach($data as $news)
          <div class="col-lg-3 col-sm-6 col-xs-12">
            <div class="block_wallpaper add_wall_category" style="box-shadow:0px 3px 8px rgba(0, 0, 0, 0.3)">
              <div class="wall_image_title" style="z-index: 1;background: rgba(0, 0, 0, 0.4);">
                <p class="my-auto">{{$news->title}}</p>
                <ul>
                  <li><a href="{{url('admin/news/'.$news->id.'/edit')}}" data-toggle="tooltip" data-tooltip="Edit"><i class="fa fa-edit"></i></a></li>
                  <li><a href="#" data-id="{{$news->id}}" class="btn_delete_a" data-toggle="modal" data-target="#myModal"><i class="fa fa-trash"></i></a></li>
                  <li><i class="fa fa-calendar-days mr-2"></i>{{date('d M, y',strtotime($news->date))}}</li>
                </ul>
                {!! Form::open(['url' => 'admin/news/'.$news->id,'method'=>'DELETE','class'=>'form-horizontal','id'=>'form_'.$news->id]) !!}
                {!! Form::hidden("id",$news->id) !!}
                {!! Form::close() !!}
              </div>
              <span>
                <img src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$news->image)}} @else {{asset('uploads/'.$news->image)}} @endif"/>
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
</script>
@endsection
