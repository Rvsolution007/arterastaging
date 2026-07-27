@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-1"><i class="fa fa-id-card text-primary mr-1"></i> Festival Header &amp; Footer Styles</h3>
      <p class="text-muted mb-0">Create reusable AI prompts for the business logo, header and footer area.</p>
    </div>
    <a href="{{ route('festival_ai_brand_chrome.create') }}" class="btn btn-primary"><i class="fa fa-plus mr-1"></i> Add Header &amp; Footer Style</a>
  </div>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <div class="alert alert-info shadow-sm border-0">
    The selected style is used while generating the image. The final output then adds the current business logo and only its visible details. Values marked <strong>Hide in frame</strong> are never used.
  </div>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr><th>Style</th><th>Header prompt</th><th>Footer prompt</th><th>Used in festivals</th><th>Status</th><th class="text-right">Action</th></tr></thead>
        <tbody>
          @forelse($presets as $preset)
            <tr>
              <td class="align-middle"><strong>{{ $preset->name }}</strong></td>
              <td class="align-middle text-muted">{{ \Illuminate\Support\Str::limit($preset->header_prompt, 80) ?: '—' }}</td>
              <td class="align-middle text-muted">{{ \Illuminate\Support\Str::limit($preset->footer_prompt, 80) ?: '—' }}</td>
              <td class="align-middle">{{ $preset->festival_configs_count }}</td>
              <td class="align-middle"><span class="badge badge-{{ $preset->status ? 'success' : 'secondary' }}">{{ $preset->status ? 'Active' : 'Hidden' }}</span></td>
              <td class="align-middle text-right">
                <a class="btn btn-outline-primary btn-sm" href="{{ route('festival_ai_brand_chrome.edit', $preset) }}">Edit</a>
                <form class="d-inline" method="POST" action="{{ route('festival_ai_brand_chrome.destroy', $preset) }}" onsubmit="return confirm('Delete this Header & Footer Style?');">
                  @csrf @method('DELETE')
                  <button class="btn btn-outline-danger btn-sm">Delete</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-5">No Header &amp; Footer Style exists. Add one, then choose it inside a festival's AI Studio.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($presets->hasPages())<div class="card-body">{{ $presets->links() }}</div>@endif
  </div>
</div>
@endsection
