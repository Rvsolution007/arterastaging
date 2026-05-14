@extends("layouts.app")

@section('extra_css')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

.aim-container { font-family: 'Inter', sans-serif; padding: 1rem; }

/* Header */
.aim-header { display: flex; align-items: center; gap: 16px; margin-bottom: 2rem; flex-wrap: wrap; }
.aim-header-icon { width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, #6366f1, #8b5cf6); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.5rem; box-shadow: 0 8px 24px rgba(99,102,241,0.3); }
.aim-header h2 { font-size: 1.5rem; font-weight: 800; color: #1e293b; margin: 0; }
.aim-header p { font-size: 0.85rem; color: #64748b; margin: 0; }

/* Cards Grid */
.aim-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 2rem; }
.aim-stat-card { background: #fff; border-radius: 16px; padding: 1.25rem; border: 1px solid #e2e8f0; position: relative; overflow: hidden; }
.aim-stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; }
.aim-stat-card.purple::before { background: linear-gradient(90deg, #6366f1, #a78bfa); }
.aim-stat-card.green::before { background: linear-gradient(90deg, #22c55e, #86efac); }
.aim-stat-card.orange::before { background: linear-gradient(90deg, #f97316, #fdba74); }
.aim-stat-card.blue::before { background: linear-gradient(90deg, #3b82f6, #93c5fd); }
.aim-stat-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.1em; color: #94a3b8; font-weight: 600; }
.aim-stat-value { font-size: 1.75rem; font-weight: 800; color: #1e293b; margin-top: 4px; }

/* Panels */
.aim-panel { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem; overflow: hidden; }
.aim-panel-header { padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; }
.aim-panel-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; color: #fff; }
.aim-panel-icon.purple { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
.aim-panel-icon.green { background: linear-gradient(135deg, #22c55e, #16a34a); }
.aim-panel-icon.orange { background: linear-gradient(135deg, #f97316, #ea580c); }
.aim-panel-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; }
.aim-panel-body { padding: 1.25rem; }

/* Table */
.aim-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.aim-table th { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; font-weight: 600; padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #f1f5f9; }
.aim-table td { padding: 0.75rem; font-size: 0.85rem; color: #334155; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.aim-table tr:hover td { background: #f8fafc; }

/* Badges */
.aim-badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
.aim-badge-pending { background: #fef3c7; color: #d97706; }
.aim-badge-processing { background: #dbeafe; color: #2563eb; animation: pulse 1.5s infinite; }
.aim-badge-completed { background: #dcfce7; color: #16a34a; }
.aim-badge-failed { background: #fee2e2; color: #dc2626; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }

/* Playground Form */
.aim-form-group { margin-bottom: 1rem; }
.aim-form-group label { font-size: 0.75rem; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 6px; }
.aim-select, .aim-input { width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 0.85rem; font-family: 'Inter', sans-serif; transition: all 0.2s; background: #fff; }
.aim-select:focus, .aim-input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.aim-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 10px; font-size: 0.85rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; font-family: 'Inter', sans-serif; }
.aim-btn-primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; box-shadow: 0 4px 16px rgba(99,102,241,0.3); }
.aim-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99,102,241,0.4); }
.aim-btn-primary:disabled { opacity: 0.6; cursor: wait; }

/* Debug Output */
.aim-debug-block { margin-top: 1rem; }
.aim-debug-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; font-weight: 600; margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
.aim-debug-pre { background: #0f172a; color: #e2e8f0; border-radius: 10px; padding: 1rem; font-size: 0.78rem; font-family: 'JetBrains Mono', 'Fira Code', monospace; white-space: pre-wrap; word-break: break-word; max-height: 300px; overflow-y: auto; line-height: 1.6; }
.aim-debug-pre .aim-highlight { color: #a78bfa; }
.aim-tokens-badge { display: inline-flex; align-items: center; gap: 4px; background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 600; margin-top: 8px; }

/* Progress bar */
.aim-progress { width: 100%; height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.aim-progress-bar { height: 100%; background: linear-gradient(90deg, #6366f1, #8b5cf6); border-radius: 3px; transition: width 0.5s ease; }

/* Loading spinner */
.aim-spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
@endsection

@section("content")
<div class="aim-container">
    <!-- Header -->
    <div class="aim-header">
        <div class="aim-header-icon"><i class="fa-solid fa-satellite-dish"></i></div>
        <div>
            <h2>AI Generation Monitor</h2>
            <p>Monitor bulk AI generation jobs, debug prompts, and track token costs</p>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="aim-stats">
        <div class="aim-stat-card purple">
            <div class="aim-stat-label">Total Batches</div>
            <div class="aim-stat-value">{{ $batches->total() }}</div>
        </div>
        <div class="aim-stat-card green">
            <div class="aim-stat-label">Completed</div>
            <div class="aim-stat-value">{{ $batches->where('status', 'completed')->count() }}</div>
        </div>
        <div class="aim-stat-card orange">
            <div class="aim-stat-label">Total Tokens Used</div>
            <div class="aim-stat-value">{{ number_format($batches->sum('total_tokens')) }}</div>
        </div>
        <div class="aim-stat-card blue">
            <div class="aim-stat-label">Est. Cost</div>
            <div class="aim-stat-value">${{ number_format($batches->sum('total_cost'), 4) }}</div>
        </div>
    </div>

    <div class="row">
        <!-- Left: Playground -->
        <div class="col-md-5">
            <div class="aim-panel">
                <div class="aim-panel-header">
                    <div class="aim-panel-icon green"><i class="fa-solid fa-flask-vial"></i></div>
                    <h5 class="aim-panel-title">Live AI Playground</h5>
                </div>
                <div class="aim-panel-body">
                    <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 1rem;">
                        Test exactly what the AI receives and returns for a specific User + Template. This does NOT consume a user's quota.
                    </p>
                    <div class="aim-form-group">
                        <label>Select Template (Frame)</label>
                        <select id="pg_frame_id" class="aim-select">
                            <option value="">-- Choose a Template --</option>
                            @foreach($frames as $f)
                                <option value="{{ $f->id }}">{{ $f->purpose->name ?? 'No Purpose' }} — ZIP: {{ $f->zip_file_path }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="aim-form-group">
                        <label>Select User</label>
                        <select id="pg_user_id" class="aim-select">
                            <option value="">-- Choose a User --</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <button id="pg_test_btn" class="aim-btn aim-btn-primary w-100 justify-content-center" onclick="runPlayground()">
                        <i class="fa-solid fa-bolt"></i> Test AI Connection
                    </button>

                    <!-- Debug Output Area -->
                    <div id="pg_result" style="display:none;">
                        <div class="aim-debug-block">
                            <div class="aim-debug-label"><i class="fa-solid fa-arrow-up" style="color:#6366f1;"></i> RAW PROMPT SENT TO AI</div>
                            <div class="aim-debug-pre" id="pg_prompt"></div>
                        </div>
                        <div class="aim-debug-block">
                            <div class="aim-debug-label"><i class="fa-solid fa-arrow-down" style="color:#22c55e;"></i> RAW AI RESPONSE</div>
                            <div class="aim-debug-pre" id="pg_response"></div>
                        </div>
                        <div class="aim-debug-block">
                            <div class="aim-debug-label"><i class="fa-solid fa-check-circle" style="color:#8b5cf6;"></i> PARSED GENERATED CONTENT</div>
                            <div class="aim-debug-pre" id="pg_parsed"></div>
                        </div>
                        <div id="pg_tokens_area"></div>
                        <div id="pg_error_area" style="color: #dc2626; font-size: 0.8rem; margin-top: 8px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Batch History -->
        <div class="col-md-7">
            <div class="aim-panel">
                <div class="aim-panel-header">
                    <div class="aim-panel-icon purple"><i class="fa-solid fa-clock-rotate-left"></i></div>
                    <h5 class="aim-panel-title">Generation Batch History</h5>
                </div>
                <div class="table-responsive">
                    <table class="aim-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Template / Purpose</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Tokens</th>
                                <th>Cost</th>
                                <th>Created</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($batches as $b)
                            <tr>
                                <td>{{ $b->id }}</td>
                                <td>
                                    <span style="font-weight:600;">{{ $b->customFrame->purpose->name ?? '—' }}</span>
                                </td>
                                <td>
                                    @if($b->status == 'pending') <span class="aim-badge aim-badge-pending">Pending</span>
                                    @elseif($b->status == 'processing') <span class="aim-badge aim-badge-processing"><i class="fa-solid fa-spinner fa-spin"></i> Processing</span>
                                    @elseif($b->status == 'completed') <span class="aim-badge aim-badge-completed"><i class="fa-solid fa-check"></i> Completed</span>
                                    @else <span class="aim-badge aim-badge-failed"><i class="fa-solid fa-xmark"></i> Failed</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div class="aim-progress" style="width:80px;">
                                            <div class="aim-progress-bar" style="width: {{ $b->total_users > 0 ? round(($b->processed_users / $b->total_users) * 100) : 0 }}%;"></div>
                                        </div>
                                        <span style="font-size:0.75rem; color:#64748b;">{{ $b->processed_users }}/{{ $b->total_users }}</span>
                                    </div>
                                </td>
                                <td style="font-size:0.8rem;">{{ number_format($b->total_tokens) }}</td>
                                <td style="font-size:0.8rem;">${{ number_format($b->total_cost, 4) }}</td>
                                <td style="font-size:0.75rem; color:#94a3b8;">{{ $b->created_at->diffForHumans() }}</td>
                                <td>
                                    <a href="{{ route('admin.ai_monitor.batch', $b->id) }}" class="aim-btn" style="padding:6px 12px; background:#f1f5f9; color:#475569; font-size:0.75rem;">
                                        <i class="fa-solid fa-eye"></i> Logs
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" style="text-align:center; padding:2rem;">
                                    <i class="fa-solid fa-box-open" style="font-size:2rem; color:#e2e8f0;"></i>
                                    <p style="color:#94a3b8; margin-top:0.5rem;">No generation batches yet. Upload a template to trigger AI generation.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($batches->hasPages())
                <div style="padding:1rem; display:flex; justify-content:center;">
                    {{ $batches->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section("script")
<script>
function runPlayground() {
    var frameId = document.getElementById('pg_frame_id').value;
    var userId = document.getElementById('pg_user_id').value;
    var btn = document.getElementById('pg_test_btn');

    if (!frameId || !userId) {
        alert('Please select both a Template and a User.');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="aim-spinner"></span> Generating...';
    document.getElementById('pg_result').style.display = 'none';
    document.getElementById('pg_error_area').innerHTML = '';

    $.ajax({
        url: "{{ route('admin.ai_monitor.playground') }}",
        type: 'POST',
        data: { frame_id: frameId, user_id: userId },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-bolt"></i> Test AI Connection';
            document.getElementById('pg_result').style.display = 'block';

            document.getElementById('pg_prompt').textContent = data.raw_prompt || '(No prompt generated)';
            document.getElementById('pg_response').textContent = data.raw_response || '(No response)';
            
            try {
                document.getElementById('pg_parsed').textContent = JSON.stringify(data.generated_content, null, 2);
            } catch(e) {
                document.getElementById('pg_parsed').textContent = String(data.generated_content);
            }

            var tokensHtml = '<div class="aim-tokens-badge"><i class="fa-solid fa-coins"></i> Tokens Used: ' + (data.tokens_used || 0) + '</div>';
            if (data.tokens_used > 0) {
                var cost = (data.tokens_used / 1000000 * 0.10).toFixed(6);
                tokensHtml += ' <span class="aim-tokens-badge" style="background:#dcfce7;color:#16a34a;">Est. Cost: $' + cost + '</span>';
            }
            document.getElementById('pg_tokens_area').innerHTML = tokensHtml;

            if (data.error) {
                document.getElementById('pg_error_area').innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + data.error;
            }
        },
        error: function(xhr) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-bolt"></i> Test AI Connection';
            document.getElementById('pg_result').style.display = 'block';
            document.getElementById('pg_error_area').innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Request failed: ' + (xhr.responseJSON?.message || xhr.statusText);
        }
    });
}
</script>
@endsection
