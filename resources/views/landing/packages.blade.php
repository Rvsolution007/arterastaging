@extends('landing.layout')

@section('title', 'Artera - Packages')

@section('extra_css')
<style>
    /* ============================================
       PACKAGES PAGE — 8x.social Design
       ============================================ */

    /* ---- Hero Section ---- */
    .pkg-hero {
        position: relative;
        background: var(--bg-dark);
        color: #fff;
        padding: 120px 0 100px;
        overflow: hidden;
        text-align: center;
    }
    .pkg-hero-inner {
        position: relative;
        z-index: 2;
        max-width: 720px;
        margin: 0 auto;
    }
    .pkg-hero .heading-lg {
        color: #fff;
        margin-bottom: 24px;
    }
    .pkg-hero-sub {
        font-size: clamp(1rem, 2vw, 1.25rem);
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.65;
        max-width: 560px;
        margin: 0 auto;
    }

    /* ---- Pricing Section ---- */
    .pkg-section {
        padding: 100px 0 120px;
        background: var(--bg-white);
    }

    .pkg-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0;
        margin-top: 64px;
    }

    /* ---- Pricing Card ---- */
    .pkg-card {
        background: #fff;
        border: 1px solid rgba(26, 26, 26, 0.08);
        padding: 48px 36px 40px;
        position: relative;
        transition: var(--transition);
        display: flex;
        flex-direction: column;
    }
    .pkg-card:hover {
        border-color: rgba(26, 26, 26, 0.2);
    }

    /* Popular card */
    .pkg-card--popular {
        border-color: var(--blue);
        border-width: 2px;
        transform: scaleY(1.02);
        z-index: 2;
    }
    .pkg-card--popular:hover {
        border-color: var(--blue);
    }

    /* Popular badge */
    .pkg-popular-badge {
        position: absolute;
        top: 0;
        left: 36px;
        background: var(--blue);
        color: #fff;
        font-family: 'JetBrains Mono', monospace;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        padding: 8px 20px;
        line-height: 1;
    }

    /* Plan name */
    .pkg-plan-name {
        font-size: 13px;
        font-family: 'JetBrains Mono', monospace;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: var(--text-gray);
        margin-bottom: 24px;
    }
    .pkg-card--popular .pkg-plan-name {
        margin-top: 28px;
    }

    /* Price */
    .pkg-price-row {
        margin-bottom: 8px;
        display: flex;
        align-items: baseline;
        gap: 8px;
    }
    .pkg-price {
        font-size: clamp(2.5rem, 5vw, 3.5rem);
        font-weight: 900;
        color: var(--blue);
        line-height: 1;
        letter-spacing: -0.02em;
    }
    .pkg-duration {
        font-size: 15px;
        color: var(--text-gray);
        font-weight: 500;
    }
    .pkg-original-price {
        text-decoration: line-through;
        color: var(--text-muted);
        font-size: 14px;
        margin-bottom: 6px;
    }

    /* Divider */
    .pkg-divider {
        width: 100%;
        height: 1px;
        background: rgba(26, 26, 26, 0.08);
        margin: 32px 0;
    }

    /* Feature list */
    .pkg-features {
        list-style: none;
        margin: 0 0 auto 0;
        padding: 0;
    }
    .pkg-features li {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        font-size: 15px;
        color: var(--text-dark);
        margin-bottom: 16px;
        line-height: 1.5;
    }
    .pkg-features li:last-child {
        margin-bottom: 0;
    }
    .pkg-feat-icon {
        flex-shrink: 0;
        width: 20px;
        height: 20px;
        margin-top: 2px;
    }
    .pkg-feat-icon--check {
        color: var(--blue);
    }
    .pkg-feat-icon--x {
        color: var(--text-muted);
    }
    .pkg-features li.pkg-feat-disabled {
        color: var(--text-muted);
    }

    /* CTA */
    .pkg-cta {
        margin-top: 40px;
    }
    .pkg-cta .btn-sharp {
        width: 100%;
        justify-content: center;
        padding: 18px 32px;
        font-size: 14px;
    }

    /* ---- Empty State ---- */
    .pkg-empty {
        text-align: center;
        padding: 80px 24px;
        color: var(--text-gray);
        font-size: 16px;
        grid-column: 1 / -1;
    }

    /* ---- Bottom CTA ---- */
    .pkg-bottom-note {
        text-align: center;
        margin-top: 64px;
        color: var(--text-gray);
        font-size: 15px;
        line-height: 1.65;
    }
    .pkg-bottom-note a {
        color: var(--blue);
        font-weight: 700;
        text-decoration: none;
        border-bottom: 2px solid var(--blue);
        transition: var(--transition);
    }
    .pkg-bottom-note a:hover {
        color: #2563eb;
        border-color: #2563eb;
    }

    /* ---- Responsive ---- */
    @media (max-width: 1024px) {
        .pkg-grid {
            grid-template-columns: 1fr;
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
            gap: 24px;
        }
        .pkg-card--popular {
            transform: none;
        }
    }
