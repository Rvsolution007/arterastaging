@extends('layouts.app')

@section('extra_css')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
    .analytics-container { font-family: 'Poppins', sans-serif; padding: 1.5rem; background-color: #f8fafc; min-height: 100vh; }
    .page-title { font-weight: 700; color: #1e293b; font-size: 1.5rem; letter-spacing: -0.025em; }
    .table-panel { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03); overflow: hidden; margin-bottom: 1.5rem; height: calc(100% - 1.5rem); }
    .table-panel-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 0.75rem; justify-content: space-between; }
    .table-panel-title { font-size: 1.125rem; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 0.75rem; }
    .table-icon-wrapper { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
    .icon-hot { background: #fee2e2; color: #e11d48; }
    .icon-cold { background: #e0f2fe; color: #0284c7; }
    .custom-table { width: 100%; border-collapse: separate; border-spacing: 0; margin: 0; }
    .custom-table th { background: #f8fafc; padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; text-align: left; border-bottom: 1px solid #e2e8f0; }
    .custom-table td { padding: 1rem 1.5rem; font-size: 0.875rem; color: #334155; font-weight: 500; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .custom-table tbody tr:hover { background-color: #f8fafc; }
    .custom-table tbody tr:last-child td { border-bottom: none; }
    .font-math { font-variant-numeric: tabular-nums; font-family: 'Poppins', sans-serif; }
    .btn-ai { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; border: none; padding: 0.4rem 1rem; border-radius: 8px; font-weight: 600; font-size: 0.8rem; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3); }
    .btn-ai:hover { transform: translateY(-1px); box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4); color: white; }
    .progress-custom { height: 8px; border-radius: 4px; background-color: #f1f5f9; overflow: hidden; margin-top: 0.5rem; }
    .progress-bar-custom { border-radius: 4px; }
</style>
@endsection

@section('content')
<div class="analytics-container">
    <div class="row align-items-center mb-4">
        <div class="col-12">
            <h4 class="page-title mb-0"><i class="fa-solid fa-users-viewfinder mr-2 text-primary"></i> Lead Management (AI Scored)</h4>
            <div class="text-muted mt-2" style="font-size: 0.875rem;">
                <p class="mb-1">This dashboard works as your intelligent sales assistant. It analyzes your free registered users to find <strong>Hot Leads</strong> (users most likely to buy premium based on AI scoring), and helps you instantly draft personalized <strong>Cold Emails</strong> for external B2B contacts.</p>
                <p class="mb-0 text-info"><i class="fa-solid fa-circle-info mr-1"></i> <strong>Note:</strong> Users who have already purchased a premium package will not appear in this list.</p>
            </div>
        </div>
    </div>

    @if(session('email_draft'))
        <div class="alert alert-success">
            <h5><i class="fa fa-envelope"></i> AI Generated Cold Email Draft</h5>
            <hr>
            <strong>Subject:</strong> {{ session('email_draft')['subject'] }}<br><br>
            <strong>Body:</strong><br>
            {!! nl2br(e(session('email_draft')['body'])) !!}
            <hr>
            <button class="btn btn-sm btn-dark">Send Email</button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    
    <div class="row">
        <!-- Hot Leads from Registered Users -->
        <div class="col-md-6 mb-4">
            <div class="table-panel">
                <div class="table-panel-header">
                    <div class="table-panel-title">
                        <div class="table-icon-wrapper icon-hot">
                            <i class="fa-solid fa-fire"></i>
                        </div>
                        Hot Registered Leads
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Name / Email</th>
                                <th style="width: 40%;">Conversion Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hotLeads as $u)
                            <tr>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $u->name }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $u->email }}</div>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span style="font-size: 0.75rem; font-weight: 600; color: {{ $u->lead_score > 80 ? '#e11d48' : '#d97706' }};">{{ $u->lead_score }} / 100</span>
                                    </div>
                                    <div class="progress-custom">
                                        <div class="progress-bar-custom {{ $u->lead_score > 80 ? 'bg-danger' : 'bg-warning' }}" role="progressbar" style="width: {{ $u->lead_score }}%;" aria-valuenow="{{ $u->lead_score }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                            @if(count($hotLeads) == 0)
                            <tr>
                                <td colspan="2" class="text-center py-4 text-muted border-0">No registered leads found.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- B2B Cold Leads -->
        <div class="col-md-6 mb-4">
            <div class="table-panel">
                <div class="table-panel-header">
                    <div class="table-panel-title">
                        <div class="table-icon-wrapper icon-cold">
                            <i class="fa-solid fa-envelope-open-text"></i>
                        </div>
                        Cold B2B Leads
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Contact Info</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($coldLeads as $l)
                            <tr>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $l->name ?? 'Unknown' }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $l->email }}</div>
                                    <span class="badge badge-light border mt-1" style="font-size: 0.7rem;">{{ $l->industry }}</span>
                                </td>
                                <td class="text-right">
                                    <form action="{{ route('admin.leads.draft_email', $l->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn-ai"><i class="fa-solid fa-robot mr-1"></i> Draft AI Email</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                            @if(count($coldLeads) == 0)
                            <tr>
                                <td colspan="2" class="text-center py-4 text-muted border-0">No B2B leads found.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
