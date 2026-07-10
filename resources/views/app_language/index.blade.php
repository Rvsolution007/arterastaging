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
        justify-content: space-between;
    }

    .table-panel-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
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

    .custom-table tbody tr {
        transition: background-color 0.2s ease;
    }

    .custom-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .btn-gradient-primary {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        border: none;
        padding: 0.5rem 1.25rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
    }
    
    .btn-gradient-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4);
        color: white;
    }

    .btn-gradient-info {
        background: linear-gradient(135deg, #38bdf8 0%, #0ea5e9 100%);
        color: white;
        border: none;
        padding: 0.5rem 1.25rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(14, 165, 233, 0.3);
    }
    
    .btn-gradient-info:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(14, 165, 233, 0.4);
        color: white;
    }

    .btn-gradient-success {
        background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
        color: white;
        border: none;
        padding: 0.5rem 1.25rem;
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

    .btn-gradient-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        border: none;
        padding: 0.5rem 1.25rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.3);
    }
    
    .btn-gradient-warning:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(245, 158, 11, 0.4);
        color: white;
    }

    .form-control-file-custom {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 0.4rem 0.75rem;
        font-size: 0.875rem;
        color: #334155;
        background-color: #f8fafc;
    }

    /* Switch styling */
    .switch {
      position: relative;
      display: inline-block;
      width: 44px;
      height: 22px;
    }
    .switch input { 
      opacity: 0;
      width: 0;
      height: 0;
    }
    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #cbd5e1;
      transition: .4s;
    }
    .slider:before {
      position: absolute;
      content: "";
      height: 18px;
      width: 18px;
      left: 2px;
      bottom: 2px;
      background-color: white;
      transition: .4s;
    }
    input:checked + .slider {
      background-color: #6366f1;
    }
    input:checked + .slider:before {
      transform: translateX(22px);
    }
    .slider.round {
      border-radius: 34px;
    }
    .slider.round:before {
      border-radius: 50%;
    }

    .checkbox-custom {
        width: 18px;
        height: 18px;
        border-radius: 4px;
        border: 1px solid #cbd5e1;
        accent-color: #6366f1;
    }
</style>
@endsection

@section('content')
<div class="analytics-container">
    <div class="row align-items-center mb-4">
        <div class="col-md-5">
            <h4 class="page-title mb-0"><i class="fa-solid fa-language mr-2 text-primary"></i> App Languages</h4>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="border-radius: 12px; font-weight: 500;">
            <i class="fa-solid fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" style="border-radius: 12px; font-weight: 500;">
            @foreach($errors->all() as $error)
                <div><i class="fa-solid fa-circle-exclamation mr-2"></i>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="table-panel">
        <div class="table-panel-header flex-wrap">
            <div class="table-panel-title">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-earth-americas"></i>
                </div>
                Manage Languages
            </div>
            
            <div class="d-flex align-items-center flex-wrap" style="gap: 12px;">
                <form action="{{ route('app-language.import') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center m-0">
                    @csrf
                    <input type="file" name="json_file" accept=".json" required class="form-control-file-custom mr-2" style="max-width: 200px;">
                    <button type="submit" class="btn-gradient-success" onclick="return confirm('This will merge existing languages. Proceed?');">
                        <i class="fas fa-file-import mr-1"></i> Import JSON
                    </button>
                </form>
                
                <button type="button" id="btn-export-selected" class="btn-gradient-warning" style="display: none;">
                    <i class="fas fa-download mr-1"></i> Export Selected
                </button>

                <a href="{{ route('app-language.export') }}" class="btn-gradient-info">
                    <i class="fas fa-file-export mr-1"></i> Export All
                </a>
                
                <a href="{{ route('app-language.create') }}" class="btn-gradient-primary">
                    <i class="fas fa-plus mr-1"></i> Add New
                </a>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">
                            <input type="checkbox" id="select-all" class="checkbox-custom">
                        </th>
                        <th>ID</th>
                        <th>Language Code</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $lang)
                    <tr>
                        <td>
                            <input type="checkbox" class="select-row checkbox-custom" value="{{ $lang->id }}">
                        </td>
                        <td>#{{ $lang->id }}</td>
                        <td><span class="badge-soft" style="background:#e0e7ff; color:#4338ca; padding:4px 10px; border-radius:6px; font-weight:600;">{{ $lang->language_code }}</span></td>
                        <td>{{ $lang->title }}</td>
                        <td>
                            <label class="switch mb-0">
                                <input type="checkbox" class="status-toggle" data-id="{{ $lang->id }}" {{ $lang->status == 1 ? 'checked' : '' }}>
                                <span class="slider round"></span>
                            </label>
                        </td>
                        <td>
                            <a href="{{ route('app-language.edit', $lang->id) }}" class="btn-gradient-info" style="padding: 0.35rem 1rem;">
                                <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
                            </a>
                        </td>
                    </tr>
                    @endforeach
                    @if(count($data) == 0)
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No languages found.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        
        <div class="p-3 border-top">
            {!! $data->links() !!}
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    $(document).ready(function(){
        // Handle Status Toggle
        $('.status-toggle').on('change', function(){
            var id = $(this).data('id');
            var checked = $(this).is(':checked');
            $.ajax({
                url: "{{ url('admin/app-language/status') }}",
                type: "POST",
                data: {
                    id: id,
                    checked: checked,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response){
                    // Show a toast or notification if needed
                }
            });
        });

        // Handle Checkbox Selection
        $('#select-all').on('change', function() {
            $('.select-row').prop('checked', $(this).prop('checked'));
            toggleExportBtn();
        });

        $('.select-row').on('change', function() {
            if (!$(this).prop('checked')) {
                $('#select-all').prop('checked', false);
            }
            toggleExportBtn();
        });

        function toggleExportBtn() {
            var selected = $('.select-row:checked').length;
            if (selected > 0) {
                $('#btn-export-selected').fadeIn(200);
            } else {
                $('#btn-export-selected').fadeOut(200);
            }
        }

        // Handle Export Selected
        $('#btn-export-selected').on('click', function() {
            var selectedIds = [];
            $('.select-row:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            if (selectedIds.length > 0) {
                var exportUrl = "{{ route('app-language.export') }}?ids=" + selectedIds.join(',');
                window.location.href = exportUrl;
            }
        });
    });
</script>
@endsection
