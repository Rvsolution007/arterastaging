@extends('layouts.app')

@section('extra_css')
<style>
    /* Modern Growth OS Dashboard Styling matching AI Analytics */
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

    .page-subtitle {
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 500;
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
        width: 4px;
        height: 100%;
        background: linear-gradient(to bottom, #6366f1, #4f46e5);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .kpi-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
    }

    .kpi-card:hover::before {
        opacity: 1;
    }

    .kpi-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
    }

    .kpi-value {
        font-size: 2rem;
        font-weight: 800;
        color: #0f172a;
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
    .icon-success { background: #dcfce7; color: #16a34a; }

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

    .custom-tabs .nav-link {
        font-weight: 600;
        color: #64748b;
        border-radius: 8px;
        margin: 0 5px;
        transition: all 0.3s ease;
    }
    .custom-tabs .nav-link:hover {
        background: #f1f5f9;
        color: #1e293b;
    }
    .custom-tabs .nav-link.active {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: #fff;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
    }
    
    /* Brain Pulse Animation */
    @keyframes brainPulse {
        0% { transform: scale(1); opacity: 0.85; color: #6366f1; }
        50% { transform: scale(1.2); opacity: 1; color: #4f46e5; }
        100% { transform: scale(1); opacity: 0.85; color: #6366f1; }
    }
    .brain-pulse-icon {
        display: inline-block;
        animation: brainPulse 2s infinite ease-in-out;
        font-size: 1.15rem;
    }
</style>
@endsection

@section('content')
<div class="analytics-container">
    
    <!-- Header Section with Integrated Filters & Status -->
    <div class="row align-items-center mb-4">
        <div class="col-lg-5 col-md-12 mb-3 mb-lg-0">
            <h4 class="page-title mb-1"><i class="fa-solid fa-rocket mr-2 text-primary" style="color: #6366f1;"></i> AI Growth OS</h4>
            <p class="page-subtitle mb-0">The unified decision engine for ArtEra Growth</p>
        </div>
        <div class="col-lg-7 col-md-12 d-flex flex-wrap align-items-center justify-content-lg-end justify-content-start" style="gap: 0.75rem;">
            <!-- Date Filter (Compact & Elegant UI) -->
            <div class="d-inline-flex align-items-center bg-white px-3 py-2 rounded shadow-sm border" style="border-color: #e2e8f0; gap: 0.5rem; height: 38px;">
                <span class="font-weight-bold text-dark" style="font-size: 0.8rem; letter-spacing: 0.5px;"><i class="fa-solid fa-calendar-days text-primary mr-1" style="color: #6366f1;"></i> DATE RANGE:</span>
                <input type="date" id="start_date" class="form-control form-control-sm border-0 bg-light px-2" style="width: 130px; font-weight: 600; font-size: 0.8rem; color: #475569; height: 26px; border-radius: 6px;" value="{{ \Carbon\Carbon::now()->subDays(30)->format('Y-m-d') }}">
                <span class="text-muted" style="font-size: 0.8rem; font-weight: 600;">to</span>
                <input type="date" id="end_date" class="form-control form-control-sm border-0 bg-light px-2" style="width: 130px; font-weight: 600; font-size: 0.8rem; color: #475569; height: 26px; border-radius: 6px;" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
            </div>
            <!-- AI Engine Active Badge with Pulsing Brain Icon -->
            <div class="d-inline-flex align-items-center bg-white px-3 py-2 rounded shadow-sm border" style="border-color: #e2e8f0; height: 38px;">
                <i class="fa-solid fa-brain mr-2 brain-pulse-icon"></i>
                <span style="font-size: 0.8rem; font-weight: 700; color: #475569; letter-spacing: 0.5px;">AI ENGINE ACTIVE</span>
            </div>
        </div>
        <div class="col-12">
            <div id="date_range_error" class="text-right text-danger small d-none" role="alert"></div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills nav-fill mb-4 custom-tabs" id="growth-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="tab-ceo" data-toggle="pill" href="#content-ceo" role="tab" onclick="loadTab('ceo')">
                <i class="fas fa-crown"></i> CEO Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-acquisition" data-toggle="pill" href="#content-acquisition" role="tab" onclick="loadTab('acquisition')">
                <i class="fas fa-chart-line"></i> Acquisition
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-engagement" data-toggle="pill" href="#content-engagement" role="tab" onclick="loadTab('engagement')">
                <i class="fas fa-users"></i> Engagement
            </a>
        </li>


        <li class="nav-item">
            <a class="nav-link" id="tab-planner" data-toggle="pill" href="#content-planner" role="tab" onclick="loadTab('planner')">
                <i class="fas fa-calendar-alt"></i> Smart Planner
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-marketing" data-toggle="pill" href="#content-marketing" role="tab" onclick="loadTab('marketing')">
                <i class="fas fa-bullhorn"></i> Marketing AI
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-aso" data-toggle="pill" href="#content-aso" role="tab" onclick="loadTab('aso')">
                <i class="fas fa-search-dollar"></i> ASO & Reviews
            </a>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="growth-tabs-content">
        
        <!-- 1. CEO Dashboard -->
        <div class="tab-pane fade show active" id="content-ceo" role="tabpanel">
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-title"><i class="fas fa-arrow-up text-success"></i> Overall Growth</div>
                        <div class="kpi-value font-math text-success" id="score_growth">--</div>
                        <div class="kpi-subtitle">Growth Index Score</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-title"><i class="fas fa-paint-brush text-warning"></i> Content Score</div>
                        <div class="kpi-value font-math text-warning" id="score_content">--</div>
                        <div class="kpi-subtitle">Template Performance</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-title"><i class="fas fa-heart text-info"></i> Retention Score</div>
                        <div class="kpi-value font-math text-info" id="score_retention">--</div>
                        <div class="kpi-subtitle">User Loyalty</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-title"><i class="fas fa-dollar-sign text-primary"></i> Revenue Score</div>
                        <div class="kpi-value font-math text-primary" id="score_revenue">--</div>
                        <div class="kpi-subtitle">Monetization</div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="table-panel">
                        <div class="table-panel-header">
                            <div class="table-icon-wrapper icon-success">
                                <i class="fas fa-lightbulb"></i>
                            </div>
                            <h3 class="table-panel-title">AI Top Opportunities</h3>
                        </div>
                        <div class="p-4">
                            <ul id="list_opportunities" class="mb-0" style="color: #334155; font-weight: 500; font-size: 0.95rem; line-height: 1.8;">
                                <li>Loading...</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="table-panel">
                        <div class="table-panel-header">
                            <div class="table-icon-wrapper icon-feature">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h3 class="table-panel-title">AI Top Problems</h3>
                        </div>
                        <div class="p-4">
                            <ul id="list_problems" class="mb-0" style="color: #334155; font-weight: 500; font-size: 0.95rem; line-height: 1.8;">
                                <li>Loading...</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="table-panel">
                        <div class="table-panel-header">
                            <div class="table-icon-wrapper icon-model">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <h3 class="table-panel-title">Execution Plan (Tasks)</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Priority</th>
                                        <th>Task Description</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="table_execution">
                                    <tr><td colspan="3" class="text-center py-4">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Acquisition -->
        <div class="tab-pane fade" id="content-acquisition" role="tabpanel">
            <h4 class="page-title mb-4">Install Analytics</h4>
            <div class="row" id="acq_metrics">
                <!-- Loaded via JS -->
            </div>
        </div>

        <!-- 3. Engagement -->
        <div class="tab-pane fade" id="content-engagement" role="tabpanel">
            <h4 class="page-title mb-4">User Engagement</h4>
            <div class="row" id="eng_metrics">
                <!-- Loaded via JS -->
            </div>
        </div>





        <!-- Smart Content Planner (Phase 2) -->
        <div class="tab-pane fade" id="content-planner" role="tabpanel">
            
            <!-- Section 1: Upcoming Festivals -->
            <div class="table-panel mb-4">
                <div class="table-panel-header">
                    <div class="table-icon-wrapper icon-user" style="background:#fbcfe8; color:#be185d;">
                        <i class="fas fa-gift"></i>
                    </div>
                    <h3 class="table-panel-title">Upcoming Festivals & Events</h3>
                </div>
                <div class="table-responsive">
                    <table class="custom-table table">
                        <thead>
                            <tr>
                                <th>Target Date</th>
                                <th>Event Name</th>
                                <th>Opp. Score</th>
                                <th>Templates Needed</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="planner_festivals_tbody">
                            <tr><td colspan="5" class="text-center">Loading planner data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 2: Business Categories -->
            <div class="table-panel mb-4">
                <div class="table-panel-header">
                    <div class="table-icon-wrapper icon-user" style="background:#d1fae5; color:#047857;">
                        <i class="fas fa-th-large"></i>
                    </div>
                    <h3 class="table-panel-title">High-Growth Business Categories</h3>
                </div>
                <div class="table-responsive">
                    <table class="custom-table table">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>Growth Trend</th>
                                <th>Opp. Score</th>
                                <th>Templates Needed</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="planner_categories_tbody">
                            <tr><td colspan="5" class="text-center">Loading planner data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 3: Custom / Business Posts -->
            <div class="table-panel">
                <div class="table-panel-header">
                    <div class="table-icon-wrapper icon-user" style="background:#e0e7ff; color:#4338ca;">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h3 class="table-panel-title">Promotional & Custom Posts</h3>
                </div>
                <div class="table-responsive">
                    <table class="custom-table table">
                        <thead>
                            <tr>
                                <th>Target Date</th>
                                <th>Post Idea</th>
                                <th>Opp. Score</th>
                                <th>Templates Needed</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="planner_custom_tbody">
                            <tr><td colspan="5" class="text-center">Loading planner data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Marketing AI (Phase 3) -->
        <div class="tab-pane fade" id="content-marketing" role="tabpanel">
            <div class="table-panel">
                <div class="table-panel-header">
                    <div class="table-icon-wrapper icon-model">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h3 class="table-panel-title">AI Auto-Drafted Push Notifications</h3>
                </div>
                <div class="table-responsive">
                    <table class="custom-table table">
                        <thead>
                            <tr>
                                <th>Target</th>
                                <th>Notification Title</th>
                                <th>Message Body</th>
                                <th>Pred. CTR</th>
                                <th>Scheduled For</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="marketing_tbody">
                            <tr><td colspan="7" class="text-center">Loading AI drafts...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ASO & Reviews (Phase 4) -->
        <div class="tab-pane fade" id="content-aso" role="tabpanel">
            <div class="row">
                <div class="col-md-6">
                    <div class="table-panel">
                        <div class="table-panel-header">
                            <div class="table-icon-wrapper icon-model">
                                <i class="fas fa-star"></i>
                            </div>
                            <h3 class="table-panel-title">AI Review Replies</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="custom-table table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Rating</th>
                                        <th>AI Draft Reply</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="aso_reviews_tbody">
                                    <tr><td colspan="4" class="text-center">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="table-panel">
                        <div class="table-panel-header">
                            <div class="table-icon-wrapper icon-model">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3 class="table-panel-title">ASO Keyword Tracker</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="custom-table table">
                                <thead>
                                    <tr>
                                        <th>Keyword</th>
                                        <th>Search Vol.</th>
                                        <th>Rank</th>
                                        <th>Diff</th>
                                    </tr>
                                </thead>
                                <tbody id="aso_keywords_tbody">
                                    <tr><td colspan="4" class="text-center">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('script')
<script>
    let activeTab = 'ceo';
    const latestRequestByTab = {};

    $(document).ready(function() {
        loadTab('ceo');
        $('#start_date, #end_date').on('change', function() {
            onDateFilterChange();
        });
    });

    function onDateFilterChange() {
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();

        if (!startDate || !endDate || startDate > endDate) {
            $('#date_range_error').text('Please select an end date that is on or after the start date.').removeClass('d-none');
            return;
        }

        $('#date_range_error').addClass('d-none').text('');
        loadTab(activeTab);
    }

    function requestTabData(tabName, endpoint, params, onSuccess) {
        const requestId = (latestRequestByTab[tabName] || 0) + 1;
        latestRequestByTab[tabName] = requestId;

        $.ajax({ url: endpoint, data: params, dataType: 'json' })
            .done(function(data) {
                if (latestRequestByTab[tabName] === requestId) {
                    onSuccess(data);
                }
            })
            .fail(function(xhr) {
                if (latestRequestByTab[tabName] !== requestId) return;

                const message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Unable to load data for the selected date range. Please try again.';
                $('#date_range_error').text(message).removeClass('d-none');
            });
    }

    function loadTab(tabName) {
        activeTab = tabName;
        let startDate = $('#start_date').val();
        let endDate = $('#end_date').val();
        let params = {
            start_date: startDate,
            end_date: endDate
        };

        if(tabName === 'ceo') {
            requestTabData(tabName, "{{ route('admin.growth_os.dashboard') }}", params, function(data) {
                if(data.status === 'success') {
                    $('#score_growth').text(data.scores.overall_growth + '/100');
                    $('#score_content').text(data.scores.content + '/100');
                    $('#score_retention').text(data.scores.retention + '/100');
                    $('#score_revenue').text(data.scores.revenue + '/100');

                    $('#list_opportunities').empty();
                    data.top_opportunities.forEach(opt => {
                        $('#list_opportunities').append('<li class="mb-2"><i class="fas fa-check text-success mr-2"></i> ' + opt + '</li>');
                    });

                    $('#list_problems').empty();
                    data.top_problems.forEach(prob => {
                        $('#list_problems').append('<li class="mb-2"><i class="fas fa-times text-danger mr-2"></i> ' + prob + '</li>');
                    });

                    $('#table_execution').empty();
                    data.execution_plan.forEach(task => {
                        let badgeColor = task.priority === 'High' ? 'background: #fee2e2; color: #e11d48;' : 'background: #fef3c7; color: #d97706;';
                        let badge = '<span class="badge-soft" style="'+badgeColor+'">' + task.priority + '</span>';
                        $('#table_execution').append('<tr><td>'+badge+'</td><td>'+task.task+'</td><td><button class="btn btn-sm" style="background:#6366f1; color:white;">Do it</button></td></tr>');
                    });
                }
            });
        }
        else if (tabName === 'acquisition') {
            requestTabData(tabName, "{{ route('admin.growth_os.acquisition') }}", params, function(data) {
                if(data.status === 'success') {
                    $('#acq_metrics').html(`
                        <div class="col-md-3 mb-4">
                            <div class="kpi-card">
                                <div class="kpi-title"><i class="fas fa-mobile-screen-button text-success"></i> Unique Installs</div>
                                <div class="kpi-value font-math text-success">${data.installs.unique}</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="kpi-card">
                                <div class="kpi-title"><i class="fas fa-download text-primary"></i> Total Installs</div>
                                <div class="kpi-value font-math text-primary">${data.installs.total}</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="kpi-card">
                                <div class="kpi-title"><i class="fas fa-trash-can text-info"></i> Total Uninstalls</div>
                                <div class="kpi-value font-math text-info">${data.installs.total_uninstalls}</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="kpi-card">
                                <div class="kpi-title"><i class="fas fa-star text-warning"></i> Positive Reviews</div>
                                <div class="kpi-value font-math text-warning">${data.reviews.positive}</div>
                            </div>
                        </div>
                    `);
                }
            });
        }
        else if (tabName === 'engagement') {
            requestTabData(tabName, "{{ route('admin.growth_os.engagement') }}", params, function(data) {
                if(data.status === 'success') {
                    $('#eng_metrics').html(`
                        <div class="col-md-3 mb-4">
                            <div class="kpi-card">
                                <div class="kpi-title"><i class="fas fa-user-clock text-info"></i> DAU (Daily Active)</div>
                                <div class="kpi-value font-math text-info">${data.engagement.dau}</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="kpi-card">
                                <div class="kpi-title"><i class="fas fa-users text-primary"></i> MAU (Monthly Active)</div>
                                <div class="kpi-value font-math text-primary">${data.engagement.mau}</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="kpi-card">
                                <div class="kpi-title"><i class="fas fa-hourglass-half text-success"></i> Avg Session Time</div>
                                <div class="kpi-value font-math text-success" style="font-size:1.5rem;">${data.engagement.avg_session_time}</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="kpi-card">
                                <div class="kpi-title"><i class="fas fa-retweet text-warning"></i> D7 Retention</div>
                                <div class="kpi-value font-math text-warning">${data.retention.day_7}</div>
                            </div>
                        </div>
                    `);
                }
            });
        }
        else if (tabName === 'planner') {
            requestTabData(tabName, "{{ route('admin.growth_os.planner') }}", params, function(data) {
                if(data.status === 'success') {
                    
                    // 1. Upcoming Festivals
                    $('#planner_festivals_tbody').empty();
                    if(data.festivals.length === 0) {
                        $('#planner_festivals_tbody').append('<tr><td colspan="5" class="text-center">No upcoming festivals found.</td></tr>');
                    } else {
                        data.festivals.forEach(plan => {
                            let badgeColor = plan.opportunity_score > 80 ? 'background: #fee2e2; color: #e11d48;' : 'background: #e0e7ff; color: #4338ca;';
                            let oppBadge = '<span class="badge-soft" style="'+badgeColor+'">' + plan.opportunity_score + '</span>';
                            let statusBadge = plan.status === 'completed' ? '<span class="badge badge-success">Completed</span>' : '<span class="badge badge-warning">Pending</span>';
                            
                            $('#planner_festivals_tbody').append(`
                                <tr>
                                    <td class="font-weight-bold text-dark">${plan.plan_date}</td>
                                    <td class="font-weight-bold">${plan.target_name}</td>
                                    <td>${oppBadge}</td>
                                    <td>${plan.suggested_templates}</td>
                                    <td>${statusBadge}</td>
                                </tr>
                             `);
                        });
                    }

                    // 2. High-Growth Categories
                    $('#planner_categories_tbody').empty();
                    if(data.categories.length === 0) {
                        $('#planner_categories_tbody').append('<tr><td colspan="5" class="text-center">No high-growth categories found.</td></tr>');
                    } else {
                        data.categories.forEach(plan => {
                            let badgeColor = plan.opportunity_score > 80 ? 'background: #fee2e2; color: #e11d48;' : 'background: #e0e7ff; color: #4338ca;';
                            let oppBadge = '<span class="badge-soft" style="'+badgeColor+'">' + plan.opportunity_score + '</span>';
                            let statusBadge = plan.status === 'completed' ? '<span class="badge badge-success">Completed</span>' : '<span class="badge badge-warning">Pending</span>';
                            let trendIcon = '<i class="fas fa-arrow-up text-success"></i> Trending';
                            
                            $('#planner_categories_tbody').append(`
                                <tr>
                                    <td class="font-weight-bold">${plan.target_name}</td>
                                    <td>${trendIcon}</td>
                                    <td>${oppBadge}</td>
                                    <td>${plan.suggested_templates}</td>
                                    <td>${statusBadge}</td>
                                </tr>
                             `);
                        });
                    }

                    // 3. Custom / Business Posts
                    $('#planner_custom_tbody').empty();
                    if(data.custom.length === 0) {
                        $('#planner_custom_tbody').append('<tr><td colspan="5" class="text-center">No custom plans found.</td></tr>');
                    } else {
                        data.custom.forEach(plan => {
                            let badgeColor = plan.opportunity_score > 80 ? 'background: #fee2e2; color: #e11d48;' : 'background: #e0e7ff; color: #4338ca;';
                            let oppBadge = '<span class="badge-soft" style="'+badgeColor+'">' + plan.opportunity_score + '</span>';
                            let statusBadge = plan.status === 'completed' ? '<span class="badge badge-success">Completed</span>' : '<span class="badge badge-warning">Pending</span>';
                            
                            $('#planner_custom_tbody').append(`
                                <tr>
                                    <td class="font-weight-bold text-dark">${plan.plan_date}</td>
                                    <td class="font-weight-bold">${plan.target_name}</td>
                                    <td>${oppBadge}</td>
                                    <td>${plan.suggested_templates}</td>
                                    <td>${statusBadge}</td>
                                </tr>
                             `);
                        });
                    }
                }
            });
        }
        else if (tabName === 'marketing') {
            requestTabData(tabName, "{{ route('admin.growth_os.marketing') }}", params, function(data) {
                if(data.status === 'success') {
                    $('#marketing_tbody').empty();
                    if(data.notifications.length === 0) {
                        $('#marketing_tbody').append('<tr><td colspan="7" class="text-center">No AI drafts found.</td></tr>');
                    } else {
                        data.notifications.forEach(notif => {
                            let statusBadge = notif.status === 'sent' ? '<span class="badge badge-success">Sent</span>' : (notif.status === 'scheduled' ? '<span class="badge badge-warning">Scheduled</span>' : '<span class="badge badge-secondary">Draft</span>');
                            let targetBadge = '<span class="badge-soft" style="background:#e0e7ff; color:#4338ca;">' + (notif.target_type || 'All Users') + '</span>';
                            
                            $('#marketing_tbody').append(`
                                <tr>
                                    <td>${targetBadge}</td>
                                    <td class="font-weight-bold">${notif.title}</td>
                                    <td>${notif.body}</td>
                                    <td class="font-math text-success">${notif.predicted_ctr}%</td>
                                    <td>${notif.scheduled_for}</td>
                                    <td>${statusBadge}</td>
                                    <td><button class="btn btn-sm" style="background:#6366f1; color:white;">Review</button></td>
                                </tr>
                             `);
                        });
                    }
                }
            });
        }
        else if (tabName === 'aso') {
            requestTabData(tabName, "{{ route('admin.growth_os.aso') }}", params, function(data) {
                if(data.status === 'success') {
                    // Reviews
                    $('#aso_reviews_tbody').empty();
                    if(data.reviews.length === 0) {
                        $('#aso_reviews_tbody').append('<tr><td colspan="4" class="text-center">No reviews found.</td></tr>');
                    } else {
                        data.reviews.forEach(rev => {
                            let stars = '';
                            for(let i=0; i<5; i++) {
                                stars += i < rev.rating ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star text-secondary"></i>';
                            }
                            $('#aso_reviews_tbody').append(`
                                <tr>
                                    <td class="font-weight-bold">${rev.reviewer_name}</td>
                                    <td>${stars}</td>
                                    <td style="font-size: 0.9em;">
                                        <strong>Review:</strong> ${rev.review_text}<br>
                                        <strong class="text-primary">AI Reply:</strong> ${rev.ai_reply_draft}
                                    </td>
                                    <td><button class="btn btn-sm btn-outline-primary">Approve</button></td>
                                </tr>
                             `);
                        });
                    }

                    // Keywords
                    $('#aso_keywords_tbody').empty();
                    if(data.keywords.length === 0) {
                        $('#aso_keywords_tbody').append('<tr><td colspan="4" class="text-center">No keywords found.</td></tr>');
                    } else {
                        data.keywords.forEach(kw => {
                            let diff = kw.previous_rank - kw.current_rank;
                            let diffHtml = diff > 0 
                                ? `<span class="text-success"><i class="fas fa-arrow-up"></i> ${diff}</span>` 
                                : (diff < 0 ? `<span class="text-danger"><i class="fas fa-arrow-down"></i> ${Math.abs(diff)}</span>` : '<span class="text-secondary">-</span>');
                            
                            $('#aso_keywords_tbody').append(`
                                <tr>
                                    <td class="font-weight-bold">${kw.keyword}</td>
                                    <td>${kw.search_volume.toLocaleString()}</td>
                                    <td class="font-weight-bold">#${kw.current_rank}</td>
                                    <td>${diffHtml}</td>
                                </tr>
                             `);
                        });
                    }
                }
            });
        }
        else if (tabName === 'content') {
            requestTabData(tabName, "{{ route('admin.growth_os.content') }}", params, function(data) {
                if(data.status === 'success') {
                    let html = '';
                    data.top_templates.forEach(t => {
                        html += `
                        <div class="col-md-3 mb-4">
                            <div class="table-panel h-auto">
                                <img src="/uploads/${t.image}" class="w-100" style="height:200px; object-fit:cover; border-bottom: 1px solid #e2e8f0;">
                                <div class="p-3">
                                    <h5 class="table-panel-title text-truncate" style="font-size: 1rem;">${t.title || 'Template #' + t.id}</h5>
                                    <p class="text-success m-0 mt-2 font-weight-bold"><i class="fas fa-download"></i> ${t.downloads_count}</p>
                                </div>
                            </div>
                        </div>`;
                    });
                    $('#content_metrics').html(html);
                }
            });
        }
    }
</script>
@endsection
