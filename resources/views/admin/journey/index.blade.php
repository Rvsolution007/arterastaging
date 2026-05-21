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

    .kpi-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
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

    .kpi-card.onboarding::before { background: linear-gradient(to right, #38bdf8, #0ea5e9); }
    .kpi-card.activation::before { background: linear-gradient(to right, #a78bfa, #8b5cf6); }
    .kpi-card.engagement::before { background: linear-gradient(to right, #34d399, #10b981); }
    .kpi-card.retention::before { background: linear-gradient(to right, #f472b6, #db2777); }
    .kpi-card.winback::before { background: linear-gradient(to right, #fbbf24, #f59e0b); }

    .kpi-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.75rem;
    }

    .kpi-value {
        font-size: 1.875rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        line-height: 1.2;
    }

    .table-panel {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
        overflow: hidden;
    }

    .table-panel-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .custom-table { width: 100%; }
    .custom-table th { background: #f8fafc; padding: 1rem; font-size: 0.75rem; color: #64748b; text-transform: uppercase; text-align: left; border-bottom: 1px solid #e2e8f0; }
    .custom-table td { padding: 1rem; font-size: 0.875rem; color: #334155; border-bottom: 1px solid #f1f5f9; }
    
    .badge-stage {
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .stage-onboarding { background: #e0f2fe; color: #0284c7; }
    .stage-activation { background: #ede9fe; color: #7c3aed; }
    .stage-engagement { background: #d1fae5; color: #059669; }
    .stage-retention { background: #fce7f3; color: #db2777; }
    .stage-winback { background: #fef3c7; color: #d97706; }
</style>
@endsection

@section('content')
<div class="analytics-container">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h4 class="page-title mb-0"><i class="fa-solid fa-route mr-2 text-primary"></i> Customer Journey Map</h4>
        </div>
    </div>

    <!-- Funnel Stats -->
    <div class="row mb-4">
        <div class="col">
            <div class="kpi-card onboarding">
                <div class="kpi-title">Onboarding</div>
                <h3 class="kpi-value">{{ $stats['onboarding'] }}</h3>
            </div>
        </div>
        <div class="col">
            <div class="kpi-card activation">
                <div class="kpi-title">Activation</div>
                <h3 class="kpi-value">{{ $stats['activation'] }}</h3>
            </div>
        </div>
        <div class="col">
            <div class="kpi-card engagement">
                <div class="kpi-title">Engagement</div>
                <h3 class="kpi-value">{{ $stats['engagement'] }}</h3>
            </div>
        </div>
        <div class="col">
            <div class="kpi-card retention">
                <div class="kpi-title">Retention</div>
                <h3 class="kpi-value">{{ $stats['retention'] }}</h3>
            </div>
        </div>
        <div class="col">
            <div class="kpi-card winback">
                <div class="kpi-title">Winback</div>
                <h3 class="kpi-value">{{ $stats['winback'] }}</h3>
            </div>
        </div>
    </div>

    <div class="table-panel mb-4">
        <div class="table-panel-header">
            <h5 class="mb-0 font-weight-bold">Recent Users Journey Stage</h5>
        </div>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Joined</th>
                        <th>Features Used</th>
                        <th>Current Stage</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    @php
                        $usage = $user->custom_post_used + $user->daily_drip_used + $user->magic_cloner_used + $user->festival_post_used;
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $user->name }}</strong><br>
                            <small class="text-muted">{{ $user->email }}</small>
                        </td>
                        <td>{{ $user->created_at ? $user->created_at->diffForHumans() : 'N/A' }}</td>
                        <td>{{ $usage }} tools</td>
                        <td>
                            <span class="badge-stage stage-{{ $user->journey_stage }}">{{ ucfirst($user->journey_stage) }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
