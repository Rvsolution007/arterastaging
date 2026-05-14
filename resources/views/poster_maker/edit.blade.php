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
    <h3 class="card-title">Update Frame</h3>
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

    {!! Form::open(['route' => ['poster-maker.update',$poster->id],'method'=>'PATCH','files'=>true]) !!}
    {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
    {!! Form::hidden('id',$poster->id)!!}
    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('poster_category','Select Frame Category', ['class' => 'col-sm-3 col-form-label','placeholder'=>'Enter Name']) !!}
                <div class="col-sm-4">
                    <select id="poster_category_id" name="poster_category_id" class="form-control" required>
                        <option value="">Select Frame Category</option>
                        @foreach($category as $c)
                            <option value="{{$c->id}}" @if($c->id == $poster->poster_category_id) selected @endif>{{$c->name}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('template_type','Template Type', ['class' => 'col-sm-3 col-form-label']) !!}
                <div class="col-sm-4">
                    <select id="template_type" name="template_type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="1:1" @if($poster->template_type == "1:1") selected @endif>1:1 (Square)</option>
                        <option value="2:1" @if($poster->template_type == "2:1") selected @endif>2:1 (Twitter Post)</option>
                        <option value="3:4" @if($poster->template_type == "3:4") selected @endif>3:4 (Pinterest)</option>
                        <option value="4:5" @if($poster->template_type == "4:5") selected @endif>4:5 (Portrait)</option>
                        <option value="9:16" @if($poster->template_type == "9:16") selected @endif>9:16 (Story)</option>
                        <option value="16:9" @if($poster->template_type == "16:9") selected @endif>16:9 (Landscap)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('theme', 'Frame Theme', ['class' => 'col-sm-3 col-form-label']) !!}
                <div class="col-sm-4">
                    <select id="theme" name="theme" class="form-control">
                        <option value="all" @if($poster->theme == 'all') selected @endif>All / Both</option>
                        <option value="light" @if($poster->theme == 'light') selected @endif>Light Theme</option>
                        <option value="dark" @if($poster->theme == 'dark') selected @endif>Dark Theme</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('requirements', 'Frame Requirements', ['class' => 'col-sm-3 col-form-label']) !!}
                <div class="col-sm-9 row">
                    <div class="col-sm-3">
                        <label>Addresses</label>
                        <input type="number" name="req_address" class="form-control" value="{{ $poster->req_address ?? 0 }}" min="0">
                    </div>
                    <div class="col-sm-3">
                        <label>Emails</label>
                        <input type="number" name="req_email" class="form-control" value="{{ $poster->req_email ?? 0 }}" min="0">
                    </div>
                    <div class="col-sm-3">
                        <label>Phones</label>
                        <input type="number" name="req_phone" class="form-control" value="{{ $poster->req_phone ?? 0 }}" min="0">
                    </div>
                    <div class="col-sm-3">
                        <label>Websites</label>
                        <input type="number" name="req_website" class="form-control" value="{{ $poster->req_website ?? 0 }}" min="0">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('zip',' Zip', ['class' => 'col-sm-3 col-form-label']) !!}
                <div class="col-sm-4">
                    <input class="form-control" type="file" id="zip" name="zip">
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('post_thumb','Post Thumb', ['class' => 'col-sm-3 col-form-label']) !!}
                <div class="col-sm-4">
                    <input class="form-control" type="file" id="post_thumb" onchange="imagePreview()" name="post_thumb" accept=".jpg, .png, jpeg, .PNG, .JPG, .JPEG">
                </div>
            </div>
            <div class="row">
                <div class="col-sm-3"></div>
                <div class="col-sm-4" id="preview"><img src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$poster->post_thumb)}} @else {{asset('uploads/'.$poster->post_thumb)}} @endif" alt="Image" class="shadow bg-white rounded" width="auto" height="150px"></div>
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
        $('#template_type').select2();
        $('#poster_category_id').select2();
    });

    function imagePreview(fileInput) 
    { 
        var total_file=document.getElementById("post_thumb").files.length;
        for(var i=0;i<total_file;i++)
        {
            $('#preview').html("<img class='img-responsive mr-3 shadow bg-white rounded' src='"+URL.createObjectURL(event.target.files[i])+"' style='width:150px;height:auto;'>");
        }
    }
</script>
@endsection