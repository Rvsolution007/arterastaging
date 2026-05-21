@extends('layouts.app')

@section('extra_css')
<style>
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

    .custom-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
        overflow: hidden;
        margin-bottom: 1.5rem;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .custom-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .custom-card-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .icon-wrapper {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .icon-primary { background: #e0e7ff; color: #4338ca; }
    .icon-success { background: #d1fae5; color: #059669; }

    .custom-card-body {
        padding: 1.5rem;
        flex: 1;
    }

    .form-group label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 0.5rem;
    }

    .custom-input {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 0.6rem 1rem;
        font-size: 0.875rem;
        color: #334155;
        transition: all 0.2s ease;
        background-color: #f8fafc;
        width: 100%;
    }

    .custom-input:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        background-color: #ffffff;
    }

    .btn-gradient {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
        width: 100%;
    }

    .btn-gradient:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4);
    }

    .btn-success-gradient {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
        width: 100%;
    }

    .btn-success-gradient:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(16, 185, 129, 0.4);
    }
</style>
@endsection

@section('content')
<div class="analytics-container">
    <div class="row align-items-center mb-4">
        <div class="col-md-12">
            <h4 class="page-title mb-1"><i class="fa-solid fa-bullhorn mr-2 text-primary"></i> Partner AI Marketing Toolkit</h4>
            <p class="text-muted mb-0" style="font-size: 0.9rem;">Generate high-converting promotional content with AI to share your affiliate link.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="custom-card">
                <div class="custom-card-header">
                    <div class="icon-wrapper icon-primary">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </div>
                    <h5 class="custom-card-title">Generate Campaign</h5>
                </div>
                <div class="custom-card-body">
                    <form id="ai-generate-form">
                        @csrf
                        <div class="form-group">
                            <label>Platform</label>
                            <select class="custom-input" name="platform" id="platform">
                                <option value="LinkedIn">LinkedIn</option>
                                <option value="Twitter">Twitter (X)</option>
                                <option value="Facebook">Facebook</option>
                                <option value="Email">Email Newsletter</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tone of Voice</label>
                            <select class="custom-input" name="tone" id="tone">
                                <option value="Professional and informative">Professional & Informative</option>
                                <option value="Exciting and sales-oriented">Exciting & Sales-Oriented</option>
                                <option value="Friendly and casual">Friendly & Casual</option>
                            </select>
                        </div>
                        <div class="form-group mb-4">
                            <label>Specific Feature to Promote (Optional)</label>
                            <input type="text" class="custom-input" name="product" id="product" placeholder="e.g. AI Blog Generator, Lead Scoring...">
                        </div>
                        <button type="submit" class="btn-gradient mt-auto" id="generate-btn">
                            <i class="fa fa-magic mr-1"></i> Generate with AI
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="custom-card">
                <div class="custom-card-header">
                    <div class="icon-wrapper icon-success">
                        <i class="fa-solid fa-check-double"></i>
                    </div>
                    <h5 class="custom-card-title">AI Generated Content</h5>
                </div>
                <div class="custom-card-body d-flex flex-column">
                    <textarea class="custom-input flex-grow-1 mb-3" id="generated-result" style="resize: none;" placeholder="Your generated content will appear here..." readonly></textarea>
                    
                    <button class="btn-success-gradient mt-auto" id="copy-btn" style="display:none;">
                        <i class="fa fa-copy mr-1"></i> Copy to Clipboard
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    $('#ai-generate-form').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#generate-btn');
        btn.html('<i class="fa fa-spinner fa-spin mr-1"></i> Generating...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("partner.toolkit.generate") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#generated-result').val(response.text);
                    $('#copy-btn').show();
                    toastr.success('Content generated successfully!');
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('An error occurred during generation.');
            },
            complete: function() {
                btn.html('<i class="fa fa-magic mr-1"></i> Generate with AI').prop('disabled', false);
            }
        });
    });

    $('#copy-btn').click(function() {
        var copyText = document.getElementById("generated-result");
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand("copy");
        toastr.success("Copied to clipboard!");
    });
</script>
@endsection
