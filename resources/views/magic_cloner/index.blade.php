@extends('layouts.app')

@section('heading')
🪄 AI Magic Cloner - The Control Room (Brain)
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">AI Vision Logic Configuration</h3>
            </div>

            <form action="{{ route('admin.magic_cloner.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="form-group mb-4">
                        <label for="ai_prompt" class="font-weight-bold" style="font-size: 1.1rem;">
                            1. System Prompt (Google Vertex AI Engine Instructions)
                        </label>
                        <p class="text-muted mb-2">
                            This is the exact instruction sent to the AI Model when a user uploads an image. You can specify what JSON keys the AI should return. <strong>Note: Check the Mapping Rules below to ensure your prompt asks for the right variables.</strong>
                        </p>
                        <textarea class="form-control" name="ai_prompt" id="ai_prompt" rows="6" style="font-family: monospace; background: #f8f9fa;">{{ old('ai_prompt', $prompt) }}</textarea>
                        @error('ai_prompt')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <hr class="my-4">

                    <div class="form-group mb-4">
                        <label for="mapping_rules" class="font-weight-bold" style="font-size: 1.1rem;">
                            2. Logic Mapping Array (AI Detection -> Mapped to -> Canvas JSON)
                        </label>
                        <p class="text-muted mb-2">
                            This array connects the AI's detected variables to the Fabric.js properties inside the Custom Templates. This represents how the Magic Cloner dynamically overwrites the template on the Mobile App/Web UI. (Keep this valid JSON syntax).
                        </p>
                        <textarea class="form-control" name="mapping_rules" id="mapping_rules" rows="12" style="font-family: monospace; background: #2b2b2b; color:#28dc66;">{{ old('mapping_rules', $mapping) }}</textarea>
                        @error('mapping_rules')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> How it works on the App Side</h5>
                        <ul>
                            <li>The user clicks <strong>"Magic Clone"</strong> and uploads an image.</li>
                            <li>The backend uses the <strong>System Prompt</strong> above to ping Vertex AI and gets the layout properties (Colors, Font type).</li>
                            <li>The layout is then matched using the <code>ai_layout_style</code> mapping to find the top 3 <strong>Custom Post Frames</strong>.</li>
                            <li>Upon user selection, the frontend editor loops through the JSON layer logic defined in the <strong>Logic Mapping Array</strong> and paints the new AI colors onto <code>objects[type=rect]</code>, <code>objects[type=textbox]</code>, etc.</li>
                        </ul>
                    </div>

                </div>

                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-primary btn-lg px-4"><i class="fas fa-save mr-2"></i> Save AI Engine Updates</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
