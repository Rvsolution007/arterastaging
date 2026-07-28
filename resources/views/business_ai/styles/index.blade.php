@extends('layouts.app')

@section('extra_css')
    @include('partials.modern_admin_css')
@endsection


@section('content')
<div class="modern-ui-wrapper container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div><h3 class="mb-1"><i class="fa fa-palette text-primary mr-1"></i> Custom Post Styles</h3><p class="text-muted mb-0">Reusable visual styles. Custom Post Types select which of these appear after the user's Brief.</p></div>
    <div><a href="{{ route('custom_post_types.index') }}" class="btn btn-outline-primary mr-1"><i class="fa fa-th-large mr-1"></i> Custom Post Types</a><a href="{{ route('custom_post_styles.create') }}" class="btn btn-primary"><i class="fa fa-plus mr-1"></i> Add Custom Post Style</a></div>
  </div>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  <div class="alert alert-info border-0 shadow-sm"><strong>Custom Post Style Prompt</strong> decides the visual look—palette, composition, lighting, mood and professional direction. The selected Type Prompt and user Brief are added separately.</div>
  <div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Style</th><th>Custom Post Style Prompt</th><th>Used by Types</th><th>Status</th><th class="text-right">Action</th></tr></thead><tbody>
    @forelse($styles as $style)
      @php($previewUrl = $style->preview_image ? (\App\Models\StorageSetting::getStorageSetting('storage') === 'DigitalOcean' ? \Illuminate\Support\Facades\Storage::disk('spaces')->url('uploads/' . $style->preview_image) : asset('uploads/' . $style->preview_image)) : '')
      <tr>
        <td class="align-middle">
          <div class="d-flex align-items-center">
            @if($previewUrl)
              <img src="{{ $previewUrl }}" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:8px;margin-right:12px;box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            @else
              <div style="width:48px;height:48px;border-radius:8px;margin-right:12px;" class="bg-light border d-flex align-items-center justify-content-center text-muted"><i class="fa fa-image"></i></div>
            @endif
            <div>
              <strong>{{ $style->name }}</strong><br><small class="text-muted">{{ $style->description ?: '—' }}</small>
            </div>
          </div>
        </td>
        <td class="align-middle text-muted">{{ \Illuminate\Support\Str::limit($style->prompt_text, 150) }}</td><td class="align-middle">{{ $style->purposes_count }}</td><td class="align-middle"><span class="badge badge-{{ $style->status ? 'success' : 'secondary' }}">{{ $style->status ? 'Active' : 'Hidden' }}</span></td><td class="align-middle text-right"><a class="btn btn-outline-primary btn-sm" href="{{ route('custom_post_styles.edit', $style) }}">Edit</a><form class="d-inline" method="POST" action="{{ route('custom_post_styles.destroy', $style) }}" onsubmit="return confirm('Delete this Custom Post Style from every Type?');">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete</button></form></td>
      </tr>
    @empty<tr><td colspan="5" class="text-center text-muted py-5">No Custom Post Style exists. Add a visual style, then link it inside a Custom Post Type.</td></tr>@endforelse
  </tbody></table></div>@if($styles->hasPages())<div class="card-body">{{ $styles->links() }}</div>@endif</div>
</div>
@endsection
