@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    @include('admin.retention.tabs')
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>AI Retention Settings</h2>
        <p class="text-muted mb-0">Configure the parameters for when AI triggers alerts and retention flows.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.retention.settings.save') }}" method="POST">
                @csrf
                
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <h5>Quota Alerts</h5>
                        <p class="text-muted small">When should the AI prompt users to upgrade?</p>
                        
                        <div class="form-group">
                            <label>Usage Threshold (%)</label>
                            <input type="number" name="quota_alert_threshold" class="form-control" value="{{ $quota_alert_threshold }}" min="50" max="100">
                            <small class="form-text text-muted">Example: 90 means AI alerts users when they hit 90% of their limit.</small>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <h5>Dynamic Discounts</h5>
                        <p class="text-muted small">When should the AI offer a tailored discount?</p>
                        
                        <div class="form-group">
                            <label>Health Score Threshold</label>
                            <input type="number" name="dynamic_discount_threshold" class="form-control" value="{{ $dynamic_discount_threshold }}" min="10" max="100">
                            <small class="form-text text-muted">Example: 40 means AI offers discounts to users with a health score below 40.</small>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <h5>Winback Reactivations</h5>
                        <p class="text-muted small">When should the AI try to recover an expired user?</p>
                        
                        <div class="form-group">
                            <label>Days Since Expiration</label>
                            <input type="number" name="winback_days_expired" class="form-control" value="{{ $winback_days_expired }}" min="1" max="90">
                            <small class="form-text text-muted">Example: 30 means AI sends a 1-click token exactly 30 days after expiry.</small>
                        </div>
                    </div>
                </div>

                <hr>
                
                <button type="submit" class="btn btn-primary px-4 py-2 font-weight-bold">
                    <i class="fa fa-save mr-2"></i> Save AI Settings
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
