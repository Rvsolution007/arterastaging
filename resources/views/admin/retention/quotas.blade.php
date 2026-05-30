@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    @include('admin.retention.tabs')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Automated Upgrade Prompts (Quota Alerts)</h2>
        <p class="text-muted mb-0">Logs of users who hit 90% of their usage limits and received an upgrade prompt.</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Usage</th>
                            <th>Percentage</th>
                            <th>Triggered At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($quotas as $alert)
                        @php
                            $percentage = $alert->limit_amount > 0 ? round(($alert->used_amount / $alert->limit_amount) * 100) : 0;
                        @endphp
                        <tr>
                            <td>{{ $alert->id }}</td>
                            <td>{{ $alert->user ? $alert->user->name : 'Unknown' }} ({{ $alert->user ? $alert->user->email : '' }})</td>
                            <td>{{ $alert->used_amount }} / {{ $alert->limit_amount }}</td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">{{ $percentage }}%</div>
                                </div>
                            </td>
                            <td>{{ $alert->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No quota alerts triggered yet. Run <code class="ms-1">php artisan artera:quota-alert</code> to test.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $quotas->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
