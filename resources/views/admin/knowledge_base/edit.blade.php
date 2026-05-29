@extends('layouts.app')

@section('extra_css')
<style>
    /* Modern AI Analytics Light Theme - Forms */
    .ai-wrapper {
        font-family: 'Inter', 'Poppins', sans-serif;
        color: #1e293b;
        padding-top: 10px;
    }
    .ai-header-section {
        padding: 0 0 1.5rem 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .ai-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        letter-spacing: -0.5px;
    }
    .ai-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-top: 0.25rem;
    }
    .form-panel {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        padding: 2rem;
        margin-bottom: 2rem;
    }
    .form-group label {
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.5rem;
    }
    .form-control {
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        padding: 0.6rem 1rem;
        font-family: 'Inter', 'Poppins', sans-serif;
        color: #1e293b;
        transition: all 0.2s;
    }
    .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .btn-glow {
        background: linear-gradient(135deg, #3b82f6 0%, #4f46e5 100%);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    }
    .btn-glow:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        color: white;
    }
    .btn-light {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.2s;
    }
    .btn-light:hover {
        background: #e2e8f0;
        color: #1e293b;
    }
</style>
@endsection

@section('content')
<div class="ai-wrapper">
    <!-- Header -->
    <div class="ai-header-section">
        <div>
            <h1 class="ai-title">Edit FAQ</h1>
            <p class="ai-subtitle">Update AI Training Knowledge</p>
        </div>
        <a href="{{ route('admin.knowledge_base') }}" class="btn-light">
            <i class="fas fa-arrow-left mr-2"></i> Back to Library
        </a>
    </div>

    <!-- Form Container -->
    <div class="row">
        <div class="col-lg-8">
            <div class="form-panel">
                <form action="{{ route('admin.knowledge_base.update', $kb->id) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Question / Intent <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="question" id="question_input" class="form-control" value="{{ $kb->question }}" required>
                            <div class="input-group-append">
                                <button type="button" id="btn_ai_question" class="btn btn-info">
                                    <i class="fas fa-brain"></i> AI Analyze
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Answer (AI Reply) <span class="text-danger">*</span></label>
                        <textarea name="answer" id="answer_input" class="form-control" rows="5" required>{{ $kb->answer }}</textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category <span class="text-danger">*</span></label>
                                <select name="category" id="category_input" class="form-control" required>
                                    <option value="subscription plan" {{ $kb->category == 'subscription plan' ? 'selected' : '' }}>Subscription Plan</option>
                                    <option value="payment" {{ $kb->category == 'payment' ? 'selected' : '' }}>Payment</option>
                                    <option value="Editor Section" {{ $kb->category == 'Editor Section' ? 'selected' : '' }}>Editor Section</option>
                                    <option value="Profile" {{ $kb->category == 'Profile' ? 'selected' : '' }}>Profile</option>
                                    <option value="Download problem" {{ $kb->category == 'Download problem' ? 'selected' : '' }}>Download problem</option>
                                    <option value="Frame" {{ $kb->category == 'Frame' ? 'selected' : '' }}>Frame</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-control">
                                    <option value="1" {{ $kb->status ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ !$kb->status ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Keywords (Comma Separated)</label>
                        <input type="text" name="keywords" class="form-control" value="{{ $kb->keywords }}">
                        <small class="text-muted mt-1 d-block">These keywords help the AI match this answer to the user's question.</small>
                    </div>
                    
                    <div class="form-group mt-4 mb-0">
                        <button type="submit" class="btn-glow w-100">
                            <i class="fas fa-save mr-2"></i> Update FAQ Entry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    $('#btn_ai_question').click(function() {
        var question = $('#question_input').val();
        var category = $('#category_input').val();
        var btn = $(this);
        var answerBox = $('#answer_input');
        
        if (!question) {
            alert('Please enter a question first.');
            return;
        }
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: "{{ route('admin.knowledge_base.ai_question') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                question: question,
                category: category
            },
            success: function(response) {
                if(response.status === 'success') {
                    answerBox.val(response.answer);
                } else {
                    alert(response.message);
                }
                btn.prop('disabled', false).html('<i class="fas fa-brain"></i> AI Analyze');
            },
            error: function(xhr) {
                alert("An error occurred while generating the answer.");
                btn.prop('disabled', false).html('<i class="fas fa-brain"></i> AI Analyze');
            }
        });
    });
</script>
@endsection
