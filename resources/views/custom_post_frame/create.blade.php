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

hr.solid {
  border-top: 1px solid #bbb;
}
</style>
@endsection

@section('content')
<div class="container">
<div class="card card-success">
    <div class="card-header">
    <h3 class="card-title">Add Custom Frame Image</h3>
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

    {!! Form::open(['route' => 'custom-post-frame.store','method'=>'post','files'=>true]) !!}
    {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
    <input type="hidden" name="total" value="" id="total">
    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('custom_frame_type','Custom Frame Type', ['class' => 'col-sm-3 col-form-label','placeholder'=>'Enter Name']) !!}
                <div class="col-sm-4">
                    <select id="custom_frame_type" name="custom_frame_type" class="form-control" required>
                        <option value="">Select Custom Frame Type</option>
                        <option value="simple">Simple</option>
                        <option value="editable">Editable</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    </div>

    <div class="row">
        <div class="col-12">
            <div class="form-group" id="otherText">
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
        $('#custom_frame_type').select2();
    });

    window.newFileList = [];

    function imagePreview(fileInput) 
    { 
        var total_file=document.getElementById("frame_image").files.length;
        for(var i=0;i<total_file;i++)
        {
            $('#preview').append("<div class='notification'><img class='img-responsive mr-3 mt-3' src='"+URL.createObjectURL(event.target.files[i])+"' style='width:150px;height:auto;'><p class='remove pull-right bg-danger' style='cursor:pointer;position: absolute;top: 15px;right: 15px;padding: 6px 10px;' id='"+i+"'><i class='fa fa-close'></i></p></span></div>");
        }
    }

    function imagePreview1(val) 
    { 
        $('#plus-btn').removeClass('d-none');
        if(val)
        {
            var total_file=document.getElementById("post_thumb"+val).files.length;
        }
        else
        {
            var total_file=document.getElementById("post_thumb").files.length;
        }
        for(var i=0;i<total_file;i++)
        {
            if(val)
            {
                $('#preview1'+val).empty();
                $('#preview1'+val).append("<div class='notification'><img class='img-responsive mr-3 mt-3' src='"+URL.createObjectURL(event.target.files[i])+"' style='width:150px;height:auto;'></span></div>");
            }
            else
            {
                $('#preview1').empty();
                $('#preview1').append("<div class='notification'><img class='img-responsive mr-3 mt-1' src='"+URL.createObjectURL(event.target.files[i])+"' style='width:150px;height:auto;'></span></div>");
            }
        }
    }

    $('.remove').css('cursor', 'pointer');

    $(document).on('click', 'p.remove', function( e ) {
        e.preventDefault();
        var id = $(this).attr('id');
        $(this).closest( 'div.notification' ).remove();
        var input = document.getElementById('frame_image');
        var files = input.files;
        if (files.length) {
            if (typeof files[id] !== 'undefined') {
                window.newFileList.push(files[id].name)
            }
        }
        
        document.getElementById('deleted_file_ids').value = JSON.stringify(window.newFileList);

        if($(".notification").length == 0) document.getElementById('frame_image').value="";
    });

    $("#custom_frame_type").change(function() {
        $('#otherText').empty();
        if($(this).find("option:selected").text() == "Simple")
        {
            $('#otherText').append('<div class="row"><div class="col-sm-3"><label class="col-form-label">Select Custom Category</label></div><div class="col-sm-4"><select id="custom_post_id" name="custom_post_id" class="form-control" required><option value="">Select Custom Category</option>@foreach($customPost as $c)<option value="{{$c->id}}">{{$c->name}}</option>@endforeach</select></div></div>');
            $('#otherText').append('<div class="row mt-3"><div class="col-sm-3"><label class="col-form-label">Select Language</label></div><div class="col-sm-4"><select id="language_id" name="language_id" class="form-control" required><option value="">Select Language</option>@foreach($language as $l)<option value="{{$l->id}}">{{$l->title}}</option>@endforeach</select></div></div>');
            $('#otherText').append('<div class="row mt-3"><div class="col-sm-3"><label class="col-form-label">Select Frame Image</label></div><div class="col-sm-4"><input class="form-control" type="file" id="frame_image" name="frame_image[]" onchange="imagePreview()" accept=".jpg, .png, jpeg, .PNG, .JPG, .JPEG" multiple required></div><input type="hidden" name="deleted_file_ids" class="deleted_file_ids" id="deleted_file_ids" value=""></div>');
            $('#otherText').append('<div class="row mt-3"><div class="col-sm-3" style="margin-top:-20px;"><small class="form-text text-muted">Recommendation Size:<ul><li>1024*1024 px (Square)</li><li>1080*1350 px (Portrait)</li><li>1280*720 px (Landscap)</li><li>1080*1920 px (Story)</li></ul></small></div><div class="col-sm-9"><div class="border p-3" id="preview"></div></div></div>');
            $('#custom_post_id').select2();
            $('#language_id').select2();
        }
        if($(this).find("option:selected").text() == "Editable")
        {
            $('#otherText').append('<div class="row"><div class="col-sm-3"><label class="col-form-label">Select Custom Category</label></div><div class="col-sm-4"><select id="custom_post_id" name="custom_post_id" class="form-control" required><option value="">Select Custom Category</option>@foreach($customPost as $c)<option value="{{$c->id}}">{{$c->name}}</option>@endforeach</select></div></div>');
            $('#otherText').append('<div class="row mt-3"><div class="col-sm-3"><label class="col-form-label">Select Language</label></div><div class="col-sm-4"><select id="language_id" name="language_id" class="form-control" required><option value="">Select Language</option>@foreach($language as $l)<option value="{{$l->id}}">{{$l->title}}</option>@endforeach</select></div></div>');
            $('#otherText').append('<div class="row mt-3"><div class="col-sm-3"><label class="col-form-label">Select Zip</label></div><div class="col-sm-4"><input class="form-control" type="file" id="zip" name="zip" accept=".zip, .ZIP" required></div></div>');
            $('#otherText').append('<div class="row mt-3"><div class="col-12"><div class="form-group row"><label for="post_thumb" class="col-sm-3 col-form-label">Post Thumb</label><div class="col-sm-4"><input class="form-control" type="file" id="post_thumb" onchange="imagePreview1()" name="post_thumb" accept=".jpg, .png, jpeg, .PNG, .JPG, .JPEG" required></div></div></div></div>');
            $('#otherText').append('<div class="row"><div class="col-sm-3" style="margin-top:-20px;"><small class="form-text text-muted">Recommendation Size:<ul><li>1024*1024 px (Square)</li><li>1080*1350 px (Portrait)</li><li>1280*720 px (Landscap)</li><li>1080*1920 px (Story)</li></ul></small></div><div class="col-sm-9"><div id="preview1"></div></div></div>');
            $('#otherText').append('<div id="add-container"></div><div class="row"><div class="col-md-1 mt-3"><button type="button" class="btn btn-primary d-none" id="plus-btn" onClick="plusBtn()"><i class="fas fa-plus"></i></button></div></div>');
            $('#custom_post_id').select2();
            $('#language_id').select2();
        }
    });

    var count = 1;
    var total = [];
    function plusBtn()
    {
        $('#add-container').append('<hr class="solid">');
        $('#add-container').append('<div class="row mt-3"><div class="col-12"><div class="form-group row"><label class="col-sm-3 col-form-label">Select Language</label><div class="col-sm-4"><select id="language_id'+count+'" name="language_id'+count+'" class="form-control" required><option value="">Select Language</option>@foreach($language as $l)<option value="{{$l->id}}">{{$l->title}}</option>@endforeach</select></div></div></div></div>');
        $('#add-container').append('<div class="row"><div class="col-sm-3"><label class="col-form-label">Select Zip</label></div><div class="col-sm-4"><input class="form-control" type="file" id="zip'+count+'" name="zip'+count+'" accept=".zip, .ZIP" required></div></div>');
        $('#add-container').append('<div class="row mt-3"><div class="col-12"><div class="form-group row"><label for="post_thumb" class="col-sm-3 col-form-label">Post Thumb</label><div class="col-sm-4"><input class="form-control" type="file" id="post_thumb'+count+'" onchange="imagePreview1('+count+')" name="post_thumb'+count+'" accept=".jpg, .png, jpeg, .PNG, .JPG, .JPEG" required></div></div></div></div>');
        $('#add-container').append('<div class="row"><div class="col-sm-3" style="margin-top:-20px;"><small class="form-text text-muted">Recommendation Size:<ul><li>1024*1024 px (Square)</li><li>1080*1350 px (Portrait)</li><li>1280*720 px (Landscap)</li><li>1080*1920 px (Story)</li></ul></small></div><div class="col-sm-9"><div id="preview1'+count+'"></div></div></div>');
        $('#language_id'+count).select2();
        total.push(count);
        $('#total').val(total);
        count++;
    }
</script>
@endsection