@extends('layouts.app')

@section('extra_css')
<style type="text/css">

</style>
@endsection

@section('content')
<div class="container">
    <div class="card card-success">
        <div class="card-header">
            <h3 class="card-title">Add Video</h3>
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

            {!! Form::open(['route' => 'video.store','method'=>'post','files'=>true]) !!}
            <div class="row">
                <div class="col-12">
                    <div class="form-group row">
                        {!! Form::label('type','Select Type', ['class' => 'col-sm-3 col-form-label']) !!}
                        <div class="col-sm-4">
                            <select id="type" name="type" class="form-control" required>
                                <option value="">Select Type</option>
                                <option value="category">Category</option>
                                <option value="festival">Festival</option>
                                <option value="business">Business Category</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row" style="display:none;" id="sub_menu">
                <div class="col-12">
                    <div class="form-group" id="otherText">
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
                                    <option value="{{$l->id}}">{{$l->title}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-12">
                    <div class="form-group row">
                        {!! Form::label('video','Select Video', ['class' => 'col-sm-3 col-form-label']) !!}
                        <div class="col-sm-4"><input class="form-control" type="file" id="video" name="video" accept="video/*" required></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-3"></div>
                        <div class="col-sm-4" id="preview">
                            <video width="auto" height="120" controls>
                                <source src="mov_bbb.mp4" id="video_here">
                            </video>
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
        $('#type').select2();
        $('#language_id').select2();
    });

    $("#video").change(function () {
        var $source = $('#video_here');
        $source[0].src = URL.createObjectURL(this.files[0]);
        $source.parent()[0].load();
    });

    $("#type").change(function() {
        $('#sub_menu').css("display","block");
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