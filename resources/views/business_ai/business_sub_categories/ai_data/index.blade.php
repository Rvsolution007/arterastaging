@extends('layouts.app')

@section('extra_css')
    @include('partials.modern_admin_css')
@endsection

@section('content')
@php($parentCategoryName = optional($businessSubCategory->business_category)->name ?: 'Parent category')

<div class="modern-ui-wrapper container-fluid py-3">
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap" style="gap: 12px;">
    <div>
      <div class="text-muted small mb-1">
        <a href="{{ route('business-sub-category.index') }}" class="text-muted">Business Subcategories</a>
        <span class="mx-1">/</span>
        <span>{{ $parentCategoryName }}</span>
        <span class="mx-1">/</span>
        <strong>{{ $businessSubCategory->name }}</strong>
      </div>
      <h3 class="mb-1"><i class="fa fa-wand-magic-sparkles text-primary mr-1"></i> AI Post Data</h3>
      <p class="text-muted mb-0">Manage approved General Data and user Brief fields for <strong>{{ $parentCategoryName }} &rarr; {{ $businessSubCategory->name }}</strong>.</p>
    </div>
    <div class="text-nowrap">
      <a href="{{ route('business-sub-category.edit', $businessSubCategory) }}" class="btn btn-outline-secondary mr-1"><i class="fa fa-pen mr-1"></i> Edit Subcategory</a>
      <a href="{{ route('business_sub_category.ai_data.create', $businessSubCategory) }}" class="btn btn-primary"><i class="fa fa-plus mr-1"></i> Add Custom Post Type Data</a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="alert alert-info border-0 shadow-sm">
    <strong>Fixed context:</strong> this page always saves data only for <strong>{{ $parentCategoryName }} &rarr; {{ $businessSubCategory->name }}</strong>.
    Choose a Custom Post Type, then add its separate General Data and Brief fields. The same subcategory can have many Custom Post Types.
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <div>
        <strong>Saved Custom Post Type data</strong>
        <small class="text-muted d-block">Each row is isolated to this subcategory.</small>
      </div>
      <span class="badge badge-light border">{{ $scopes->total() }} type{{ $scopes->total() === 1 ? '' : 's' }}</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Custom Post Type</th>
            <th>General Data</th>
            <th>Brief Fields</th>
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
              <td class="align-middle">
                <strong>{{ optional($scope->purpose)->title ?: 'Deleted Custom Post Type' }}</strong>
                @if(optional($scope->purpose)->description)
                  <small class="text-muted d-block">{{ $scope->purpose->description }}</small>
                @endif
              </td>
              <td class="align-middle"><span class="badge badge-info">{{ count((array) $scope->general_data) }} point{{ count((array) $scope->general_data) === 1 ? '' : 's' }}</span></td>
              <td class="align-middle">{{ count((array) $scope->brief_fields) }}</td>
              <td class="align-middle">{{ $scope->styles_count ? $scope->styles_count . ' selected' : 'All Type styles' }}</td>
              <td class="align-middle">{{ filled($scope->content_instruction) ? 'Yes' : '—' }}</td>
              <td class="align-middle"><span class="badge badge-{{ $scope->status ? 'success' : 'secondary' }}">{{ $scope->status ? 'Active' : 'Hidden' }}</span></td>
              <td class="align-middle">{{ $scope->sort_order }}</td>
              <td class="align-middle text-right text-nowrap">
                <a href="{{ route('business_sub_category.ai_data.edit', [$businessSubCategory, $scope]) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                <form class="d-inline" method="POST" action="{{ route('business_sub_category.ai_data.destroy', [$businessSubCategory, $scope]) }}" onsubmit="return confirm('Delete this AI Post Data? This will not delete the Custom Post Type itself.');">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-outline-danger btn-sm">Delete</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center text-muted py-5">
                No AI Post Data has been added for this subcategory yet.
                <div class="mt-2"><a href="{{ route('business_sub_category.ai_data.create', $businessSubCategory) }}" class="btn btn-primary btn-sm">Add the first Custom Post Type Data</a></div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($scopes->hasPages())
      <div class="card-body">{{ $scopes->links() }}</div>
    @endif
  </div>
</div>
@endsection
