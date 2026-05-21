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
    }

    .custom-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .custom-card-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
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

    .icon-primary { background: #e0e7ff; color: #4338ca; }
    .icon-success { background: #d1fae5; color: #059669; }

    .custom-card-body {
        padding: 1.5rem;
    }

    .custom-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
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

    .btn-gradient {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        border: none;
        padding: 0.6rem 1.25rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
    }

    .btn-gradient:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4);
        color: white;
    }

    .status-badge {
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-active { background: #d1fae5; color: #059669; }
    .status-inactive { background: #fee2e2; color: #dc2626; }
</style>
@endsection

@section('content')
<div class="analytics-container">
    <div class="row align-items-center mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="page-title mb-1"><i class="fa-solid fa-gamepad mr-2 text-primary"></i> Design Challenges</h4>
                <p class="text-muted mb-0" style="font-size: 0.9rem;">Manage weekly interactive contests for users.</p>
            </div>
            <button class="btn-gradient" data-toggle="modal" data-target="#createModal">
                <i class="fa fa-plus mr-1"></i> New Challenge
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px;">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="custom-card">
                <div class="custom-card-header">
                    <h5 class="custom-card-title">
                        <div class="icon-wrapper icon-primary"><i class="fa-solid fa-list"></i></div>
                        All Challenges
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($challenges as $challenge)
                            <tr>
                                <td>
                                    <div class="font-weight-bold" style="color: #1e293b; font-size: 1rem;">{{ $challenge->title }}</div>
                                    <div class="text-muted" style="font-size: 0.8rem;">{{ Str::limit($challenge->description, 50) }}</div>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($challenge->start_date)->format('d M, Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($challenge->end_date)->format('d M, Y') }}</td>
                                <td>
                                    @if($challenge->is_active)
                                        <span class="status-badge status-active">Active</span>
                                    @else
                                        <span class="status-badge status-inactive">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.challenges.toggle', $challenge->id) }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                                        Toggle Status
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No challenges found. Create one to get started!</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title font-weight-bold" style="color: #1e293b;">Create Challenge</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.challenges.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold text-muted small">Challenge Title</label>
                        <input type="text" class="form-control" style="border-radius: 8px;" name="title" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold text-muted small">Description</label>
                        <textarea class="form-control" style="border-radius: 8px;" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold text-muted small">Start Date</label>
                            <input type="date" class="form-control" style="border-radius: 8px;" name="start_date" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold text-muted small">End Date</label>
                            <input type="date" class="form-control" style="border-radius: 8px;" name="end_date" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="submit" class="btn-gradient w-100">Create Challenge</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
