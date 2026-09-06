@extends('layouts.app')

@section('extra_css')
    @include('partials.modern_admin_css')
@endsection

@section('content')
<div class="modern-ui-wrapper container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-1"><i class="fa fa-database text-primary mr-1"></i> Linked Subcategory Data</h3>
      <p class="text-muted mb-0">{{ $businessAiPurpose->title }} — keep each category and subcategory's approved content separate.</p>
    </div>
    <div class="text-nowrap">
      <a href="{{ route('custom_post_types.edit', $businessAiPurpose) }}" class="btn btn-outline-secondary mr-1"><i class="fa fa-cog mr-1"></i> Type Studio</a>
      <a href="{{ route('business-sub-category.index') }}" class="btn btn-primary"><i class="fa fa-sitemap mr-1"></i> Open Business Subcategories</a>
    </div>
  </div>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif

  <div class="alert alert-info border-0 shadow-sm">
    <strong>Simple flow:</strong> Open <strong>Business Subcategories</strong>, select Eye Clinic or Skin Care Clinic, then use <strong>AI Post Data</strong>. Category and subcategory stay fixed there, so only the Custom Post Type needs to be selected.
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <div>
        <strong>Saved category data</strong>
        <small class="text-muted d-block">General Data + separate user Brief + optional instructions</small>
      </div>
      <span class="badge badge-light border">{{ $scopes->total() }} row{{ $scopes->total() === 1 ? '' : 's' }}</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Business category</th>
            <th>Subcategory</th>
            <th>General Data</th>
            <th>Brief fields</th>
            <th>Styles</th>
            <th>Instruction</th>
            <th>Status</th>
            <th>Order</th>
            <th class="text-right">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($scopes as $scope)
            <tr>
              <td class="align-middle"><strong>{{ optional($scope->category)->name ?: 'Deleted category' }}</strong></td>
              <td class="align-middle">
                @if($scope->business_sub_category_id)
                  {{ optional($scope->subCategory)->name ?: 'Deleted subcategory' }}
                @else
                  <span class="text-muted">All subcategories</span>
                @endif
              </td>
              <td class="align-middle"><span class="badge badge-info">{{ count($scope->general_data ?? []) }} point{{ count($scope->general_data ?? []) === 1 ? '' : 's' }}</span></td>
              <td class="align-middle">{{ count($scope->brief_fields ?? []) }}</td>
              <td class="align-middle">{{ $scope->styles_count ? $scope->styles_count . ' selected' : 'All Type styles' }}</td>
              <td class="align-middle">{{ filled($scope->content_instruction) ? 'Yes' : '—' }}</td>
              <td class="align-middle"><span class="badge badge-{{ $scope->status ? 'success' : 'secondary' }}">{{ $scope->status ? 'Active' : 'Hidden' }}</span></td>
              <td class="align-middle">{{ $scope->sort_order }}</td>
              <td class="align-middle text-right text-nowrap">
                @if($scope->business_sub_category_id && $scope->subCategory)
                  <a href="{{ route('business_sub_category.ai_data.edit', [$scope->subCategory, $scope]) }}" class="btn btn-outline-primary btn-sm">Open in Subcategory</a>
                @else
                  <span class="text-muted small">Category-wide fallback</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center text-muted py-5">
                No subcategory data has been linked to this Type yet.
                <div class="mt-2"><a href="{{ route('business-sub-category.index') }}" class="btn btn-primary btn-sm">Open Business Subcategories</a></div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($scopes->hasPages())<div class="card-body">{{ $scopes->links() }}</div>@endif
  </div>
</div>
@endsection
