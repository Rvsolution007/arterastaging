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

    .icon-box {
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

    .preview-img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        cursor: zoom-in;
        transition: transform 0.2s;
    }

    .preview-img:hover {
        transform: scale(1.1);
    }

    .transition-hover { transition: all 0.3s ease; }
    .transition-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important; }
    
    /* Session Funnel Styles */
    .step-icon-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: #94a3b8;
        position: relative;
        margin: 0 auto;
        border: 2px solid #e2e8f0;
        transition: all 0.3s ease;
    }
    .step-box.active .step-icon-circle {
        background: #e0f2fe;
        color: #0284c7;
        border-color: #7dd3fc;
        box-shadow: 0 0 15px rgba(2, 132, 199, 0.1);
    }
    .step-check {
        position: absolute;
        top: -5px;
        right: -5px;
        width: 20px;
        height: 20px;
        background: #22c55e;
        color: white;
        border-radius: 50%;
        font-size: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
    }
    .step-line {
        flex: 1;
        height: 3px;
        background: #e2e8f0;
        margin: 0 15px;
        margin-top: -25px;
        border-radius: 2px;
    }
    .step-line.active {
        background: #7dd3fc;
    }
    .session-item-tag {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 11px;
        color: #475569;
        font-weight: 600;
    }
    .session-item-tag.success {
        background: #f0fdf4;
        border-color: #bbf7d0;
        color: #166534;
    }

    .active-card {
        border: 2px solid #6366f1 !important;
        background: rgba(99, 102, 241, 0.05) !important;
    }
</style>
@endsection

