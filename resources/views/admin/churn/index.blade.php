@extends('layouts.app')

@section('extra_css')
<style>
    /* Modern Analytics Dashboard Styling matching AI Analytics */
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
        color: white;
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

    .kpi-card.requests::before { background: linear-gradient(to right, #ef4444, #b91c1c); }
    .kpi-card.tokens::before { background: linear-gradient(to right, #fbbf24, #f59e0b); }
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

    .requests .kpi-icon { background: #fee2e2; color: #e11d48; }
    .tokens .kpi-icon { background: #fef3c7; color: #d97706; }
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

    .icon-user { background: #e0e7ff; color: #4338ca; }

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

    .badge-soft {
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        display: inline-block;
    }
    
    .badge-risk-high { background-color: #fee2e2; color: #e11d48;}
    .badge-risk-medium { background-color: #fef3c7; color: #d97706;}
    .badge-risk-low { background-color: #d1fae5; color: #059669;}

    .btn-ai {
        background: linear-gradient(135deg, #a78bfa, #8b5cf6);
        color: white;
        border: none;
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        font-size: 0.8rem;
        transition: opacity 0.2s;
    }
    .btn-ai:hover { opacity: 0.9; color: white;}

    .strategy-box {
        display: none;
        background: #f8fafc;
        border-radius: 12px;
        padding: 1.5rem;
        margin: 1rem 1.5rem;
        border: 1px solid #e2e8f0;
        border-left: 4px solid #8b5cf6;
    }
    .font-math {
        font-variant-numeric: tabular-nums;
    }
</style>
@endsection

@section('content')
<div class="analytics-container">
    <!-- Header Section -->
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h4 class="page-title mb-0"><i class="fa-solid fa-heart-pulse mr-2 text-danger"></i> AI Churn Analytics</h4>
        </div>
        <div class="col-md-6 text-right">
            <button class="btn-filter" onclick="window.location.reload()"><i class="fas fa-sync-alt mr-1"></i> Refresh Data</button>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-4 mb-4">
            <div class="kpi-card requests">
                <div class="kpi-title">
                    <div class="kpi-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    High Risk Users
                </div>
                <h3 class="kpi-value font-math">{{ $stats['total_high_risk'] }}</h3>
                <div class="kpi-subtitle">Users likely to churn</div>
            </div>
        </div>
        
        <div class="col-xl-4 col-md-4 mb-4">
            <div class="kpi-card tokens">
                <div class="kpi-title">
                    <div class="kpi-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
                    Medium Risk Users
                </div>
                <h3 class="kpi-value font-math">{{ $stats['total_medium_risk'] }}</h3>
                <div class="kpi-subtitle">Users needing attention</div>
            </div>
        </div>

        <div class="col-xl-4 col-md-4 mb-4">
            <div class="kpi-card cost">
                <div class="kpi-title">
                    <div class="kpi-icon"><i class="fa-solid fa-heartbeat"></i></div>
                    Average Health Score
                </div>
                <h3 class="kpi-value font-math">{{ $stats['avg_health'] }}/100</h3>
                <div class="kpi-subtitle">Overall platform health</div>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="table-panel">
                <div class="table-panel-header">
                    <div class="table-icon-wrapper icon-user">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <h5 class="table-panel-title">At-Risk User Details</h5>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>User Profile</th>
                                <th>Last Active</th>
                                <th>Health Score</th>
                                <th>Risk Level</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($user->image)
                                            <img src="{{ asset('storage/' . $user->image) }}" class="rounded-circle mr-2" width="36" height="36" style="object-fit: cover; border: 2px solid #e2e8f0;">
                                        @else
                                            <div class="rounded-circle d-flex justify-content-center align-items-center mr-2" style="width:36px; height:36px; background:#e2e8f0; color:#475569; font-weight:600;">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
                                        @endif
                                        <div>
                                            <div class="font-weight-bold text-dark">{{ $user->name }}</div>
                                            <div class="text-muted" style="font-size:0.75rem;">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $user->last_active_at ? \Carbon\Carbon::parse($user->last_active_at)->diffForHumans() : 'Never' }}</td>
                                <td>
                                    <div class="progress mb-1" style="height: 6px; width: 100px; background-color:#e2e8f0; border-radius:3px;">
                                        <div class="progress-bar {{ $user->health_score < 40 ? 'bg-danger' : ($user->health_score < 70 ? 'bg-warning' : 'bg-success') }}" 
                                             role="progressbar" style="width: {{ $user->health_score }}%; border-radius:3px;"></div>
                                    </div>
                                    <span class="font-math font-weight-bold" style="font-size:0.8rem;">{{ $user->health_score }}</span><span class="text-muted" style="font-size:0.75rem;">/100</span>
                                </td>
                                <td>
                                    <span class="badge-soft badge-risk-{{ $user->churn_risk }}">{{ ucfirst($user->churn_risk) }}</span>
                                </td>
                                <td>
                                    <button type="button" class="btn-ai" onclick="generateStrategy({{ $user->id }}, this)">
                                        <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Ask AI Strategy
                                    </button>
                                </td>
                            </tr>
                            <tr id="strategy-row-{{ $user->id }}" style="display:none;">
                                <td colspan="5" class="p-0 border-0">
                                    <div class="strategy-box" id="strategy-content-{{ $user->id }}">
                                        <!-- AI Content goes here -->
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                            @if(count($users) == 0)
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted border-0">No users found.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-top">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
// Global store for AI-generated data per user (avoids inline onclick encoding issues)
var _churnData = {};

function generateStrategy(userId, btn) {
    let row = document.getElementById('strategy-row-' + userId);
    let box = document.getElementById('strategy-content-' + userId);
    
    // Toggle close if already open and not loading
    if(row.style.display === 'table-row' && box.innerHTML.indexOf('fa-spinner') === -1) {
        row.style.display = 'none';
        return;
    }

    row.style.display = 'table-row';
    box.style.display = 'block';
    box.innerHTML = '<div class="text-center py-4"><i class="fas fa-circle-notch fa-spin fa-2x text-primary mb-2"></i><p class="text-muted mb-0">AI is analyzing user behavior...</p></div>';
    
    let originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
    btn.disabled = true;

    fetch(`{{ url('admin/churn/generate-strategy') }}/${userId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            // Store the AI data globally so buttons can access it safely
            _churnData[userId] = data.data;

            let html = '<h6 class="font-weight-bold" style="color: #4338ca;"><i class="fa-solid fa-chess-knight mr-2"></i> AI Retention Strategy</h6>';
            html += '<ul class="text-dark mb-4" style="padding-left: 1.2rem;">';
            data.data.strategy_steps.forEach(function(step) {
                html += '<li class="mb-1">' + step + '</li>';
            });
            html += '</ul>';
            
            // Email Section
            html += '<div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.2rem; margin-bottom: 1rem;">';
            html += '<div class="d-flex justify-content-between align-items-center mb-3">';
            html += '<h6 class="font-weight-bold mb-0" style="color: #0ea5e9;"><i class="fa-regular fa-envelope mr-2"></i> Suggested Outreach Email</h6>';
            html += '<button class="btn btn-sm btn-primary" id="send-mail-btn-' + userId + '" onclick="sendMail(' + userId + ', this)"><i class="fa-regular fa-paper-plane mr-1"></i> Send Mail</button>';
            html += '</div>';
            html += '<div class="mb-2"><span class="badge-soft" style="background:#f1f5f9;">Subject:</span> <strong class="text-dark">' + data.data.email_subject + '</strong></div>';
            html += '<div class="text-dark mt-3" style="white-space: pre-wrap; font-size: 0.9rem;">' + data.data.email_body + '</div>';
            html += '</div>';

            // Push Notification Section
            if (data.data.push_title && data.data.push_message) {
                html += '<div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.2rem;">';
                html += '<div class="d-flex justify-content-between align-items-center mb-3">';
                html += '<h6 class="font-weight-bold mb-0" style="color: #f59e0b;"><i class="fa-regular fa-bell mr-2"></i> Suggested Push Notification</h6>';
                html += '<button class="btn btn-sm btn-warning text-white" id="send-notif-btn-' + userId + '" onclick="sendPushNotification(' + userId + ', this)"><i class="fa-solid fa-mobile-screen-button mr-1"></i> Send Notification</button>';
                html += '</div>';
                html += '<div class="mb-2"><span class="badge-soft" style="background:#f1f5f9;">Title:</span> <strong class="text-dark">' + data.data.push_title + '</strong></div>';
                html += '<div class="text-dark mt-2" style="font-size: 0.9rem;">' + data.data.push_message + '</div>';
                html += '</div>';
            }
            
            box.innerHTML = html;
        } else {
            box.innerHTML = '<div class="text-danger py-3"><i class="fa-solid fa-triangle-exclamation mr-2"></i> Error: ' + data.message + '</div>';
        }
    })
    .catch(error => {
        box.innerHTML = '<div class="text-danger py-3"><i class="fa-solid fa-triangle-exclamation mr-2"></i> Network error occurred. Please check console.</div>';
        console.error(error);
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function sendMail(userId, btn) {
    try {
        var d = _churnData[userId];
        if (!d) { alert('Error: No AI data found. Please generate strategy again.'); return; }

        var originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Sending...';
        btn.disabled = true;

        fetch('{{ url("admin/churn/send-mail") }}/' + userId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ subject: d.email_subject, body: d.email_body })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if(data.status === 'success') {
                alert('✅ ' + data.message);
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Sent';
                btn.className = 'btn btn-sm btn-success';
            } else {
                alert('❌ ' + data.message);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(function(err) {
            console.error('sendMail error:', err);
            alert('❌ Failed to send mail: ' + err.message);
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    } catch(e) {
        console.error('sendMail exception:', e);
        alert('❌ JS Error: ' + e.message);
    }
}

function sendPushNotification(userId, btn) {
    try {
        var d = _churnData[userId];
        if (!d) { alert('Error: No AI data found. Please generate strategy again.'); return; }

        var originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Sending...';
        btn.disabled = true;

        fetch('{{ url("admin/churn/send-notification") }}/' + userId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ title: d.push_title, message: d.push_message })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if(data.status === 'success') {
                alert('✅ ' + data.message);
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Sent';
                btn.className = 'btn btn-sm btn-success text-white';
            } else {
                alert('❌ ' + data.message);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(function(err) {
            console.error('sendPushNotification error:', err);
            alert('❌ Failed to send notification: ' + err.message);
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    } catch(e) {
        console.error('sendPushNotification exception:', e);
        alert('❌ JS Error: ' + e.message);
    }
}
</script>
@endsection

