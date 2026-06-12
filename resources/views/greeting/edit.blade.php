@extends('layouts.app')

@section('extra_css')
<style type="text/css">

</style>
@endsection

@section('content')
<div class="container">
<div class="card card-success">
    <div class="card-header">
    <h3 class="card-title">Update Custom Frame Image</h3>
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

    {!! Form::open(['route' =>['greeting.update',$greeting->id],'method'=>'PATCH','files'=>true]) !!}
    {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
    {!! Form::hidden('id',$greeting->id)!!}
    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('greeting_type','Custom Frame Type', ['class' => 'col-sm-3 col-form-label','placeholder'=>'Enter Name']) !!}
                <div class="col-sm-4">
                    <select id="greeting_type" name="greeting_type" class="form-control" required>
                        <option value="">Select Custom Frame Type</option>
                        <option value="simple" @if($greeting->greeting_type == "simple") selected @endif>Simple</option>
                        <option value="editable" @if($greeting->greeting_type == "editable") selected @endif>Editable</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    </div>

    <div class="row">
        <div class="col-12">
            <div class="form-group" id="otherText">
                @if($greeting->greeting_type == "simple")
                <div class="row">
                    <div class="col-12">
                        <div class="form-group row">
                            {!! Form::label('custom','Select Custom Category', ['class' => 'col-sm-3 col-form-label','placeholder'=>'Enter Name']) !!}
                            <div class="col-sm-4">
                                <select id="greeting_category_id" name="greeting_category_id" class="form-control" required>
                                    <option value="">Select Custom Category</option>
                                    @foreach($greetingCategory as $c)
                                        <option value="{{$c->id}}" @if($c->id == $greeting->greeting_category_id) selected @endif>{{$c->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="form-group row">
                            {!! Form::label('language','Select Language', ['class' => 'col-sm-3 col-form-label','placeholder'=>'Enter Name']) !!}
                            <div class="col-sm-4">
                                <select id="language_id" name="language_id" class="form-control" required>
                                    <option value="">Select Language</option>
                                    @foreach($language as $l)
                                        <option value="{{$l->id}}" @if($l->id == $greeting->language_id) selected @endif>{{$l->title}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="form-group row">
                            {!! Form::label('frame_image',' Select Frame Image', ['class' => 'col-sm-3 col-form-label']) !!}
                            <div class="col-sm-4"><input class="form-control" type="file" id="frame_image" name="frame_image"></div>
                        </div>
                        <div class="row">
                            <div class="col-sm-3" style="margin-top:-20px;">
                                <small class="form-text text-muted">
                                    Recommendation Size: 
                                    <ul>
                                        <li>1024*1024 px (Square)</li>
                                        <li>1080*1350 px (Portrait)</li>
                                        <li>1280*720 px (Landscap)</li>
                                        <li>1080*1920 px (Story)</li>
                                    </ul>
                                </small>
                            </div>
                            <div class="col-sm-9" id="preview"><img src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$greeting->frame_image)}} @else {{asset('uploads/'.$greeting->frame_image)}} @endif" alt="Image" class="shadow bg-white rounded" width="auto" height="120px"></div>
                        </div>
                    </div>
                </div>
                @endif

                @if($greeting->greeting_type == "editable")
                <div class="row">
                    <div class="col-12">
                        <div class="form-group row">
                            {!! Form::label('custom','Select Custom Category', ['class' => 'col-sm-3 col-form-label','placeholder'=>'Enter Name']) !!}
                            <div class="col-sm-4">
                                <select id="greeting_category_id" name="greeting_category_id" class="form-control" required>
                                    <option value="">Select Custom Category</option>
                                    @foreach($greetingCategory as $c)
                                        <option value="{{$c->id}}" @if($c->id == $greeting->greeting_category_id) selected @endif>{{$c->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="form-group row">
                            {!! Form::label('language','Select Language', ['class' => 'col-sm-3 col-form-label','placeholder'=>'Enter Name']) !!}
                            <div class="col-sm-4">
                                <select id="language_id" name="language_id" class="form-control" required>
                                    <option value="">Select Language</option>
                                    @foreach($language as $l)
                                        <option value="{{$l->id}}" @if($l->id == $greeting->language_id) selected @endif>{{$l->title}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="form-group row">
                            <div class="col-sm-3">
                                <label class="col-form-label">Select Zip</label>
                            </div>
                            <div class="col-sm-4">
                                <input class="form-control" type="file" id="zip" name="zip" accept=".zip, .ZIP">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="form-group row">
                            <label for="post_thumb" class="col-sm-3 col-form-label">Post Thumb</label>
                            <div class="col-sm-4">
                                <input class="form-control" type="file" id="post_thumb" onchange="imagePreview1()" name="post_thumb" accept=".jpg, .png, jpeg, .PNG, .JPG, .JPEG">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-3" style="margin-top:-20px;">
                                <small class="form-text text-muted">
                                    Recommendation Size: 
                                    <ul>
                                        <li>1024*1024 px (Square)</li>
                                        <li>1080*1350 px (Portrait)</li>
                                        <li>1280*720 px (Landscap)</li>
                                        <li>1080*1920 px (Story)</li>
                                    </ul>
                                </small>
                            </div>
                            <div class="col-sm-9" id="preview1"><img src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$greeting->frame_image)}} @else {{asset('uploads/'.$greeting->frame_image)}} @endif" alt="Image" class="shadow bg-white rounded" width="auto" height="120px"></div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 m-3 text-center">
        @if(optional(Auth::user())->user_type == "Demo")
        <button type="button" class="btn btn-success ToastrButton">Update</button>
        @else
        {!! Form::submit('Update', ['class' => 'btn btn-success']) !!}
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
        $('#greeting_category_id').select2();
        $('#language_id').select2();
        $('#greeting_type').select2();
    });

    function imagePreview(fileInput) 
    { 
        if (fileInput.files && fileInput.files[0]) 
        {
            var fileReader = new FileReader();
            fileReader.onload = function (event) 
            {
                $('#preview').html('<img src="'+event.target.result+'" class="shadow bg-white rounded" width="auto" alt="Select Image" height="120px"/>');
            };
            fileReader.readAsDataURL(fileInput.files[0]);
        }
    }

    $("#frame_image").change(function () {
        imagePreview(this);
    });

    function imagePreview1() 
    { 
        var total_file=document.getElementById("post_thumb").files.length;
        for(var i=0;i<total_file;i++)
        {
            $('#preview1').empty();
            $('#preview1').append("<img class='shadow bg-white rounded' src='"+URL.createObjectURL(event.target.files[i])+"' style='width:120px;height:auto;'>");
        }
    }

    $("#greeting_type").change(function() {
        $('#otherText').empty();
        if($(this).find("option:selected").text() == "Simple")
        {
            $('#otherText').append('<div class="row"><div class="col-sm-3"><label class="col-form-label">Select Custom Category</label></div><div class="col-sm-4"><select id="greeting_category_id" name="greeting_category_id" class="form-control" required><option value="">Select Custom Category</option>@foreach($greetingCategory as $c)<option value="{{$c->id}}">{{$c->name}}</option>@endforeach</select></div></div>');
            $('#otherText').append('<div class="row mt-3"><div class="col-sm-3"><label class="col-form-label">Select Language</label></div><div class="col-sm-4"><select id="language_id" name="language_id" class="form-control" required><option value="">Select Language</option>@foreach($language as $l)<option value="{{$l->id}}">{{$l->title}}</option>@endforeach</select></div></div>');
            $('#otherText').append('<div class="row mt-3"><div class="col-sm-3"><label class="col-form-label">Select Frame Image</label></div><div class="col-sm-4"><input class="form-control" type="file" id="frame_image_one" name="frame_image"></div></div>');
            $('#otherText').append('<div class="row mt-3"><div class="col-sm-3" style="margin-top:-20px;"><small class="form-text text-muted">Recommendation Size:<ul><li>1024*1024 px (Square)</li><li>1080*1350 px (Portrait)</li><li>1280*720 px (Landscap)</li><li>1080*1920 px (Story)</li></ul></small></div><div class="col-sm-9" id="preview1"></div></div>');
            $('#greeting_category_id').select2();
            $('#language_id').select2();

            function imagePreviewOne(fileInput) 
            { 
                if (fileInput.files && fileInput.files[0]) 
                {
                    var fileReader = new FileReader();
                    fileReader.onload = function (event) 
                    {
                        $('#preview1').html('<img src="'+event.target.result+'" class="shadow bg-white rounded" width="auto" alt="Select Image" height="120px"/>');
                    };
                    fileReader.readAsDataURL(fileInput.files[0]);
                }
            }

            $("#frame_image_one").change(function () {
                imagePreviewOne(this);
            });
        }
        if($(this).find("option:selected").text() == "Editable")
        {
            $('#otherText').append('<div class="row"><div class="col-sm-3"><label class="col-form-label">Select Custom Category</label></div><div class="col-sm-4"><select id="greeting_category_id" name="greeting_category_id" class="form-control" required><option value="">Select Custom Category</option>@foreach($greetingCategory as $c)<option value="{{$c->id}}">{{$c->name}}</option>@endforeach</select></div></div>');
            $('#otherText').append('<div class="row mt-3"><div class="col-sm-3"><label class="col-form-label">Select Language</label></div><div class="col-sm-4"><select id="language_id" name="language_id" class="form-control" required><option value="">Select Language</option>@foreach($language as $l)<option value="{{$l->id}}">{{$l->title}}</option>@endforeach</select></div></div>');
            $('#otherText').append('<div class="row mt-3"><div class="col-sm-3"><label class="col-form-label">Select Zip</label></div><div class="col-sm-4"><input class="form-control" type="file" id="zip" name="zip" accept=".zip, .ZIP" required></div></div>');
            $('#otherText').append('<div class="row mt-3"><div class="col-12"><div class="form-group row"><label for="post_thumb" class="col-sm-3 col-form-label">Post Thumb</label><div class="col-sm-4"><input class="form-control" type="file" id="post_thumb" onchange="imagePreview1()" name="post_thumb" accept=".jpg, .png, jpeg, .PNG, .JPG, .JPEG" required></div></div></div></div>');
            $('#otherText').append('<div class="row"><div class="col-sm-3" style="margin-top:-20px;"><small class="form-text text-muted">Recommendation Size:<ul><li>1024*1024 px (Square)</li><li>1080*1350 px (Portrait)</li><li>1280*720 px (Landscap)</li><li>1080*1920 px (Story)</li></ul></small></div><div class="col-sm-9"><div id="preview1"></div></div></div>');
            $('#greeting_category_id').select2();
            $('#language_id').select2();
        }
    });
</script>
@endsection
