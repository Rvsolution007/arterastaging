@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    @include('admin.retention.tabs')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>One-Click Reactivation (Winback Flows)</h2>
        <p class="text-muted mb-0">Logs of 1-click magic links sent to users whose subscription expired 30 days ago.</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Magic Link</th>
                            <th>Sent At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($winbacks as $winback)
                        <tr>
                            <td>{{ $winback->id }}</td>
                            <td>{{ $winback->user ? $winback->user->name : 'Unknown User' }} ({{ $winback->user ? $winback->user->email : '' }})</td>
                            <td><a href="{{ $winback->magic_link_url }}" target="_blank" class="text-truncate d-inline-block" style="max-width: 400px;">{{ $winback->magic_link_url }}</a></td>
                            <td>{{ $winback->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No winback emails sent yet. Run <code class="ms-1">php artisan artera:winback</code> to test.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $winbacks->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
