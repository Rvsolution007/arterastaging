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
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
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

    /* ---- Pricing Grid (Modern) ---- */
    .pkg-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 32px;
        margin-top: 64px;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
    }

    .pricing-card {
        background: #fff;
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.04);
        border: 1px solid rgba(0,0,0,0.05);
        transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.4s ease;
        display: flex;
        flex-direction: column;
        position: relative;
        overflow: hidden;
    }
    .pricing-card:hover {
        transform: translateY(-12px);
        box-shadow: 0 20px 50px rgba(99, 102, 241, 0.15);
        border-color: rgba(99, 102, 241, 0.3);
    }
    .pricing-card.popular {
        border: 2px solid #6366f1;
        box-shadow: 0 20px 50px rgba(99, 102, 241, 0.2);
    }
    .pricing-badge {
        position: absolute;
        top: 16px;
        right: 16px;
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        padding: 6px 16px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .plan-name {
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 12px;
    }
    .plan-price-wrap {
        margin-bottom: 24px;
        display: flex;
        align-items: baseline;
        gap: 8px;
        flex-wrap: wrap;
    }
    .plan-price {
        font-size: clamp(32px, 5vw, 48px);
        font-weight: 900;
        color: #0f172a;
        line-height: 1;
        word-break: break-word;
    }
    .plan-currency {
        font-size: clamp(16px, 3vw, 24px);
        font-weight: 700;
        color: #64748b;
    }
    .plan-duration {
        font-size: 16px;
        color: #64748b;
        font-weight: 500;
    }
    .plan-discount {
        font-size: 16px;
        color: #ef4444;
        text-decoration: line-through;
        font-weight: 600;
        margin-left: 8px;
    }
    .plan-features {
        list-style: none;
        padding: 0;
        margin: 0 0 32px 0;
        flex-grow: 1;
    }
    .plan-features li {
        font-size: 16px;
        color: #475569;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .plan-features li i.fa-check-circle {
        color: #10b981;
        font-size: 18px;
    }
    .plan-features li i.fa-times-circle {
        color: #ef4444;
        font-size: 18px;
    }
    .plan-features li span.missing-feature {
        color: #94a3b8;
        text-decoration: line-through;
    }
    .plan-btn {
        display: block;
        width: 100%;
        text-align: center;
        padding: 16px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 16px;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    .pricing-card.popular .plan-btn {
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: #fff;
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
    }
    .pricing-card.popular .plan-btn:hover {
        box-shadow: 0 6px 20px rgba(99, 102, 241, 0.6);
        transform: scale(1.02);
    }
    .pricing-card:not(.popular) .plan-btn {
        background: #f1f5f9;
        color: #0f172a;
    }
    .pricing-card:not(.popular) .plan-btn:hover {
        background: #e2e8f0;
        transform: scale(1.02);
    }

    /* ---- Empty State ---- */
    .pkg-empty {
        text-align: center;
        padding: 80px 24px;
        color: var(--text-gray);
        font-size: 16px;
        grid-column: 1 / -1;
    }

    /* ---- Bottom Note ---- */
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
    
    /* Toggle Styles */
    .pricing-toggle-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        margin-bottom: 40px;
    }
    .toggle-label {
        font-size: 16px;
        font-weight: 600;
        color: #64748b;
        transition: color 0.3s;
        cursor: pointer;
    }
    .toggle-label.active {
        color: #0f172a;
    }
    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }
    .switch input { 
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cbd5e1;
        transition: .4s;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    input:checked + .slider {
        background-color: #6366f1;
    }
    input:checked + .slider:before {
        transform: translateX(26px);
    }
    .slider.round {
        border-radius: 34px;
    }
    .badge-save {
        background-color: #10b981;
        color: white;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 12px;
        margin-left: 8px;
        vertical-align: middle;
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

        <div class="pricing-toggle-wrap reveal">
            <span class="toggle-label" id="label-monthly" onclick="document.getElementById('billing-toggle').click()">Monthly</span>
            <label class="switch">
                <input type="checkbox" id="billing-toggle" checked>
                <span class="slider round"></span>
            </label>
            <span class="toggle-label active" id="label-yearly" onclick="document.getElementById('billing-toggle').click()">Yearly <span class="badge-save">Save 20%</span></span>
        </div>

        <div class="pkg-grid">
            @if(isset($packages) && count($packages) > 0)
                @foreach($packages as $index => $plan)
                @php
                    $isPopular = $plan->plan_name == 'Basic' || $plan->yearly_price > 0 && $plan->yearly_price < 2000;
                    $monthlyFinal = $plan->monthly_discount_price > 0 ? $plan->monthly_discount_price : $plan->monthly_price;
                    $yearlyFinal = $plan->yearly_discount_price > 0 ? $plan->yearly_discount_price : $plan->yearly_price;
                    if ($yearlyFinal == 0 && $plan->plan_price > 0) {
                        $yearlyFinal = $plan->discount_price > 0 ? $plan->discount_price : $plan->plan_price;
                    }
                    $isFree = $monthlyFinal == 0 && $yearlyFinal == 0 && $plan->plan_price == 0;
                @endphp
                <div class="pricing-card {{ $isPopular ? 'popular' : '' }} reveal-scale reveal-delay-{{ ($index % 4) + 1 }}" data-is-free="{{ $isFree ? 'true' : 'false' }}">

                    @if($index == 1)
                        <div class="pricing-badge">Most Popular</div>
                    @endif

                    <h3 class="plan-name">{{ $plan->plan_name }}</h3>

                    @if($isFree)
                        <div class="plan-price-wrap">
                            <span class="plan-currency">{{ App\Models\AppSetting::getAppSetting('currency') ?? '₹' }}</span>
                            <span class="plan-price">0</span>
                        </div>
                    @else
                        <!-- Monthly Price -->
                        <div class="plan-price-wrap price-monthly" style="display: none;">
                            <span class="plan-currency">{{ App\Models\AppSetting::getAppSetting('currency') ?? '₹' }}</span>
                            <span class="plan-price">{{ round($monthlyFinal) }}</span>
                            @if($plan->monthly_discount_price > 0 && $plan->monthly_price > $plan->monthly_discount_price)
                                <span class="plan-discount">{{ App\Models\AppSetting::getAppSetting('currency') ?? '₹' }}{{ round($plan->monthly_price) }}</span>
                            @endif
                            @if($monthlyFinal > 0)
                                <span class="plan-duration">/ month</span>
                            @endif
                        </div>
                        <!-- Yearly Price -->
                        <div class="plan-price-wrap price-yearly">
                            <span class="plan-currency">{{ App\Models\AppSetting::getAppSetting('currency') ?? '₹' }}</span>
                            <span class="plan-price">{{ round($yearlyFinal) }}</span>
                            @if($plan->yearly_discount_price > 0 && $plan->yearly_price > $plan->yearly_discount_price)
                                <span class="plan-discount">{{ App\Models\AppSetting::getAppSetting('currency') ?? '₹' }}{{ round($plan->yearly_price) }}</span>
                            @endif
                            @if($yearlyFinal > 0)
                                <span class="plan-duration">/ year</span>
                            @endif
                        </div>
                    @endif

                    @php
                        $planDetails = $plan->plan_detail ? @unserialize($plan->plan_detail) : [];
                        if (!is_array($planDetails)) $planDetails = [];
                    @endphp

                    <ul class="plan-features">
                        @if(count($planDetails) > 0)
                            @foreach($planDetails as $detail)
                                @if(trim($detail) != '')
                                @php
                                    $detailText = trim($detail);
                                    $isMissing = false;
                                    if (\Illuminate\Support\Str::startsWith(strtolower($detailText), ['x ', 'no: ', '- ', '! '])) {
                                        $isMissing = true;
                                        $detailText = trim(preg_replace('/^(x |no: |- |! )/i', '', $detailText));
                                    }
                                @endphp
                                <li>
                                    @if($isMissing)
                                        <i class="fas fa-times-circle"></i>
                                        <span class="missing-feature">{{ $detailText }}</span>
                                    @else
                                        <i class="fas fa-check-circle"></i>
                                        {{ $detailText }}
                                    @endif
                                </li>
                                @endif
                            @endforeach
                        @else
                            <li>
                                <i class="fas fa-check-circle"></i>
                                {{ $plan->business_limit }} Business Profiles
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                {{ $plan->festival_post_limit ?: 'Unlimited' }} Festival Posts
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                {{ $plan->custom_post_edit_limit ?: 'Unlimited' }} Custom Posts Edit
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                {{ $plan->daily_drip_limit ?: 'Unlimited' }} Daily Drip Posts
                            </li>
                        @endif
                    </ul>

                    @if(Auth::check())
                        <a href="{{ url('/app-gateway') }}" class="plan-btn">Open in App</a>
                    @else
                        <a href="{{ url('/auth-gate') }}" class="plan-btn">Subscribe Now</a>
                    @endif

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
            <p>Ready to get started? <a href="{{ route('business.registration') }}">Register your business</a> on the web, then download the app to begin.</p>
        </div>

    </div>
</section>

@endsection

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const toggle = document.getElementById('billing-toggle');
        const cards = document.querySelectorAll('.pricing-card');
        
        function updatePricingDisplay() {
            const isYearly = toggle.checked;
            document.getElementById('label-yearly').classList.toggle('active', isYearly);
            document.getElementById('label-monthly').classList.toggle('active', !isYearly);
            
            cards.forEach(card => {
                const isFree = card.getAttribute('data-is-free') === 'true';
                
                if (!isFree) {
                    const monthlyEl = card.querySelector('.price-monthly');
                    const yearlyEl = card.querySelector('.price-yearly');
                    if (monthlyEl && yearlyEl) {
                        monthlyEl.style.display = isYearly ? 'none' : 'flex';
                        yearlyEl.style.display = isYearly ? 'flex' : 'none';
                    }
                }
            });
        }
        
        if(toggle) {
            toggle.addEventListener('change', updatePricingDisplay);
            updatePricingDisplay();
        }
    });
</script>
