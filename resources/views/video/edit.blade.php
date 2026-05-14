@extends('layouts.app')

@section('extra_css')
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.11/summernote-lite.css" rel="stylesheet">
<style type="text/css">

</style>
@endsection

@section('content')
<div class="container">
    <div class="card card-success">
        <div class="card-header">
        <h3 class="card-title">Update Video</h3>
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

        {!! Form::open(['route' =>['video.update',$video->id],'method'=>'PATCH','files'=>true]) !!}
        {!! Form::hidden('id',$video->id)!!}
        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('type','Select Type', ['class' => 'col-sm-3 col-form-label']) !!}
                    <div class="col-sm-4">
                        <select id="type" name="type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="category" @if($video->type == "category") selected @endif>Category</option>
                            <option value="festival" @if($video->type == "festival") selected @endif>Festival</option>
                            <option value="business" @if($video->type == "business") selected @endif>Business Category</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group" id="otherText">
                    @if($video->category_id)
                        <div class="row">
                            {!! Form::label('category_id','Select Category', ['class' => 'col-sm-3 col-form-label']) !!}
                            <div class="col-sm-4">
                                <select id="category_id" name="category_id" class="form-control">
                                    <option value="">Select Category</option>
                                    @foreach($category as $c)
                                    <option value="{{$c->id}}" @if($video->category_id == $c->id) selected @endif>{{$c->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                    @if($video->festival_id)
                        <div class="row">
                            {!! Form::label('festival_id','Select Festival', ['class' => 'col-sm-3 col-form-label']) !!}
                            <div class="col-sm-4">
                                <select id="festival_id" name="festival_id" class="form-control">
                                    <option value="">Select Festival</option>
                                    @foreach($festival as $f)
                                    <option value="{{$f->id}}" @if($video->festival_id == $f->id) selected @endif>{{$f->title}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                    @if($video->business_category_id)
                        <div class="row">
                            {!! Form::label('business_category_id','Select Business Category', ['class' => 'col-sm-3 col-form-label']) !!}
                            <div class="col-sm-4">
                                <select id="business_category_id" name="business_category_id" class="form-control">
                                    <option value="">Select Business Category</option>
                                    @foreach($business_category as $c)
                                    <option value="{{$c->id}}" @if($video->business_category_id == $c->id) selected @endif>{{$c->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
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
                                <option value="{{$l->id}}" @if($l->id == $video->language_id) selected @endif>{{$l->title}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('video',' Select Video', ['class' => 'col-sm-3 col-form-label']) !!}
                    <div class="col-sm-4">
                        <input class="form-control" type="file" id="video" name="video">
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-3"></div>
                    <div class="col-sm-9" id="preview">
                        <div class="col-sm-4" id="preview">
                            <video width="auto" height="120" controls>
                                <source src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/video/'.$video->video)}} @else {{asset('uploads/video/'.$video->video)}} @endif" id="video_here">
                            </video>
                        </div>
                    </div>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.11/summernote-lite.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#type').select2();
        $('#category_id').select2();
        $('#festival_id').select2();
        $('#business_category_id').select2();
        $('#language_id').select2();
    });

    $("#video").change(function () {
        var $source = $('#video_here');
        $source[0].src = URL.createObjectURL(this.files[0]);
        $source.parent()[0].load();
    });

    $("#type").change(function() {
        $('#otherText').empty();
        if($(this).find("option:selected").text() == "Category")
        {
            $('#otherText').append('<div class="row"><div class="col-sm-3"><label class="col-form-label">Select Category</label></div><div class="col-sm-4"><select id="category_id" name="category_id" class="form-control" required><option value="">Select Category</option>@foreach($category as $c)<option value="{{$c->id}}">{{$c->name}}</option>@endforeach</select></div></div>');
        }
        if($(this).find("option:selected").text() == "Festival")
        {
            $('#otherText').append('<div class="row"><div class="col-sm-3"><label class="col-form-label">Select Festival</label></div><div class="col-sm-4"><select id="festival_id" name="festival_id" class="form-control" required><option value="">Select Festival</option>@foreach($festival as $f)<option value="{{$f->id}}">{{$f->title}}</option>@endforeach</select></div></div>');
        }
        if($(this).find("option:selected").text() == "Business Category")
        {
            $('#otherText').append('<div class="row"><div class="col-sm-3"><label class="col-form-label">Select Business Category</label></div><div class="col-sm-4"><select id="business_category_id" name="business_category_id" class="form-control" required><option value="">Select Business Category</option>@foreach($business_category as $c)<option value="{{$c->id}}">{{$c->name}}</option>@endforeach</select></div></div>');
        }
        $('#category_id').select2();
        $('#business_category_id').select2();
        $('#festival_id').select2();
    });
</script>
@endsection