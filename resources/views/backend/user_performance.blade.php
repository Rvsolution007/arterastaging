@extends('layouts.app')

@section('extra_css')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

    .performance-container {
        font-family: 'Outfit', sans-serif;
        padding: 1.5rem;
        background-color: #f0f2f5;
        min-height: 100vh;
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
        transition: all 0.3s ease;
    }

    .kpi-card {
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .kpi-card .icon-box {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    .icon-reg { background: #e0e7ff; color: #4338ca; }
    .icon-pur { background: #dcfce7; color: #166534; }
    .icon-usage { background: #fef3c7; color: #92400e; }
    .icon-funnel { background: #fce7f3; color: #9d174d; }

    .kpi-value {
        font-size: 2rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }

    .kpi-label {
        font-size: 0.875rem;
        color: #64748b;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    /* Funnel Visualization */
    .funnel-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 1rem;
    }

    .funnel-step {
        display: flex;
        align-items: center;
        background: #f8fafc;
        border-radius: 12px;
        padding: 1rem;
        border-left: 5px solid #6366f1;
        transition: all 0.25s ease;
    }

    .funnel-step.step2 { border-left-color: #8b5cf6; }
    .funnel-step.step3 { border-left-color: #ec4899; }

    .step-info { flex-grow: 1; }
    .step-name { font-weight: 600; color: #334155; font-size: 0.95rem; }
    .step-count { font-size: 1.25rem; font-weight: 700; color: #1e293b; }
    .step-percent { font-size: 0.75rem; color: #64748b; background: #e2e8f0; padding: 2px 8px; border-radius: 10px; }

    /* Custom Table */
    .custom-table-container {
        padding: 0;
        overflow: hidden;
    }

    .custom-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .custom-table th {
        background: #f8fafc;
        padding: 1rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #475569;
        text-transform: uppercase;
        border-bottom: 1px solid #edf2f7;
    }

    .custom-table td {
        padding: 1rem;
        font-size: 0.875rem;
        color: #334155;
        border-bottom: 1px solid #edf2f7;
    }

    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #6366f1;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        margin-right: 12px;
    }

    .badge-paid { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .badge-free { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    
    .filter-bar {
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .btn-premium {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        border: none;
        padding: 0.6rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
    }

    .active-card {
        border: 2px solid #6366f1 !important;
        background: rgba(99, 102, 241, 0.05) !important;
    }
</style>
@endsection

@section('content')
<div class="performance-container">
    <div class="filter-bar glass-card mb-4 p-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0 font-weight-bold" style="color: #1e293b;">Performance Dashboard</h4>
            <small class="text-muted">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</small>
        </div>
        <form action="{{ route('admin.user_performance') }}" method="GET" class="d-flex align-items-center bg-white p-1 rounded-pill shadow-sm border">
            <div class="px-3 border-right">
                <select name="period" class="border-0 bg-transparent font-weight-bold text-primary" style="outline: none; cursor: pointer;" onchange="this.form.submit()">
                    <option value="">Custom Range</option>
                    <option value="hour" {{ request('period') == 'hour' ? 'selected' : '' }}>Last Hour</option>
                    <option value="day" {{ request('period') == 'day' ? 'selected' : '' }}>Today</option>
                    <option value="month" {{ request('period') == 'month' ? 'selected' : '' }}>This Month</option>
                    <option value="year" {{ request('period') == 'year' ? 'selected' : '' }}>This Year</option>
                </select>
            </div>
            <div class="d-flex align-items-center px-3">
                <input type="date" name="start_date" class="border-0 small" value="{{ request('period') ? '' : request('start_date') }}" style="outline: none; width: 130px;">
                <span class="mx-2 text-muted">to</span>
                <input type="date" name="end_date" class="border-0 small" value="{{ request('period') ? '' : request('end_date') }}" style="outline: none; width: 130px;">
            </div>
            <button type="submit" class="btn btn-premium rounded-pill px-4">
                <i class="fas fa-sync-alt mr-1"></i> Update
            </button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const periodSelect = document.querySelector('select[name="period"]');
            const dateInputs = document.querySelectorAll('input[type="date"]');
            
            dateInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (periodSelect) periodSelect.value = "";
                });
            });
        });
    </script>

    <!-- KPI Summary Row -->
    <div class="row">
        <div class="col-md">
            <a href="{{ route('admin.user_performance.details', ['type' => 'registrations', 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="text-decoration-none">
                <div class="glass-card kpi-card h-100">
                    <div class="icon-box icon-reg"><i class="fas fa-user-plus"></i></div>
                    <div class="kpi-value">{{ $totalRegistered }}</div>
                    <div class="kpi-label">Registered Users</div>
                </div>
            </a>
        </div>
        <div class="col-md">
            <a href="{{ route('admin.user_performance.details', ['type' => 'premium', 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="text-decoration-none">
                <div class="glass-card kpi-card h-100">
                    <div class="icon-box icon-pur"><i class="fas fa-crown"></i></div>
                    <div class="kpi-value">{{ $totalPurchased }}</div>
                    <div class="kpi-label">Premium Users</div>
                </div>
            </a>
        </div>
        <div class="col-md">
            <div class="glass-card kpi-card h-100">
                <div class="icon-box" style="background: #e0f2fe; color: #0369a1;"><i class="fas fa-briefcase"></i></div>
                <div class="kpi-value">{{ $totalBusinesses }}</div>
                <div class="kpi-label">Total Business</div>
            </div>
        </div>
        <div class="col-md">
            <a href="{{ route('admin.user_performance.details', ['type' => 'funnel_step3', 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="text-decoration-none">
                <div class="glass-card kpi-card h-100">
                    <div class="icon-box icon-usage"><i class="fas fa-download"></i></div>
                    <div class="kpi-value">{{ array_sum($usageStats->toArray()) }}</div>
                    <div class="kpi-label">Total Downloads</div>
                </div>
            </a>
        </div>
        <div class="col-md">
            <div class="glass-card kpi-card h-100">
                <div class="icon-box icon-funnel"><i class="fas fa-filter"></i></div>
                <div class="kpi-value">{{ $funnel['step1_home'] > 0 ? round(($funnel['step3_download'] / $funnel['step1_home']) * 100, 1) : 0 }}%</div>
                <div class="kpi-label">Conv. Rate</div>
            </div>
        </div>
    </div>

    <div class="row mt-4" id="funnel-section">
        <!-- User Action Funnel -->
        <div class="col-lg-6 mb-4">
            <div class="glass-card h-100" style="padding: 1.5rem;">
                <h5 class="font-weight-bold mb-4">User Action Funnel</h5>
                <div class="funnel-container">
                    <div class="funnel-step">
                        <div class="step-info">
                            <div class="step-name">Step 1: Visited Homepage</div>
                            <div class="step-count">{{ $funnel['step1_home'] }} users</div>
                        </div>
                        <span class="step-percent">100%</span>
                    </div>
                    <div class="text-center my-1 text-muted"><i class="fas fa-arrow-down"></i></div>
                    <div class="funnel-step step2">
                        <div class="step-info">
                            <div class="step-name">Step 2: Browsed Templates</div>
                            <div class="step-count">{{ $funnel['step2_template'] }} users</div>
                        </div>
                        <span class="step-percent">
                            {{ $funnel['step1_home'] > 0 ? round(($funnel['step2_template'] / $funnel['step1_home']) * 100, 1) : 0 }}%
                        </span>
                    </div>
                    <div class="text-center my-1 text-muted"><i class="fas fa-arrow-down"></i></div>
                    <div class="funnel-step step3">
                        <div class="step-info">
                            <div class="step-name">Step 3: Downloaded Template</div>
                            <div class="step-count">{{ $funnel['step3_download'] }} users</div>
                        </div>
                        <span class="step-percent">
                            {{ $funnel['step2_template'] > 0 ? round(($funnel['step3_download'] / $funnel['step2_template']) * 100, 1) : 0 }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Download Breakdown -->
        <div class="col-lg-6 mb-4">
            <div class="glass-card h-100" style="padding: 1.5rem;">
                <h5 class="font-weight-bold mb-4">Download Breakdown</h5>
                <p class="text-muted small mb-4">Distribution of premium and free asset downloads by category type.</p>
                <ul class="list-group list-group-flush" style="background: transparent;">
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-3" style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                        <span class="font-weight-bold"><i class="fas fa-calendar-alt text-primary mr-2"></i> Festival Posts</span>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-primary badge-pill px-3 py-2 mr-3" style="font-size: 0.9rem;">{{ $usageStats['festival'] ?? 0 }} downloads</span>
                            <a href="{{ route('admin.user_performance.details', ['type' => 'template_performance', 'post_type' => 'festival', 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">View Details</a>
                        </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-3" style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                        <span class="font-weight-bold"><i class="fas fa-th text-info mr-2"></i> Category Posts</span>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-info badge-pill px-3 py-2 mr-3" style="font-size: 0.9rem;">{{ $usageStats['category'] ?? 0 }} downloads</span>
                            <a href="{{ route('admin.user_performance.details', ['type' => 'template_performance', 'post_type' => 'category', 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-sm btn-outline-info rounded-pill px-3">View Details</a>
                        </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-3" style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                        <span class="font-weight-bold"><i class="fas fa-magic text-warning mr-2"></i> Custom Posts</span>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-warning badge-pill px-3 py-2 mr-3" style="font-size: 0.9rem;">{{ $usageStats['custom'] ?? 0 }} downloads</span>
                            <a href="{{ route('admin.user_performance.details', ['type' => 'template_performance', 'post_type' => 'custom', 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-sm btn-outline-warning rounded-pill px-3">View Details</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Live Activity Log -->
    <div class="row">
        <div class="col-12">
            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                    <h5 class="font-weight-bold mb-0">Live User Tracking Log</h5>
                    <form method="GET" action="" class="form-inline mt-2 mt-sm-0">
                        <input type="hidden" name="start_date" value="{{ $startDate }}">
                        <input type="hidden" name="end_date" value="{{ $endDate }}">
                        
                        <div class="input-group" style="width: 280px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); border-radius: 8px; overflow: hidden;">
                            <input type="text" name="search_user" class="form-control border-right-0" placeholder="Search user..." value="{{ $searchUser ?? '' }}" style="border: 1px solid #e2e8f0; font-size: 0.9rem; border-radius: 8px 0 0 8px; height: calc(1.5em + .75rem + 2px);">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit" style="border-radius: 0 8px 8px 0; font-size: 0.9rem; padding: 0 16px;">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        @if(isset($searchUser) && $searchUser)
                            <a href="{{ route('admin.user_performance', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-light ml-2 d-inline-flex align-items-center justify-content-center" style="border-radius: 8px; border: 1px solid #cbd5e1; color: #64748b; font-size: 0.9rem; height: calc(1.5em + .75rem + 2px); width: calc(1.5em + .75rem + 2px);" title="Clear search">
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif
                    </form>
                </div>
                <div id="user-tracking-list-wrapper">
                <p class="text-muted mb-4">Select a user below to track their live actions and browse history timeline.</p>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Joined</th>
                                <th>Package</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $u)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar mr-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            <i class="fas fa-user text-primary" style="font-size: 0.85rem;"></i>
                                        </div>
                                        <div>
                                            <div class="font-weight-bold" style="color: #1e293b; font-size: 0.92rem;">{{ $u->name }}</div>
                                            <small class="text-muted">ID: #{{ $u->id }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.9rem;">{{ $u->email }}</div>
                                    <small class="text-muted">{{ $u->mobile_no }}</small>
                                </td>
                                <td>{{ $u->created_at->format('d/m/Y') }}</td>
                                <td>
                                    @if($u->subscription)
                                        <span class="badge badge-premium p-1" style="font-size: 10px;">{{ $u->subscription->plan_name }}</span>
                                    @else
                                        <span class="text-muted small">Free</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.user_performance.details', ['type' => 'user_session_tracking', 'user_id' => $u->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <i class="fas fa-eye mr-1"></i> Track Live
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">
                    {{ $users->appends(request()->query())->links() }}
                </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search_user"]');
    const tableWrapper = document.getElementById('user-tracking-list-wrapper');
    
    if (searchInput && tableWrapper) {
        // Debounce helper to prevent excessive requests
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        const handleSearch = debounce(function() {
            const query = searchInput.value;
            const url = new URL(window.location.href);
            url.searchParams.set('search_user', query);
            
            // Add visual loading feedback
            tableWrapper.style.opacity = '0.4';
            tableWrapper.style.transition = 'opacity 0.15s ease';
            
            fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.getElementById('user-tracking-list-wrapper');
                
                if (newContent) {
                    tableWrapper.innerHTML = newContent.innerHTML;
                }
                tableWrapper.style.opacity = '1';
            })
            .catch(error => {
                console.error('Error fetching search results:', error);
                tableWrapper.style.opacity = '1';
            });
        }, 200); // 200ms debounce gives a highly instant, responsive typing feel

        searchInput.addEventListener('input', handleSearch);
        
        // Initialize tooltips
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });
        
        // Prevent form submission on enter to keep it fully AJAX-driven
        const searchForm = searchInput.closest('form');
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                handleSearch();
            });
        }
    }
});
</script>
@endsection
