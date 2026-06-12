@extends("layouts.app")

@section('extra_css')
<link href="{{ asset('assets/css/frame.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/clean-switch.css')}}">
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        @if (Session::has('success'))
        <div class="alert alert-success">
            {{ Session::get('success') }}
        </div>
        @endif
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="card-title">Mini Website Templates</h4>
            <a href="{{ route('mini-website-template.create') }}" class="btn btn-primary">Add Template</a>
        </div>
    </div>
</div>

<div class="row">
    @foreach ($templates as $template)
    <div class="col-md-4 col-sm-6 mb-4">
        <div class="card p-3 shadow-sm">
            <div class="text-center mb-3">
                @if($template->preview_image)
                    <img src="{{ asset('public/uploads/'.$template->preview_image) }}" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;">
                @else
                    <div style="width: 100%; height: 200px; background: #eee; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #888;">
                        No Preview
                    </div>
                @endif
            </div>
            <h5 class="text-center">{{ $template->name }}</h5>
            
            <div class="d-flex justify-content-between align-items-center mt-3">
                <label class="cl-switch cl-switch-green">
                    <input type="checkbox" id="status_{{ $template->id }}" onchange="updateStatus({{ $template->id }})" {{ $template->status == 1 ? 'checked' : '' }}>
                    <span class="switcher"></span>
                </label>
                
                <div>
                    <a href="{{ route('mini-website-template.edit', $template->id) }}" class="btn btn-sm btn-info text-white"><i class="fas fa-edit"></i></a>
                    <form action="{{ route('mini-website-template.destroy', $template->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this template?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection

@section('script')
<script>
function updateStatus(id) {
    var checked = $('#status_' + id).is(':checked');
    $.ajax({
        type: 'POST',
        url: '{{ url("admin/mini-website-template/status") }}',
        data: {
            _token: '{{ csrf_token() }}',
            id: id,
            checked: checked
        },
        success: function(response) {
            console.log("Status updated");
        }
    });
}
</script>
@endsection
