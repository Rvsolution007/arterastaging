@extends("layouts.app")

@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="card card-primary">
      <div class="card-header">
        <h3 class="card-title float-left">
            User Activity Logs
        </h3>
      </div> 

      <div class="card-body">
        <table class="table table-bordered table-striped" id="data_table_activities">
          <thead class="thead-inverse">
            <tr>
              <th>User</th>
              <th>Action</th>
              <th>URL</th>
              <th>Method</th>
              <th>IP Address</th>
              <th>Time</th>
            </tr>
          </thead>
          <tbody>
          @foreach($activities as $row)
            <tr>
              <td>
                  @if($row->user)
                    <div class="d-flex align-items-center">
                        <img src="{{ $row->user->image ? (substr($row->user->image, 0, 4)=='http' ? $row->user->image : asset('uploads/'.$row->user->image)) : asset('assets/images/no-user.jpg') }}" class="rounded-circle mr-2" width="40" height="40" alt="Image"/>
                        <div>
                            <b>{{ $row->user->name }}</b><br/>
                            <small>{{ $row->user->email }}</small>
                        </div>
                    </div>
                  @else
                    <span class="text-muted">Guest</span>
                  @endif
              </td>
              <td>
                <span class="badge badge-info">{{ $row->action }}</span>
              </td>
              <td style="max-width:300px; word-wrap:break-word;">
                <small>{{ $row->url }}</small>
              </td>
              <td>
                <span class="badge badge-{{ $row->method == 'GET' ? 'success' : ($row->method == 'POST' ? 'primary' : 'warning') }}">
                    {{ $row->method }}
                </span>
              </td>
              <td>{{ $row->ip_address }}</td>
              <td>{{ date('d/m/Y g:i A', strtotime($row->created_at)) }}</td>
            </tr>
          @endforeach
          @if(count($activities) == 0)
            <tr class="text-center">
              <td colspan="6">No activity logs found</td>
            </tr>
          @endif
          </tbody>
        </table>
        <div class="d-flex justify-content-center mt-3">{{ $activities->appends(request()->input())->onEachSide(1)->links() }}</div>
      </div>
    </div>
  </div>
</div>
@endsection
