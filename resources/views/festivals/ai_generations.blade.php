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
                        <p class="text-muted mb-0">Queue state, provider request, reference validation, compiled prompt and output are available here.</p>
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
                                <th>Language</th>
                                <th>Model</th>
                                <th>Quality / size</th>
                                <th>Status</th>
                                <th>Request details</th>
                                <th>Output</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($generations as $generation)
                                @php
                                    $diagnostics = (array) $generation->request_diagnostics;
                                    $requestFacts = array_filter([
                                        'Compiler' => data_get($diagnostics, 'compiler_version'),
                                        'Prompt characters' => data_get($diagnostics, 'prompt_characters'),
                                        'Estimated prompt tokens' => data_get($diagnostics, 'estimated_prompt_tokens'),
                                        'Endpoint' => data_get($diagnostics, 'endpoint', data_get($diagnostics, 'planned_endpoint')),
                                        'References' => data_get($diagnostics, 'attached_reference_count') !== null
                                            ? data_get($diagnostics, 'attached_reference_count') . ' / ' . data_get($diagnostics, 'expected_reference_count', 0)
                                            : null,
                                        'Reference check' => data_get($diagnostics, 'reference_validation'),
                                        'Brand overlay' => data_get($diagnostics, 'branding_overlay_result'),
                                        'Provider status' => data_get($diagnostics, 'provider_status_code'),
                                        'Provider request ID' => data_get($diagnostics, 'provider_request_id'),
                                        'Prompt hash' => data_get($diagnostics, 'prompt_sha256'),
                                    ], fn($value) => $value !== null && $value !== '');
                                @endphp
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
                                    <td>{{ ucfirst($generation->quality) }} &middot; {{ $generation->size_key }}</td>
                                    <td>
                                        @php($statusClass = ['queued' => 'warning', 'processing' => 'info', 'completed' => 'success', 'failed' => 'danger'][$generation->status] ?? 'secondary')
                                        <span class="badge badge-{{ $statusClass }}">{{ ucfirst($generation->status) }}</span>
                                        @if($generation->status === 'failed' && $generation->error_message)
                                            <div class="small text-danger mt-1">{{ $generation->error_message }}</div>
                                        @endif
                                    </td>
                                    <td style="min-width:280px">
                                        <details>
                                            <summary class="btn btn-outline-secondary btn-sm">Inspect request</summary>
                                            <div class="border rounded bg-light p-2 mt-2 small">
                                                @foreach($requestFacts as $label => $value)
                                                    <div class="mb-1">
                                                        <strong>{{ $label }}:</strong>
                                                        <span class="text-break">{{ $value }}</span>
                                                    </div>
                                                @endforeach
                                                @if(!empty(data_get($diagnostics, 'language_overrides')))
                                                    <div class="mb-1"><strong>Sanitized language conflicts:</strong> {{ implode(', ', (array) data_get($diagnostics, 'language_overrides')) }}</div>
                                                @endif
                                                @if(!empty(data_get($diagnostics, 'truncated_sources')))
                                                    <div class="mb-1"><strong>Bounded sources:</strong> {{ implode(', ', (array) data_get($diagnostics, 'truncated_sources')) }}</div>
                                                @endif
                                                <div class="d-flex justify-content-between align-items-center mt-2 mb-1">
                                                    <strong>Compiled provider prompt</strong>
                                                    <button type="button" class="btn btn-link btn-sm p-0 js-copy-festival-prompt" data-target="festival-ai-prompt-{{ $generation->id }}">Copy</button>
                                                </div>
                                                <pre id="festival-ai-prompt-{{ $generation->id }}" class="mb-0 p-2 bg-white border rounded text-wrap" style="max-height:240px;overflow:auto;white-space:pre-wrap">{{ $generation->final_prompt }}</pre>
                                            </div>
                                        </details>
                                    </td>
                                    <td>
                                        @if($generation->generated_image_url)
                                            <a href="{{ $generation->generated_image_url }}" target="_blank" class="btn btn-outline-primary btn-sm">View image</a>
                                        @else
                                            <span class="text-muted small">&mdash;</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-5">No Festival AI generation has been requested yet.</td></tr>
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

@push('scripts')
<script>
document.addEventListener('click', function (event) {
    const button = event.target.closest('.js-copy-festival-prompt');
    if (!button) return;
    const prompt = document.getElementById(button.dataset.target);
    if (!prompt || !navigator.clipboard) return;
    navigator.clipboard.writeText(prompt.textContent || '').then(function () {
        const original = button.textContent;
        button.textContent = 'Copied';
        setTimeout(function () { button.textContent = original; }, 1200);
    });
});
</script>
@endpush
