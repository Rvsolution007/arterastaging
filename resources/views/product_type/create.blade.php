@extends('layouts.app')

@section('content')
<div class="content">
    <div class="page-title-box mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <a href="{{ url('admin/product-type') }}" class="btn btn-sm btn-light mb-2" style="border-radius: 8px; color: #64748b; font-weight: 600;">
                    <i class="fa fa-arrow-left mr-1"></i> Back to Product Types
                </a>
                <h4 class="page-title mb-1">Create Product Type</h4>
                <p class="text-muted mb-0">Add a new Product Type to the system.</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 col-md-8 col-sm-12">
            <div class="card premium-card">
                <div class="card-body">
                    <form action="{{ url('admin/product-type') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group mb-4">
                            <label class="form-label-premium">Type Name <span class="text-danger">*</span></label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-tag input-icon"></i>
                                <input type="text" class="form-control form-control-premium @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" placeholder="Enter Product Type Name (e.g. Physical Product)" required autofocus>
                            </div>
                            @error('name')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label-premium">Status</label>
                            <div class="status-toggle-wrapper">
                                <span class="status-label">Active Status</span>
                                <label class="switch mb-0">
                                    <input type="checkbox" name="status" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <small class="text-muted mt-2 d-block"><i class="fa-solid fa-circle-info mr-1"></i> Inactive product types will not be available for selection when adding products.</small>
                        </div>

                        <hr class="my-4" style="border-top: 1px solid #e2e8f0;">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ url('admin/product-type') }}" class="aim-btn aim-btn-secondary">Cancel</a>
                            <button type="submit" class="aim-btn aim-btn-primary">
                                <i class="fa fa-save mr-2"></i> Save Product Type
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection