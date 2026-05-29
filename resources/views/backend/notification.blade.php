@extends('layouts.app')

@section('extra_css')
    <style type="text/css">
        .drag-drop-zone {
            border: 2px dashed #007bff;
            border-radius: 10px;
            padding: 30px 20px;
            text-align: center;
            background-color: #f8f9fa;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .drag-drop-zone.dragover {
            background-color: #e2e6ea;
            border-color: #28a745;
        }
        .drag-drop-zone input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }
        .drag-drop-text {
            color: #6c757d;
            font-size: 16px;
            font-weight: 500;
            pointer-events: none;
            position: relative;
            z-index: 1;
        }
    </style>
@endsection

@section('content')
    <div class="container">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Notification</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.notification_list') }}" class="btn btn-success btn-sm">View List</a>
                </div>
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

                @if(session()->has('message'))
                    <div class="alert alert-success">
                        {{ session()->get('message') }}
                    </div>
                @endif

                {!! Form::open(['url' => 'admin/notification', 'method' => 'post', 'files' => true]) !!}
                {!! Form::hidden('user_id', optional(Auth::user())->id)!!}
                <div class="row">
                    <div class="col-12">
                        <div class="form-group row">
                            {!! Form::label('title', 'Title', ['class' => 'col-sm-3 col-form-label']) !!}
                            <div class="col-sm-4">
                                {!! Form::text('title', null, ['class' => 'form-control', 'required']) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="form-group row">
                            {!! Form::label('message', 'Message', ['class' => 'col-sm-3 col-form-label']) !!}
                            <div class="col-sm-4">
                                {!! Form::textarea('message', null, ['class' => 'form-control', 'rows' => 3, 'required']) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="form-group row">
                            {!! Form::label('image', 'Select Image', ['class' => 'col-sm-3 col-form-label']) !!}
                            <div class="col-sm-6">
                                <div class="drag-drop-zone" id="dragDropZone">
                                    <div class="drag-drop-text" id="dragDropText">
                                        <i class="fas fa-cloud-upload-alt" style="font-size: 30px; color: #007bff; margin-bottom: 10px; display: block;"></i>
                                        <span>Drag and drop an image here or click to select</span>
                                    </div>
                                    <input type="file" id="image" name="image" required accept="image/*">
                                    <div id="preview" style="display: none; margin-top: 15px; position: relative; z-index: 1;">
                                        <img class="shadow bg-white rounded" src="" alt="Image Preview" style="max-height: 150px; max-width: 100%; border-radius: 8px;" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="form-group row">
                            {!! Form::label('type', 'Type', ['class' => 'col-sm-3 col-form-label']) !!}
                            <div class="col-sm-4">
                                <select id="type" name="type" class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="category">Category</option>
                                    <option value="festival">Festival</option>
                                    <option value="custom">Custom</option>
                                    <option value="externalLink">External Link</option>
                                    <option value="subscriptionPlan">Subscription Plan</option>
                                </select>
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
                            <button type="button" class="btn btn-success ToastrButton">Send</button>
                        @else
                            {!! Form::submit('Send', ['class' => 'btn btn-success']) !!}
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
        $(document).ready(function () {
            $('#type').select2();

            $("#type").change(function () {
                $('#otherText').empty();
                if ($(this).find("option:selected").text() == "Category") {
                    $('#otherText').append('<div class="row"><div class="col-sm-3"><label class="col-form-label">Select Category</label></div><div class="col-sm-4"><select id="category_id" name="category_id" class="form-control" required><option value="">Select Category</option>@foreach($category as $c)<option value="{{$c->id}}">{{$c->name}}</option>@endforeach</select></div></div>');
                }
                if ($(this).find("option:selected").text() == "Festival") {
                    $('#otherText').append('<div class="row"><div class="col-sm-3"><label class="col-form-label">Select Festival</label></div><div class="col-sm-4"><select id="festival_id" name="festival_id" class="form-control" required><option value="">Select Festival</option>@foreach($festival as $f)<option value="{{$f->id}}">{{$f->title}}</option>@endforeach</select></div></div>');
                }
                if ($(this).find("option:selected").text() == "Custom") {
                    $('#otherText').append('<div class="row"><div class="col-sm-3"><label class="col-form-label">Select Custom Category</label></div><div class="col-sm-4"><select id="custom_category_id" name="custom_category_id" class="form-control" required><option value="">Select Custom Category</option>@foreach($custom as $c)<option value="{{$c->id}}">{{$c->name}}</option>@endforeach</select></div></div>');
                }
                if ($(this).find("option:selected").text() == "External Link") {
                    $('#otherText').append('<div class="row"><div class="col-sm-3"><label class="col-form-label">External Link (Optional)</label></div><div class="col-sm-4"><input type="text" id="external_link" class="form-control" name="external_link" placeholder="http://www.google.com"></div></div>');
                }
                if ($(this).find("option:selected").text() == "Subscription Plan") {
                    $('#otherText').append('<div class="row"><div class="col-sm-3"><label class="col-form-label">Subscription Plan</label></div><div class="col-sm-4"><select id="plan_id" name="subscription_id" class="form-control" required><option value="">Select Subscription Plan</option>@foreach($plan as $p)<option value="{{$p->id}}">{{$p->plan_name}}</option>@endforeach</select></div></div>');
                }
                $('#category_id').select2();
                $('#festival_id').select2();
                $('#custom_category_id').select2();
                $('#plan_id').select2();
            });
        });

        function imagePreview(fileInput) {
            if (fileInput.files && fileInput.files[0]) {
                var fileReader = new FileReader();
                fileReader.onload = function (event) {
                    $('#preview').show();
                    $('#preview img').attr('src', event.target.result);
                    $('#dragDropText').hide();
                };
                fileReader.readAsDataURL(fileInput.files[0]);
            } else {
                $('#preview').hide();
                $('#dragDropText').show();
            }
        }

        $("#image").change(function () {
            imagePreview(this);
        });

        // Drag and drop effects
        var dragDropZone = document.getElementById('dragDropZone');
        dragDropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        dragDropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        dragDropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            if(e.dataTransfer.files.length) {
                document.getElementById('image').files = e.dataTransfer.files;
                imagePreview(document.getElementById('image'));
            }
        });
    </script>
@endsection