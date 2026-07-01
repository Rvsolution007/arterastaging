@extends('layouts.app')

@section('extra_css')
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
<style type="text/css">
.notification {
  position: relative;
  display: inline-block;
  cursor: pointer;
}

.notification .badge {
  position: absolute;
  top: -7px;
  right: -7px;
}
.notification img {
  transition: transform 0.2s;
}
.notification:hover img {
  opacity: 0.8;
}
.crop-icon-overlay {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: rgba(0,0,0,0.6);
  color: white;
  padding: 10px;
  border-radius: 50%;
  opacity: 0;
  transition: opacity 0.2s;
  pointer-events: none;
}
.notification:hover .crop-icon-overlay {
  opacity: 1;
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
                {!! Form::label('keywords','Keywords (Tags)', ['class' => 'col-sm-3 col-form-label']) !!}
                <div class="col-sm-4">
                    {!! Form::text('keywords', null, ['class' => 'form-control', 'placeholder' => 'e.g. hiring, suit, professional']) !!}
                    <small class="text-muted">These tags help in searching. (Applies to all images uploaded together)</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="form-group row">
                {!! Form::label('image',' Select Image', ['class' => 'col-sm-3 col-form-label']) !!}
                <div class="col-sm-4">
                    <input class="form-control" type="file" id="image" name="image[]" onchange="imagePreview(event)" accept=".jpg, .png, jpeg, .PNG, .JPG, .JPEG" multiple required>
                    <small class="text-muted d-block mt-1"><i class="fa fa-info-circle text-info"></i> Note: All images will be automatically compressed and converted to <b>.webp</b> format for faster loading without quality loss.</small>
                </div>
                <input type="hidden" name="deleted_file_ids" class="deleted_file_ids" id="deleted_file_ids"  value="">
                <div id="cropped_inputs_container"></div>
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

<!-- Cropper Modal -->
<div class="modal fade" id="cropperModal" tabindex="-1" role="dialog" aria-labelledby="cropperModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cropperModalLabel">Crop Image</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <div style="max-height: 500px; overflow: hidden;">
            <img id="cropperImage" src="" style="max-width: 100%;">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="saveCropBtn">Save Crop</button>
      </div>
    </div>
  </div>
</div>

</div>
@endsection

@section("script")
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#sticker_category_id').select2();
    });

    window.imageDataArray = [];
    let currentCropIndex = -1;
    let cropper = null;

    function imagePreview(event) 
    { 
        $('#preview').empty();
        window.imageDataArray = [];
        
        var files = event.target.files;
        if(files.length > 0) {
            $('#image').removeAttr('required');
        } else {
            $('#image').attr('required', 'required');
        }

        for(let i=0; i<files.length; i++) {
            let file = files[i];
            let reader = new FileReader();
            reader.onload = function(e) {
                window.imageDataArray.push({
                    originalFile: file,
                    dataUrl: e.target.result,
                    isCropped: false
                });
                renderPreview();
            };
            reader.readAsDataURL(file);
        }
    }

    function renderPreview() {
        $('#preview').empty();
        window.imageDataArray.forEach((item, index) => {
            let html = `
            <div class='notification m-2' onclick='openCropper(${index})'>
                <img class='img-responsive mt-3' src='${item.dataUrl}' style='width:150px;height:150px;object-fit:contain;border:1px solid #ddd;padding:5px;'>
                <div class="crop-icon-overlay"><i class="fa fa-crop"></i></div>
                <p class='remove pull-right bg-danger' style='cursor:pointer;position: absolute;top: 15px;right: -5px;padding: 6px 10px;border-radius:50%;' onclick='removeImage(event, ${index})'>
                    <i class='fa fa-close'></i>
                </p>
            </div>`;
            $('#preview').append(html);
        });
    }

    function removeImage(event, index) {
        event.stopPropagation();
        window.imageDataArray.splice(index, 1);
        renderPreview();
        if(window.imageDataArray.length === 0) {
            document.getElementById('image').value = "";
            $('#image').attr('required', 'required');
        }
    }

    function openCropper(index) {
        currentCropIndex = index;
        let item = window.imageDataArray[index];
        $('#cropperImage').attr('src', item.dataUrl);
        $('#cropperModal').modal('show');
    }

    $('#cropperModal').on('shown.bs.modal', function () {
        let image = document.getElementById('cropperImage');
        if (cropper) {
            cropper.destroy();
        }
        cropper = new Cropper(image, {
            viewMode: 2,
            autoCropArea: 1,
            background: false
        });
    }).on('hidden.bs.modal', function () {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    });

    $('#saveCropBtn').click(function() {
        if (!cropper) return;
        
        let canvas = cropper.getCroppedCanvas();
        if(canvas) {
            let croppedDataUrl = canvas.toDataURL('image/png');
            window.imageDataArray[currentCropIndex].dataUrl = croppedDataUrl;
            window.imageDataArray[currentCropIndex].isCropped = true;
            renderPreview();
        }
        $('#cropperModal').modal('hide');
    });

    // Before submit, clear raw file input and append base64 hidden inputs
    $('form').on('submit', function(e) {
        if(window.imageDataArray.length > 0) {
            $('#cropped_inputs_container').empty();
            window.imageDataArray.forEach((item) => {
                $('#cropped_inputs_container').append(`<input type="hidden" name="cropped_images[]" value="${item.dataUrl}">`);
            });
            // Disable original file input to prevent uploading massive uncropped files
            $('#image').prop('disabled', true);
        }
    });
</script>
@endsection