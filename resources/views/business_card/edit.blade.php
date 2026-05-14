@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card card-success">
        <div class="card-header">
            <h3 class="card-title">Update Business Card</h3>
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

        {!! Form::open(['route' =>['business-card.update',$business_card->id],'method'=>'PATCH','files'=>true]) !!}
        {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
        {!! Form::hidden('id',$business_card->id)!!}
        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('name','Name', ['class' => 'col-sm-2 col-form-label']) !!}
                    <div class="col-sm-4">
                        {!! Form::text('name',$business_card->name,['class' => 'form-control','placeholder'=>'Enter Business Card Name','required','autocomplete'=>"off","readonly"]) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-group row">
                    {!! Form::label('icon',' Select Image', ['class' => 'col-sm-2 col-form-label']) !!}
                    <div class="col-sm-4"><input class="form-control" type="file" id="image" name="image"></div>
                </div>
                <div class="row"><div class="col-sm-2"></div><div class="col-sm-4" id="preview"> @if($business_card->image)<img src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean'){{\Storage::disk('spaces')->url('uploads/'.$business_card->image)}} @else {{asset('uploads/'.$business_card->image)}} @endif" alt="Image" class="shadow bg-white rounded" width="auto" height="120px">@endif</div></div>  
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

    $("#image").change(function () {
        imagePreview(this);
    });
</script>
@endsection