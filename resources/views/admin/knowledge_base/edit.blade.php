@extends('layouts.app')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Edit FAQ (AI Training)</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.knowledge_base') }}">Knowledge Base</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
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
                            <form action="{{ route('admin.knowledge_base.update', $kb->id) }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label>Question</label>
                                    <input type="text" name="question" class="form-control" value="{{ $kb->question }}" required>
                                </div>
                                <div class="form-group">
                                    <label>Answer (AI Reply)</label>
                                    <textarea name="answer" class="form-control" rows="5" required>{{ $kb->answer }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Category</label>
                                    <input type="text" name="category" class="form-control" value="{{ $kb->category }}" required>
                                </div>
                                <div class="form-group">
                                    <label>Keywords (comma separated)</label>
                                    <input type="text" name="keywords" class="form-control" value="{{ $kb->keywords }}">
                                </div>
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="1" {{ $kb->status ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ !$kb->status ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Update FAQ</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
