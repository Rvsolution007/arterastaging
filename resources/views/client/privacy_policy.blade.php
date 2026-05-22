@extends('landing.layout')

@section('title', 'Artera - Privacy Policy')

@section('extra_css')
<style>
    .page-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: white;
        padding: 80px 0 60px;
        text-align: center;
    }
    .page-title {
        font-size: 42px;
        font-weight: 800;
        margin-bottom: 15px;
    }
    .page-subtitle {
        font-size: 18px;
        color: rgba(255, 255, 255, 0.8);
    }
    .policy-content-wrapper {
        background: white;
        border-radius: 20px;
        box-shadow: var(--shadow-lg);
        padding: 50px;
        margin-top: -40px;
        margin-bottom: 80px;
        position: relative;
        z-index: 10;
    }
    .prose {
        max-width: 800px;
        margin: 0 auto;
        color: var(--text-gray);
        line-height: 1.8;
        font-size: 16px;
    }
    /* Force base styles to override WYSIWYG editor inline styles */
    .prose p, .prose span, .prose div, .prose li {
        font-size: 16px !important;
        font-family: 'Inter', sans-serif !important;
        line-height: 1.8 !important;
    }
    .prose h1, .prose h2, .prose h3 {
        color: var(--text-dark) !important;
        margin-top: 30px !important;
        margin-bottom: 15px !important;
        font-weight: 700 !important;
        line-height: 1.3 !important;
    }
    .prose h1, .prose h1 * { font-size: 32px !important; }
    .prose h2, .prose h2 * { font-size: 26px !important; }
    .prose h3, .prose h3 * { font-size: 22px !important; }
    .prose p {
        margin-bottom: 20px !important;
    }
    .prose ul, .prose ol {
        margin-bottom: 20px !important;
        padding-left: 20px !important;
    }
    .prose li {
        margin-bottom: 10px !important;
    }
</style>
@endsection

@section('content')
    <div class="page-header">
        <div class="container">
            <h1 class="page-title" data-aos="fade-up">Privacy Policy</h1>
            <p class="page-subtitle" data-aos="fade-up" data-aos-delay="100">Your privacy is critically important to us.</p>
        </div>
    </div>

    <div class="container">
        <div class="policy-content-wrapper" data-aos="fade-up" data-aos-delay="200">
            <div class="prose">
                {!! App\Models\OtherSetting::getOtherSetting('privacy_policy') !!}
            </div>
            
            <div class="text-center" style="border-top: 1px solid #e2e8f0; padding-top: 20px; margin-top: 40px;">
                <p style="font-size: 14px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">
                    Last updated: {{ date('F Y') }}
                </p>
            </div>
        </div>
    </div>
@endsection