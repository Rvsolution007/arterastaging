@extends('layouts.app')

@section('extra_css')
    @include('partials.modern_admin_css')
@endsection


@section('content')
<div class="modern-ui-wrapper container-fluid py-3"><div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="mb-1"><i class="fa fa-id-card text-primary mr-1"></i> Custom Post Header &amp; Footer Styles</h3><p class="text-muted mb-0">Reusable business-branding direction selected inside every Custom Post Type.</p></div><a href="{{ route('custom_post_header_footer_styles.create') }}" class="btn btn-primary"><i class="fa fa-plus mr-1"></i> Add Header &amp; Footer Style</a></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Style</th><th>Header prompt</th><th>Footer prompt</th><th>Used by Types</th><th>Status</th><th class="text-right">Action</th></tr></thead><tbody>@forelse($styles as $style)<tr><td class="align-middle"><strong>{{ $style->name }}</strong>@if($style->overlay_enabled)<br><span class="badge badge-info">Branding enabled</span>@endif</td><td class="align-middle text-muted">{{ \Illuminate\Support\Str::limit($style->header_prompt, 100) ?: '—' }}</td><td class="align-middle text-muted">{{ \Illuminate\Support\Str::limit($style->footer_prompt, 100) ?: '—' }}</td><td class="align-middle">{{ $style->custom_post_types_count }}</td><td class="align-middle"><span class="badge badge-{{ $style->status ? 'success' : 'secondary' }}">{{ $style->status ? 'Active' : 'Hidden' }}</span></td><td class="align-middle text-right"><a class="btn btn-outline-primary btn-sm" href="{{ route('custom_post_header_footer_styles.edit', $style) }}">Edit</a><form class="d-inline" method="POST" action="{{ route('custom_post_header_footer_styles.destroy', $style) }}" onsubmit="return confirm('Delete this Header & Footer Style?');">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete</button></form></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-5">No Header &amp; Footer Style exists. Add one before activating a Custom Post Type.</td></tr>@endforelse</tbody></table></div>@if($styles->hasPages())<div class="card-body">{{ $styles->links() }}</div>@endif</div></div>
@endsection
