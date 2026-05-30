@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    @include('admin.retention.tabs')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Automated Invoices History</h2>
        <p class="text-muted mb-0">List of all generated invoices for successful payments.</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Plan</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                        <tr>
                            <td>#INV-{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $invoice->user ? $invoice->user->name : 'Unknown User' }}</td>
                            <td><span class="badge bg-primary">${{ $invoice->amount }}</span></td>
                            <td>{{ $invoice->subscription ? $invoice->subscription->plan_name : 'Custom Payment' }}</td>
                            <td>{{ $invoice->created_at->format('d M Y') }}</td>
                            <td>
                                <a href="{{ url('invoice/' . $invoice->id) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                    <i class="fa fa-file-pdf"></i> View Invoice
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No invoices found. Generate a successful payment to test.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
