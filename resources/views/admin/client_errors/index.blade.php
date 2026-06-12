@extends("layouts.app")

@section('extra_css')
<style>
    /* ═══════════════════════════════════════════════════════════════
       AI-Powered Reported Errors Dashboard
       ═══════════════════════════════════════════════════════════════ */

    /* AI Summary Cards */
    .ai-stat-card {
        border-radius: 14px;
        padding: 18px 20px;
        color: #fff;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        min-height: 110px;
    }
    .ai-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    .ai-stat-card .stat-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 2.5rem;
        opacity: 0.2;
    }
    .ai-stat-card h2 {
        font-size: 2rem;
        font-weight: 800;
        margin: 0;
    }
    .ai-stat-card p {
        margin: 2px 0 0;
        font-size: 0.82rem;
        opacity: 0.9;
        font-weight: 500;
    }
    .bg-gradient-critical { background: linear-gradient(135deg, #dc3545 0%, #a71d2a 100%); }
    .bg-gradient-ux { background: linear-gradient(135deg, #6f42c1 0%, #4a1d96 100%); }
    .bg-gradient-analyzed { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); }
    .bg-gradient-patterns { background: linear-gradient(135deg, #198754 0%, #0f5132 100%); }
    .bg-gradient-pending { background: linear-gradient(135deg, #fd7e14 0%, #c35a02 100%); }
    .bg-gradient-total { background: linear-gradient(135deg, #495057 0%, #212529 100%); }

    /* Pulse animation for critical */
    @keyframes pulse-critical {
        0%, 100% { box-shadow: 0 0 0 0 rgba(220,53,69,0.4); }
        50% { box-shadow: 0 0 0 10px rgba(220,53,69,0); }
    }
    .pulse-critical { animation: pulse-critical 2s infinite; }

    /* AI Insights Panel */
    .ai-insights-panel {
        border-radius: 14px;
        border: 1px solid #e9ecef;
        background: #fff;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    }
    .ai-insights-panel .panel-header {
        padding: 14px 20px;
        border-bottom: 1px solid #f0f0f0;
        font-weight: 700;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .insight-item {
        padding: 12px 20px;
        border-bottom: 1px solid #f8f9fa;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        transition: background 0.2s;
    }
    .insight-item:hover { background: #f8f9fa; }
    .insight-item:last-child { border-bottom: none; }
    .insight-severity-dot {
        width: 10px; height: 10px;
        border-radius: 50%;
        margin-top: 5px;
        flex-shrink: 0;
    }

    /* Pattern Group Chips */
    .pattern-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        background: #f0f0f0;
        color: #495057;
        margin: 3px;
        transition: all 0.2s;
    }
    .pattern-chip:hover {
        background: #e2e6ea;
        transform: scale(1.03);
        cursor: pointer;
        text-decoration: none;
        color: #212529;
    }
    .pattern-chip .count {
        background: rgba(0,0,0,0.1);
        border-radius: 10px;
        padding: 1px 7px;
        font-size: 0.7rem;
    }

    /* Severity Badges */
    .severity-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .severity-critical { background: rgba(220,53,69,0.12); color: #dc3545; }
    .severity-high { background: rgba(253,126,20,0.12); color: #e0700d; }
    .severity-medium { background: rgba(255,193,7,0.12); color: #997404; }
    .severity-low { background: rgba(40,167,69,0.12); color: #198754; }
    .severity-info { background: rgba(108,117,125,0.12); color: #6c757d; }

    /* Category Tag */
    .category-tag {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 600;
        background: #e8f0fe;
        color: #1967d2;
    }
    .category-tag.ux-bug { background: #fce4ec; color: #c62828; }

    /* UX Bug Badge */
    .ux-badge {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.68rem;
        font-weight: 700;
        background: linear-gradient(135deg, #ff6b6b, #ee5a24);
        color: #fff;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Confidence Meter */
    .confidence-meter {
        width: 50px;
        height: 5px;
        background: #e9ecef;
        border-radius: 3px;
        overflow: hidden;
        display: inline-block;
        vertical-align: middle;
    }
    .confidence-meter-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.5s ease;
    }

    /* AI Analyze Button */
    .btn-ai-analyze {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 5px 12px;
        font-size: 0.75rem;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .btn-ai-analyze:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(102,126,234,0.4);
        color: #fff;
    }
    .btn-ai-analyze:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .btn-ai-batch {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 8px 20px;
        font-weight: 700;
        font-size: 0.85rem;
        transition: all 0.3s;
    }
    .btn-ai-batch:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(17,153,142,0.4);
        color: #fff;
    }

    /* Filter Bar */
    .filter-bar {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 12px 16px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        margin-bottom: 16px;
    }
    .filter-bar select, .filter-bar .btn {
        font-size: 0.8rem;
        border-radius: 8px;
    }

    /* Auto-Analyze Toggle */
    .auto-toggle {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .toggle-switch {
        position: relative;
        width: 38px; height: 20px;
        background: #ccc;
        border-radius: 20px;
        cursor: pointer;
        transition: background 0.3s;
    }
    .toggle-switch.active { background: #28a745; }
    .toggle-switch::after {
        content: '';
        position: absolute;
        top: 2px; left: 2px;
        width: 16px; height: 16px;
        background: #fff;
        border-radius: 50%;
        transition: transform 0.3s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .toggle-switch.active::after { transform: translateX(18px); }

    /* AI Root Cause / Fix collapsible */
    .ai-detail-toggle {
        cursor: pointer;
        color: #6c757d;
        font-size: 0.78rem;
        transition: color 0.2s;
    }
    .ai-detail-toggle:hover { color: #0d6efd; }
    .ai-detail-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s ease;
    }
    .ai-detail-content.open {
        max-height: 500px;
    }
    .ai-detail-box {
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 0.8rem;
        line-height: 1.5;
        margin-top: 6px;
    }
    .root-cause-box { background: #fff3cd; border-left: 3px solid #ffc107; color: #664d03; }
    .fix-box { background: #d1e7dd; border-left: 3px solid #198754; color: #0f5132; }

    /* Enhanced Table */
    .error-table { border-radius: 14px; overflow: hidden; }
    .error-table thead { background: #f8f9fa; }
    .error-table thead th {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 700;
        color: #6c757d;
        border: none;
        padding: 12px 10px;
    }
    .error-table tbody tr {
        transition: background 0.2s;
        border-bottom: 1px solid #f0f0f0;
    }
    .error-table tbody tr:hover { background: #f8f9fe; }

    /* Spinner for AI analyzing */
    .ai-spinner {
        display: inline-block;
        width: 14px; height: 14px;
        border: 2px solid rgba(255,255,255,0.3);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Status dropdown styling */
    .status-badge-btn {
        font-size: 0.78rem;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
        border: none;
        cursor: pointer;
    }

    /* Bulk actions bar */
    .bulk-actions {
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        opacity: 0;
        transform: translateY(-10px);
        pointer-events: none;
    }
    .bulk-actions.active {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }

    /* Empty state */
    .empty-state {
        padding: 60px 20px;
        text-align: center;
    }
    .empty-state i { font-size: 4rem; color: #dee2e6; margin-bottom: 15px; }
</style>
@endsection

@section('content')
<div class="container-fluid">

    {{-- ═══════════════ AI STATS DASHBOARD ═══════════════ --}}
    <div class="row mb-3">
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="ai-stat-card bg-gradient-total">
                <h2>{{ $totalErrors }}</h2>
                <p>Total Errors</p>
                <i class="fas fa-bug stat-icon"></i>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="ai-stat-card bg-gradient-critical {{ $criticalCount > 0 ? 'pulse-critical' : '' }}">
                <h2>{{ $criticalCount }}</h2>
                <p>🔴 Critical Bugs</p>
                <i class="fas fa-skull-crossbones stat-icon"></i>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="ai-stat-card bg-gradient-ux">
                <h2>{{ $uxBugCount }}</h2>
                <p>🎨 UI/UX Bugs</p>
                <i class="fas fa-paint-brush stat-icon"></i>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="ai-stat-card bg-gradient-analyzed">
                <h2>{{ $totalErrors > 0 ? round(($analyzedCount / $totalErrors) * 100) : 0 }}%</h2>
                <p>🧠 AI Analyzed</p>
                <i class="fas fa-robot stat-icon"></i>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="ai-stat-card bg-gradient-patterns">
                <h2>{{ $patternGroups->count() }}</h2>
                <p>🧩 Bug Patterns</p>
                <i class="fas fa-project-diagram stat-icon"></i>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="ai-stat-card bg-gradient-pending">
                <h2>{{ $pendingAnalysis }}</h2>
                <p>⏳ Pending Scan</p>
                <i class="fas fa-hourglass-half stat-icon"></i>
            </div>
        </div>
    </div>

    {{-- ═══════════════ AI INSIGHTS + PATTERNS ═══════════════ --}}
    <div class="row mb-3">
        {{-- Critical Issues Panel --}}
        <div class="col-lg-6 mb-3">
            <div class="ai-insights-panel h-100">
                <div class="panel-header">
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                    Top Critical Issues (AI Detected)
                </div>
                @forelse($criticalIssues as $issue)
                <div class="insight-item">
                    <div class="insight-severity-dot" style="background: {{ $issue->severity_color }};"></div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-size:0.82rem; font-weight:600; color:#212529;">
                            {{ Str::limit($issue->ai_root_cause ?: $issue->error_code, 90) }}
                        </div>
                        @if($issue->ai_suggested_fix)
                        <div style="font-size:0.75rem; color:#198754; margin-top:3px;">
                            <i class="fas fa-wrench"></i> {{ Str::limit($issue->ai_suggested_fix, 80) }}
                        </div>
                        @endif
                        <div style="font-size:0.7rem; color:#adb5bd; margin-top:2px;">
                            {{ $issue->created_at->diffForHumans() }}
                            @if($issue->ai_is_ux_bug) <span class="ux-badge ml-1"><i class="fas fa-eye"></i> UX</span> @endif
                        </div>
                    </div>
                    <span class="severity-badge severity-{{ $issue->ai_severity }}">{{ strtoupper($issue->ai_severity) }}</span>
                </div>
                @empty
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-check-circle fa-2x mb-2 d-block" style="color:#dee2e6;"></i>
                    <span style="font-size:0.85rem;">No critical issues found! 🎉</span>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Pattern Groups Panel --}}
        <div class="col-lg-6 mb-3">
            <div class="ai-insights-panel h-100">
                <div class="panel-header">
                    <i class="fas fa-project-diagram text-success"></i>
                    Recurring Bug Patterns
                </div>
                <div class="p-3">
                    @forelse($patternGroups as $pg)
                        <a href="{{ route('admin.reported_errors', ['pattern' => $pg->ai_pattern_group]) }}" class="pattern-chip">
                            @php
                                $pColor = match($pg->max_severity) {
                                    'critical' => '#dc3545',
                                    'high' => '#fd7e14',
                                    'medium' => '#ffc107',
                                    default => '#6c757d',
                                };
                            @endphp
                            <span style="width:8px;height:8px;border-radius:50%;background:{{ $pColor }};display:inline-block;"></span>
                            {{ str_replace('_', ' ', $pg->ai_pattern_group) }}
                            <span class="count">{{ $pg->count }}</span>
                        </a>
                    @empty
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-robot fa-2x mb-2 d-block" style="color:#dee2e6;"></i>
                            <span style="font-size:0.85rem;">Run AI analysis to detect patterns</span>
                        </div>
                    @endforelse
                </div>

                @if($categories->isNotEmpty())
                <div class="panel-header" style="border-top:1px solid #f0f0f0;">
                    <i class="fas fa-tags text-primary"></i>
                    Categories Breakdown
                </div>
                <div class="p-3">
                    @foreach($categories as $cat)
                    <div class="d-flex justify-content-between align-items-center mb-2" style="font-size:0.82rem;">
                        <span class="category-tag {{ $cat->ai_category == 'UI Bug' || $cat->ai_category == 'UX Flow Issue' ? 'ux-bug' : '' }}">
                            {{ $cat->ai_category }}
                        </span>
                        <span class="font-weight-bold text-dark">{{ $cat->count }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════ FILTER BAR + ACTIONS ═══════════════ --}}
    <div class="filter-bar">
        <form method="GET" action="{{ route('admin.reported_errors') }}" class="d-flex flex-wrap gap-2 align-items-center" style="gap:10px;" id="filter_form">
            <select name="severity" class="form-control form-control-sm" style="width:130px;" onchange="this.form.submit()">
                <option value="">All Severity</option>
                <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>🔴 Critical</option>
                <option value="high" {{ request('severity') == 'high' ? 'selected' : '' }}>🟠 High</option>
                <option value="medium" {{ request('severity') == 'medium' ? 'selected' : '' }}>🟡 Medium</option>
                <option value="low" {{ request('severity') == 'low' ? 'selected' : '' }}>🟢 Low</option>
                <option value="info" {{ request('severity') == 'info' ? 'selected' : '' }}>⚪ Info</option>
            </select>

            <select name="category" class="form-control form-control-sm" style="width:140px;" onchange="this.form.submit()">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->ai_category }}" {{ request('category') == $cat->ai_category ? 'selected' : '' }}>{{ $cat->ai_category }}</option>
                @endforeach
            </select>

            <select name="status" class="form-control form-control-sm" style="width:120px;" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                <option value="Resolved" {{ request('status') == 'Resolved' ? 'selected' : '' }}>Resolved</option>
                <option value="In Progress" {{ request('status') == 'In Progress' ? 'selected' : '' }}>In Progress</option>
            </select>

            <select name="ux_only" class="form-control form-control-sm" style="width:130px;" onchange="this.form.submit()">
                <option value="">All Bugs</option>
                <option value="1" {{ request('ux_only') == '1' ? 'selected' : '' }}>🎨 UX Bugs Only</option>
            </select>

            <select name="analyzed" class="form-control form-control-sm" style="width:130px;" onchange="this.form.submit()">
                <option value="">All Analysis</option>
                <option value="yes" {{ request('analyzed') == 'yes' ? 'selected' : '' }}>✅ AI Analyzed</option>
                <option value="no" {{ request('analyzed') == 'no' ? 'selected' : '' }}>⏳ Not Analyzed</option>
            </select>

            @if(request()->hasAny(['severity','category','status','ux_only','analyzed','pattern']))
            <a href="{{ route('admin.reported_errors') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times"></i> Clear
            </a>
            @endif
        </form>

        <div class="ml-auto d-flex align-items-center" style="gap:10px;">
            {{-- Auto-Analyze Toggle --}}
            <div class="auto-toggle">
                <span>Auto AI Scan</span>
                <div class="toggle-switch {{ $autoAnalyzeEnabled ? 'active' : '' }}" id="auto_analyze_toggle" title="Daily auto-analyze at 4:00 AM"></div>
            </div>

            {{-- Batch Analyze Button --}}
            @if($pendingAnalysis > 0)
            <button type="button" class="btn-ai-batch" id="btn_batch_analyze" title="Analyze {{ $pendingAnalysis }} unanalyzed errors with AI">
                <i class="fas fa-robot"></i> 🚀 AI Scan All ({{ $pendingAnalysis }})
            </button>
            @endif
        </div>
    </div>

    {{-- ═══════════════ BULK ACTIONS BAR ═══════════════ --}}
    <div class="bulk-actions mb-2" id="bulk_actions_container">
        <button type="button" class="btn btn-outline-success btn-sm shadow-sm mr-2" id="bulk_resolve">
            <i class="fas fa-check-circle mr-1"></i> Mark Resolved (<span id="selected_count_resolve">0</span>)
        </button>
        <button type="button" class="btn btn-outline-danger btn-sm shadow-sm" id="bulk_delete">
            <i class="fas fa-trash-alt mr-1"></i> Delete Selected (<span id="selected_count">0</span>)
        </button>
    </div>

    {{-- ═══════════════ ERROR TABLE ═══════════════ --}}
    <div class="card error-table" style="border:none; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border-radius:14px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle m-0" id="reported_errors_table">
                    <thead>
                        <tr>
                            <th width="35" class="pl-3">
                                <input type="checkbox" id="select_all_errors">
                            </th>
                            <th>AI Severity</th>
                            <th>User</th>
                            <th>Error</th>
                            <th width="320">AI Analysis</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th width="90" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($errors as $row)
                        <tr id="error_row_{{ $row->id }}">
                            <td class="pl-3">
                                <input type="checkbox" class="error_checkbox" value="{{ $row->id }}">
                            </td>

                            {{-- AI Severity --}}
                            <td id="severity_cell_{{ $row->id }}">
                                @if($row->isAnalyzed())
                                    <span class="severity-badge severity-{{ $row->ai_severity }}">
                                        <i class="fas {{ $row->severity_icon }}"></i>
                                        {{ strtoupper($row->ai_severity ?? 'N/A') }}
                                    </span>
                                    @if($row->ai_is_ux_bug)
                                    <span class="ux-badge mt-1" style="display:inline-flex;">
                                        <i class="fas fa-eye"></i> UX
                                    </span>
                                    @endif
                                    @if($row->ai_category)
                                    <div class="mt-1">
                                        <span class="category-tag {{ in_array($row->ai_category, ['UI Bug','UX Flow Issue']) ? 'ux-bug' : '' }}">
                                            {{ $row->ai_category }}
                                        </span>
                                    </div>
                                    @endif
                                @else
                                    <span class="text-muted" style="font-size:0.75rem;">
                                        <i class="fas fa-hourglass-half"></i> Not scanned
                                    </span>
                                @endif
                            </td>

                            {{-- User --}}
                            <td>
                                @if($row->user)
                                <div class="d-flex align-items-center">
                                    <img src="{{ $row->user->image ? (substr($row->user->image, 0, 4)=='http' ? $row->user->image : asset('uploads/'.$row->user->image)) : asset('assets/images/no-user.jpg') }}" 
                                         class="rounded-circle mr-2 shadow-sm" width="32" height="32" style="object-fit: cover; border: 2px solid #eee;"/>
                                    <div style="line-height:1.2;">
                                        <div style="font-weight:600; font-size:0.82rem;">{{ Str::limit($row->user->name, 15) }}</div>
                                        <small class="text-muted" style="font-size:0.7rem;">{{ Str::limit($row->user->email, 20) }}</small>
                                    </div>
                                </div>
                                @else
                                <span class="text-muted" style="font-size:0.78rem;"><i class="fas fa-user-secret"></i> Guest</span>
                                @endif
                            </td>

                            {{-- Error Code + Message --}}
                            <td>
                                <code class="bg-light px-2 py-1 rounded text-danger" style="font-size: 0.75rem;">{{ $row->error_code }}</code>
                                <div class="text-wrap mt-1" style="max-width:200px; font-size:0.78rem; color:#666; word-break:break-word;">
                                    {{ Str::limit($row->error_message, 100) }}
                                </div>
                                @if($row->device_info)
                                <div style="font-size:0.68rem; color:#adb5bd; margin-top:2px;">
                                    <i class="fas fa-mobile-alt"></i> {{ Str::limit($row->device_info, 40) }}
                                </div>
                                @endif
                            </td>

                            {{-- AI Analysis (Root Cause + Fix) --}}
                            <td id="ai_cell_{{ $row->id }}">
                                @if($row->isAnalyzed())
                                    {{-- Root Cause --}}
                                    <div class="ai-detail-toggle" onclick="toggleDetail('root_{{ $row->id }}')">
                                        <i class="fas fa-lightbulb text-warning"></i>
                                        <strong>Root Cause</strong>
                                        <i class="fas fa-chevron-down ml-1" style="font-size:0.6rem;"></i>
                                    </div>
                                    <div class="ai-detail-content" id="root_{{ $row->id }}">
                                        <div class="ai-detail-box root-cause-box">{{ $row->ai_root_cause }}</div>
                                    </div>

                                    {{-- Suggested Fix --}}
                                    @if($row->ai_suggested_fix)
                                    <div class="ai-detail-toggle mt-1" onclick="toggleDetail('fix_{{ $row->id }}')">
                                        <i class="fas fa-wrench text-success"></i>
                                        <strong>Fix</strong>
                                        <i class="fas fa-chevron-down ml-1" style="font-size:0.6rem;"></i>
                                    </div>
                                    <div class="ai-detail-content" id="fix_{{ $row->id }}">
                                        <div class="ai-detail-box fix-box">{{ $row->ai_suggested_fix }}</div>
                                    </div>
                                    @endif

                                    {{-- Confidence --}}
                                    <div class="mt-1" style="font-size:0.7rem; color:#adb5bd;">
                                        Confidence: 
                                        <span class="confidence-meter">
                                            <span class="confidence-meter-fill" style="width:{{ $row->ai_confidence }}%; background:{{ $row->ai_confidence >= 80 ? '#28a745' : ($row->ai_confidence >= 50 ? '#ffc107' : '#dc3545') }};"></span>
                                        </span>
                                        <strong>{{ $row->ai_confidence }}%</strong>
                                        @if($row->ai_pattern_group)
                                        | <span title="Pattern Group" style="color:#6f42c1;"><i class="fas fa-puzzle-piece"></i> {{ $row->ai_pattern_group }}</span>
                                        @endif
                                    </div>
                                @else
                                    <button type="button" class="btn-ai-analyze btn-analyze-single" data-id="{{ $row->id }}">
                                        <i class="fas fa-robot"></i> Analyze with AI
                                    </button>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td id="status_cell_{{ $row->id }}">
                                @php
                                    $badgeClass = 'bg-warning';
                                    if($row->status == 'Resolved') $badgeClass = 'bg-success';
                                    if($row->status == 'In Progress') $badgeClass = 'bg-primary';
                                @endphp
                                <span class="badge {{ $badgeClass }} status-badge-btn">{{ $row->status }}</span>
                            </td>

                            {{-- Date --}}
                            <td>
                                <div style="font-size:0.78rem; font-weight:600;">{{ $row->created_at->format('d M') }}</div>
                                <div style="font-size:0.7rem; color:#adb5bd;">{{ $row->created_at->format('h:i A') }}</div>
                            </td>

                            {{-- Actions --}}
                            <td class="text-center">
                                <div class="d-flex justify-content-center align-items-center" style="gap:6px;">
                                    @if($row->status == 'Pending')
                                    <button type="button" class="btn btn-link text-success p-0 quick-resolve" data-id="{{ $row->id }}" title="Resolve">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                    @endif
                                    
                                    @if(!$row->isAnalyzed())
                                    <button type="button" class="btn btn-link p-0 btn-analyze-single" data-id="{{ $row->id }}" title="AI Analyze" style="color:#764ba2;">
                                        <i class="fas fa-brain"></i>
                                    </button>
                                    @endif

                                    <form action="{{ route('admin.reported_errors.destroy', $row->id) }}" method="POST" onsubmit="return confirm('Delete this error report?');" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link text-danger p-0" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fas fa-check-circle d-block"></i>
                                    <h5 class="text-muted mt-2">No errors found</h5>
                                    <p class="text-muted" style="font-size:0.85rem;">Everything is running smooth! 🎉</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($errors->hasPages())
        <div class="card-footer bg-white py-3" style="border-top:1px solid #f0f0f0;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    Showing {{ $errors->firstItem() }} to {{ $errors->lastItem() }} of {{ $errors->total() }}
                </div>
                <div>
                    {{ $errors->appends(request()->input())->onEachSide(1)->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('script')
<script>
$(document).ready(function() {

    // ── Checkbox & Bulk Actions ──
    function toggleBulkActions() {
        var count = $('.error_checkbox:checked').length;
        if (count > 0) {
            $('#bulk_actions_container').addClass('active');
            $('#selected_count').text(count);
            $('#selected_count_resolve').text(count);
        } else {
            $('#bulk_actions_container').removeClass('active');
        }
    }

    $(document).on('click', '#select_all_errors', function() {
        var isChecked = $(this).prop('checked');
        $('.error_checkbox').prop('checked', isChecked);
        if ($.fn.iCheck) { $('.error_checkbox').iCheck(isChecked ? 'check' : 'uncheck'); }
        toggleBulkActions();
    });

    $(document).on('change', '.error_checkbox', function() {
        var total = $('.error_checkbox').length;
        var checked = $('.error_checkbox:checked').length;
        $('#select_all_errors').prop('checked', total === checked && total > 0);
        toggleBulkActions();
    });

    // iCheck events
    if ($.fn.iCheck) {
        $('input[type="checkbox"]').iCheck({ checkboxClass: 'icheckbox_square-blue' });
        $(document).on('ifChanged', '#select_all_errors', function(e) {
            var isChecked = e.target.checked;
            $('.error_checkbox').prop('checked', isChecked);
            if ($.fn.iCheck) { $('.error_checkbox').iCheck(isChecked ? 'check' : 'uncheck'); }
            toggleBulkActions();
        });
        $(document).on('ifChanged', '.error_checkbox', function() {
            var total = $('.error_checkbox').length;
            var checked = $('.error_checkbox:checked').length;
            if (total === checked && total > 0) {
                $('#select_all_errors').prop('checked', true);
                if ($.fn.iCheck) { $('#select_all_errors').iCheck('check'); }
            } else {
                $('#select_all_errors').prop('checked', false);
                if ($.fn.iCheck) { $('#select_all_errors').iCheck('uncheck'); }
            }
            toggleBulkActions();
        });
    }

    // ── Quick Resolve ──
    $(document).on('click', '.quick-resolve', function() {
        var id = $(this).data('id');
        var btn = $(this);
        $.ajax({
            url: "{{ url('admin/reported-errors/status') }}/" + id,
            type: 'POST',
            data: { _token: "{{ csrf_token() }}", status: 'Resolved' },
            success: function(response) {
                if (typeof toastr !== 'undefined') toastr.success(response.message);
                $('#status_cell_' + id).html('<span class="badge bg-success status-badge-btn">Resolved</span>');
                btn.fadeOut();
            }
        });
    });

    // ── Bulk Resolve ──
    $('#bulk_resolve').on('click', function() {
        var ids = [];
        $('.error_checkbox:checked').each(function() { ids.push($(this).val()); });
        if (ids.length > 0) {
            $.ajax({
                url: "{{ route('admin.reported_errors.bulk_status') }}",
                type: 'POST',
                data: { _token: "{{ csrf_token() }}", ids: ids, status: 'Resolved' },
                success: function(response) {
                    if (typeof toastr !== 'undefined') toastr.success(response.message);
                    location.reload();
                }
            });
        }
    });

    // ── Bulk Delete ──
    $('#bulk_delete').on('click', function() {
        var ids = [];
        $('.error_checkbox:checked').each(function() { ids.push($(this).val()); });
        if (ids.length > 0 && confirm('Delete ' + ids.length + ' error reports?')) {
            $.ajax({
                url: "{{ route('admin.reported_errors.bulk_destroy') }}",
                type: 'POST',
                data: { _token: "{{ csrf_token() }}", ids: ids },
                success: function(response) {
                    if (typeof toastr !== 'undefined') toastr.success(response.message);
                    location.reload();
                }
            });
        }
    });

    // ── AI Single Analyze ──
    $(document).on('click', '.btn-analyze-single', function() {
        var id = $(this).data('id');
        var btn = $(this);
        var originalHtml = btn.html();
        
        btn.prop('disabled', true).html('<span class="ai-spinner"></span> Analyzing...');

        $.ajax({
            url: "{{ url('admin/reported-errors/ai-analyze') }}/" + id,
            type: 'POST',
            data: { _token: "{{ csrf_token() }}" },
            timeout: 120000,
            success: function(response) {
                if (response.status === 'success') {
                    if (typeof toastr !== 'undefined') toastr.success('🧠 ' + response.message);
                    // Reload the page to show AI results properly
                    location.reload();
                } else {
                    btn.prop('disabled', false).html(originalHtml);
                    if (typeof toastr !== 'undefined') toastr.error(response.message);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalHtml);
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'AI analysis failed. Check AI settings.';
                if (typeof toastr !== 'undefined') toastr.error(msg);
            }
        });
    });

    // ── AI Batch Analyze ──
    $('#btn_batch_analyze').on('click', function() {
        var btn = $(this);
        var originalHtml = btn.html();
        
        if (!confirm('Start AI analysis on all pending errors? This may take a few minutes.')) return;

        btn.prop('disabled', true).html('<span class="ai-spinner"></span> AI Scanning...');

        $.ajax({
            url: "{{ route('admin.reported_errors.ai_batch') }}",
            type: 'POST',
            data: { _token: "{{ csrf_token() }}", limit: 20 },
            timeout: 300000,
            success: function(response) {
                if (typeof toastr !== 'undefined') toastr.success('🚀 ' + response.message);
                setTimeout(function() { location.reload(); }, 1500);
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalHtml);
                if (typeof toastr !== 'undefined') toastr.error('Batch analysis failed.');
            }
        });
    });

    // ── Auto-Analyze Toggle ──
    $('#auto_analyze_toggle').on('click', function() {
        var toggle = $(this);
        var isActive = toggle.hasClass('active');
        var newState = !isActive;

        $.ajax({
            url: "{{ route('admin.reported_errors.toggle_auto') }}",
            type: 'POST',
            data: { _token: "{{ csrf_token() }}", enabled: newState ? 1 : 0 },
            success: function(response) {
                if (newState) {
                    toggle.addClass('active');
                } else {
                    toggle.removeClass('active');
                }
                if (typeof toastr !== 'undefined') toastr.success(response.message);
            }
        });
    });
});

// ── Toggle AI Detail (Root Cause / Fix) ──
function toggleDetail(id) {
    var el = document.getElementById(id);
    if (el) {
        el.classList.toggle('open');
    }
}
</script>
@endsection
