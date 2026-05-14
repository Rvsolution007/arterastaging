@extends('layouts.app')

@section('extra_css')
<style type="text/css">
.notification {
  position: relative;
  display: inline-block;
}

.notification .badge {
  position: absolute;
  top: -7px;
  right: -7px;
}
</style>
@endsection

@section('content')
<div class="container">
<div class="card card-success">
    <div class="card-header">
    <h3 class="card-title">Add Sticker</h3>
    </div>

    <div class="card-body">
    @if (count($errors) > 0)
        <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        </div>
    @endif

    {!! Form::open(['route' => 'sticker.store','method'=>'post','files'=>true]) !!}
    {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('category','Select Sticker Category', ['class' => 'col-sm-3 col-form-label','placeholder'=>'Enter Name']) !!}
                <div class="col-sm-4">
                    <select id="sticker_category_id" name="sticker_category_id" class="form-control" required>
                        <option value="">Select Sticker Category</option>
                        @foreach($category as $c)
                            <option value="{{$c->id}}">{{$c->name}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('image',' Select Image', ['class' => 'col-sm-3 col-form-label']) !!}
                <div class="col-sm-4">
                    <input class="form-control" type="file" id="image" name="image[]" onchange="imagePreview()" accept=".jpg, .png, jpeg, .PNG, .JPG, .JPEG" multiple required>
                </div>
                <input type="hidden" name="deleted_file_ids" class="deleted_file_ids" id="deleted_file_ids"  value="">
            </div>
            <div class="row">
                <div class="col-sm-3"></div>
                <div class="col-sm-9">
                    <div class="border p-3" id="preview"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 m-3 text-center">
        @if(optional(Auth::user())->user_type == "Demo")
        <button type="button" class="btn btn-success ToastrButton">Save</button>
        @else 
        {!! Form::submit('Save', ['class' => 'btn btn-success']) !!}
        @endif
        </div>
    </div>
    {!! Form::close() !!}
    </div>
</div>
</div>
@endsection

@section("script")
<script type="text/javascript">
    $(document).ready(function() {
        $('#sticker_category_id').select2();
    });

    window.newFileList = [];

    function imagePreview(fileInput) 
    { 
        var total_file=document.getElementById("image").files.length;
        for(var i=0;i<total_file;i++)
        {
            $('#preview').append("<div class='notification'><img class='img-responsive mr-3 mt-3' src='"+URL.createObjectURL(event.target.files[i])+"' style='width:150px;height:auto;'><p class='remove pull-right bg-danger' style='cursor:pointer;position: absolute;top: 15px;right: 15px;padding: 6px 10px;' id='"+i+"'><i class='fa fa-close'></i></p></span></div>");
        }
    }

    $('.remove').css('cursor', 'pointer');

    $(document).on('click', 'p.remove', function( e ) {
        e.preventDefault();
        var id = $(this).attr('id');
        $(this).closest( 'div.notification' ).remove();
        var input = document.getElementById('image');
        var files = input.files;
        if (files.length) {
            if (typeof files[id] !== 'undefined') {
                window.newFileList.push(files[id].name)
            }
        }
        
        document.getElementById('deleted_file_ids').value = JSON.stringify(window.newFileList);

        if($(".notification").length == 0) document.getElementById('image').value="";
    });
</script>
@endsection