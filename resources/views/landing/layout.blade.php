<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'BrandKit - AI-Powered Digital Marketing')</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #1E3A8A; /* Deep Blue */
            --primary-light: #3B82F6; /* Bright Blue */
            --accent: #60A5FA; /* Sky Blue */
            --bg-white: #FFFFFF;
            --bg-alt: #F0F7FF; /* Ice Blue */
            --text-dark: #0F172A;
            --text-gray: #64748B;
            --transition: all 0.3s ease-in-out;
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        html { scroll-behavior: smooth; }
        body { color: var(--text-dark); overflow-x: hidden; background-color: var(--bg-white); }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
        .section { padding: 100px 0; }
        .section-alt { background-color: var(--bg-alt); }
        .text-center { text-align: center; }
        .text-primary { color: var(--primary); }
        .text-gradient { background: linear-gradient(135deg, var(--primary), var(--primary-light)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .section-title { font-size: 36px; font-weight: 700; margin-bottom: 16px; }
        .section-desc { color: var(--text-gray); font-size: 18px; max-width: 600px; margin: 0 auto 50px; line-height: 1.6; }

        /* Buttons */
        .btn { display: inline-block; padding: 12px 28px; border-radius: 50px; font-weight: 600; text-decoration: none; transition: var(--transition); cursor: pointer; border: none; font-size: 16px; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: white; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.39); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5); }
        .btn-outline { background: transparent; color: var(--primary); border: 2px solid var(--primary); }
        .btn-outline:hover { background: var(--primary); color: white; }

        /* Navbar */
        .navbar { position: fixed; top: 0; width: 100%; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); z-index: 1000; padding: 15px 0; box-shadow: var(--shadow-sm); transition: var(--transition); }
        .nav-container { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 24px; font-weight: 800; color: var(--primary); text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .logo i { color: var(--primary-light); }
        .nav-links { display: flex; gap: 30px; list-style: none; align-items: center; }
        .nav-links a { text-decoration: none; color: var(--text-dark); font-weight: 500; transition: var(--transition); position: relative; }
        .nav-links a::after { content: ''; position: absolute; width: 0; height: 2px; bottom: -5px; left: 0; background-color: var(--primary-light); transition: var(--transition); }
        .nav-links a:hover::after, .nav-links a.active::after { width: 100%; }
        .nav-links a:hover, .nav-links a.active { color: var(--primary-light); }
        .mobile-menu-btn { display: none; font-size: 24px; color: var(--text-dark); cursor: pointer; }

        @media (max-width: 768px) {
            .nav-links, .nav-action { display: none; }
            .mobile-menu-btn { display: block; }
            .nav-links.active { display: flex; flex-direction: column; position: absolute; top: 70px; left: 0; width: 100%; background: white; padding: 20px; box-shadow: 0 10px 10px rgba(0,0,0,0.1); }
        }

        /* Footer */
        .footer { background: var(--text-dark); color: white; padding: 80px 0 30px; margin-top: 50px; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1.5fr; gap: 40px; margin-bottom: 50px; }
        .footer-logo { font-size: 28px; font-weight: 800; color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .footer-logo i { color: var(--primary-light); }
        .footer-desc { color: #94a3b8; line-height: 1.6; margin-bottom: 20px; max-width: 300px; }
        .footer-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; position: relative; padding-bottom: 10px; }
        .footer-title::after { content: ''; position: absolute; bottom: 0; left: 0; width: 40px; height: 2px; background: var(--primary-light); }
        .footer-links { list-style: none; }
        .footer-links li { margin-bottom: 12px; }
        .footer-links a { color: #94a3b8; text-decoration: none; transition: var(--transition); }
        .footer-links a:hover { color: white; padding-left: 5px; }
        .footer-bottom { padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; color: #94a3b8; font-size: 14px; }
        
        @media (max-width: 1024px) {
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) {
            .footer-grid { grid-template-columns: 1fr; text-align: center; }
            .footer-desc { margin: 0 auto 20px; }
            .footer-title::after { left: 50%; transform: translateX(-50%); }
            .footer-bottom { flex-direction: column; gap: 15px; }
        }
    </style>
    @yield('extra_css')
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="container nav-container">
            <a href="{{ route('landing.home') }}" class="logo"><i class="fa-solid fa-layer-group"></i> BrandKit</a>
            
            <ul class="nav-links" id="navLinks">
                <li><a href="{{ route('landing.home') }}" class="{{ request()->routeIs('landing.home') ? 'active' : '' }}">Home</a></li>
                <li><a href="{{ route('landing.about') }}" class="{{ request()->routeIs('landing.about') ? 'active' : '' }}">About Us</a></li>
                <li><a href="{{ route('landing.features') }}" class="{{ request()->routeIs('landing.features') ? 'active' : '' }}">Features</a></li>
                <li><a href="{{ route('landing.packages') }}" class="{{ request()->routeIs('landing.packages') ? 'active' : '' }}">Packages</a></li>
                <li><a href="{{ route('landing.reviews') }}" class="{{ request()->routeIs('landing.reviews') ? 'active' : '' }}">Reviews</a></li>
                <li><a href="{{ route('landing.contact') }}" class="{{ request()->routeIs('landing.contact') ? 'active' : '' }}">Contact Us</a></li>
            </ul>
            
            <div class="nav-action">
                <a href="#" class="btn btn-primary"><i class="fa-brands fa-google-play mr-2"></i> Download App</a>
            </div>
            
            <div class="mobile-menu-btn" id="mobileBtn">
                <i class="fa-solid fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main style="padding-top: 80px;">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <a href="{{ route('landing.home') }}" class="footer-logo"><i class="fa-solid fa-layer-group"></i> BrandKit</a>
                    <p class="footer-desc">The ultimate AI-powered poster maker and marketing automation tool for small and medium businesses.</p>
                    <div style="margin-top: 20px;">
                        <a href="#" class="btn btn-primary" style="padding: 10px 20px;"><i class="fa-brands fa-google-play mr-2"></i> Download App</a>
                    </div>
                </div>
                
                <div>
                    <h4 class="footer-title">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="{{ route('landing.home') }}">Home</a></li>
                        <li><a href="{{ route('landing.about') }}">About Us</a></li>
                        <li><a href="{{ route('landing.features') }}">Features</a></li>
                        <li><a href="{{ route('landing.packages') }}">Pricing Plans</a></li>
                        <li><a href="{{ route('landing.contact') }}">Contact</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="footer-title">Legal</h4>
                    <ul class="footer-links">
                        <li><a href="/privacy-policy">Privacy Policy</a></li>
                        <li><a href="/terms-condition">Terms & Conditions</a></li>
                        <li><a href="/refund-policy">Refund Policy</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="footer-title">Newsletter</h4>
                    <p class="footer-desc">Subscribe to get the latest marketing tips and template updates.</p>
                    <form style="display: flex; gap: 10px; margin-top: 15px;" onsubmit="event.preventDefault();">
                        <input type="email" placeholder="Your email address" style="padding: 12px; border-radius: 8px; border: none; width: 100%;" required>
                        <button type="submit" style="background: var(--primary-light); color: white; border: none; padding: 0 20px; border-radius: 8px; cursor: pointer;"><i class="fa-solid fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2026 BrandKit Inc. All rights reserved.</p>
                <div style="display: flex; gap: 20px;">
                    <a href="#" style="color: #94a3b8;"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" style="color: #94a3b8;"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" style="color: #94a3b8;"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800, once: true, offset: 100 });

        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.style.padding = '10px 0';
                navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
            } else {
                navbar.style.padding = '15px 0';
                navbar.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
            }
        });

        const mobileBtn = document.getElementById('mobileBtn');
        const navLinks = document.getElementById('navLinks');
        if(mobileBtn && navLinks) {
            mobileBtn.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
        }
    </script>
    @yield('extra_js')
</body>
</html>
