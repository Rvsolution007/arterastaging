@extends('landing.layout')

@section('title', 'Artera - Packages')

@section('extra_css')
<style>
    .pricing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-top: 50px; }
    
    .pricing-card {
        background: white; border-radius: 20px; padding: 40px 30px; box-shadow: var(--shadow-md);
        text-align: center; transition: var(--transition); position: relative; border: 1px solid #e2e8f0;
    }
    .pricing-card:hover { transform: translateY(-10px); box-shadow: var(--shadow-lg); border-color: var(--primary-light); }
    
    .pricing-header h3 { font-size: 24px; color: var(--text-dark); margin-bottom: 10px; }
    .pricing-price { font-size: 48px; font-weight: 800; color: var(--primary); margin-bottom: 5px; }
    .pricing-price span { font-size: 16px; color: var(--text-gray); font-weight: 400; }
    
    .pricing-features { list-style: none; margin: 30px 0; text-align: left; }
    .pricing-features li { margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-size: 15px; color: var(--text-dark); }
    .pricing-features li i.fa-check { color: #10B981; }
    .pricing-features li i.fa-times { color: #EF4444; }
    
    .pricing-btn { width: 100%; display: block; padding: 14px; border-radius: 8px; font-weight: 600; text-decoration: none; transition: var(--transition); text-align: center; }
    .btn-popular { background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: white; }
    .btn-popular:hover { box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4); transform: translateY(-2px); }
    .btn-regular { background: white; color: var(--primary); border: 2px solid var(--primary); }
    .btn-regular:hover { background: var(--primary); color: white; }

    .popular-badge {
        position: absolute; top: -15px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #F59E0B, #EA580C);
        color: white; padding: 6px 15px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
    }
    
    .pricing-card.popular { border: 2px solid var(--primary-light); transform: scale(1.05); }
    .pricing-card.popular:hover { transform: scale(1.05) translateY(-5px); }

    @media (max-width: 768px) {
        .pricing-card.popular { transform: scale(1); }
        .pricing-card.popular:hover { transform: translateY(-5px); }
    }
</style>
@endsection

@section('content')
<section class="section">
    <div class="container">
        <div class="text-center" data-aos="fade-up">
            <div style="color: var(--primary-light); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-size: 14px; margin-bottom: 10px;">Pricing Plans</div>
            <h2 class="section-title">Choose the Right <span class="text-gradient">Plan</span></h2>
            <p class="section-desc">Scale your marketing effort as your business grows. No hidden fees.</p>
        </div>

        <div class="pricing-grid">
            @if(isset($packages) && count($packages) > 0)
                @foreach($packages as $index => $plan)
                <div class="pricing-card {{ $index == 1 ? 'popular' : '' }}" data-aos="fade-up" data-aos-delay="{{ $index * 100 }}">
                    @if($index == 1)
                        <div class="popular-badge">Most Popular</div>
                    @endif
                    
                    <div class="pricing-header">
                        <h3>{{ $plan->plan_name }}</h3>
                        <div class="pricing-price">
                            {{ App\Models\AppSetting::getAppSetting('currency') ?? '$' }}{{ $plan->discount_price > 0 ? $plan->discount_price : $plan->plan_price }}
                            <span>/ {{ $plan->duration }} {{ $plan->duration_type }}</span>
                        </div>
                        @if($plan->discount_price > 0 && $plan->discount_price < $plan->plan_price)
                            <div style="text-decoration: line-through; color: var(--text-gray); font-size: 14px;">
                                {{ App\Models\AppSetting::getAppSetting('currency') ?? '$' }}{{ $plan->plan_price }}
                            </div>
                        @endif
                    </div>

                    <ul class="pricing-features">
                        <li><i class="fa-solid fa-check"></i> {{ $plan->business_limit }} Business Profiles</li>
                        <li><i class="fa-solid fa-check"></i> {{ $plan->festival_post_limit ?: 'Unlimited' }} Festival Posts</li>
                        <li><i class="fa-solid fa-check"></i> {{ $plan->custom_post_edit_limit ?: 'Unlimited' }} Custom Posts Edit</li>
                        <li><i class="fa-solid fa-check"></i> {{ $plan->daily_drip_limit ?: 'Unlimited' }} Daily Drip Posts</li>

                        <li>
                            @if($plan->daily_drip_can_choose)
                                <i class="fa-solid fa-check"></i> Select Daily Drip Content
                            @else
                                <i class="fa-solid fa-times"></i> Select Daily Drip Content
                            @endif
                        </li>
                    </ul>

                    <!-- Redirect to Auth Gate -->
                    @if(Auth::check())
                        <a href="{{ url('/app-gateway') }}" class="pricing-btn {{ $index == 1 ? 'btn-popular' : 'btn-regular' }}">
                            Open in App
                        </a>
                    @else
                        <a href="{{ url('/auth-gate') }}" class="pricing-btn {{ $index == 1 ? 'btn-popular' : 'btn-regular' }}">
                            Subscribe Now
                        </a>
                    @endif
                </div>
                @endforeach
            @else
                <div class="col-12 text-center w-100">
                    <p>No subscription plans available at the moment.</p>
                </div>
            @endif
        </div>
        
        <div class="text-center mt-5" style="margin-top: 40px; color: var(--text-gray); font-size: 14px;">
            <p>Ready to get started? <a href="{{ url('/register') }}" style="color: var(--primary-light); font-weight: 600;">Register your business</a> on the web, then download the app to begin.</p>
        </div>
    </div>
</section>
@endsection
