@extends('landing.layout')

@section('title', 'Artera - Contact Us')

@section('extra_css')
<style>
    /* ---- Hero ---- */
    .contact-hero {
        position: relative;
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
        color: #fff;
        padding: 120px 0 100px;
        overflow: hidden;
    }
    .contact-hero .noise-overlay { z-index: 1; }
    .contact-hero-inner {
        position: relative;
        z-index: 2;
        max-width: 800px;
    }
    .contact-hero .eyebrow {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .pulse-dot {
        width: 8px;
        height: 8px;
        background: #34d399;
        border-radius: 50%;
        display: inline-block;
        animation: pulse-glow 2s ease-in-out infinite;
    }
    @keyframes pulse-glow {
        0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.5); }
        50% { opacity: 0.7; box-shadow: 0 0 0 6px rgba(52, 211, 153, 0); }
    }
    .contact-hero h1 { color: #fff; margin-bottom: 24px; }
    .contact-hero-subtitle {
        font-size: clamp(1rem, 2vw, 1.25rem);
        color: rgba(255, 255, 255, 0.9);
        line-height: 1.7;
        max-width: 600px;
    }

    /* ---- Contact Grid ---- */
    .contact-section { padding: 0 0 100px; }
    .contact-grid {
        display: grid;
        grid-template-columns: 1fr 1.4fr;
        margin-top: -60px;
        position: relative;
        z-index: 3;
    }

    /* Left Column — Dark */
    .contact-info-col {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        color: #fff;
        padding: 56px 48px;
    }
    .contact-info-heading {
        font-size: 24px;
        font-weight: 800;
        margin-bottom: 12px;
        letter-spacing: -0.01em;
    }
    .contact-info-desc {
        color: rgba(255, 255, 255, 0.55);
        font-size: 15px;
        line-height: 1.7;
        margin-bottom: 40px;
    }

    .info-item {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 32px;
    }
    .info-icon {
        width: 44px;
        height: 44px;
        background: rgba(59, 130, 246, 0.12);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .info-icon svg {
        width: 20px;
        height: 20px;
        stroke: var(--blue);
        fill: none;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }
    .info-text h4 {
        font-family: 'JetBrains Mono', monospace;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: var(--blue);
        margin-bottom: 6px;
    }
    .info-text p {
        color: rgba(255, 255, 255, 0.75);
        font-size: 15px;
        line-height: 1.6;
    }

    .contact-divider {
        width: 100%;
        height: 1px;
        background: rgba(255, 255, 255, 0.08);
        margin: 16px 0 32px;
    }

    .social-label {
        font-family: 'JetBrains Mono', monospace;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: rgba(255, 255, 255, 0.35);
        margin-bottom: 16px;
    }
    .social-links {
        display: flex;
        gap: 12px;
    }
    .social-link {
        width: 44px;
        height: 44px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
        transition: var(--transition);
        font-size: 16px;
    }
    .social-link:hover {
        border-color: var(--blue);
        color: var(--blue);
        background: rgba(59, 130, 246, 0.08);
    }

    /* Right Column — White */
    .contact-form-col {
        background: #fff;
        padding: 56px 48px;
    }
    .form-heading {
        font-size: 24px;
        font-weight: 800;
        color: var(--text-dark);
        margin-bottom: 8px;
        letter-spacing: -0.01em;
    }
    .form-desc {
        color: var(--text-gray);
        font-size: 15px;
        margin-bottom: 36px;
    }

    .form-group { margin-bottom: 24px; }
    .form-label {
        display: block;
        font-family: 'JetBrains Mono', monospace;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: var(--text-dark);
        margin-bottom: 10px;
    }
    .form-input {
        width: 100%;
        padding: 14px 16px;
        border: 1px solid #e2e2e2;
        border-radius: 0;
        font-size: 15px;
        font-family: 'Inter', sans-serif;
        color: var(--text-dark);
        background: #fafafa;
        transition: var(--transition);
        -webkit-appearance: none;
    }
    .form-input:focus {
        outline: none;
        border-color: var(--blue);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08);
    }
    .form-input::placeholder { color: #aaa; }
    textarea.form-input {
        height: 140px;
        resize: vertical;
        line-height: 1.6;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .submit-btn {
        width: 100%;
        justify-content: center;
    }

    /* ---- Bottom CTA ---- */
    .contact-cta {
        position: relative;
        background: #f8fafc;
        color: var(--text-dark);
        padding: 100px 0;
        overflow: hidden;
    }
    .contact-cta .noise-overlay { z-index: 1; opacity: 0.3; }
    .contact-cta-inner {
        position: relative;
        z-index: 2;
        text-align: center;
        max-width: 700px;
        margin: 0 auto;
    }
    .contact-cta h2 { color: var(--text-dark); margin-bottom: 20px; }
    .contact-cta p {
        color: var(--text-gray);
        font-size: clamp(1rem, 2vw, 1.125rem);
        line-height: 1.7;
        margin-bottom: 40px;
    }
    .cta-actions {
        display: flex;
        justify-content: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    /* ---- Responsive ---- */
    @media (max-width: 900px) {
        .contact-grid { grid-template-columns: 1fr; }
        .contact-info-col { padding: 40px 24px; }
        .contact-form-col { padding: 40px 24px; }
        .form-row { grid-template-columns: 1fr; }
    }
    @media (max-width: 600px) {
        .contact-hero { padding: 80px 0 80px; }
        .cta-actions { flex-direction: column; align-items: center; }
        .cta-actions .btn-sharp { width: 100%; justify-content: center; }
    }
</style>
@endsection

@section('content')

{{-- ===== HERO SECTION ===== --}}
<section class="contact-hero">
    <div class="noise-overlay"></div>
    <div class="container-full">
        <div class="contact-hero-inner">
            <div class="reveal">
                <span class="eyebrow">
                    <span class="pulse-dot"></span>
                    <span class="typewriter">Contact Us</span>
                </span>
            </div>
            <h1 class="heading-xl split-text reveal-delay-1">Get in touch.</h1>
            <p class="contact-hero-subtitle stagger-words reveal-delay-2">
                Have questions about our plans or need help setting up your brand? Our support team is available 24/7 to assist you.
            </p>
        </div>
    </div>
</section>

{{-- ===== CONTACT GRID ===== --}}
<section class="contact-section">
    <div class="container-full">
        <div class="contact-grid">

            {{-- Left Column — Contact Info --}}
            <div class="contact-info-col reveal-left">
                <h3 class="contact-info-heading">Contact Information</h3>
                <p class="contact-info-desc">Reach out through any channel — we respond within hours, not days.</p>

                <div class="info-item">
                    <div class="info-icon">
                        <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div class="info-text">
                        <h4>Visit Us</h4>
                        <p>123 Business Avenue, Tech Hub<br>Mumbai, India 400001</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </div>
                    <div class="info-text">
                        <h4>Email Us</h4>
                        <p>support@artera.app</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <div class="info-text">
                        <h4>Call Us</h4>
                        <p>+91 98765 43210</p>
                    </div>
                </div>

                <div class="contact-divider"></div>

                <div class="social-label">Follow Us</div>
                <div class="social-links">
                    <a href="#" class="social-link" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="social-link" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="social-link" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
            </div>

            {{-- Right Column — Form --}}
            <div class="contact-form-col reveal-right reveal-delay-1">
                <h3 class="form-heading">Send us a message</h3>
                <p class="form-desc">Fill out the form and we'll get back to you shortly.</p>

                <form onsubmit="event.preventDefault(); alert('Message sent successfully!');">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-input" placeholder="John Doe" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-input" placeholder="john@example.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" class="form-input" placeholder="+91 XXXX XXXXX">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Message</label>
                        <textarea class="form-input" placeholder="How can we help you?" required></textarea>
                    </div>
                    <button type="submit" class="btn-sharp btn-sharp-primary submit-btn btn-glow">
                        Send Message
                        <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </button>
                </form>
            </div>

        </div>
    </div>
</section>

{{-- ===== BOTTOM CTA ===== --}}
<section class="contact-cta">
    <div class="noise-overlay"></div>
    <div class="container-full">
        <div class="contact-cta-inner">
            <div class="reveal">
                <span class="eyebrow-plain font-mono uppercase tracking-widest" style="color: var(--blue); margin-bottom: 16px; display: inline-block;">Prefer self-service?</span>
            </div>
            <h2 class="heading-md text-shimmer reveal reveal-delay-1">Download the app<br>and get started now.</h2>
            <p class="reveal reveal-delay-2">Everything you need to create professional marketing content — festival posters, business templates, and social media designs — right from your phone.</p>
            <div class="cta-actions reveal reveal-delay-3">
                <a href="#" class="btn-sharp btn-glow" style="background: var(--primary); color: #fff; border: none;">
                    <i class="fa-brands fa-google-play"></i> Get the App
                    <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
                <a href="{{ route('landing.packages') }}" class="btn-sharp btn-sharp-outline" style="border-color: rgba(0,0,0,0.2); color: var(--text-dark);">
                    View Plans
                    <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
