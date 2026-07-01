@extends("layouts.app")

@section('extra_css')
<style type="text/css">
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

    .analytics-container {
        font-family: 'Poppins', sans-serif;
        padding: 1.5rem;
        background-color: #f8fafc;
        min-height: 100vh;
    }

    .page-title {
        font-weight: 700;
        color: #1e293b;
        font-size: 1.5rem;
        letter-spacing: -0.025em;
    }

    .table-panel {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .table-panel-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .table-panel-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .custom-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin: 0;
    }

    .custom-table th {
        background: #f8fafc;
        padding: 1rem 1.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    .custom-table td {
        padding: 1rem 1.5rem;
        font-size: 0.875rem;
        color: #334155;
        font-weight: 500;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .badge-soft {
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        background: #f1f5f9;
        color: #475569;
        display: inline-block;
    }

    .status-pending { background: #fef3c7; color: #d97706; }
    .status-approved { background: #d1fae5; color: #059669; }
    .status-rejected { background: #fee2e2; color: #e11d48; }

</style>
@endsection

@section('content')
<div class="analytics-container">
    <div class="row align-items-center mb-4">
        <div class="col-md-12">
            <h4 class="page-title mb-0"><i class="fa-solid fa-list-check mr-2 text-primary"></i> Custom Product Requests</h4>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="table-panel">
                <div class="table-panel-header">
                    <h5 class="table-panel-title">Requests Pending Review</h5>
                    <span class="badge-soft">{{ count($requests) }} Total</span>
                </div>
                <div class="table-responsive">
                    <table class="custom-table" id="data_table">
                        <thead>
                            <tr>
                                <th># ID</th>
                                <th>Requested Product</th>
                                <th>Business / User</th>
                                <th>Sub Category</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $row)
                            <tr>
                                <td><span class="text-muted">#{{$row->id}}</span></td>
                                <td><strong>{{ $row->requested_name }}</strong></td>
                                <td>
                                    {{ $row->business->name ?? 'Unknown' }}
                                </td>
                                <td>
                                    {{ $row->subCategory->name ?? 'Unknown' }}
                                </td>
                                <td>
                                    <span class="badge-soft status-{{$row->status}}">
                                        {{ ucfirst($row->status) }}
                                    </span>
                                    @if($row->status == 'approved' && $row->resolvedProduct)
                                        <br><small class="text-muted">Mapped to: {{ $row->resolvedProduct->name }}</small>
                                    @endif
                                </td>
                                <td>{{ $row->created_at->format('Y-m-d') }}</td>
                                <td>
                                    @if($row->status == 'pending')
                                        <button type="button" class="btn btn-sm btn-success mb-1 w-100" data-toggle="modal" data-target="#approveModal{{$row->id}}">
                                            Approve
                                        </button>
                                        <form method="POST" action="{{ route('custom-product-request.resolve', $row->id) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-sm btn-danger w-100" onclick="return confirm('Are you sure you want to reject this request?')">Reject</button>
                                        </form>

                                        <!-- Approve Modal -->
                                        <div class="modal fade" id="approveModal{{$row->id}}" tabindex="-1" role="dialog" aria-hidden="true">
                                          <div class="modal-dialog modal-dialog-centered" role="document">
                                            <div class="modal-content">
                                              <form method="POST" action="{{ route('custom-product-request.resolve', $row->id) }}">
                                                @csrf
                                                <input type="hidden" name="action" value="approve">
                                                <div class="modal-header">
                                                  <h5 class="modal-title">Approve Request: "{{$row->requested_name}}"</h5>
                                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                  </button>
                                                </div>
                                                <div class="modal-body">
                                                  <div class="form-group">
                                                    <label>Map to Master Product</label>
                                                    <select name="resolved_product_id" class="form-control select2" style="width:100%" required>
                                                        <option value="">Select a Product</option>
                                                        @foreach($products as $p)
                                                            @if($p->business_sub_category_id == $row->business_sub_category_id)
                                                                <option value="{{$p->id}}">{{$p->name}}</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    <small class="form-text text-muted">
                                                        If the requested product doesn't exist yet, please go to <b>Business Products</b>, create it first, and then come back to approve this request.
                                                    </small>
                                                  </div>
                                                </div>
                                                <div class="modal-footer">
                                                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                  <button type="submit" class="btn btn-success">Approve & Map</button>
                                                </div>
                                              </form>
                                            </div>
                                          </div>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
@endsection
