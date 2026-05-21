@extends('layouts.app')

@section('extra_css')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap');

    .god-view-container {
        font-family: 'Space Grotesk', sans-serif;
        padding: 1.5rem;
        background-color: #0f172a;
        min-height: 100vh;
        color: #f8fafc;
    }

    .page-title {
        font-weight: 700;
        color: #f8fafc;
        font-size: 1.8rem;
        letter-spacing: -0.025em;
        text-shadow: 0 0 10px rgba(99, 102, 241, 0.5);
    }

    .god-card {
        background: #1e293b;
        border-radius: 16px;
        border: 1px solid #334155;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
        overflow: hidden;
        margin-bottom: 1.5rem;
        transition: transform 0.2s;
    }

    .god-card:hover {
        transform: translateY(-2px);
        border-color: #4f46e5;
    }

    .god-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #334155;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(180deg, rgba(30, 41, 59, 1) 0%, rgba(15, 23, 42, 1) 100%);
    }

    .god-card-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #e2e8f0;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .god-card-body {
        padding: 1.5rem;
    }

    .metric-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: #f8fafc;
        line-height: 1;
    }

    .metric-label {
        font-size: 0.875rem;
        color: #94a3b8;
        margin-top: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .alert-item {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 0.75rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #0f172a;
        border-left: 4px solid;
    }

    .alert-critical { border-left-color: #ef4444; }
    .alert-warning { border-left-color: #f59e0b; }
    .alert-competitor { border-left-color: #3b82f6; }

    .progress-bar-custom {
        height: 8px;
        border-radius: 4px;
        background-color: #334155;
        overflow: hidden;
        margin-top: 0.5rem;
    }

    .progress-fill {
        height: 100%;
        border-radius: 4px;
    }

    .fill-positive { background-color: #10b981; }
    .fill-neutral { background-color: #f59e0b; }
    .fill-negative { background-color: #ef4444; }

    .btn-resolve {
        background: transparent;
        color: #94a3b8;
        border: 1px solid #475569;
        padding: 0.25rem 0.75rem;
        border-radius: 6px;
        font-size: 0.75rem;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-resolve:hover {
        background: #475569;
        color: white;
        text-decoration: none;
    }
</style>
@endsection

@section('content')
<div class="god-view-container">
    <div class="row align-items-center mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="page-title mb-1"><i class="fa-solid fa-eye mr-2" style="color: #6366f1;"></i> The God View</h4>
                <p class="text-muted mb-0" style="font-size: 0.9rem; color: #94a3b8 !important;">Real-time SaaS telemetry & health metrics.</p>
            </div>
            <div>
                <span class="badge badge-success p-2" style="background: #10b981; color: #022c22; font-weight: bold;"><i class="fa fa-circle text-white" style="font-size: 8px; vertical-align: middle; margin-right: 4px;"></i> SYSTEM ONLINE</span>
            </div>
        </div>
    </div>

    <!-- Top Key Metrics -->
    <div class="row">
        <div class="col-md-3">
            <div class="god-card text-center">
                <div class="god-card-body">
                    <div class="metric-value text-success">${{ number_format($monthlyRevenue, 2) }}</div>
                    <div class="metric-label">Monthly Revenue</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="god-card text-center">
                <div class="god-card-body">
                    <div class="metric-value text-primary">{{ number_format($totalUsers) }}</div>
                    <div class="metric-label">Total Active Users</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="god-card text-center">
                <div class="god-card-body">
                    <div class="metric-value @if($criticalAlertsCount > 0) text-danger @else text-success @endif">{{ $criticalAlertsCount }}</div>
                    <div class="metric-label">Critical Alerts</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="god-card text-center">
                <div class="god-card-body">
                    <div class="metric-value text-warning">{{ number_format($newUsersThisMonth) }}</div>
                    <div class="metric-label">New Signups (Mtd)</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- System Alerts Panel -->
        <div class="col-md-7">
            <div class="god-card" style="min-height: 400px;">
                <div class="god-card-header">
                    <h5 class="god-card-title"><i class="fa-solid fa-shield-halved" style="color: #ef4444;"></i> Active Anomalies & Alerts</h5>
                </div>
                <div class="god-card-body">
                    @forelse($activeAlerts as $alert)
                        <div class="alert-item @if($alert->severity == 'critical') alert-critical @else alert-warning @endif">
                            <div>
                                <strong style="color: #f8fafc; text-transform: uppercase; font-size: 0.8rem;">[{{ $alert->type }}]</strong>
                                <span style="color: #cbd5e1; font-size: 0.9rem; margin-left: 10px;">{{ $alert->message }}</span>
                                <div style="font-size: 0.75rem; color: #64748b; margin-top: 4px;">{{ $alert->created_at->diffForHumans() }}</div>
                            </div>
                            <div>
                                <a href="{{ route('admin.god_view.resolve', $alert->id) }}" class="btn-resolve">Resolve</a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class="fa-solid fa-check-circle" style="font-size: 3rem; color: #10b981; margin-bottom: 1rem;"></i>
                            <h5 style="color: #94a3b8;">All Systems Nominal</h5>
                            <p style="color: #64748b; font-size: 0.9rem;">No active alerts or anomalies detected.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right Side panels -->
        <div class="col-md-5">
            <!-- Sentiment Dashboard -->
            <div class="god-card mb-4">
                <div class="god-card-header">
                    <h5 class="god-card-title"><i class="fa-solid fa-heart-pulse" style="color: #ec4899;"></i> User Sentiment (This Month)</h5>
                </div>
                <div class="god-card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span style="color: #10b981; font-weight: bold;">Positive</span>
                        <span style="color: #f8fafc;">{{ round(($sentimentStats['positive'] / $sentimentStats['total']) * 100) }}%</span>
                    </div>
                    <div class="progress-bar-custom mb-3"><div class="progress-fill fill-positive" style="width: {{ ($sentimentStats['positive'] / $sentimentStats['total']) * 100 }}%"></div></div>

                    <div class="d-flex justify-content-between mb-2">
                        <span style="color: #f59e0b; font-weight: bold;">Neutral</span>
                        <span style="color: #f8fafc;">{{ round(($sentimentStats['neutral'] / $sentimentStats['total']) * 100) }}%</span>
                    </div>
                    <div class="progress-bar-custom mb-3"><div class="progress-fill fill-neutral" style="width: {{ ($sentimentStats['neutral'] / $sentimentStats['total']) * 100 }}%"></div></div>

                    <div class="d-flex justify-content-between mb-2">
                        <span style="color: #ef4444; font-weight: bold;">Negative (Churn Risk)</span>
                        <span style="color: #f8fafc;">{{ round(($sentimentStats['negative'] / $sentimentStats['total']) * 100) }}%</span>
                    </div>
                    <div class="progress-bar-custom"><div class="progress-fill fill-negative" style="width: {{ ($sentimentStats['negative'] / $sentimentStats['total']) * 100 }}%"></div></div>
                </div>
            </div>

            <!-- Competitor Intel -->
            <div class="god-card">
                <div class="god-card-header">
                    <h5 class="god-card-title"><i class="fa-solid fa-satellite-dish" style="color: #3b82f6;"></i> Competitor Intel</h5>
                </div>
                <div class="god-card-body p-0">
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        @forelse($competitors as $comp)
                            @php($stats = json_decode($comp->last_social_stats, true))
                            <li style="padding: 1rem 1.5rem; border-bottom: 1px solid #334155;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong style="color: #f8fafc;">{{ $comp->name }}</strong>
                                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 4px;">Last check: {{ $comp->last_checked_at ? \Carbon\Carbon::parse($comp->last_checked_at)->diffForHumans() : 'Never' }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div style="color: #10b981; font-weight: 600; font-size: 0.9rem;">
                                            <i class="fa-brands fa-instagram mr-1"></i> {{ isset($stats['followers']) ? number_format($stats['followers']) : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li style="padding: 1.5rem; text-align: center; color: #94a3b8;">No competitors tracked yet. Add via DB.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
