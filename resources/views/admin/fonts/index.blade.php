@extends('layouts.app')
@section('title', 'Global Fonts Manager')
@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

    .font-manager-wrapper { 
        font-family: 'Poppins', sans-serif; 
        background-color: #f8fafc; 
        min-height: calc(100vh - 60px); 
        padding: 1.5rem; 
        margin-left: 0;
    }
    
    .manager-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    
    .manager-header h1 {
        font-weight: 700;
        font-size: 1.8rem;
        color: #1e293b;
        letter-spacing: -0.025em;
        margin: 0;
    }

    .ai-btn-primary {
        background: #6366f1;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.95rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.2);
    }
    .ai-btn-primary:hover {
        background: #4f46e5;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(99, 102, 241, 0.3);
    }

    .custom-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .custom-table {
        width: 100%;
        margin: 0;
        border-collapse: collapse;
    }

    .custom-table th {
        background-color: #f1f5f9;
        color: #475569;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .custom-table td {
        padding: 1.2rem 1.5rem;
        vertical-align: middle;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
        font-size: 0.95rem;
    }

    .custom-table tbody tr:hover {
        background-color: #f8fafc;
    }
    
    .font-name-display {
        font-weight: 600;
        color: #1e293b;
        font-size: 1.05rem;
    }

    .font-path-code {
        background: #f1f5f9;
        color: #64748b;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.8rem;
        font-family: monospace;
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .status-active { background: #d1fae5; color: #065f46; }
    .status-inactive { background: #fee2e2; color: #991b1b; }

    .btn-delete {
        background: #fee2e2;
        color: #ef4444;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-delete:hover {
        background: #ef4444;
        color: white;
    }
</style>

<div class="font-manager-wrapper">
        
        <div class="manager-header" style="justify-content: flex-start;">
            <h1>Global Fonts Manager</h1>
            <div style="flex-grow: 1; max-width: 400px; margin-left: 30px;">
                <input type="text" id="fontSearch" class="form-control" placeholder="Search fonts by name..." style="border-radius: 8px; padding: 10px 15px; border: 1px solid #cbd5e1; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            </div>
            <a href="{{ route('admin.fonts.create') }}" class="ai-btn-primary" style="margin-left: auto;">
                <i class="fas fa-plus"></i> Upload Fonts
            </a>
        </div>

        @if(session('success'))
        <div class="alert alert-success" style="background: #10b981; color: white; border: none; border-radius: 10px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
        @endif

        <div class="custom-card">
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="30%">Font Name</th>
                            <th width="35%">File Path</th>
                            <th width="15%">Status</th>
                            <th width="15%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($fonts as $font)
                        <tr>
                            <td>{{ $font->id }}</td>
                            <td>
                                <div class="font-name-display">{{ $font->name }}</div>
                            </td>
                            <td>
                                <span class="font-path-code">{{ $font->file_path }}</span>
                            </td>
                            <td>
                                @if($font->status)
                                <span class="status-badge status-active"><i class="fa-solid fa-circle"></i> Active</span>
                                @else
                                <span class="status-badge status-inactive"><i class="fa-solid fa-circle"></i> Inactive</span>
                                @endif
                            </td>
                            <td style="display: flex; gap: 8px; align-items: center;">
                                <button type="button" onclick="openEditFontModal({{ $font->id }}, '{{ addslashes($font->name) }}')" style="background: #e0e7ff; color: #4338ca; border: none; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; font-weight: 500; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form action="{{ route('admin.fonts.destroy', $font->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this font?');" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-delete"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center" style="padding: 3rem 1rem;">
                                <div style="color: #64748b; font-size: 1.1rem; margin-bottom: 1rem;"><i class="fa-solid fa-folder-open fa-2x"></i></div>
                                No fonts injected yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
    </div>
</div>

<!-- Edit Font Modal -->
<div class="modal fade" id="editFontModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
      <form id="editFontForm" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; border-radius: 12px 12px 0 0;">
          <h5 class="modal-title" style="font-weight: 600; color: #1e293b;">Edit Font Name</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body" style="padding: 1.5rem;">
          <div class="form-group">
            <label style="font-weight: 500; color: #475569; margin-bottom: 8px;">Font Name</label>
            <input type="text" name="name" id="editFontNameInput" class="form-control" style="border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 15px;" required>
          </div>
        </div>
        <div class="modal-footer" style="border-top: 1px solid #e2e8f0; padding: 1rem 1.5rem;">
          <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 8px; padding: 8px 16px;">Cancel</button>
          <button type="submit" class="btn btn-primary" style="background: #6366f1; border: none; border-radius: 8px; padding: 8px 16px;">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openEditFontModal(id, name) {
    document.getElementById('editFontNameInput').value = name;
    document.getElementById('editFontForm').action = "{{ url('admin/fonts') }}/" + id;
    $('#editFontModal').modal('show');
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('fontSearch');
    const tableRows = document.querySelectorAll('.custom-table tbody tr');

    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        tableRows.forEach(row => {
            // Skip the empty message row
            if (row.children.length === 1) return;
            
            // Font name is in the second column
            const fontName = row.children[1].textContent.toLowerCase();
            
            if (fontName.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});
</script>
@endsection
