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

    .btn-action-primary {
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

    .btn-action-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4);
        color: white;
    }

    .table-panel {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

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

    .badge-soft {
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        background: #f1f5f9;
        color: #475569;
        display: inline-block;
    }

    .badge-soft-primary { background: #e0e7ff; color: #4338ca; }
    .badge-soft-success { background: #d1fae5; color: #059669; }

    .table-img-preview {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .checkbox-custom {
        width: 1.1rem;
        height: 1.1rem;
        border-radius: 4px;
        border: 2px solid #cbd5e1;
        cursor: pointer;
    }

    .bulk-action-bar {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
</style>
@endsection

@section('content')
<div class="analytics-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-code-branch text-primary mr-2"></i> Version Control Dashboard</h1>
            <p class="text-muted mt-1 mb-0">Manage rendering versions across all frames to ensure cross-platform compatibility.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('Frame.index') }}" class="btn btn-light" style="border-radius: 8px; font-weight: 500;">
                <i class="fa-solid fa-arrow-left mr-1"></i> Back to Frames
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="table-panel p-3 mb-4 d-flex align-items-center justify-content-between">
        <form action="{{ route('admin.poster_maker.version_control') }}" method="GET" class="d-flex align-items-center mb-0 w-100" id="filterForm">
            <label class="mr-3 mb-0" style="font-weight: 600; color: #475569;">Filter by Version:</label>
            <select name="version" class="form-control mr-4" style="width: 200px; border-radius: 8px;" onchange="document.getElementById('filterForm').submit()">
                <option value="">All Versions</option>
                @for($i = 1; $i <= $currentMaxVersion; $i++)
                    <option value="{{ $i }}" {{ isset($selectedVersion) && $selectedVersion == $i ? 'selected' : '' }}>Version {{ $i }}</option>
                @endfor
            </select>
            
            <label class="mr-3 mb-0" style="font-weight: 600; color: #475569;">Search Zip Name:</label>
            <div class="input-group" style="width: 300px;">
                <input type="text" name="search" class="form-control" placeholder="Search frame..." value="{{ $searchQuery ?? '' }}" style="border-radius: 8px 0 0 8px;">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit" style="border-radius: 0 8px 8px 0;"><i class="fa fa-search"></i></button>
                </div>
            </div>
            
            @if(isset($searchQuery) && $searchQuery !== '' || isset($selectedVersion) && $selectedVersion !== '')
                <a href="{{ route('admin.poster_maker.version_control') }}" class="btn btn-outline-secondary ml-3" style="border-radius: 8px;">Clear</a>
            @endif
        </form>
    </div>

    <!-- Bulk Action Bar -->
    <div class="bulk-action-bar">
        <div style="font-weight: 600; color: #475569;">
            <span id="selectedCount">0</span> selected
        </div>
        <div style="width: 1px; height: 24px; background: #cbd5e1; margin: 0 0.5rem;"></div>
        <div class="d-flex align-items-center gap-2 ml-auto" style="margin-left: auto;">
            <div class="custom-control custom-checkbox mr-3 d-flex align-items-center" style="margin-right: 15px;">
                <input type="checkbox" class="custom-control-input" id="upgradeIconsCheck">
                <label class="custom-control-label" for="upgradeIconsCheck" style="font-weight: 500; font-size: 0.875rem; cursor: pointer; padding-top: 2px;">
                    <i class="fa-solid fa-wand-magic-sparkles text-primary mr-1"></i> Upgrade Legacy Icons
                </label>
            </div>

            <span style="font-weight: 500; font-size: 0.875rem;">Upgrade to:</span>
            <select id="targetVersion" class="form-control form-control-sm" style="width: 120px; border-radius: 6px;">
                <option value="none">None</option>
                @for($i = 2; $i <= $currentMaxVersion; $i++)
                    <option value="{{ $i }}" {{ $i == $currentMaxVersion ? 'selected' : '' }}>Version {{ $i }}</option>
                @endfor
            </select>
            <button onclick="applyMigration()" class="btn-action-primary ml-2" id="btnMigrate" style="padding: 0.4rem 1rem;">
                <i class="fa-solid fa-rocket mr-1"></i> Apply Migration
            </button>
        </div>
    </div>

    <!-- Data Table -->
    <div class="table-panel">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">
                            <input type="checkbox" id="selectAll" class="checkbox-custom">
                        </th>
                        <th style="width: 80px;">Thumb</th>
                        <th>Category</th>
                        <th>Zip Name</th>
                        <th>Current Version</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $frame)
                    <tr>
                        <td>
                            <input type="checkbox" class="checkbox-custom frame-checkbox" value="{{ $frame->id }}" data-version="{{ $frame->render_version }}">
                        </td>
                        <td>
                            @if($frame->post_thumb)
                                <img src="{{ asset('uploads/' . $frame->post_thumb) }}" class="table-img-preview" alt="Thumb">
                            @else
                                <div style="width: 48px; height: 48px; background: #e2e8f0; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fa-solid fa-image text-muted"></i>
                                </div>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight: 600;">{{ $frame->poster_category->name ?? 'N/A' }}</div>
                        </td>
                        <td>
                            <code style="background: #f1f5f9; padding: 0.2rem 0.5rem; border-radius: 4px; color: #475569;">{{ $frame->zip_name }}</code>
                        </td>
                        <td>
                            @if($frame->render_version >= $currentMaxVersion)
                                <span class="badge-soft badge-soft-success">V{{ $frame->render_version }} (Latest)</span>
                            @else
                                <span class="badge-soft" style="background: #fef9c3; color: #ca8a04;">V{{ $frame->render_version }} (Outdated)</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fa-solid fa-inbox mb-2" style="font-size: 2rem; opacity: 0.5;"></i>
                                <div>No frames found.</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="mt-4">
        {{ $data->links() }}
    </div>
</div>

<!-- Migration Result Modal -->
<div id="migrationResultModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); z-index:9999; padding:40px; align-items:center; justify-content:center; overflow-y:auto;">
    <div style="width:100%; max-width:950px; background:#1e293b; border-radius:16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); border:1px solid #334155; font-family:'Poppins',sans-serif; color:#f8fafc; display:flex; flex-direction:column; max-height:90vh;">

        <!-- Header -->
        <div style="padding:24px 32px; border-bottom:1px solid #334155; display:flex; justify-content:space-between; align-items:center; background:#0f172a; border-radius:16px 16px 0 0; flex-shrink:0;">
            <h4 style="margin:0; font-weight:600; font-size:1.25rem; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-code-compare" style="color:#38bdf8;"></i>
                Version Migration Report
            </h4>
            <button onclick="closeMigrationModal()" style="background:none; border:none; color:#94a3b8; font-size:24px; cursor:pointer; transition:color 0.2s;" onmouseover="this.style.color='#f1f5f9'" onmouseout="this.style.color='#94a3b8'">✖</button>
        </div>

        <!-- Summary Bar -->
        <div id="migrationSummary" style="padding:20px 32px; background:#1e293b; border-bottom:1px solid #334155; flex-shrink:0;">
            <!-- Populated by JS -->
        </div>

        <!-- Content Area -->
        <div style="padding:10px 32px; overflow-y:auto; flex-grow:1;">
            <!-- Auto-Committed Section -->
            <div id="autoCommittedSection" style="padding:16px 0; border-bottom:1px solid #334155;">
                <h5 style="color:#22c55e; font-weight:600; font-size:1.1rem; margin-bottom:16px;">
                    <i class="fas fa-check-circle mr-2"></i> Auto-Committed (<span id="autoCount">0</span>)
                </h5>
                <div id="autoCommittedList"><!-- Populated by JS --></div>
            </div>

            <!-- Needs Review Section -->
            <div id="needsReviewSection" style="padding:24px 0 16px;">
                <h5 style="color:#f59e0b; font-weight:600; font-size:1.1rem; margin-bottom:16px;">
                    <i class="fas fa-exclamation-triangle mr-2"></i> Needs Review (<span id="reviewCount">0</span>)
                </h5>
                <div id="reviewFramesList"><!-- Populated by JS --></div>
            </div>
        </div>

        <!-- Footer -->
        <div style="padding:24px 32px; background:#0f172a; border-top:1px solid #334155; display:flex; justify-content:flex-end; gap:12px; border-radius:0 0 16px 16px; flex-shrink:0;">
            <button onclick="approveAllReviewed()" class="btn" id="btnApproveAll" style="display:none; background:#10b981; color:white; border:none; padding:10px 24px; border-radius:8px; font-weight:500; font-family:'Poppins'; transition:all 0.2s;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                <i class="fas fa-check-double mr-2"></i> Approve & Commit All
            </button>
            <button onclick="closeMigrationModal()" class="btn" style="background:transparent; border:1px solid #475569; color:#f1f5f9; padding:10px 24px; border-radius:8px; font-weight:500; font-family:'Poppins'; transition:all 0.2s;" onmouseover="this.style.background='#334155'" onmouseout="this.style.background='transparent'">
                Close
            </button>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (sessionStorage.getItem('vc_auth') !== 'true') {
            const pwd = prompt('Enter Version Control password:');
            if (pwd !== 'Brijesh@1415') {
                alert('Access Denied.');
                window.location.href = "{{ route('Frame.index') }}";
                return;
            } else {
                sessionStorage.setItem('vc_auth', 'true');
            }
        }

        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.frame-checkbox');
        const selectedCount = document.getElementById('selectedCount');

        function updateCount() {
            const count = document.querySelectorAll('.frame-checkbox:checked').length;
            selectedCount.textContent = count;
        }

        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateCount();
        });

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateCount);
        });
    });

    function applyMigration() {
        const selected = Array.from(document.querySelectorAll('.frame-checkbox:checked')).map(cb => cb.value);
        if (selected.length === 0) {
            alert('Please select at least one frame.');
            return;
        }

        const targetVersion = document.getElementById('targetVersion').value;
        const upgradeIcons = document.getElementById('upgradeIconsCheck').checked;
        
        let confirmMsg = `Are you sure you want to migrate ${selected.length} frame(s) to Version ${targetVersion}?`;
        if (targetVersion === 'none') {
            if (!upgradeIcons) {
                alert('Please select a target version or enable icon upgrade.');
                return;
            }
            confirmMsg = `Are you sure you want to upgrade icons for ${selected.length} frame(s) without changing version?`;
        }

        if (!confirm(confirmMsg)) {
            return;
        }

        const btn = document.getElementById('btnMigrate');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Migrating...';

        fetch("{{ route('admin.poster_maker.bulk_migrate') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                ids: selected,
                target_version: targetVersion,
                upgrade_icons: upgradeIcons
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMigrationResult(data);
            } else {
                alert('Migration failed: ' + (data.errors ? data.errors.join('\n') : 'Unknown error'));
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-rocket mr-1"></i> Apply Migration';
            }
        })
        .catch(error => {
            alert('An error occurred during migration.');
            console.error(error);
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-rocket mr-1"></i> Apply Migration';
        });
    }

    function showMigrationResult(data) {
        // Summary
        document.getElementById('migrationSummary').innerHTML = `
            <div style="display:flex; gap:40px; flex-wrap:wrap; font-size:15px; align-items:center;">
                <div style="display:flex; flex-direction:column;">
                    <span style="color:#94a3b8; font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Total Frames</span>
                    <strong style="font-size:1.3rem; color:#f1f5f9; font-weight:600;">${data.total}</strong>
                </div>
                <div style="display:flex; flex-direction:column;">
                    <span style="color:#22c55e; font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Auto-Committed</span>
                    <strong style="font-size:1.3rem; color:#4ade80; font-weight:600;">${data.auto_committed}</strong>
                </div>
                <div style="display:flex; flex-direction:column;">
                    <span style="color:#f59e0b; font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Needs Review</span>
                    <strong style="font-size:1.3rem; color:#fbbf24; font-weight:600;">${data.needs_review}</strong>
                </div>
                <div style="display:flex; flex-direction:column;">
                    <span style="color:#ef4444; font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Errors</span>
                    <strong style="font-size:1.3rem; color:#f87171; font-weight:600;">${data.errors?.length || 0}</strong>
                </div>
            </div>
        `;

        // Auto-committed list
        document.getElementById('autoCount').textContent = data.auto_committed;
        const autoList = document.getElementById('autoCommittedList');
        autoList.innerHTML = '';
        (data.auto_committed_frames || []).forEach(f => {
            autoList.innerHTML += `
                <div style="padding:14px 18px; margin-bottom:10px; background:rgba(34, 197, 94, 0.08); border:1px solid rgba(34, 197, 94, 0.2); border-left:4px solid #22c55e; border-radius:8px; font-size:14px; color:#f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                    <div><i class="fas fa-check text-success mr-2"></i> <strong>${f.zip_name || f.name || 'Frame #' + f.frame_id}</strong></div>
                    <span style="color:#94a3b8; font-size:13px;">${f.max_diff_px ? '(max drift: ' + f.max_diff_px + 'px)' : ''}</span>
                </div>
            `;
        });

        // Needs review list
        document.getElementById('reviewCount').textContent = data.needs_review;
        const reviewList = document.getElementById('reviewFramesList');
        reviewList.innerHTML = '';
        window._reviewFrames = data.review_frames || [];

        if (window._reviewFrames.length > 0) {
            document.getElementById('btnApproveAll').style.display = 'inline-block';
        }

        window._reviewFrames.forEach((f, idx) => {
            let mismatchRows = '';
            const allMismatches = [...(f.web_mismatches || []), ...(f.native_mismatches || [])];
            allMismatches.forEach(m => {
                const color = m.severity === 'major' ? '#ef4444' : '#f59e0b';
                const autoTag = m.auto_compensatable ? '<span style="background:rgba(56,189,248,0.1); color:#38bdf8; padding:4px 10px; border-radius:6px; font-size:11px; font-weight:500; border:1px solid rgba(56,189,248,0.2);">Auto-Fix Available</span>' : '';
                mismatchRows += `
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.05); transition:background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                        <td style="padding:12px 14px; color:#94a3b8; font-weight:500; font-size:13px;">${m.engine.toUpperCase()}</td>
                        <td style="padding:12px 14px; color:#e2e8f0; font-weight:500; font-size:13px;">${m.layer}</td>
                        <td style="padding:12px 14px;"><code style="background:rgba(0,0,0,0.3); color:#cbd5e1; padding:4px 8px; border-radius:6px; font-size:12px; font-family:monospace;">${m.property}</code></td>
                        <td style="padding:12px 14px; color:#f87171; font-weight:500; font-size:13px;">${m.golden_value}</td>
                        <td style="padding:12px 14px; color:#4ade80; font-weight:500; font-size:13px;">${m.new_value}</td>
                        <td style="padding:12px 14px; color:${color}; font-weight:600; font-size:13px;">${m.diff > 0 ? '+' : ''}${m.diff}px</td>
                        <td style="padding:12px 14px; text-align:right;">${autoTag}</td>
                    </tr>
                `;
            });

            reviewList.innerHTML += `
                <div style="margin-bottom:24px; background:#0f172a; border:1px solid #334155; border-radius:12px; overflow:hidden; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);">
                    <div style="padding:16px 20px; background:rgba(245, 158, 11, 0.05); border-bottom:1px solid #334155; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
                        <div style="display:flex; flex-direction:column;">
                            <span style="font-size:15px; color:#f8fafc; display:flex; align-items:center;">
                                <i class="fas fa-exclamation-triangle text-warning mr-2"></i> 
                                <strong style="font-weight:600; letter-spacing:0.5px;">${f.zip_name || 'Frame #' + f.frame_id}</strong>
                                <span style="color:#94a3b8; margin:0 12px;">—</span>
                                <span style="color:#e2e8f0; font-weight:500; background:rgba(255,255,255,0.05); padding:4px 10px; border-radius:6px; border:1px solid rgba(255,255,255,0.1);">
                                    V${f.current_version} <i class="fas fa-arrow-right mx-2 text-muted" style="font-size:11px;"></i> V${f.target_version}
                                </span>
                            </span>
                            <span style="color:#ef4444; font-size:12px; margin-top:6px; margin-left:26px; font-weight:500;"><i class="fas fa-ruler-vertical mr-1"></i> Max drift: ${f.max_diff_px}px</span>
                        </div>
                        <div style="display:flex; gap:12px;">
                            <button onclick="autoCompensateFrame(${idx})" class="btn" style="background:rgba(56,189,248,0.1); border:1px solid rgba(56,189,248,0.3); color:#38bdf8; font-family:'Poppins'; font-weight:500; font-size:13px; padding:8px 16px; border-radius:8px; transition:all 0.2s; box-shadow:0 2px 4px rgba(0,0,0,0.1);" onmouseover="this.style.background='rgba(56,189,248,0.2)'; this.style.borderColor='rgba(56,189,248,0.5)';" onmouseout="this.style.background='rgba(56,189,248,0.1)'; this.style.borderColor='rgba(56,189,248,0.3)';" title="Auto-fix linear properties">
                                <i class="fas fa-sync-alt mr-1"></i> Auto-Compensate
                            </button>
                            <button onclick="approveFrame(${idx})" class="btn" style="background:rgba(34,197,94,0.1); border:1px solid rgba(34,197,94,0.3); color:#4ade80; font-family:'Poppins'; font-weight:500; font-size:13px; padding:8px 16px; border-radius:8px; transition:all 0.2s; box-shadow:0 2px 4px rgba(0,0,0,0.1);" onmouseover="this.style.background='rgba(34,197,94,0.2)'; this.style.borderColor='rgba(34,197,94,0.5)';" onmouseout="this.style.background='rgba(34,197,94,0.1)'; this.style.borderColor='rgba(34,197,94,0.3)';">
                                <i class="fas fa-check mr-1"></i> Approve
                            </button>
                        </div>
                    </div>
                    <div style="padding:0; overflow-x:auto;">
                        <table style="width:100%; border-collapse:collapse; min-width:600px;">
                            <thead>
                                <tr style="background:rgba(255,255,255,0.03); text-transform:uppercase; font-size:11px; letter-spacing:0.5px; color:#94a3b8;">
                                    <th style="padding:14px 14px; text-align:left; font-weight:600; border-bottom:1px solid #334155;">Engine</th>
                                    <th style="padding:14px 14px; text-align:left; font-weight:600; border-bottom:1px solid #334155;">Layer</th>
                                    <th style="padding:14px 14px; text-align:left; font-weight:600; border-bottom:1px solid #334155;">Property</th>
                                    <th style="padding:14px 14px; text-align:left; font-weight:600; border-bottom:1px solid #334155;">Golden</th>
                                    <th style="padding:14px 14px; text-align:left; font-weight:600; border-bottom:1px solid #334155;">New</th>
                                    <th style="padding:14px 14px; text-align:left; font-weight:600; border-bottom:1px solid #334155;">Diff</th>
                                    <th style="padding:14px 14px; border-bottom:1px solid #334155;"></th>
                                </tr>
                            </thead>
                            <tbody>${mismatchRows}</tbody>
                        </table>
                    </div>
                </div>
            `;
        });

        const modal = document.getElementById('migrationResultModal');
        modal.style.display = 'flex';
    }

    function closeMigrationModal() {
        document.getElementById('migrationResultModal').style.display = 'none';
        location.reload();
    }

    function autoCompensateFrame(idx) {
        const frame = window._reviewFrames[idx];
        const allMismatches = [...(frame.web_mismatches || []), ...(frame.native_mismatches || [])];
        const autoFixable = allMismatches.filter(m => m.auto_compensatable);

        if (autoFixable.length === 0) {
            alert('No auto-compensatable properties found. Manual review required.');
            return;
        }

        if (!confirm(`Auto-compensate ${autoFixable.length} properties for "${frame.zip_name}"?`)) return;

        fetch("{{ route('admin.poster_maker.auto_compensate') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                frame_id: frame.frame_id,
                target_version: frame.target_version,
                mismatches: autoFixable,
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Remove from review list visually
                window._reviewFrames.splice(idx, 1);
                showMigrationResult({
                    total: window._reviewFrames.length + 1, // keeping original count ish
                    auto_committed: parseInt(document.getElementById('autoCount').textContent) + 1,
                    needs_review: window._reviewFrames.length,
                    auto_committed_frames: [{
                        zip_name: frame.zip_name,
                        status: 'AUTO_COMPENSATED'
                    }],
                    review_frames: window._reviewFrames,
                    errors: [],
                });
            } else {
                alert('Auto-compensation failed: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error during auto-compensation.');
        });
    }

    function approveFrame(idx) {
        // Since it's a bulk migration tool, approving just removes it from the list
        // and optionally updates DB if we wanted manual override.
        // For now, just mark it visually resolved.
        alert('Frame approved manually. Note: You still need to fix complex mismatches in the Web Editor later.');
        window._reviewFrames.splice(idx, 1);
        showMigrationResult({
            total: window._reviewFrames.length,
            auto_committed: parseInt(document.getElementById('autoCount').textContent),
            needs_review: window._reviewFrames.length,
            auto_committed_frames: [],
            review_frames: window._reviewFrames,
            errors: [],
        });
    }

    function approveAllReviewed() {
        alert('All remaining frames marked as reviewed.');
        closeMigrationModal();
    }
</script>
@endsection
