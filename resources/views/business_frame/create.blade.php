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
                <h3 class="card-title">Add Business Frame Image</h3>
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

                {!! Form::open(['route' => 'business-frame.store', 'method' => 'post', 'files' => true]) !!}
                {!! Form::hidden('user_id', optional(Auth::user())->id)!!}
                <div class="row">
                    <div class="col-12">
                        <div class="form-group row">
                            {!! Form::label('category', 'Business Category', ['class' => 'col-sm-3 col-form-label']) !!}
                            <div class="col-sm-4">
                                <select id="business_category_id" name="business_category_id" class="form-control" required>
                                    <option value="">Select Business Category</option>
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
                            {!! Form::label('subCategory', 'Business Sub Category', ['class' => 'col-sm-3 col-form-label']) !!}
                            <div class="col-sm-4">
                                <select id="business_sub_category_id" name="business_sub_category_id" class="form-control">

                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="form-group row">
                            {!! Form::label('frame_image', ' Select Frame Image', ['class' => 'col-sm-3 col-form-label']) !!}
                            <div class="col-sm-4">
                                <input class="form-control" type="file" id="frame_image" name="frame_image[]"
                                    onchange="imagePreview()" accept=".jpg, .png, jpeg, .PNG, .JPG, .JPEG" multiple
                                    required>
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
                            <div class="col-sm-9">
                                <div class="border p-3 mt-3" id="preview"></div>
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
        $(document).ready(function () {
            $('#business_category_id').select2();
        });

        function imagePreview() {
            var total_file = document.getElementById("frame_image").files.length;
            $('#preview').empty();
            for (var i = 0; i < total_file; i++) {
                $('#preview').append("<div class='notification'><img class='img-responsive mr-3 mt-3' src='" + URL.createObjectURL(event.target.files[i]) + "' style='width:150px;height:auto;'></span></div>");
            }
        }

        $('#business_category_id').on('change', function () {
            var id = $(this).val();

            $.ajax({
                type: "GET",
                url: "{{url('admin/get-business-sub-category')}}",
                data: { id: id },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (data) {
                    $('#business_sub_category_id').empty();
                    $("#business_sub_category_id").append('<option value="">Select Business Sub Category</option>');
                    $.each(data, function (key, val) {
                        $("#business_sub_category_id").append('<option value=' + val.id + '>' + val.name + '</option>');
                    });
                    $('#business_sub_category_id').select2();
                },
            });
        });
    </script>
@endsection