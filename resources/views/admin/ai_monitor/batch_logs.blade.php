@extends("layouts.app")

@section('extra_css')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

.aim-container { font-family: 'Inter', sans-serif; padding: 1rem; }
.aim-header { display: flex; align-items: center; gap: 16px; margin-bottom: 2rem; flex-wrap: wrap; }
.aim-header-icon { width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, #6366f1, #8b5cf6); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.5rem; box-shadow: 0 8px 24px rgba(99,102,241,0.3); }
.aim-header h2 { font-size: 1.5rem; font-weight: 800; color: #1e293b; margin: 0; }
.aim-header p { font-size: 0.85rem; color: #64748b; margin: 0; }
.aim-back { display: inline-flex; align-items: center; gap: 6px; font-size: 0.8rem; color: #6366f1; text-decoration: none; font-weight: 600; margin-bottom: 1rem; }
.aim-back:hover { color: #4f46e5; }

.aim-panel { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem; overflow: hidden; }
.aim-panel-header { padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; }
.aim-panel-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; color: #fff; }
.aim-panel-icon.purple { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
.aim-panel-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; }

.aim-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.aim-table th { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; font-weight: 600; padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #f1f5f9; }
.aim-table td { padding: 0.75rem; font-size: 0.85rem; color: #334155; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.aim-table tr:hover td { background: #f8fafc; }

.aim-badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
.aim-badge-success { background: #dcfce7; color: #16a34a; }
.aim-badge-failed { background: #fee2e2; color: #dc2626; }

.aim-expand-btn { background: none; border: none; color: #6366f1; cursor: pointer; font-size: 0.8rem; font-weight: 600; }
.aim-expand-content { display: none; background: #0f172a; color: #e2e8f0; border-radius: 10px; padding: 0.75rem; font-size: 0.75rem; font-family: 'JetBrains Mono', monospace; white-space: pre-wrap; word-break: break-word; max-height: 200px; overflow-y: auto; margin-top: 6px; }

.aim-stats-mini { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 1rem; }
.aim-stat-pill { background: #f1f5f9; border-radius: 10px; padding: 8px 16px; font-size: 0.8rem; color: #475569; font-weight: 600; }
.aim-stat-pill strong { color: #1e293b; }
</style>
@endsection

@section("content")
<div class="aim-container">
    <a href="{{ route('admin.ai_monitor') }}" class="aim-back"><i class="fa-solid fa-arrow-left"></i> Back to AI Monitor</a>

    <div class="aim-header">
        <div class="aim-header-icon"><i class="fa-solid fa-list-check"></i></div>
        <div>
            <h2>Batch #{{ $batch->id }} — {{ $batch->customFrame->purpose->name ?? 'Unknown Purpose' }}</h2>
            <p>Detailed log of every user's AI generation result</p>
        </div>
    </div>

    <div class="aim-stats-mini">
        <div class="aim-stat-pill">Status: <strong>{{ ucfirst($batch->status) }}</strong></div>
        <div class="aim-stat-pill">Users: <strong>{{ $batch->processed_users }}/{{ $batch->total_users }}</strong></div>
        <div class="aim-stat-pill">Tokens: <strong>{{ number_format($batch->total_tokens) }}</strong></div>
        <div class="aim-stat-pill">Cost: <strong>${{ number_format($batch->total_cost, 4) }}</strong></div>
    </div>

    <div class="aim-panel">
        <div class="aim-panel-header">
            <div class="aim-panel-icon purple"><i class="fa-solid fa-scroll"></i></div>
            <h5 class="aim-panel-title">Generation Logs</h5>
        </div>
        <div class="table-responsive">
            <table class="aim-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Product ID</th>
                        <th>Status</th>
                        <th>Tokens</th>
                        <th>Prompt</th>
                        <th>Response</th>
                        <th>Error</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>
                            <span style="font-weight:600;">{{ $log->user->name ?? '—' }}</span>
                            <br><small style="color:#94a3b8;">{{ $log->user->email ?? '' }}</small>
                        </td>
                        <td>{{ $log->product_id ?? '—' }}</td>
                        <td>
                            @if($log->status == 'success')
                                <span class="aim-badge aim-badge-success"><i class="fa-solid fa-check"></i> OK</span>
                            @else
                                <span class="aim-badge aim-badge-failed"><i class="fa-solid fa-xmark"></i> Fail</span>
                            @endif
                        </td>
                        <td>{{ number_format($log->tokens_used) }}</td>
                        <td>
                            @if($log->raw_prompt)
                                <button class="aim-expand-btn" onclick="toggleExpand(this)"><i class="fa-solid fa-eye"></i> View</button>
                                <div class="aim-expand-content">{{ $log->raw_prompt }}</div>
                            @else —
                            @endif
                        </td>
                        <td>
                            @if($log->raw_response)
                                <button class="aim-expand-btn" onclick="toggleExpand(this)"><i class="fa-solid fa-eye"></i> View</button>
                                <div class="aim-expand-content">{{ $log->raw_response }}</div>
                            @else —
                            @endif
                        </td>
                        <td style="font-size:0.75rem; color:#dc2626;">{{ $log->error_message ?? '—' }}</td>
                        <td style="font-size:0.75rem; color:#94a3b8;">{{ $log->created_at->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:2rem; color:#94a3b8;">No logs found for this batch.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div style="padding:1rem; display:flex; justify-content:center;">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@section("script")
<script>
function toggleExpand(btn) {
    var content = btn.nextElementSibling;
    if (content.style.display === 'block') {
        content.style.display = 'none';
        btn.innerHTML = '<i class="fa-solid fa-eye"></i> View';
    } else {
        content.style.display = 'block';
        btn.innerHTML = '<i class="fa-solid fa-eye-slash"></i> Hide';
    }
}
</script>
@endsection
