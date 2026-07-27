@extends('layouts.app')

@section('heading')
<div class="mt-5">Festival AI Generation Monitor</div>
@endsection

@section('content')
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="mb-1"><i class="fa fa-magic text-primary mr-1"></i> User-generated festival visuals</h5>
                        <p class="text-muted mb-0">Every request, selected model/quality, queue state, and output is visible here.</p>
                    </div>
                    <form method="GET" class="form-inline mt-2 mt-md-0">
                        <select name="status" class="form-control form-control-sm mr-2">
                            <option value="">All statuses</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary btn-sm">Filter</button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Created</th>
                                <th>User</th>
                                <th>Festival / style</th>
                                <th>Text language</th>
                                <th>Model</th>
                                <th>Quality / size</th>
                                <th>Status</th>
                                <th>Output</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($generations as $generation)
                                <tr>
                                    <td class="small text-muted">{{ optional($generation->created_at)->format('d M Y, h:i A') }}</td>
                                    <td>{{ optional($generation->user)->name ?? 'Deleted user' }}</td>
                                    <td>
                                        <div class="font-weight-bold">{{ optional($generation->festival)->title ?? 'Deleted festival' }}</div>
                                        <small class="text-muted">{{ optional($generation->style)->name ?? 'Style removed' }}</small>
                                    </td>
                                    <td>{{ optional($generation->language)->title ?? 'Removed language' }}</td>
                                    <td>
                                        <div>{{ optional($generation->imageModel)->display_name ?? $generation->provider_model_id }}</div>
                                        <small class="text-muted">{{ $generation->provider }}</small>
                                    </td>
                                    <td>{{ ucfirst($generation->quality) }} · {{ $generation->size_key }}</td>
                                    <td>
                                        @php($statusClass = ['queued' => 'warning', 'processing' => 'info', 'completed' => 'success', 'failed' => 'danger'][$generation->status] ?? 'secondary')
                                        <span class="badge badge-{{ $statusClass }}">{{ ucfirst($generation->status) }}</span>
                                        @if($generation->status === 'failed' && $generation->error_message)
                                            <div class="small text-danger mt-1">{{ $generation->error_message }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($generation->generated_image_url)
                                            <a href="{{ $generation->generated_image_url }}" target="_blank" class="btn btn-outline-primary btn-sm">View image</a>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-5">No Festival AI generation has been requested yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">{{ $generations->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
