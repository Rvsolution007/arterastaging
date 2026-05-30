@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    @include('admin.retention.tabs')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Dynamic Pricing History</h2>
        <p class="text-muted mb-0">Logs of AI-generated discounts sent to churn-risk users.</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Discount Code</th>
                            <th>AI Subject</th>
                            <th>Sent At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($discounts as $discount)
                        <tr>
                            <td>{{ $discount->id }}</td>
                            <td>{{ $discount->user ? $discount->user->name : 'Unknown User' }} ({{ $discount->user ? $discount->user->email : '' }})</td>
                            <td><span class="badge bg-success">{{ $discount->discount_code }}</span></td>
                            <td>{{ $discount->ai_subject }}</td>
                            <td>{{ $discount->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No dynamic discounts sent yet. Run <code class="ms-1">php artisan artera:dynamic-discount</code> to test.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $discounts->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
