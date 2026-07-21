@extends('landing.layout')

@section('title', $seo['title'])

@section('seo')
    @include('components.seo-head', ['seo' => $seo])
@endsection

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
<!-- Hero Header -->
<section class="section" style="padding-bottom: 40px;">
    <div class="container">
        <div class="text-center" data-aos="fade-up" style="margin-bottom: 60px;">
            <div style="color: var(--primary-light); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-size: 14px; margin-bottom: 10px;">About Artera</div>
            <h1 class="section-title">Who <span class="text-gradient">We Are</span></h1>
            <p class="section-desc">We are revolutionizing digital marketing for small and medium businesses through the power of Artificial Intelligence.</p>
        </div>
        
        <!-- About Company Section -->
        <div class="about-content" style="margin-bottom: 100px;">
            <div class="about-image" data-aos="fade-right">
                <img src="{{ asset('landing/images/company.png') }}" alt="Artera Company Overview" onerror="this.src='https://placehold.co/600x500/E0E7FF/1E3A8A?text=About+Company'">
            </div>
            <div class="about-text" data-aos="fade-left">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <i class="fa-solid fa-building" style="font-size: 24px; color: var(--primary-light);"></i>
                    <h2 style="font-size: 28px; color: var(--primary); margin: 0;">Our Company</h2>
                </div>
                <p><strong>Artera is a forward-thinking SaaS platform built specifically for the modern entrepreneur.</strong></p>
                <p>Founded on the belief that premium branding should not be restricted by budget, we have developed a comprehensive suite of AI-powered tools that automate the heavy lifting of digital marketing. From generating festival greetings to creating stunning custom posts, Artera serves as your 24/7 in-house marketing agency.</p>
                <p>Based in the cloud and accessible via a seamless mobile application, our platform serves thousands of businesses across 50+ categories, ensuring they remain relevant, consistent, and visually stunning in today's fast-paced digital world.</p>
            </div>
        </div>

        <!-- Mission Section -->
        <div class="about-content" style="direction: rtl;">
            <div class="about-image" data-aos="fade-left" style="direction: ltr;">
                <img src="{{ asset('landing/images/mission.png') }}" alt="Artera Mission - Empowering Businesses" onerror="this.src='https://placehold.co/600x500/F0F7FF/1E3A8A?text=Our+Mission'">
            </div>
            <div class="about-text" data-aos="fade-right" style="direction: ltr;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <i class="fa-solid fa-bullseye" style="font-size: 24px; color: var(--primary-light);"></i>
                    <h2 style="font-size: 28px; color: var(--primary); margin: 0;">Our Mission</h2>
                </div>
                <p><strong>To democratize professional digital marketing by making it accessible, automated, and affordable for every business owner worldwide.</strong></p>
                <p>We understand that running a business is hard work. You shouldn't have to be a graphic design expert or hire an expensive marketing agency just to maintain a professional social media presence. Artera bridges this gap by merging cutting-edge AI technology with intuitive, mobile-first design tools.</p>
                
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

<!-- Vision Section -->
<section class="section section-alt">
    <div class="container">
        <div class="about-content">
            <div class="about-image" data-aos="fade-right">
                <img src="{{ asset('landing/images/vision.png') }}" alt="Artera Vision - Future of AI Marketing" onerror="this.src='https://placehold.co/600x500/1E3A8A/FFFFFF?text=Our+Vision'">
            </div>
            <div class="about-text" data-aos="fade-left">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <i class="fa-regular fa-eye" style="font-size: 24px; color: var(--primary-light);"></i>
                    <h2 style="font-size: 28px; color: var(--primary); margin: 0;">Our Vision</h2>
                </div>
                <p><strong>To be the global standard for AI-driven business branding and automated social media growth.</strong></p>
                <p>We envision a future where every entrepreneur, from local shop owners to growing startups, has an intelligent marketing assistant in their pocket. By continuously innovating our Daily Drip engine and smart templates, we aim to eliminate the friction between having a great product and effectively marketing it to the world.</p>
                <ul style="list-style: none; margin-top: 20px; padding: 0;">
                    <li style="margin-bottom: 10px;"><i class="fa-solid fa-check" style="color: #10B981; margin-right: 10px;"></i> Pioneering Generative AI for local businesses</li>
                    <li style="margin-bottom: 10px;"><i class="fa-solid fa-check" style="color: #10B981; margin-right: 10px;"></i> Building a seamless ecosystem of web and mobile tools</li>
                    <li style="margin-bottom: 10px;"><i class="fa-solid fa-check" style="color: #10B981; margin-right: 10px;"></i> Fostering a community of growing entrepreneurs</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Core Values Section -->
<section class="section">
    <div class="container">
        <div class="text-center" data-aos="fade-up" style="margin-bottom: 50px;">
            <h2 class="section-title">Our <span class="text-gradient">Core Values</span></h2>
            <p class="section-desc">The principles that guide our product development and customer support.</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
            
            <div style="background: white; padding: 40px 30px; border-radius: 16px; box-shadow: var(--shadow-md); text-align: center; border-bottom: 4px solid var(--primary-light);" data-aos="fade-up" data-aos-delay="100">
                <i class="fa-solid fa-lightbulb" style="font-size: 40px; color: var(--primary-light); margin-bottom: 20px;"></i>
                <h3 style="font-size: 20px; color: var(--text-dark); margin-bottom: 15px;">Innovation First</h3>
                <p style="color: var(--text-gray); font-size: 15px; line-height: 1.6;">We constantly push the boundaries of AI technology to bring enterprise-level marketing tools to small businesses.</p>
            </div>

            <div style="background: white; padding: 40px 30px; border-radius: 16px; box-shadow: var(--shadow-md); text-align: center; border-bottom: 4px solid #10B981;" data-aos="fade-up" data-aos-delay="200">
                <i class="fa-solid fa-handshake-angle" style="font-size: 40px; color: #10B981; margin-bottom: 20px;"></i>
                <h3 style="font-size: 20px; color: var(--text-dark); margin-bottom: 15px;">Customer Centric</h3>
                <p style="color: var(--text-gray); font-size: 15px; line-height: 1.6;">Your growth is our success. We design every feature, from the Custom Post Studio to the Daily Drip, with your ease of use in mind.</p>
            </div>

            <div style="background: white; padding: 40px 30px; border-radius: 16px; box-shadow: var(--shadow-md); text-align: center; border-bottom: 4px solid #F59E0B;" data-aos="fade-up" data-aos-delay="300">
                <i class="fa-solid fa-bolt" style="font-size: 40px; color: #F59E0B; margin-bottom: 20px;"></i>
                <h3 style="font-size: 20px; color: var(--text-dark); margin-bottom: 15px;">Simplicity & Speed</h3>
                <p style="color: var(--text-gray); font-size: 15px; line-height: 1.6;">Marketing shouldn't take all day. We prioritize fast, automated, and simple solutions that save you time.</p>
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
