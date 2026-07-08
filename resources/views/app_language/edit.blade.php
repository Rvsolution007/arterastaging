@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">{{ isset($language) ? 'Edit App Language' : 'Add App Language' }}</h4>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ isset($language) ? route('app-language.update', $language->id) : route('app-language.store') }}" method="POST">
                    @csrf
                    @if(isset($language))
                        @method('PUT')
                    @endif

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Language Code (e.g., en, hi)</label>
                                <input type="text" name="language_code" class="form-control" value="{{ old('language_code', $language->language_code ?? '') }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Title (e.g., English, Hindi)</label>
                                <input type="text" name="title" class="form-control" value="{{ old('title', $language->title ?? '') }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mt-4">
                                <label>
                                    <input type="checkbox" name="status" value="1" {{ old('status', $language->status ?? 1) == 1 ? 'checked' : '' }}> Active
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5>Translations</h5>
                    <p class="text-muted">Enter the translated text in the right column. The left column shows the default English reference.</p>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Key</th>
                                    <th>English (Reference)</th>
                                    <th>Translation (You Type)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($englishKeys as $key => $englishText)
                                <tr>
                                    <td><code>{{ $key }}</code></td>
                                    <td>{{ $englishText }}</td>
                                    <td>
                                        <input type="text" name="translations[{{ $key }}]" class="form-control" value="{{ isset($language) ? ($language->translations[$key] ?? '') : '' }}">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">Save Language</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
