@extends('layouts.app')

@section('extra_css')
<style>
    /* Modern AI Analytics Light Theme */
    .ai-wrapper {
        font-family: 'Inter', 'Poppins', sans-serif;
        color: #1e293b;
        padding-top: 10px;
    }
    
    .ai-header-section {
        padding: 0 0 1.5rem 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .ai-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        letter-spacing: -0.5px;
    }
    
    .ai-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-top: 0.25rem;
    }
    
    .btn-glow {
        background: linear-gradient(135deg, #3b82f6 0%, #4f46e5 100%);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 0.6rem 1.2rem;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    }
    
    .btn-glow:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        color: white;
    }
    
    /* Stats Cards */
    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        border-color: #cbd5e1;
    }
    
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-right: 1rem;
    }
    
    .icon-blue { background: #eff6ff; color: #3b82f6; }
    .icon-green { background: #f0fdf4; color: #22c55e; }
    .icon-purple { background: #faf5ff; color: #a855f7; }
    
    .stat-details h3 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
    }
    
    .stat-details p {
        margin: 0;
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 500;
    }

    /* Table Container */
    .table-container {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }
    
    .table-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
    }
    
    .table-header h4 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
    }
    
    /* Modern Table */
    .ai-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .ai-table th {
        background: #f8fafc;
        color: #64748b;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 1rem 1.5rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .ai-table td {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        font-size: 0.95rem;
        vertical-align: middle;
    }
    
    .ai-table tbody tr {
        transition: background-color 0.2s ease;
    }
    
    .ai-table tbody tr:hover {
        background-color: #f8fafc;
    }
    
    /* Badges */
    .ai-badge {
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    
    .badge-active {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }
    
    .badge-inactive {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
    
    /* Category tag */
    .cat-tag {
        background: #f1f5f9;
        color: #475569;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 500;
        border: 1px solid #e2e8f0;
        white-space: nowrap;
    }

    /* Actions */
    .action-btns {
        display: flex;
        gap: 0.5rem;
    }
    
    .btn-icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        transition: all 0.2s;
        cursor: pointer;
    }
    
    .btn-edit {
        background: #eff6ff;
        color: #3b82f6;
    }
    
    .btn-edit:hover {
        background: #3b82f6;
        color: white;
    }
    
    .btn-delete {
        background: #fef2f2;
        color: #ef4444;
    }
    
    .btn-delete:hover {
        background: #ef4444;
        color: white;
    }
    
    /* Pagination */
    .pagination-wrapper {
        padding: 1rem 1.5rem;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
    }
    
    .pagination {
        margin: 0;
    }
    
    .page-item.active .page-link {
        background-color: #3b82f6;
        border-color: #3b82f6;
        color: white;
    }
    
    .page-link {
        background-color: #ffffff;
        border-color: #e2e8f0;
        color: #64748b;
    }
    
    .page-link:hover {
        background-color: #f1f5f9;
        border-color: #cbd5e1;
        color: #0f172a;
    }
    
    .page-item.disabled .page-link {
        background-color: #f8fafc;
        border-color: #e2e8f0;
        color: #94a3b8;
    }
</style>
@endsection

@section('content')
<div class="ai-wrapper">
    <!-- Header -->
    <div class="ai-header-section">
        <div>
            <h1 class="ai-title">Knowledge Base</h1>
            <p class="ai-subtitle">Manage AI FAQs and training data</p>
        </div>
        <a href="{{ route('admin.knowledge_base.create') }}" class="btn-glow">
            <i class="fas fa-plus mr-2"></i> New Entry
        </a>
    </div>

    <!-- Analytics Cards -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon icon-blue">
                <i class="fas fa-database"></i>
            </div>
            <div class="stat-details">
                <h3>{{ $kbs->total() }}</h3>
                <p>Total Entries</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h3>{{ collect($kbs->items())->where('status', 1)->count() }}</h3>
                <p>Active FAQs (This Page)</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-purple">
                <i class="fas fa-brain"></i>
            </div>
            <div class="stat-details">
                <h3>AI</h3>
                <p>Analytics Mode</p>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="row">
        <div class="col-12">
            <div class="table-container mb-4">
                <div class="table-header">
                    <h4>Knowledge Base Library</h4>
                </div>
                <div class="table-responsive">
                    <table class="ai-table">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="40%">Question / Intent</th>
                                <th width="15%">Category</th>
                                <th width="20%">Keywords</th>
                                <th width="10%">Status</th>
                                <th width="10%" class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($kbs as $kb)
                            <tr>
                                <td><span class="text-muted">#{{ $kb->id }}</span></td>
                                <td>
                                    <div style="font-weight: 500; color: #1e293b;">{{ Str::limit($kb->question, 60) }}</div>
                                </td>
                                <td><span class="cat-tag">{{ $kb->category }}</span></td>
                                <td>
                                    <div style="font-size: 0.85rem; color: #64748b;">
                                        {{ Str::limit($kb->keywords, 30) ?: 'N/A' }}
                                    </div>
                                </td>
                                <td>
                                    @if($kb->status)
                                        <span class="ai-badge badge-active">Active</span>
                                    @else
                                        <span class="ai-badge badge-inactive">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-btns justify-content-end">
                                        <a href="{{ route('admin.knowledge_base.edit', $kb->id) }}" class="btn-icon btn-edit" title="Edit Entry">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <a href="{{ route('admin.knowledge_base.delete', $kb->id) }}" class="btn-icon btn-delete" title="Delete Entry" onclick="return confirm('Are you sure you want to delete this FAQ?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                                    <p class="mb-0" style="font-size: 1.1rem;">No Knowledge Base entries found.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($kbs->hasPages())
                <div class="pagination-wrapper d-flex justify-content-end">
                    {{ $kbs->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
