@extends("layouts.app")

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Zip File Manager</h3>
                    <div class="card-tools">
                        <a href="{{ route('zip-file-manager.create') }}" class="btn btn-success btn-sm">Upload New ZIP</a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>File Name</th>
                                <th>Upload Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $row)
                                <tr>
                                    <td>{{ $row->id }}</td>
                                    <td>{{ $row->file_name }}</td>
                                    <td>{{ $row->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ route('zip-file-manager.show', $row->id) }}"
                                            class="btn btn-info btn-sm">View Posts</a>
                                        <form action="{{ route('zip-file-manager.destroy', $row->id) }}" method="POST"
                                            style="display:inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Are you sure you want to delete this ZIP record and all its associated posts?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-3">
                        {{ $data->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection