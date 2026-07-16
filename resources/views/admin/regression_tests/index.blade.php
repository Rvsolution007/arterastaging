@extends('layouts.app')

@section('title', 'Regression Tests')

@section('extra_css')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

.aim-container { font-family: 'Inter', sans-serif; padding: 1rem; }

/* Header */
.aim-header { display: flex; align-items: center; gap: 16px; margin-bottom: 2rem; flex-wrap: wrap; }
.aim-header-icon { width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, #6366f1, #8b5cf6); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.5rem; box-shadow: 0 8px 24px rgba(99,102,241,0.3); }
.aim-header h2 { font-size: 1.5rem; font-weight: 800; color: #1e293b; margin: 0; }
.aim-header p { font-size: 0.85rem; color: #64748b; margin: 0; }

/* Panels */
.aim-panel { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem; overflow: hidden; }
.aim-panel-header { padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.aim-panel-title-wrapper { display: flex; align-items: center; gap: 12px; }
.aim-panel-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; color: #fff; }
.aim-panel-icon.purple { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
.aim-panel-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; }
.aim-panel-body { padding: 1.25rem; }

/* Table */
.aim-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.aim-table th { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; font-weight: 600; padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #f1f5f9; }
.aim-table td { padding: 0.75rem; font-size: 0.85rem; color: #334155; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.aim-table tr:hover td { background: #f8fafc; }

/* Badges */
.aim-badge { display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
.aim-badge-success { background: #dcfce7; color: #16a34a; }
.aim-badge-danger { background: #fee2e2; color: #dc2626; }
.aim-badge-warning { background: #fef3c7; color: #d97706; }
.aim-badge-info { background: #dbeafe; color: #2563eb; }
.aim-badge-secondary { background: #f1f5f9; color: #475569; }

/* Buttons */
.aim-btn { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 10px; font-size: 0.85rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; font-family: 'Inter', sans-serif; text-decoration: none; }
.aim-btn-primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; box-shadow: 0 4px 16px rgba(99,102,241,0.3); }
.aim-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99,102,241,0.4); color: #fff; }
</style>
@endsection

@section('content')
<div class="aim-container">
    <!-- Header -->
    <div class="aim-header">
        <div class="aim-header-icon"><i class="fa-solid fa-vial"></i></div>
        <div>
            <h2>Regression Tests</h2>
            <p>Run and monitor automated regression tests against benchmark frames</p>
        </div>
    </div>

    <!-- Panel -->
    <div class="aim-panel">
        <div class="aim-panel-header">
            <div class="aim-panel-title-wrapper">
                <div class="aim-panel-icon purple"><i class="fa-solid fa-list-check"></i></div>
                <h5 class="aim-panel-title">Run Tests</h5>
            </div>
            <button type="button" class="aim-btn aim-btn-primary" onclick="runTests()">
                <i class="fa-solid fa-play"></i> Run Tests on {{ count($benchmarkFrames) }} Benchmarks
            </button>
        </div>
        <div class="table-responsive">
            <table class="aim-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Trigger</th>
                        <th>Tested</th>
                        <th>Passed</th>
                        <th>Failed</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td style="font-weight: 600;">{{ $log->id }}</td>
                            <td>{{ ucfirst($log->trigger) }}</td>
                            <td>{{ $log->total_frames_tested }}</td>
                            <td>
                                <span class="aim-badge aim-badge-success">{{ $log->passed }}</span>
                            </td>
                            <td>
                                <span class="aim-badge aim-badge-danger">{{ $log->failed }}</span>
                            </td>
                            <td>
                                @if($log->status === 'completed')
                                    @if($log->failed > 0)
                                        <span class="aim-badge aim-badge-warning">Failed Cases</span>
                                    @else
                                        <span class="aim-badge aim-badge-success">Success</span>
                                    @endif
                                @else
                                    <span class="aim-badge aim-badge-info">{{ ucfirst($log->status) }}</span>
                                @endif
                            </td>
                            <td style="font-size: 0.75rem; color: #94a3b8;">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center; padding:2rem;">
                                <i class="fa-solid fa-box-open" style="font-size:2rem; color:#e2e8f0;"></i>
                                <p style="color:#94a3b8; margin-top:0.5rem;">No regression test logs found.</p>
                            </td>
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

<script>
    function runTests() {
        if (!confirm('Run regression tests against all benchmark frames?')) return;

        fetch("{{ route('admin.regression_tests.run') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                trigger: 'manual'
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(`Tests completed! Passed: ${data.passed}, Failed: ${data.failed}`);
                location.reload();
            } else {
                alert('Error running tests: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error triggering regression tests.');
        });
    }
</script>
@endsection
