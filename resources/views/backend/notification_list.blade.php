@extends('layouts.app')

@section('extra_css')
    <style type="text/css">
    </style>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Notification List</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.notification') }}" class="btn btn-success btn-sm">Sent Notification</a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session()->has('message'))
                        <div class="alert alert-success">
                            {{ session()->get('message') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="notificationTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Message</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($notifications as $notification)
                                    <tr>
                                        <td>{{ $notification->id }}</td>
                                        <td>
                                            @if($notification->image)
                                                <img src="{{ asset('uploads/' . $notification->image) }}" width="50px" height="50px"
                                                    class="rounded shadow-sm">
                                            @else
                                                <img src="{{ asset('assets/images/no-image.png') }}" width="50px" height="50px"
                                                    class="rounded shadow-sm">
                                            @endif
                                        </td>
                                        <td>{{ $notification->title }}</td>
                                        <td>{{ Str::limit($notification->message, 50) }}</td>
                                        <td><span class="badge badge-info">{{ ucfirst($notification->type) }}</span></td>
                                        <td>{{ $notification->created_at->format('d-m-Y H:i') }}</td>
                                        <td>
                                            <form action="{{ route('admin.notification_delete') }}" method="POST"
                                                style="display:inline-block;">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $notification->id }}">
                                                <button type="submit" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Are you sure?')"><i
                                                        class="fa fa-trash"></i></button>
                                            </form>
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

@section("script")
    <script type="text/javascript">
        $(document).ready(function () {
            $('#notificationTable').DataTable({
                "order": [[0, "desc"]]
            });
        });
    </script>
@endsection