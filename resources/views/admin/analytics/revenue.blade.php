@extends('layouts.app')

@section('extra_css')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
    .revenue-dashboard {
        font-family: 'Inter', sans-serif;
        padding: 30px;
    }
    .metric-card {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        border-radius: 16px;
        padding: 30px;
        color: white;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        position: relative;
        overflow: hidden;
    }
    .metric-title {
        font-size: 16px;
        color: #94a3b8;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 10px;
    }
    .metric-value {
        font-size: 42px;
        font-weight: 800;
        margin: 0;
        background: linear-gradient(to right, #38bdf8, #818cf8);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .metric-sub {
        font-size: 14px;
        color: #cbd5e1;
        margin-top: 10px;
    }
    .icon-bg {
        position: absolute;
        right: -20px;
        bottom: -20px;
        font-size: 120px;
        opacity: 0.05;
    }
</style>
@endsection

@section('content')
<div class="revenue-dashboard">
    @include('admin.retention.tabs')
    <div class="row mb-4">
        <div class="col-12">
            <h2 style="font-weight: 800; color: #333;">Advanced Payment Analytics</h2>
            <p class="text-muted">Real-time MRR, ARR, and Customer Lifetime Value tracking.</p>
        </div>
    </div>

    <div class="row">
        <!-- MRR -->
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="metric-card">
                <i class="fa fa-dollar-sign icon-bg"></i>
                <div class="metric-title">Monthly Recurring Revenue (MRR)</div>
                <h3 class="metric-value">${{ number_format($mrr, 2) }}</h3>
                <div class="metric-sub">Based on {{ $activeSubscribers }} active subscribers</div>
            </div>
        </div>

        <!-- ARR -->
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="metric-card">
                <i class="fa fa-chart-line icon-bg"></i>
                <div class="metric-title">Annual Recurring Revenue (ARR)</div>
                <h3 class="metric-value">${{ number_format($arr, 2) }}</h3>
                <div class="metric-sub">Projected yearly income</div>
            </div>
        </div>

        <!-- LTV -->
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="metric-card">
                <i class="fa fa-users icon-bg"></i>
                <div class="metric-title">Lifetime Value (LTV)</div>
                <h3 class="metric-value">${{ number_format($ltv, 2) }}</h3>
                <div class="metric-sub">Avg revenue per user before churn ({{ number_format($churnRate * 100, 1) }}% churn)</div>
            </div>
        </div>
    </div>
</div>
@endsection
