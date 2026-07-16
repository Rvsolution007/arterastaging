@extends('layouts.app')

@section('title', 'Benchmark Frames')

@section('extra_css')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

.aim-container { font-family: 'Inter', sans-serif; padding: 1rem; }

/* Header */
.aim-header { display: flex; align-items: center; gap: 16px; margin-bottom: 2rem; flex-wrap: wrap; }
.aim-header-icon { width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, #f97316, #fdba74); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.5rem; box-shadow: 0 8px 24px rgba(249,115,22,0.3); }
.aim-header h2 { font-size: 1.5rem; font-weight: 800; color: #1e293b; margin: 0; }
.aim-header p { font-size: 0.85rem; color: #64748b; margin: 0; }

/* Panels */
.aim-panel { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem; overflow: hidden; }
.aim-panel-header { padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.aim-panel-title-wrapper { display: flex; align-items: center; gap: 12px; }
.aim-panel-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; color: #fff; }
.aim-panel-icon.orange { background: linear-gradient(135deg, #f97316, #ea580c); }
.aim-panel-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; }

/* Table */
.aim-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.aim-table th { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; font-weight: 600; padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #f1f5f9; }
.aim-table td { padding: 0.75rem; font-size: 0.85rem; color: #334155; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.aim-table tr:hover td { background: #f8fafc; }

/* Badges */
.aim-badge { display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
.aim-badge-success { background: #dcfce7; color: #16a34a; }
.aim-badge-secondary { background: #f1f5f9; color: #475569; }

/* Buttons */
.aim-btn { display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; font-family: 'Inter', sans-serif; text-decoration: none; }
.aim-btn-warning { background: #fef3c7; color: #d97706; }
.aim-btn-warning:hover { background: #fde68a; }
.aim-btn-info { background: #e0f2fe; color: #0284c7; }
.aim-btn-info:hover { background: #bae6fd; }

.frame-thumbnail { height: 40px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
</style>
@endsection

@section('content')
<div class="aim-container">
    <!-- Header -->
    <div class="aim-header">
        <div class="aim-header-icon"><i class="fa-solid fa-images"></i></div>
        <div>
            <h2>Benchmark Frames</h2>
            <p>Select which frames should be used as benchmarks for automated regression testing</p>
        </div>
    </div>

    <!-- Panel -->
    <div class="aim-panel">
        <div class="aim-panel-header">
            <div class="aim-panel-title-wrapper">
                <div class="aim-panel-icon orange"><i class="fa-solid fa-list-ul"></i></div>
                <h5 class="aim-panel-title">All Frames ({{ count($benchmarkIds) }} Benchmarks Set)</h5>
            </div>
        </div>
        <div class="table-responsive">
            <table class="aim-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Zip Name</th>
                        <th>Thumbnail</th>
                        <th>Version</th>
                        <th>Is Benchmark?</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($frames as $frame)
                        <tr>
                            <td style="font-weight: 600;">{{ $frame->id }}</td>
                            <td style="font-weight: 500;">{{ $frame->zip_name }}</td>
                            <td class="align-middle">
                                <img src="{{ asset('uploads/template/'.$frame->zip_name.'/preview.webp') }}" alt="thumbnail" class="frame-thumbnail">
                            </td>
                            <td><span class="aim-badge aim-badge-secondary">v{{ $frame->render_version ?? 1 }}</span></td>
                            <td>
                                @if($frame->is_benchmark)
                                    <span class="aim-badge aim-badge-success" id="badge-{{ $frame->id }}">Yes</span>
                                @else
                                    <span class="aim-badge aim-badge-secondary" id="badge-{{ $frame->id }}">No</span>
                                @endif
                            </td>
                            <td>
                                <button class="aim-btn aim-btn-{{ $frame->is_benchmark ? 'warning' : 'info' }}" id="btn-{{ $frame->id }}" onclick="toggleBenchmark({{ $frame->id }})">
                                    {{ $frame->is_benchmark ? 'Remove Benchmark' : 'Set Benchmark' }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($frames->hasPages())
        <div style="padding:1rem; display:flex; justify-content:center;">
            {{ $frames->links() }}
        </div>
        @endif
    </div>
</div>

<script>
    function toggleBenchmark(frameId) {
        fetch("{{ route('admin.regression_tests.toggle_benchmark') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                frame_id: frameId
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById(`badge-${frameId}`);
                const btn = document.getElementById(`btn-${frameId}`);
                
                if (data.is_benchmark) {
                    badge.className = 'aim-badge aim-badge-success';
                    badge.textContent = 'Yes';
                    btn.className = 'aim-btn aim-btn-warning';
                    btn.textContent = 'Remove Benchmark';
                } else {
                    badge.className = 'aim-badge aim-badge-secondary';
                    badge.textContent = 'No';
                    btn.className = 'aim-btn aim-btn-info';
                    btn.textContent = 'Set Benchmark';
                }
            } else {
                alert('Error toggling benchmark status.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error updating benchmark status.');
        });
    }
</script>
@endsection
