@extends("layouts.app")

@section('content')
    <div class="row">
        <div class="col-md-12">
            @if (count($errors) > 0)
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Edit General Post</h3>
                </div>
                {!! Form::model($post, ['route' => ['general-post.update', $post->id], 'method' => 'PATCH', 'files' => true]) !!}
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Task Name</label>
                                {!! Form::text('task_name', null, ['class' => 'form-control', 'placeholder' => 'Enter Task Name']) !!}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Post Purpose</label>
                                <select name="post_purpose_id" class="form-control">
                                    <option value="">Select Purpose (Option)</option>
                                    @foreach($purposes as $purpose)
                                        <option value="{{ $purpose->id }}" @if($post->post_purpose_id == $purpose->id) selected
                                        @endif>{{ $purpose->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Business Category</label>
                        <select class="form-control" name="business_category_id" id="business_category_id" required>
                            <option value="">Select Category</option>
                            @foreach($category as $row)
                                <option value="{{$row->id}}" @if($post->business_category_id == $row->id) selected @endif>
                                    {{$row->name}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Business Subcategory</label>
                        <select class="form-control" name="business_sub_category_id" id="business_sub_category_id">
                            <option value="">Select Subcategory</option>
                            @foreach($subcategory as $row)
                                <option value="{{$row->id}}" @if($post->business_sub_category_id == $row->id) selected @endif>
                                    {{$row->name}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Product</label>
                        <select class="form-control" name="product_id" id="product_id">
                            <option value="">Select Product</option>
                            @foreach($product as $row)
                                <option value="{{$row->id}}" @if($post->product_id == $row->id) selected @endif>
                                    {{$row->title}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="frame_image">Post Image</label>
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" name="frame_image" class="custom-file-input" id="frame_image">
                                <label class="custom-file-label" for="frame_image">Choose image</label>
                            </div>
                        </div>
                        <img class="mt-2"
                            src="@if(App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {{\Storage::disk('spaces')->url('uploads/' . $post->frame_image)}} @else {{asset('uploads/' . $post->frame_image)}} @endif"
                            width="150px">
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        $('#business_category_id').on('change', function () {
            var id = $(this).val();
            if (id) {
                $.ajax({
                    url: "{{ url('admin/get-business-sub-category') }}",
                    type: "GET",
                    data: { id: id },
                    success: function (data) {
                        $('#business_sub_category_id').empty();
                        $('#business_sub_category_id').append('<option value="">Select Subcategory</option>');
                        $.each(data, function (key, value) {
                            $('#business_sub_category_id').append('<option value="' + value.id + '">' + value.name + '</option>');
                        });
                    }
                });
            }
        });
    </script>
@endsection