@extends('layouts.app')

@section('extra_css')
<style type="text/css">

</style>
@endsection

@section('content')
<div class="container">
    <div class="card card-success">
        <div class="card-header">
            <h3 class="card-title">Edit Whatsapp Message</h3>
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

        {!! Form::open(['route' =>['whatsapp-message.update',$message->id],'method'=>'PATCH','files'=>true]) !!}
        {!! Form::hidden('user_id',optional(Auth::user())->id)!!}
        {!! Form::hidden('id',$message->id)!!}
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="example-text-input" class="form-control-label">Message <b style="color:red">*</b></label>
                    <textarea class="form-control" name="message" type="text" placeholder="Write message here" required >{{$message->message}}</textarea>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group" id="type_lay">
                    <label for="example-text-input" class="form-control-label">Select Type <b style="color:red">*</b></label>
                    <select class="form-control" id="type" name="type" onchange="onTypeChange()" required>
                        <option value="text" @if($message->type == "text") selected @endif>Text Message</option>
                        <option value="media" @if($message->type == "media") selected @endif>Media Message</option>
                        <option value="button" @if($message->type == "button") selected @endif>Button Message</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row" id="action_lay"> 
            @if($message->type == "media")
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="example-text-input" class="form-control-label">Image <b style="color:red">*</b></label>
                        <input class="form-control" type="file" id="image_posts" name="image" accept=".jpg, .png, jpeg, .PNG, .JPG, .JPEG" onchange="fileValidation()" required>
                    </div>
                </div>
            @endif
            @if($message->type == "button")
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="example-text-input" class="form-control-label">Btn1 <b style="color:red">*</b></label>
                        <input class="form-control" name="btn1" type="text" placeholder="Open Now" value="{{$message->btn1}}" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group" id="btn1type_lay">
                        <label for="example-text-input" class="form-control-label">Btn1 Type <b style="color:red">*</b></label>
                        <select class="form-control" id="btn1type" name="btn1type" required>
                            <option value="replyButton" @if($message->btn1_type == "replyButton") selected @endif>Reply Button</option>
                            <option value="callButton" @if($message->btn1_type == "callButton") selected @endif>Call Button</option>
                            <option value="urlButton" @if($message->btn1_type == "urlButton") selected @endif>Url Button</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="example-text-input" class="form-control-label">Btn1 Value <b style="color:red">*</b></label>
                        <input class="form-control" name="btn1value" type="text" value="{{$message->btn1_value}}" placeholder="https://brandpeak.in/" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="example-text-input" class="form-control-label">Btn2</label>
                        <input class="form-control" name="btn2" type="text" placeholder="Call Now" value="{{$message->btn2}}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group" id="btn2type_lay">
                        <label for="example-text-input" class="form-control-label">Btn2 Type</label>
                        <select class="form-control" id="btn2type" name="btn2type">
                            <option value="replyButton" @if($message->btn2_type == "replyButton") selected @endif>Reply Button</option>
                            <option value="callButton" @if($message->btn2_type == "callButton") selected @endif>Call Button</option>
                            <option value="urlButton" @if($message->btn2_type == "urlButton") selected @endif>Url Button</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="example-text-input" class="form-control-label">Btn2 Value</label>
                        <input class="form-control" name="btn2value" type="text" value="{{$message->btn2_value}}" placeholder="+919999999999">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="example-text-input" class="form-control-label">Image <b style="color:red"></b></label>
                        <input class="form-control" type="file" id="image_posts" name="image" accept=".jpg, .png, jpeg, .PNG, .JPG, .JPEG" onchange="fileValidation()">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="example-text-input" class="form-control-label">Footer</label>
                        <input class="form-control" name="footer" type="text" value="{{$message->footer}}" placeholder="Footer">
                    </div>
                </div>
            @endif
        </div>
                        
        <div class="col-md-6 card-body mt-n3">
            <div class="row mb" id="previewImages">
                @if($message->type == "media")
                <div class='imageCard col-xl-4 col-sm-4 mb-2'>
                  <div class='avatar avatar-xxl position-relative'>
                    <img src='{{url("uploads/".$message->image)}}' id='imge' class='border-radius-md' alt='image' height='120px' width='auto'>
                  </div>
                </div>
                @endif
                @if($message->type == "button")
                <div class='imageCard col-xl-4 col-sm-4 mb-2'>
                  <div class='avatar avatar-xxl position-relative'>
                    <img src='{{url("uploads/".$message->image)}}' id='imge' class='border-radius-md' alt='image' height='120px' width='auto'>
                  </div>
                </div>
                @endif
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
        $('#type').select2();

        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
        });
    });

    function onTypeChange() 
    {
        d = document.getElementById("type").value;
                
        if(d == 'text')
        {
            $('#action_lay').empty();
        }
        else if(d == 'media')
        {
            $('#action_lay').empty();
            $('#previewImages').empty();
            $('#action_lay').append(
                
            '<div class="col-md-6">'+
              '<div class="form-group">'+
                '<label for="example-text-input" class="form-control-label">Image <b style="color:red">*</b></label>'+
                '<input class="form-control" type="file" id="image_posts" name="image" accept=".jpg, .png, jpeg, .PNG, .JPG, .JPEG" onchange="fileValidation()" required>'+
              '</div>'+
            '</div>'
            
            );
        }
        else if(d == 'button')
        {
            $('#action_lay').empty();
            $('#action_lay').append(
                '<div class="col-md-4">'+
                  '<div class="form-group">'+
                    '<label for="example-text-input" class="form-control-label">Btn1 <b style="color:red">*</b></label>'+
                    '<input class="form-control" name="btn1" type="text" placeholder="Open Now" required>'+
                  '</div>'+
                '</div>'+
                '<div class="col-md-4">'+
                  '<div class="form-group" id="btn1type_lay">'+
                    '<label for="example-text-input" class="form-control-label">Btn1 Type <b style="color:red">*</b></label>'+
                        '<select class="form-control" id="btn1type" name="btn1type" required>'+
                            '<option value="replyButton" >Reply Button</option>'+
                            '<option value="callButton" >Call Button</option>'+
                            '<option value="urlButton" selected>Url Button</option>'+
                        '</select>'+
                  '</div>'+
                '</div>'+
                '<div class="col-md-4">'+
                  '<div class="form-group">'+
                    '<label for="example-text-input" class="form-control-label">Btn1 Value <b style="color:red">*</b></label>'+
                    '<input class="form-control" name="btn1value" type="text" placeholder="https://brandpeak.in/" required>'+
                  '</div>'+
                '</div>'+
                '<div class="col-md-4">'+
                  '<div class="form-group">'+
                    '<label for="example-text-input" class="form-control-label">Btn2</label>'+
                    '<input class="form-control" name="btn2" type="text" placeholder="Call Now">'+
                  '</div>'+
                '</div>'+
                '<div class="col-md-4">'+
                  '<div class="form-group" id="btn2type_lay">'+
                    '<label for="example-text-input" class="form-control-label">Btn2 Type</label>'+
                        '<select class="form-control" id="btn2type" name="btn2type">'+
                            '<option value="replyButton" >Reply Button</option>'+
                            '<option value="callButton" selected>Call Button</option>'+
                            '<option value="urlButton" >Url Button</option>'+
                        '</select>'+
                  '</div>'+
                '</div>'+
                '<div class="col-md-4">'+
                  '<div class="form-group">'+
                    '<label for="example-text-input" class="form-control-label">Btn2 Value</label>'+
                    '<input class="form-control" name="btn2value" type="text" placeholder="+919999999999">'+
                  '</div>'+
                '</div>'+
                '<div class="col-md-6">'+
                  '<div class="form-group">'+
                    '<label for="example-text-input" class="form-control-label">Image <b style="color:red"></b></label>'+
                    '<input class="form-control" type="file" id="image_posts" name="image" accept=".jpg, .png, jpeg, .PNG, .JPG, .JPEG" onchange="fileValidation()">'+
                  '</div>'+
                '</div>'+
                '<div class="col-md-6">'+
                  '<div class="form-group">'+
                    '<label for="example-text-input" class="form-control-label">Footer</label>'+
                    '<input class="form-control" name="footer" type="text" placeholder="Footer">'+
                  '</div>'+
                '</div>'
            );
            $('#btn1type').select2();
            $('#btn2type').select2();
        }
    }

    function fileValidation(){
        var fileInput = document.getElementById('image_posts');
        if (fileInput.files && fileInput.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById("previewImages").innerHTML = "";
                $('#previewImages').append(
                "<div class='imageCard col-xl-4 col-sm-4 mb-2'>"+
                  "<div class='avatar avatar-xxl position-relative'>"+
                    "<img src='"+e.target.result+"' id='imge' class='border-radius-md' alt='image' height='120px' width='auto'>"+
                  "</div>"+
                "</div>");
            };
            reader.readAsDataURL(fileInput.files[0]);
        }
    }

</script>
@endsection