@section('content')
<div class="performance-container">
    <!-- Header with Back Button -->
    <div class="filter-bar glass-card mb-4 p-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <a href="{{ route('admin.user_performance', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-light rounded-circle mr-3" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h4 class="mb-0 font-weight-bold" style="color: #1e293b;">{{ $title }}</h4>
                <small class="text-muted">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</small>
            </div>
        </div>
        
        <!-- Global Filters (User Selection / Search) -->
        <div class="d-flex align-items-center">
            @if($type == 'template_performance')
                <form action="{{ url()->current() }}" method="GET" class="d-flex align-items-center mr-3">
                    <input type="hidden" name="type" value="{{ $type }}">
                    <input type="hidden" name="post_type" value="{{ $postType }}">
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    
                    <div class="input-group">
                        <input type="text" name="search" class="form-control form-control-sm rounded-pill px-3" style="width: 250px;" placeholder="Search template ID or Name..." value="{{ request('search') }}">
                        <div class="input-group-append">
                            <button class="btn btn-sm btn-primary rounded-pill ml-n4 z-index-10" type="submit" style="border-top-left-radius: 0; border-bottom-left-radius: 0; z-index: 5;">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    @if(request('search'))
                        <a href="{{ route('admin.user_performance.details', ['type' => $type, 'post_type' => $postType, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-sm btn-light rounded-pill ml-2" title="Clear Search">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </form>
            @else
                <form action="{{ url()->current() }}" method="GET" class="d-flex align-items-center mr-3">
                    <input type="hidden" name="type" value="{{ $type }}">
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    @if(isset($postType)) <input type="hidden" name="post_type" value="{{ $postType }}"> @endif
                    
                    <select name="user_id" class="form-control form-control-sm rounded-pill px-3 mr-2" style="width: 200px;" onchange="this.form.submit()">
                        <option value="">All Users</option>
                        @foreach($allUsers as $u)
                            <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    
                    @if($userId || $postType)
                        <a href="{{ route('admin.user_performance.details', ['type' => $type, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-sm btn-light rounded-pill" title="Clear Filters">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </form>
            @endif
        </div>
    </div>

    @if($type == 'user_session_tracking')
    <div class="row">
        @forelse($data as $session)
            <div class="col-md-12 mb-4">
                <div class="glass-card p-4 h-100 transition-hover">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                        <div>
                            <span class="badge badge-primary px-3 py-2 rounded-pill mr-2 shadow-sm">
                                <i class="fas fa-clock mr-1"></i> {{ $session['start_time']->format('d M, Y h:i A') }}
                            </span>
                            <span class="text-muted small font-weight-bold">
                                <i class="fas fa-network-wired mr-1"></i> {{ $session['ip'] }} | 
                                <i class="fas fa-mobile-alt mr-1 ml-1"></i> {{ $session['platform'] }}
                            </span>
                        </div>
                        <div class="badge badge-light border px-3 py-2 rounded-pill">
                            Session Duration: <strong>{{ $session['start_time']->diffInMinutes($session['last_time']) }} mins</strong>
                        </div>
                    </div>
                    
                    <!-- Visual Funnel for Session -->
                    <div class="session-funnel-container py-3">
                        <div class="d-flex justify-content-around align-items-center text-center">
                            <div class="step-box {{ $session['funnel']['step1'] ? 'active' : 'inactive' }}">
                                <div class="step-icon-circle">
                                    <i class="fas fa-home"></i>
                                    @if($session['funnel']['step1']) <div class="step-check"><i class="fas fa-check"></i></div> @endif
                                </div>
                                <div class="step-label mt-2 font-weight-bold small">Visited Home</div>
                                <div class="text-muted" style="font-size: 10px;">{{ $session['funnel']['step1'] ? $session['funnel']['step1']->format('h:i A') : 'Skipped' }}</div>
                            </div>
                            
                            <div class="step-line {{ count($session['funnel']['step2']) > 0 ? 'active' : '' }}"></div>
                            
                            <div class="step-box {{ count($session['funnel']['step2']) > 0 ? 'active' : 'inactive' }}">
                                <div class="step-icon-circle">
                                    <i class="fas fa-search"></i>
                                    @if(count($session['funnel']['step2']) > 0) <div class="step-check"><i class="fas fa-check"></i></div> @endif
                                </div>
                                <div class="step-label mt-2 font-weight-bold small">Browsed Templates</div>
                                <div class="text-muted" style="font-size: 10px;">{{ count($session['funnel']['step2']) }} items viewed</div>
                            </div>
                            
                            <div class="step-line {{ count($session['funnel']['step3']) > 0 ? 'active' : '' }}"></div>
                            
                            <div class="step-box {{ count($session['funnel']['step3']) > 0 ? 'active' : 'inactive' }}">
                                <div class="step-icon-circle">
                                    <i class="fas fa-cloud-download-alt"></i>
                                    @if(count($session['funnel']['step3']) > 0) <div class="step-check"><i class="fas fa-check"></i></div> @endif
                                </div>
                                <div class="step-label mt-2 font-weight-bold small">Final Download</div>
                                <div class="text-muted" style="font-size: 10px;">{{ count($session['funnel']['step3']) }} templates</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Item List for this session -->
                    @if(count($session['funnel']['step2']) > 0 || count($session['funnel']['step3']) > 0)
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="small font-weight-bold text-uppercase text-muted mb-3" style="letter-spacing: 1px;">Session Items Journey:</h6>
                        <div class="d-flex flex-wrap align-items-center">
                            @php
                                $groupedBrowsed = collect($session['funnel']['step2'])->groupBy(function($item) {
                                    $prefix = $item['type'] == 'festival' ? 'FEST' : ($item['type'] == 'custom' ? 'CUST' : 'CAT');
                                    return $prefix . '-' . $item['item_id'];
                                });
                            @endphp
                            @foreach($groupedBrowsed as $key => $items)
                                <div class="session-item-tag mr-2 mb-2">
                                    <i class="fas fa-eye text-info mr-1"></i> {{ $key }} ({{ count($items) }} times)
                                </div>
                            @endforeach
                            @foreach($session['funnel']['step3'] as $item)
                                <div class="session-item-tag mr-2 mb-2 success">
                                    <i class="fas fa-arrow-down mr-1"></i> DOWNLOADED {{ $item['item_type'] == 'festival' ? 'FEST' : ($item['item_type'] == 'custom' ? 'CUST' : 'CAT') }}-{{ $item['item_id'] }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="glass-card p-5">
                    <i class="fas fa-ghost fa-4x text-muted mb-3 opacity-25"></i>
                    <h5 class="text-muted">No tracking sessions found for this user in the selected period.</h5>
                    <p class="small text-muted">Try expanding the date range or check if the user has been active.</p>
                </div>
            </div>
        @endforelse
    </div>
    @else
    <!-- Data Table for Lists -->
    <div class="glass-card p-4">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    @if($type == 'template_performance')
                    <tr>
                        <th>Template ID</th>
                        <th>Preview</th>
                        <th>Name / Info</th>
                        <th>Download Count</th>
                    </tr>
                    @elseif($type == 'funnel_step3')
                    <tr>
                        <th>User</th>
                        <th>Type</th>
                        <th>Action At</th>
                        <th>Item Details</th>
                        <th>Preview</th>
                    </tr>
                    @elseif($type == 'businesses')
                    <tr>
                        <th>Business Name</th>
                        <th>Owner</th>
                        <th>Category</th>
                        <th>Registered At</th>
                        <th>Status</th>
                    </tr>
                    @else
                    <tr>
                        <th>User</th>
                        <th>Contact</th>
                        <th>Joined At</th>
                        <th>Package</th>
                        <th>Status</th>
                    </tr>
                    @endif
                </thead>
                <tbody>
                    @foreach($data as $row)
                    <tr>
                        @if($type == 'template_performance')
                        <td class="align-middle"><strong>#{{ $row->id }}</strong></td>
                        <td class="align-middle">
                            @if($row->frame_image)
                                @php
                                    $imgUrl = $row->frame_image;
                                    if (!str_starts_with($imgUrl, 'http') && !str_starts_with($imgUrl, 'uploads/')) {
                                        $imgUrl = 'uploads/' . $imgUrl;
                                    }
                                    $fullUrl = asset($imgUrl);
                                @endphp
                                <img src="{{ $fullUrl }}" class="preview-img img-thumbnail" data-toggle="modal" data-target="#previewModal" onclick="showPreview('{{ $fullUrl }}')">
                            @else
                                <div class="d-flex align-items-center justify-content-center bg-light rounded border text-muted" style="width: 60px; height: 60px;">
                                    <i class="fas fa-image fa-lg"></i>
                                </div>
                            @endif
                        </td>
                        <td class="align-middle">
                            <div class="font-weight-bold" style="color: #1e293b;">{{ $row->name }}</div>
                            <small class="text-muted">Type: {{ ucfirst($postType) }}</small>
                        </td>
                        <td class="align-middle">
                            <div class="d-flex align-items-center">
                                <div class="badge badge-primary px-3 py-2 mr-2" style="font-size: 1rem; border-radius: 10px;">
                                    <i class="fas fa-download mr-1"></i> {{ $row->download_count }}
                                </div>
                                <small class="text-muted">Total Downloads</small>
                            </div>
                        </td>
                        @elseif($type == 'funnel_step3')
                        <td class="align-middle">
                            <div class="d-flex align-items-center">
                                <div class="user-avatar">{{ substr($row->user->name ?? 'U', 0, 1) }}</div>
                                <div>
                                    <div class="font-weight-bold" style="color: #1e293b;">{{ $row->user->name ?? 'Deleted User' }}</div>
                                    <small class="text-muted">ID: #{{ $row->user_id }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="align-middle">
                            <span class="badge badge-light border text-capitalize px-2 py-1">
                                {{ str_replace('_', ' ', $row->payload['item_type'] ?? 'General') }}
                            </span>
                        </td>
                        <td class="align-middle">{{ $row->created_at->format('d/m/Y H:i') }}</td>
                        <td class="align-middle">
                            <div class="small">
                                <strong>Action:</strong> {{ str_replace(['select_', 'download_'], '', $row->action) }}<br>
                                <strong>Platform:</strong> {{ $row->payload['platform'] ?? 'Web' }}
                            </div>
                        </td>
                        <td class="align-middle">
                            @php
                                $previewUrl = null;
                                if(isset($row->payload['downloaded_image'])) {
                                    $previewUrl = $row->payload['downloaded_image'];
                                } elseif(isset($row->payload['design'])) {
                                    $previewUrl = $row->payload['design'];
                                }
                            @endphp
                            @if($previewUrl)
                                <img src="{{ $previewUrl }}" class="preview-img img-thumbnail" data-toggle="modal" data-target="#previewModal" onclick="showPreview('{{ $previewUrl }}')">
                            @else
                                <div class="d-flex align-items-center justify-content-center bg-light rounded border text-muted" style="width: 60px; height: 60px;">
                                    <i class="fas fa-image fa-lg"></i>
                                </div>
                            @endif
                        </td>
                        @elseif($type == 'businesses')
                        <td class="align-middle">
                            <div class="font-weight-bold" style="color: #1e293b;">{{ $row->name }}</div>
                            <small class="text-muted">ID: #{{ $row->id }}</small>
                        </td>
                        <td class="align-middle">
                            <div class="font-weight-bold" style="color: #1e293b;">{{ $row->user_name ?? 'Deleted User' }}</div>
                            <small class="text-muted">User ID: #{{ $row->user_id }}</small>
                        </td>
                        <td class="align-middle">
                            <span class="badge badge-light border px-2 py-1">{{ $row->category_name ?? 'N/A' }}</span>
                        </td>
                        <td class="align-middle">{{ \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') }}</td>
                        <td class="align-middle">
                            @if($row->status == 1)
                                <span class="text-success"><i class="fas fa-check-circle mr-1"></i> Active</span>
                            @else
                                <span class="text-danger"><i class="fas fa-times-circle mr-1"></i> Inactive</span>
                            @endif
                        </td>
                        @else
                        <td class="align-middle">
                            <div class="d-flex align-items-center">
                                <div class="user-avatar">{{ substr($row->name, 0, 1) }}</div>
                                <div>
                                    <div class="font-weight-bold" style="color: #1e293b;">{{ $row->name }}</div>
                                    <small class="text-muted">ID: #{{ $row->id }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="align-middle">
                            <div>{{ $row->email }}</div>
                            <small class="text-muted">{{ $row->mobile_no }}</small>
                        </td>
                        <td class="align-middle">{{ $row->created_at->format('d/m/Y H:i') }}</td>
                        <td class="align-middle">
                            @if($row->subscription)
                                <span class="badge badge-paid px-2 py-1">{{ $row->subscription->plan_name }}</span>
                            @else
                                <span class="badge badge-free px-2 py-1">Free</span>
                            @endif
                        </td>
                        <td class="align-middle">
                            @if($row->status == 1)
                                <span class="text-success"><i class="fas fa-check-circle mr-1"></i> Active</span>
                            @else
                                <span class="text-danger"><i class="fas fa-times-circle mr-1"></i> Inactive</span>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            @if(method_exists($data, 'links'))
                {{ $data->appends(request()->query())->links() }}
            @endif
        </div>
    </div>
    @endif
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="outline: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center p-4">
                <img id="modalPreviewImg" src="" class="img-fluid rounded shadow-lg" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
    function showPreview(url) {
        document.getElementById('modalPreviewImg').src = url;
    }
</script>
@endsection
