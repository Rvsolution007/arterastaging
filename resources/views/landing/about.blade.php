@extends('landing.layout')

@section('title', 'Artera - About Us')

@section('extra_css')
<style>
    .about-content { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center; }
    .about-image img { width: 100%; border-radius: 20px; box-shadow: var(--shadow-md); }
    .about-text h3 { font-size: 24px; color: var(--primary); margin-bottom: 15px; }
    .about-text p { color: var(--text-gray); line-height: 1.7; margin-bottom: 20px; font-size: 16px; }

    .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px; }
    .stat-item { background: white; padding: 20px; border-radius: 12px; box-shadow: var(--shadow-sm); text-align: center; border-bottom: 3px solid var(--primary-light); transition: var(--transition); }
    .stat-item:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); }
    .stat-number { font-size: 32px; font-weight: 800; color: var(--primary); margin-bottom: 5px; }
    .stat-label { color: var(--text-gray); font-weight: 500; font-size: 14px; }
    
    @media (max-width: 768px) {
        .about-content { grid-template-columns: 1fr; text-align: center; }
        .about-image { order: -1; margin-bottom: 30px; }
        .stats-grid { text-align: left; }
    }
</style>
@endsection

@section('content')
<section class="section">
    <div class="container">
        <div class="text-center" data-aos="fade-up">
            <h2 class="section-title">Who <span class="text-gradient">We Are</span></h2>
            <p class="section-desc">Our mission is to empower local businesses.</p>
        </div>
        <div class="about-content">
            <div class="about-image" data-aos="fade-right">
                <img src="{{ asset('landing/images/about-illustration.png') }}" alt="About Artera" onerror="this.src='https://placehold.co/600x500/F0F7FF/1E3A8A?text=Team+Illustration'">
            </div>
            <div class="about-text" data-aos="fade-left">
                <div style="color: var(--primary-light); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-size: 14px; margin-bottom: 10px;">Our Mission</div>
                <h3>Empowering Businesses with Smart Marketing</h3>
                <p>Artera was founded with a single mission: to make professional digital marketing accessible to every small and medium business owner. We merge cutting-edge Artificial Intelligence with intuitive design tools.</p>
                <p>With our robust platform spanning a Cloud backend and a seamless mobile experience, we help you maintain a consistent, beautiful brand identity across WhatsApp, Instagram, and Facebook without hiring an expensive design agency.</p>
                
                <div class="stats-grid" id="stats">
                    <div class="stat-item">
                        <div class="stat-number" data-target="10000">0</div>
                        <div class="stat-label">Active Users</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" data-target="5000">0</div>
                        <div class="stat-label">Premium Templates</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" data-target="50">0</div>
                        <div class="stat-label">Business Categories</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" data-target="99">0</div>
                        <div class="stat-label">Satisfaction %</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('extra_js')
<script>
    const counters = document.querySelectorAll('.stat-number');
    let hasAnimated = false;

    window.addEventListener('scroll', () => {
        const statsSection = document.getElementById('stats');
        if (!statsSection) return;
        
        const statsPos = statsSection.getBoundingClientRect().top;
        const screenPos = window.innerHeight;

        if (statsPos < screenPos && !hasAnimated) {
            hasAnimated = true;
            counters.forEach(counter => {
                const target = +counter.getAttribute('data-target');
                const duration = 2000;
                const increment = target / (duration / 16);
                let current = 0;
                const updateCounter = () => {
                    current += increment;
                    if (current < target) {
                        counter.innerText = Math.ceil(current).toLocaleString();
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.innerText = target.toLocaleString() + '+';
                    }
                };
                updateCounter();
            });
        }
    });
</script>
@endsection
