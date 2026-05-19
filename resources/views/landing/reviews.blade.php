@extends('landing.layout')

@section('title', 'Artera - Reviews')

@section('extra_css')
<style>
    .reviews-wrapper { display: flex; gap: 30px; overflow-x: auto; padding: 20px 0; scroll-snap-type: x mandatory; scrollbar-width: none; }
    .reviews-wrapper::-webkit-scrollbar { display: none; }

    .review-card { min-width: 350px; background: white; padding: 30px; border-radius: 16px; box-shadow: var(--shadow-sm); scroll-snap-align: center; border: 1px solid #e2e8f0; }
    .review-stars { color: #F59E0B; margin-bottom: 15px; }
    .review-text { color: var(--text-gray); font-style: italic; margin-bottom: 20px; line-height: 1.6; }

    .reviewer-info { display: flex; align-items: center; gap: 15px; }
    .reviewer-img { width: 50px; height: 50px; border-radius: 50%; background: #cbd5e1; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 20px; }
    .reviewer-details h4 { font-size: 16px; color: var(--text-dark); margin-bottom: 2px; }
    .reviewer-details p { font-size: 13px; color: var(--text-gray); }
</style>
@endsection

@section('content')
<section class="section section-alt" style="min-height: calc(100vh - 80px);">
    <div class="container">
        <div class="text-center" data-aos="fade-up">
            <div style="color: var(--primary-light); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-size: 14px; margin-bottom: 10px;">Testimonials</div>
            <h2 class="section-title">What Our <span class="text-gradient">Clients Say</span></h2>
            <p class="section-desc">Join thousands of business owners who have transformed their digital presence.</p>
        </div>

        <div class="reviews-wrapper" data-aos="fade-up" data-aos-delay="200">
            <!-- Review 1 -->
            <div class="review-card">
                <div class="review-stars">
                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                </div>
                <p class="review-text">"The daily automation feature is a lifesaver. I used to spend hours figuring out what to put on my WhatsApp status for my boutique. Now Artera does it for me every morning."</p>
                <div class="reviewer-info">
                    <div class="reviewer-img" style="background-color: #EC4899;">P</div>
                    <div class="reviewer-details">
                        <h4>Priya Sharma</h4>
                        <p>Fashion Boutique Owner</p>
                    </div>
                </div>
            </div>

            <!-- Review 2 -->
            <div class="review-card">
                <div class="review-stars">
                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star-half-stroke"></i>
                </div>
                <p class="review-text">"The AI Magic Cloner is mind-blowing. I upload an ad I like from Instagram, and the app recreates my electronics products in the exact same stylish layout. Unbelievable!"</p>
                <div class="reviewer-info">
                    <div class="reviewer-img" style="background-color: #3B82F6;">R</div>
                    <div class="reviewer-details">
                        <h4>Rahul Desai</h4>
                        <p>Electronics Retailer</p>
                    </div>
                </div>
            </div>

            <!-- Review 3 -->
            <div class="review-card">
                <div class="review-stars">
                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                </div>
                <p class="review-text">"Since I started using the festival templates with my custom frame, my customer engagement has doubled. It looks like I have a professional agency handling my branding."</p>
                <div class="reviewer-info">
                    <div class="reviewer-img" style="background-color: #10B981;">A</div>
                    <div class="reviewer-details">
                        <h4>Amit Patel</h4>
                        <p>Real Estate Agent</p>
                    </div>
                </div>
            </div>
            
            <!-- Review 4 -->
            <div class="review-card">
                <div class="review-stars">
                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                </div>
                <p class="review-text">"Uploading my PDF brochure and watching the AI extract all my products into a digital catalog was magic. So easy to use."</p>
                <div class="reviewer-info">
                    <div class="reviewer-img" style="background-color: #F59E0B;">S</div>
                    <div class="reviewer-details">
                        <h4>Sunita Verma</h4>
                        <p>Furniture Store</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
