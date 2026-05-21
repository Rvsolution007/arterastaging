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

    .custom-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
        overflow: hidden;
        margin-bottom: 1.5rem;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .custom-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .custom-card-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .icon-wrapper {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .icon-success { background: #d1fae5; color: #059669; }

    .custom-card-body {
        padding: 1.5rem;
        flex: 1;
    }

    .form-group label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 0.5rem;
    }

    .custom-input, .custom-file-input {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 0.6rem 1rem;
        font-size: 0.875rem;
        color: #334155;
        transition: all 0.2s ease;
        background-color: #f8fafc;
        width: 100%;
    }

    .custom-input:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        background-color: #ffffff;
    }

    .btn-success-gradient {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
        width: 100%;
    }

    .btn-success-gradient:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(16, 185, 129, 0.4);
    }
    
    .badge-soft {
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        background: #e0f2fe;
        color: #0284c7;
        display: inline-block;
    }
</style>
@endsection

@section('content')
<div class="analytics-container">
    <div class="row align-items-center mb-4">
        <div class="col-md-12">
            <h4 class="page-title mb-1"><i class="fa-solid fa-user-shield mr-2 text-success"></i> Partner Compliance Profile</h4>
            <p class="text-muted mb-0" style="font-size: 0.9rem;">Update your tax details and documentation to ensure smooth commission payouts.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px; border: none; background: #d1fae5; color: #065f46;">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="color: #065f46;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px; border: none; background: #fee2e2; color: #991b1b;">
                    <ul class="mb-0 pl-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="color: #991b1b;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="custom-card">
                <div class="custom-card-header">
                    <div class="icon-wrapper icon-success">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                    <h5 class="custom-card-title">Tax & Compliance Information</h5>
                </div>
                <div class="custom-card-body">
                    <form action="{{ route('partner.profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group mb-4">
                            <label>Tax Identification Number (TIN / PAN / SSN)</label>
                            <input type="text" class="custom-input" name="tax_id" value="{{ old('tax_id', $user->tax_id) }}" placeholder="Enter your Tax ID">
                        </div>
                        <div class="form-group mb-4">
                            <label>Upload Tax Document (PDF, JPG, PNG)</label>
                            @if($user->tax_document)
                                <div class="mb-3 d-flex align-items-center">
                                    <span class="badge-soft"><i class="fa fa-file-alt mr-1"></i> Document Uploaded</span>
                                    <a href="{{ asset('uploads/partner_docs/' . $user->tax_document) }}" target="_blank" class="ml-3 font-weight-bold" style="font-size: 0.85rem; color: #6366f1;">
                                        <i class="fa fa-external-link-alt mr-1"></i> View Document
                                    </a>
                                </div>
                            @endif
                            <input type="file" class="custom-file-input" name="tax_document" style="padding: 0.45rem;">
                            <small class="text-muted mt-2 d-block" style="font-size: 0.75rem;">Upload your tax registration certificate or identification card (Max 2MB).</small>
                        </div>
                        <button type="submit" class="btn-success-gradient mt-2">
                            <i class="fa fa-save mr-1"></i> Save Compliance Details
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
