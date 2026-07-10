@extends('layouts.app')

@section('extra_css')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
    .analytics-container { font-family: 'Poppins', sans-serif; padding: 1.5rem; background-color: #f8fafc; min-height: 100vh; }
    .page-title { font-weight: 700; color: #1e293b; font-size: 1.5rem; letter-spacing: -0.025em; }
    .table-panel { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03); overflow: hidden; margin-bottom: 1.5rem; }
    .table-panel-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 0.75rem; justify-content: space-between; }
    .table-panel-title { font-size: 1.125rem; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 0.75rem; }
    .table-icon-wrapper { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
    .icon-blog { background: #e0e7ff; color: #4338ca; }
    .custom-table { width: 100%; border-collapse: separate; border-spacing: 0; margin: 0; }
    .custom-table th { background: #f8fafc; padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; text-align: left; border-bottom: 1px solid #e2e8f0; }
    .custom-table td { padding: 1rem 1.5rem; font-size: 0.875rem; color: #334155; font-weight: 500; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .custom-table tbody tr:hover { background-color: #f8fafc; }
    .custom-table tbody tr:last-child td { border-bottom: none; }
    .badge-soft { padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 500; display: inline-block; }
    .badge-soft-success { background: #d1fae5; color: #059669; }
    .badge-soft-warning { background: #fef3c7; color: #d97706; }
    .btn-ai { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; border: none; padding: 0.5rem 1.25rem; border-radius: 8px; font-weight: 600; font-size: 0.875rem; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3); }
    .btn-ai:hover { transform: translateY(-1px); box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4); color: white; }
</style>
@endsection

@section('content')
<div class="analytics-container">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h4 class="page-title mb-0"><i class="fa-solid fa-pen-nib mr-2 text-primary"></i> AI Blog Manager</h4>
        </div>
        <div class="col-md-6 text-right">
            <form action="{{ url('/admin/blogs/generate') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn-ai"><i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Generate AI Blog</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="table-panel">
        <div class="table-panel-header">
            <div class="table-panel-title">
                <div class="table-icon-wrapper icon-blog">
                    <i class="fa-solid fa-list"></i>
                </div>
                Generated Blogs
            </div>
        </div>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Keywords</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($blogs as $blog)
                    <tr>
                        <td class="font-weight-bold text-dark">{{ $blog->title }}</td>
                        <td>{{ $blog->meta_keywords }}</td>
                        <td>
                            @if($blog->status == 'published')
                                <span class="badge-soft badge-soft-success"><i class="fa fa-check mr-1"></i> Published</span>
                            @else
                                <span class="badge-soft badge-soft-warning"><i class="fa fa-file mr-1"></i> Draft</span>
                            @endif
                        </td>
                        <td>{{ $blog->created_at->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route('admin.blogs.edit', $blog->id) }}" class="btn btn-sm btn-light border" style="border-radius: 8px;"><i class="fa fa-edit text-info"></i></a>
                            <form action="{{ route('admin.blogs.destroy', $blog->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border" style="border-radius: 8px;" onclick="return confirm('Delete this blog?')"><i class="fa fa-trash text-danger"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted border-0">No blogs generated yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
