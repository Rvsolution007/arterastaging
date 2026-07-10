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

    .form-panel {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .form-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 0.5rem;
    }

    .form-control-custom {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 0.6rem 1rem;
        font-size: 0.875rem;
        color: #334155;
        background-color: #f8fafc;
        transition: all 0.2s ease;
        width: 100%;
    }

    .form-control-custom:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        background-color: #ffffff;
    }

    .btn-gradient-success {
        background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
        color: white;
        border: none;
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
    }
    
    .btn-gradient-success:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(16, 185, 129, 0.4);
        color: white;
    }

    .btn-light-custom {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.2s ease;
    }

    .btn-light-custom:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

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

    .checkbox-custom {
        width: 18px;
        height: 18px;
        border-radius: 4px;
        border: 1px solid #cbd5e1;
        accent-color: #6366f1;
        margin-right: 8px;
        vertical-align: middle;
    }
</style>
@endsection

@section('content')
<div class="analytics-container">
    <div class="row align-items-center mb-4">
        <div class="col-md-12">
            <h4 class="page-title mb-0">
                <i class="fa-solid {{ isset($language) ? 'fa-pen-to-square' : 'fa-plus-circle' }} mr-2 text-primary"></i> 
                {{ isset($language) ? 'Edit App Language' : 'Add App Language' }}
            </h4>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" style="border-radius: 12px; font-weight: 500;">
            @foreach($errors->all() as $error)
                <div><i class="fa-solid fa-circle-exclamation mr-2"></i>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="form-panel">
        <form action="{{ isset($language) ? route('app-language.update', $language->id) : route('app-language.store') }}" method="POST">
            @csrf
            @if(isset($language))
                @method('PUT')
            @endif

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label class="form-label">Language Code (e.g., en, hi, ta)</label>
                        <input type="text" name="language_code" class="form-control-custom" value="{{ old('language_code', $language->language_code ?? '') }}" required placeholder="Enter ISO code">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label class="form-label">Title (e.g., English, Hindi)</label>
                        <input type="text" name="title" class="form-control-custom" value="{{ old('title', $language->title ?? '') }}" required placeholder="Enter language name">
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-group mb-0 pb-2">
                        <label style="cursor: pointer;" class="form-label d-flex align-items-center m-0">
                            <input type="checkbox" name="status" class="checkbox-custom" value="1" {{ old('status', $language->status ?? 1) == 1 ? 'checked' : '' }}>
                            <span>Active / Visible in App</span>
                        </label>
                    </div>
                </div>
            </div>

            <hr style="border-color: #e2e8f0; margin: 2rem 0;">
            
            <h5 class="mb-2" style="font-weight: 700; color: #1e293b;">Translations</h5>
            <p class="text-muted" style="font-size: 0.875rem;">Enter the translated text in the right column. The left column shows the default English reference.</p>
            
            <div class="table-responsive" style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; margin-bottom: 1.5rem;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Key</th>
                            <th style="width: 40%;">English (Reference)</th>
                            <th style="width: 40%;">Translation (You Type)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($englishKeys as $key => $value)
                            <tr>
                                <td style="font-family: monospace; color: #6366f1;">{{ $key }}</td>
                                <td>{{ $value }}</td>
                                <td>
                                    <input type="text" name="translations[{{ $key }}]" class="form-control-custom" 
                                        value="{{ old('translations.'.$key, $language->translations[$key] ?? '') }}"
                                        placeholder="Translate '{{ $value }}'">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex align-items-center mt-4">
                <button type="submit" class="btn-gradient-success mr-2">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Save Language
                </button>
                <a href="{{ route('app-language.index') }}" class="btn-light-custom">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
