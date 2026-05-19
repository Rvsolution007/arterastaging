@extends('landing.layout')

@section('title', 'Artera - Contact Us')

@section('extra_css')
<style>
    .contact-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 50px; background: white; border-radius: 20px; overflow: hidden; box-shadow: var(--shadow-lg); margin-top: 50px; }
    
    .contact-info { background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: white; padding: 50px; }
    .contact-info h3 { font-size: 28px; margin-bottom: 20px; }
    .contact-info p { opacity: 0.9; margin-bottom: 40px; line-height: 1.6; }

    .info-item { display: flex; align-items: flex-start; gap: 15px; margin-bottom: 30px; }
    .info-item i { font-size: 20px; margin-top: 4px; }
    .info-item h4 { font-size: 18px; margin-bottom: 5px; }
    .info-item p { margin-bottom: 0; font-size: 15px; }
    
    .social-icons-contact { display: flex; gap: 15px; margin-top: 40px; }
    .social-icons-contact a { width: 40px; height: 40px; background: rgba(255,255,255,0.2); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: var(--transition); }
    .social-icons-contact a:hover { background: white; color: var(--primary); transform: translateY(-3px); }

    .contact-form { padding: 50px; }
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; margin-bottom: 8px; color: var(--text-dark); font-weight: 500; }
    
    .form-control { width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 16px; font-family: 'Inter', sans-serif; transition: var(--transition); }
    .form-control:focus { outline: none; border-color: var(--primary-light); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
    textarea.form-control { height: 120px; resize: vertical; }

    .submit-btn { width: 100%; padding: 14px; border: none; border-radius: 8px; background: var(--primary); color: white; font-size: 16px; font-weight: 600; cursor: pointer; transition: var(--transition); }
    .submit-btn:hover { background: var(--primary-light); }

    @media (max-width: 768px) {
        .contact-grid { grid-template-columns: 1fr; text-align: center; }
        .contact-form { padding: 30px 20px; }
        .info-item { text-align: left; }
    }
</style>
@endsection

@section('content')
<section class="section">
    <div class="container">
        <div class="text-center" data-aos="fade-up">
            <h2 class="section-title">Get in <span class="text-gradient">Touch</span></h2>
            <p class="section-desc">Have questions about our plans or need help setting up your brand?</p>
        </div>

        <div class="contact-grid" data-aos="fade-up" data-aos-delay="100">
            <div class="contact-info">
                <h3>Contact Information</h3>
                <p>Our support team is available 24/7 to assist you with any inquiries.</p>
                
                <div class="info-item">
                    <i class="fa-solid fa-location-dot"></i>
                    <div>
                        <h4>Visit Us</h4>
                        <p>123 Business Avenue, Tech Hub<br>Mumbai, India 400001</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fa-solid fa-envelope"></i>
                    <div>
                        <h4>Email Us</h4>
                        <p>support@artera.app</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fa-solid fa-phone"></i>
                    <div>
                        <h4>Call Us</h4>
                        <p>+91 98765 43210</p>
                    </div>
                </div>
                
                <div class="social-icons-contact">
                    <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#"><i class="fa-brands fa-twitter"></i></a>
                </div>
            </div>
            
            <div class="contact-form">
                <h3 style="font-size: 24px; margin-bottom: 30px; color: var(--text-dark);">Send us a Message</h3>
                <form onsubmit="event.preventDefault(); alert('Message sent successfully!');">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" placeholder="John Doe" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" placeholder="john@example.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" placeholder="+91 XXXX XXXXX">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" placeholder="How can we help you?" required></textarea>
                    </div>
                    <button type="submit" class="submit-btn">Send Message</button>
                </form>
            </div>
            
        </div>
    </div>
</section>
@endsection
