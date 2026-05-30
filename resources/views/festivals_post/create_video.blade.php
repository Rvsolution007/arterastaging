@extends("layouts.app")

@section('extra_css')
<style type="text/css">
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

    .admin-container {
        font-family: 'Poppins', sans-serif;
        padding: 1.5rem;
        background-color: #f8fafc;
        min-height: 100vh;
    }

    .page-title {
        font-weight: 700;
        color: #1e293b;
        font-size: 1.75rem;
        letter-spacing: -0.025em;
        margin-bottom: 0.25rem;
    }

    .premium-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
        overflow: hidden;
    }

    .card-header-premium {
        padding: 1.25rem 1.5rem;
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: white;
        border-bottom: none;
    }

    .card-title-premium {
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .form-label-premium {
        font-weight: 600;
        color: #475569;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .btn-premium-save {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        border: none;
        padding: 0.75rem 2.5rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
    }

    .btn-premium-save:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4);
    }

    /* Select2 Customization */
    .select2-container--default .select2-selection--single {
        height: 42px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 10px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 40px !important;
        padding-left: 12px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
    }
</style>
@endsection

@section('content')
<div class="admin-container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex align-items-center mb-4">
                <a href="{{ url('admin/festivals-post?tab=video') }}" class="mr-3 text-muted" style="font-size: 1.2rem;"><i class="fa fa-arrow-left"></i></a>
                <h1 class="page-title">Add New Video</h1>
            </div>

            <div class="premium-card">
                <div class="card-header-premium">
                    <h3 class="card-title-premium"><i class="fa fa-video mr-2"></i> Video Configuration</h3>
                </div>

                <div class="card-body p-4">
                    @if (count($errors) > 0)
                        <div class="alert alert-danger shadow-sm border-0 mb-4" style="border-radius: 12px;">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {!! Form::open(['route' => 'video.store', 'method' => 'post', 'files' => true, 'class' => 'poppins-font']) !!}
                    {!! Form::hidden('type', 'festival')!!}
                    {!! Form::hidden('redirect_to', 'festivals-post')!!}

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label-premium">Select Festival</label>
                            <select id="festival_id" name="festival_id" class="form-control" required>
                                <option value="">Choose a festival...</option>
                                @foreach($festivals as $f)
                                    <option value="{{$f->id}}">{{$f->title}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label-premium">Select Language</label>
                            <select id="language_id" name="language_id" class="form-control" required>
                                <option value="">Choose a language...</option>
                                @foreach($language as $l)
                                    <option value="{{$l->id}}">{{$l->title}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-4">
                            <label class="form-label-premium">Upload Video</label>
                            <input type="file" class="form-control" name="video" accept=".mp4, .webm, .ogg" required style="height: auto; padding: 0.6rem;">
                        </div>
                    </div>

                    <div class="row mt-5">
                        <div class="col-12 text-center">
                            @if(optional(Auth::user())->user_type == "Demo")
                                <button type="button" class="btn btn-premium-save ToastrButton">Save Video</button>
                            @else
                                <button type="submit" class="btn btn-premium-save">Save Video</button>
                            @endif
                        </div>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section("script")
    <script type="text/javascript">
        $(document).ready(function () {
            $('#festival_id').select2();
            $('#language_id').select2();
        });
    </script>
@endsection
