@extends('layouts.app')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">AI Knowledge Base (FAQ)</h1>
                </div>
                <div class="col-sm-6">
                    <div class="float-sm-right">
                        <a href="{{ route('admin.knowledge_base.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add New FAQ</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Question</th>
                                        <th>Category</th>
                                        <th>Keywords</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($kbs as $kb)
                                    <tr>
                                        <td>{{ $kb->id }}</td>
                                        <td>{{ Str::limit($kb->question, 50) }}</td>
                                        <td>{{ $kb->category }}</td>
                                        <td>{{ $kb->keywords }}</td>
                                        <td>
                                            @if($kb->status)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.knowledge_base.edit', $kb->id) }}" class="btn btn-sm btn-info"><i class="fas fa-edit"></i></a>
                                            <a href="{{ route('admin.knowledge_base.delete', $kb->id) }}" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this FAQ?');"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="mt-3">
                                {{ $kbs->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
