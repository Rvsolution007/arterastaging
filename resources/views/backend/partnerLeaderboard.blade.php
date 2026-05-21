@extends("layouts.app")

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
        gap: 0.75rem;
    }

    .table-panel-title {
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

    .icon-warning { background: #fef3c7; color: #d97706; }

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

    .leaderboard-rank {
        font-size: 1.25rem;
        font-weight: bold;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .rank-1 { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #fff; }
    .rank-2 { background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%); color: #fff; }
    .rank-3 { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); color: #fff; }
    .rank-other { background: #f1f5f9; color: #475569; font-size: 1rem; box-shadow: none; border: 1px solid #e2e8f0; }

    .badge-soft {
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        background: #e0f2fe;
        color: #0284c7;
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
    <div class="row align-items-center mb-4">
        <div class="col-md-12">
            <h4 class="page-title mb-1"><i class="fa-solid fa-trophy mr-2 text-warning"></i> Partner Leaderboard</h4>
            <p class="text-muted mb-0" style="font-size: 0.9rem;">Top performing partners ranked by total earnings.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="table-panel">
                <div class="table-panel-header">
                    <div class="icon-wrapper icon-warning">
                        <i class="fa-solid fa-crown"></i>
                    </div>
                    <h5 class="table-panel-title">Top Affiliates</h5>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 80px;">Rank</th>
                                <th>Partner</th>
                                <th>Referral Code</th>
                                <th class="text-right">Total Earnings</th>
                                <th class="text-right">Current Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topPartners as $index => $partner)
                            <tr>
                                <td class="text-center">
                                    @if($index == 0)
                                        <span class="leaderboard-rank rank-1"><i class="fa-solid fa-medal"></i></span>
                                    @elseif($index == 1)
                                        <span class="leaderboard-rank rank-2"><i class="fa-solid fa-medal"></i></span>
                                    @elseif($index == 2)
                                        <span class="leaderboard-rank rank-3"><i class="fa-solid fa-medal"></i></span>
                                    @else
                                        <span class="leaderboard-rank rank-other font-math">{{ $index + 1 }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="font-weight-bold text-dark" style="font-size: 1rem;">{{ $partner->name }}</div>
                                    <div class="text-muted" style="font-size: 0.8rem;">{{ $partner->email }}</div>
                                </td>
                                <td>
                                    <span class="badge-soft"><i class="fa-solid fa-link mr-1"></i>{{ $partner->referral_code }}</span>
                                </td>
                                <td class="text-right cost-highlight font-math">
                                    ${{ number_format($partner->total_balance, 2) }}
                                </td>
                                <td class="text-right font-math" style="color: #4338ca; font-weight: 600; font-size: 0.95rem;">
                                    ${{ number_format($partner->current_balance, 2) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted border-0">No partners found on the leaderboard yet.</td>
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
