@extends("layouts.app")

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Add New Post Purpose</h3>
                </div>
                <div class="card-body">
                    {!! Form::open(['route' => 'post-purpose.store', 'method' => 'POST']) !!}
                    <div class="form-group row">
                        {!! Form::label('name', 'Name', ['class' => 'col-sm-2 col-form-label']) !!}
                        <div class="col-sm-10">
                            {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => 'Enter Post Purpose Name']) !!}
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="offset-sm-2 col-sm-10">
                            <button type="submit" class="btn btn-primary">Save</button>
                            <a href="{{ route('post-purpose.index') }}" class="btn btn-default">Cancel</a>
                        </div>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
@endsection