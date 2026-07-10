@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h2>Edit Blog Post</h2>
    
    <div class="card mt-4">
        <div class="card-body">
            <form action="{{ route('admin.blogs.update', $blog->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="form-group mb-3">
                    <label>Title</label>
                    <input type="text" name="title" class="form-control" value="{{ $blog->title }}" required>
                </div>
                
                <div class="form-group mb-3">
                    <label>Blog Image</label>
                    <input type="file" name="og_image" class="form-control-file" accept="image/*">
                    <small class="text-muted d-block mt-1">Recommended size: 1200x630 px. (Leave blank to keep existing image)</small>
                    @if($blog->og_image)
                        <div class="mt-2">
                            <img src="{{ asset($blog->og_image) }}" alt="Current Image" style="max-height: 100px; border-radius: 8px;">
                        </div>
                    @endif
                </div>
                
                <div class="form-group mb-3">
                    <label>SEO Meta Keywords</label>
                    <input type="text" name="meta_keywords" class="form-control" value="{{ $blog->meta_keywords }}">
                </div>
                
                <div class="form-group mb-3">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="published" {{ $blog->status == 'published' ? 'selected' : '' }}>Published</option>
                        <option value="draft" {{ $blog->status == 'draft' ? 'selected' : '' }}>Draft</option>
                    </select>
                </div>
                
                <div class="form-group mb-4">
                    <label>Content (HTML)</label>
                    <textarea name="content" class="form-control" rows="15">{{ $blog->content }}</textarea>
                </div>
                
                <button type="submit" class="btn btn-success">Save Changes</button>
                <a href="{{ route('admin.blogs') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
