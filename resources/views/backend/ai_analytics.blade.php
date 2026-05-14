@extends('layouts.app')

@section('extra_css')
<style>
    /* Modern AI Analytics Dashboard Styling */
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

    /* Filter Form Styling */
    .filter-wrapper {
        background: #ffffff;
        padding: 0.75rem 1.25rem;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        display: inline-flex;
        align-items: center;
        gap: 1rem;
        border: 1px solid #e2e8f0;
    }

    .filter-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .filter-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #64748b;
        margin: 0;
    }

    .filter-input {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 0.4rem 0.75rem;
        font-size: 0.875rem;
        color: #334155;
        transition: all 0.2s ease;
        background-color: #f8fafc;
    }

    .filter-input:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        background-color: #ffffff;
    }

    .btn-filter {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        border: none;
        padding: 0.5rem 1.25rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
    }

    .btn-filter:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4);
    }

    /* KPI Cards Styling */
    .kpi-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
    }

    .kpi-card.requests::before { background: linear-gradient(to right, #38bdf8, #0ea5e9); }
    .kpi-card.tokens::before { background: linear-gradient(to right, #a78bfa, #8b5cf6); }
    .kpi-card.ratio::before { background: linear-gradient(to right, #fbbf24, #f59e0b); }
    .kpi-card.cost::before { background: linear-gradient(to right, #34d399, #10b981); }

    .kpi-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.03);
    }

    .kpi-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .kpi-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        font-size: 1rem;
    }

    .requests .kpi-icon { background: #e0f2fe; color: #0284c7; }
    .tokens .kpi-icon { background: #ede9fe; color: #7c3aed; }
    .ratio .kpi-icon { background: #fef3c7; color: #d97706; }
    .cost .kpi-icon { background: #d1fae5; color: #059669; }

    .kpi-value {
        font-size: 1.875rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        line-height: 1.2;
    }
    
    .kpi-subtitle {
        font-size: 0.8rem;
        color: #94a3b8;
        margin-top: 0.5rem;
        font-weight: 500;
    }

    /* Table Panels Styling */
    .table-panel {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
        overflow: hidden;
        margin-bottom: 1.5rem;
        height: calc(100% - 1.5rem);
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

    .icon-feature { background: #fee2e2; color: #e11d48; }
    .icon-user { background: #ffedd5; color: #ea580c; }
    .icon-model { background: #e0e7ff; color: #4338ca; }

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

    .font-math {
        font-variant-numeric: tabular-nums;
        font-family: 'Poppins', sans-serif;
    }

    .badge-soft {
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        background: #f1f5f9;
        color: #475569;
        display: inline-block;
    }

    .cost-highlight {
        color: #059669;
        font-weight: 700;
        font-size: 1rem;
    }

</style>
@endsection

@section('content')
<div class="analytics-container">
    <!-- Header Section -->
    <div class="row align-items-center mb-4">
        <div class="col-md-5">
            <h4 class="page-title mb-0"><i class="fa-solid fa-chart-pie mr-2 text-primary"></i> AI Token Analytics</h4>
        </div>
        <div class="col-md-7 text-right">
            <form action="{{ route('admin.ai_analytics') }}" method="GET" class="filter-wrapper m-0">
                <div class="filter-group">
                    <label class="filter-label"><i class="fa-regular fa-calendar mr-1"></i> From Date</label>
                    <input type="date" name="start_date" class="filter-input" value="{{ $startDate }}">
                </div>
                <div class="filter-group">
                    <label class="filter-label"><i class="fa-regular fa-calendar-check mr-1"></i> To Date</label>
                    <input type="date" name="end_date" class="filter-input" value="{{ $endDate }}">
                </div>
                <button type="submit" class="btn-filter"><i class="fa-solid fa-filter mr-1"></i> Filter Data</button>
            </form>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row mb-2">
        <!-- Total Requests -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="kpi-card requests">
                <div class="kpi-title">
                    <div class="kpi-icon"><i class="fa-solid fa-server"></i></div>
                    Total API Requests
                </div>
                <h3 class="kpi-value font-math">{{ number_format($summary->total_requests ?? 0) }}</h3>
                <div class="kpi-subtitle">Hits processed by Vertex AI</div>
            </div>
        </div>

        <!-- Total Tokens -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="kpi-card tokens">
                <div class="kpi-title">
                    <div class="kpi-icon"><i class="fa-solid fa-microchip"></i></div>
                    Total Tokens Used
                </div>
                <h3 class="kpi-value font-math">{{ number_format(($summary->total_prompt ?? 0) + ($summary->total_completion ?? 0)) }}</h3>
                <div class="kpi-subtitle">Combined prompt & completion</div>
            </div>
        </div>

        <!-- Prompt Completion Ratio -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="kpi-card ratio">
                <div class="kpi-title">
                    <div class="kpi-icon"><i class="fa-solid fa-bolt"></i></div>
                    Input / Output Split
                </div>
                <h3 class="kpi-value font-math" style="font-size: 1.5rem;">
                    {{ number_format($summary->total_prompt ?? 0) }} <span class="text-muted font-weight-normal text-sm mx-1">/</span> {{ number_format($summary->total_completion ?? 0) }}
                </h3>
                <div class="kpi-subtitle">Prompt vs Completion Tokens</div>
            </div>
        </div>

        <!-- Total Cost -->
        <div class="col-xl-3 col-md-6 mb-4">
             <div class="kpi-card cost">
                <div class="kpi-title">
                    <div class="kpi-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                    Gross Estimated Cost
                </div>
                <h3 class="kpi-value text-success">₹ {{ number_format($summary->total_cost ?? 0, 2) }}</h3>
                <div class="kpi-subtitle">Conversion Rate: $1 = 90 INR</div>
            </div>
        </div>
    </div>

    <!-- Data Tables Section -->
    <div class="row">
        <!-- Feature Wise Data -->
        <div class="col-lg-6 mb-4">
            <div class="table-panel">
                <div class="table-panel-header">
                    <div class="table-icon-wrapper icon-feature">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <h5 class="table-panel-title">Tokens by Feature</h5>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Feature Name</th>
                                <th>Requests</th>
                                <th>Total Tokens</th>
                                <th class="text-right">Total Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($featureStats as $fs)
                            <tr>
                                <td><span class="badge-soft">{{ $fs->feature_name ?? 'Unknown Feature' }}</span></td>
                                <td class="font-math">{{ $fs->requests }}</td>
                                <td class="font-math">{{ number_format($fs->tokens) }}</td>
                                <td class="text-right cost-highlight font-math">₹ {{ number_format($fs->cost, 4) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted border-0">No API usage recorded for this date range.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- User Wise Data -->
        <div class="col-lg-6 mb-4">
            <div class="table-panel">
                <div class="table-panel-header">
                    <div class="table-icon-wrapper icon-user">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <h5 class="table-panel-title">Tokens by User</h5>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>User Profile</th>
                                <th>Requests</th>
                                <th>Total Tokens</th>
                                <th class="text-right">Total Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($userStats as $us)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="font-weight-bold text-dark">{{ $us->user ? $us->user->name : 'Admin (System)' }}</div>
                                    </div>
                                </td>
                                <td class="font-math">{{ $us->requests }}</td>
                                <td class="font-math">{{ number_format($us->tokens) }}</td>
                                <td class="text-right cost-highlight font-math">₹ {{ number_format($us->cost, 4) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted border-0">No API usage recorded for this date range.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Model Wise Data -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="table-panel">
                <div class="table-panel-header">
                    <div class="table-icon-wrapper icon-model">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <h5 class="table-panel-title">Tokens by AI Model</h5>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>AI Engine / Sub-model</th>
                                <th>API Calls</th>
                                <th>Total Tokens Processed</th>
                                <th class="text-right">Billed Cost (Estimated)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($modelStats as $ms)
                            <tr>
                                <td>
                                    <span class="badge-soft align-items-center d-inline-flex" style="background:#f1f5f9; border: 1px solid #e2e8f0;">
                                        <i class="fa-brands fa-google mr-2 text-primary" style="font-size: 0.85em;"></i>
                                        {{ $ms->model ?? 'Unknown' }}
                                    </span>
                                </td>
                                <td class="font-math">{{ $ms->requests }}</td>
                                <td class="font-math">{{ number_format($ms->tokens) }}</td>
                                <td class="text-right cost-highlight font-math" style="font-size: 1.1rem;">₹ {{ number_format($ms->cost, 4) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted border-0">No models hit during this period.</td>
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
