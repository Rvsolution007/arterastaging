@extends('layouts.app')

@section('extra_css')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

    .analytics-container {
        font-family: 'Poppins', sans-serif;
        padding: 1.5rem;
        background-color: #f8fafc;
        min-height: 100vh;
    }

    .page-title {
        font-weight: 700;
        color: #1e293b;
        font-size: 1.5rem;
        letter-spacing: -0.025em;
    }

    /* Form Panel Styling */
    .form-panel {
        background: #ffffff;
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
        margin-bottom: 1.5rem;
    }

    .form-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 0.5rem;
        display: block;
    }

    .form-input, .form-select {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 0.6rem 1rem;
        font-size: 0.875rem;
        color: #334155;
        transition: all 0.2s ease;
        background-color: #f8fafc;
    }

    .form-input:focus, .form-select:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        background-color: #ffffff;
    }

    .btn-submit {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        border: none;
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
        margin-top: 1.8rem;
    }

    .btn-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4);
    }

    /* Table Panels Styling */
    .table-panel {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .table-panel-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .table-panel-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .table-icon-wrapper {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .icon-primary { background: #e0e7ff; color: #4338ca; }

    .custom-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin: 0;
    }

    .custom-table th {
        background: #f8fafc;
        padding: 1rem 1.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    .custom-table td {
        padding: 1rem 1.5rem;
        font-size: 0.875rem;
        color: #334155;
        font-weight: 500;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .custom-table tbody tr {
        transition: background-color 0.2s ease;
    }

    .custom-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .custom-table tbody tr:last-child td {
        border-bottom: none;
    }

    .badge-soft {
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        background: #f1f5f9;
        color: #475569;
        display: inline-block;
    }

    .badge-ratio {
        background: #e0f2fe;
        color: #0284c7;
    }

    .btn-delete {
        background: #fee2e2;
        color: #e11d48;
        border: none;
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-delete:hover {
        background: #fecdd3;
    }
</style>
@endsection

@section('content')
<div class="analytics-container">
    <!-- Header Section -->
    <div class="row align-items-center mb-4">
        <div class="col-md-12">
            <h4 class="page-title mb-0"><i class="fa-regular fa-image mr-2 text-primary"></i> Category Background Images</h4>
            <p class="text-muted mt-2 mb-0" style="font-size: 0.9rem;">Upload dynamic backgrounds for the custom template editor.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="border-radius: 8px; font-weight: 500;">
            <i class="fa-solid fa-check-circle mr-2"></i> {{ session('success') }}
        </div>
    @endif

    <!-- Upload Form -->
    <div class="row mb-2">
        <div class="col-12">
            <div class="form-panel">
                <form action="{{ route('category-background-image.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><i class="fa-solid fa-sitemap mr-1"></i> Business Category</label>
                            <select name="business_category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><i class="fa-solid fa-crop-simple mr-1"></i> Aspect Ratio</label>
                            <select name="aspect_ratio" class="form-select" required>
                                <option value="">Select Aspect Ratio</option>
                                <option value="1:1">1:1 (Square)</option>
                                <option value="16:9">16:9 (Landscape)</option>
                                <option value="9:16">9:16 (Portrait)</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="fa-solid fa-upload mr-1"></i> Image File</label>
                            <input type="file" name="image" class="form-input" required accept="image/*" style="padding: 0.45rem 1rem;">
                        </div>
                        <div class="col-md-2 mb-3">
                            <button type="submit" class="btn-submit w-100"><i class="fa-solid fa-plus mr-1"></i> Upload</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Data Tables Section -->
    <div class="row">
        <div class="col-12">
            <div class="table-panel">
                <div class="table-panel-header">
                    <div class="table-icon-wrapper icon-primary">
                        <i class="fa-solid fa-images"></i>
                    </div>
                    <h5 class="table-panel-title">Uploaded Backgrounds</h5>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th># ID</th>
                                <th>Preview</th>
                                <th>Category</th>
                                <th>Aspect Ratio</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($images as $img)
                            <tr>
                                <td class="font-math">#{{ $img->id }}</td>
                                <td>
                                    <img src="{{ asset($img->image) }}" alt="bg" style="width: 80px; height: auto; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                </td>
                                <td>
                                    <span class="badge-soft"><i class="fa-solid fa-tag mr-1 text-muted"></i> {{ $img->businessCategory ? $img->businessCategory->name : 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="badge-soft badge-ratio font-math">{{ $img->aspect_ratio }}</span>
                                </td>
                                <td class="text-right">
                                    <form action="{{ route('category-background-image.destroy', $img->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this background image?');" class="d-inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-delete"><i class="fa-solid fa-trash mr-1"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted border-0">No background images uploaded yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