</style>
@endsection

@section('content')

{{-- ============================================
     HERO — Dark with noise
     ============================================ --}}
<section class="pkg-hero">
    <div class="noise-overlay"></div>
    <div class="container-full">
        <div class="pkg-hero-inner">
            <div class="reveal">
                <span class="eyebrow"><span class="typewriter">PRICING</span></span>
            </div>
            <h1 class="heading-lg split-text reveal reveal-delay-1">Choose your plan.</h1>
            <p class="pkg-hero-sub stagger-words reveal reveal-delay-2">
                Scale your marketing as your business grows. No hidden fees.
            </p>
        </div>
    </div>
</section>

{{-- ============================================
     PRICING GRID
     ============================================ --}}
<section class="pkg-section">
    <div class="container-full" style="max-width: 1200px; margin: 0 auto;">

        <div class="pkg-grid">
            @if(isset($packages) && count($packages) > 0)
                @foreach($packages as $index => $plan)
                <div class="pkg-card {{ $index == 1 ? 'pkg-card--popular' : '' }} reveal-scale reveal-delay-{{ ($index % 4) + 1 }}">

                    @if($index == 1)
                        <div class="pkg-popular-badge">MOST POPULAR</div>
                    @endif

                    {{-- Plan Name --}}
                    <div class="pkg-plan-name">{{ $plan->plan_name }}</div>

                    {{-- Price --}}
                    <div class="pkg-price-row">
                        <span class="pkg-price">{{ App\Models\AppSetting::getAppSetting('currency') ?? '$' }}{{ $plan->discount_price > 0 ? $plan->discount_price : $plan->plan_price }}</span>
                        <span class="pkg-duration">/ {{ $plan->duration }} {{ $plan->duration_type }}</span>
                    </div>

                    @if($plan->discount_price > 0 && $plan->discount_price < $plan->plan_price)
                        <div class="pkg-original-price">
                            {{ App\Models\AppSetting::getAppSetting('currency') ?? '$' }}{{ $plan->plan_price }}
                        </div>
                    @endif

                    <div class="pkg-divider"></div>

                    {{-- Features --}}
                    <ul class="pkg-features stagger-list">
                        <li class="stagger-item">
                            <svg class="pkg-feat-icon pkg-feat-icon--check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            {{ $plan->business_limit }} Business Profiles
                        </li>
                        <li class="stagger-item">
                            <svg class="pkg-feat-icon pkg-feat-icon--check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            {{ $plan->festival_post_limit ?: 'Unlimited' }} Festival Posts
                        </li>
                        <li class="stagger-item">
                            <svg class="pkg-feat-icon pkg-feat-icon--check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            {{ $plan->custom_post_edit_limit ?: 'Unlimited' }} Custom Posts Edit
                        </li>
                        <li class="stagger-item">
                            <svg class="pkg-feat-icon pkg-feat-icon--check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            {{ $plan->daily_drip_limit ?: 'Unlimited' }} Daily Drip Posts
                        </li>
                        <li class="stagger-item {{ $plan->daily_drip_can_choose ? '' : 'pkg-feat-disabled' }}">
                            @if($plan->daily_drip_can_choose)
                                <svg class="pkg-feat-icon pkg-feat-icon--check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            @else
                                <svg class="pkg-feat-icon pkg-feat-icon--x" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            @endif
                            Select Daily Drip Content
                        </li>
                    </ul>

                    {{-- CTA Button --}}
                    <div class="pkg-cta">
                        @if(Auth::check())
                            <a href="{{ url('/app-gateway') }}" class="btn-sharp {{ $index == 1 ? 'btn-sharp-primary btn-glow' : 'btn-sharp-outline' }}">
                                Open in App
                                <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                        @else
                            <a href="{{ url('/auth-gate') }}" class="btn-sharp {{ $index == 1 ? 'btn-sharp-primary btn-glow' : 'btn-sharp-outline' }}">
                                Subscribe Now
                                <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                        @endif
                    </div>

                </div>
                @endforeach
            @else
                <div class="pkg-empty">
                    <p>No subscription plans available at the moment.</p>
                </div>
            @endif
        </div>

        {{-- Bottom Note --}}
        <div class="pkg-bottom-note reveal-blur">
            <p>Ready to get started? <a href="{{ url('/register') }}">Register your business</a> on the web, then download the app to begin.</p>
        </div>

    </div>
</section>

@endsection
