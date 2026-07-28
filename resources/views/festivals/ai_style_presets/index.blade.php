@extends('layouts.app')

@section('extra_css')
    @include('partials.modern_admin_css')
@endsection


@section('content')
<div class="modern-ui-wrapper container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-1"><i class="fa fa-palette text-primary mr-1"></i> Festival Styles</h3>
      <p class="text-muted mb-0">Create reusable Festival Style Prompts once, then select them for any festival.</p>
    </div>
    <a href="{{ route('festival_ai_styles.create') }}" class="btn btn-primary"><i class="fa fa-plus mr-1"></i> Add Festival Style</a>
  </div>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <div class="alert alert-info">
    <strong>Festival Prompt</strong> stays inside each festival's AI Studio. Add colours, typography, layout and design theme in the central <strong>Festival Style Prompt</strong> here.
  </div>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr><th>Style</th><th>Festival Style Prompt</th><th>Used in festivals</th><th>Status</th><th class="text-right">Action</th></tr></thead>
        <tbody>
          @forelse($presets as $preset)
            <tr>
              <td class="align-middle"><strong>{{ $preset->name }}</strong>@if($preset->product_required)<br><span class="badge badge-info">Product required</span>@endif</td>
              <td class="align-middle text-muted">{{ \Illuminate\Support\Str::limit($preset->prompt_text, 130) }}</td>
              <td class="align-middle">{{ $preset->active_festival_assignments_count }}</td>
              <td class="align-middle"><span class="badge badge-{{ $preset->status ? 'success' : 'secondary' }}">{{ $preset->status ? 'Active' : 'Hidden' }}</span></td>
              <td class="align-middle text-right">
                <a class="btn btn-outline-primary btn-sm" href="{{ route('festival_ai_styles.edit', $preset) }}">Edit</a>
                <form class="d-inline" method="POST" action="{{ route('festival_ai_styles.destroy', $preset) }}" onsubmit="return confirm('Delete this Festival Style from every festival?');">
                  @csrf @method('DELETE')
                  <button class="btn btn-outline-danger btn-sm">Delete</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted py-5">No Festival Style exists. Add your first reusable design style.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($presets->hasPages())<div class="card-body">{{ $presets->links() }}</div>@endif
  </div>
</div>
@endsection
