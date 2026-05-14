@extends("layouts.app")

@section('content')
    <div class="row">
        <div class="col-md-6 offset-md-3">
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
                    <h3 class="card-title">Upload ZIP File</h3>
                </div>
                <form action="{{ route('zip-file-manager.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="zip_file">Select ZIP File</label>
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" name="zip_file" class="custom-file-input" id="zip_file" accept=".zip"
                                        required>
                                    <label class="custom-file-label" for="zip_file">Choose file</label>
                                </div>
                            </div>
                            <small class="form-text text-muted">Upload a ZIP file containing .jpg, .jpeg, or .png
                                images.</small>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <button type="submit" class="btn btn-primary">Process ZIP</button>
                        <a href="{{ route('zip-file-manager.index') }}" class="btn btn-default">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $('#zip_file').on('change', function () {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName);
        })
    </script>
@endsection