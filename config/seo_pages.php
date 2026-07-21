<?php

/**
 * Programmatic SEO Page Definitions
 *
 * This config file defines all SEO landing pages for the Artera platform.
 * Each entry contains metadata, keywords, content, FAQs, and HowTo steps.
 * Adding a new page is as simple as adding a config entry — no code changes needed.
 *
 * Structure:
 * - use_case_hubs: Top-level feature pages (/ai-poster-maker, /banner-maker, etc.)
 * - social_platform_pages: Platform-specific pages (/instagram-post-maker, etc.)
 * - industry_vanity_urls: Maps vanity slugs to BusinessCategory slugs
 * - festival_vanity_urls: Maps vanity slugs to Festival slugs
 * - long_tail_pages: Geo-targeted and feature-specific long-tail pages
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Use-Case Hub Pages (Phase 2)
    |--------------------------------------------------------------------------
    */
    'use_case_hubs' => [

        'ai-poster-maker' => [
            'title' => 'AI Poster Maker — Create Professional Business Posters in Seconds | Artera',
            'h1' => 'AI Poster Maker for Business Marketing',
            'description' => 'Create stunning professional business posters in seconds with Artera\'s AI poster maker. Powered by generative AI, design marketing posters, festival greetings, and social media content for 30+ industries without any design skills.',
            'keywords' => 'ai poster maker, ai poster generator, ai graphic design tool, ai design platform, generative ai design, ai image generator, automated poster creation',
            'intro' => 'Artera\'s AI Poster Maker transforms the way businesses create marketing content. Using advanced artificial intelligence and generative AI design technology, our AI graphic design tool automatically creates professional posters, banners, and social media content tailored to your brand. Whether you need festival greetings, promotional offers, or daily marketing posts — our AI poster generator delivers stunning designs in under 30 seconds.',
            'features' => [
                ['icon' => 'fa-robot', 'title' => 'AI-Powered Generation', 'desc' => 'Our AI poster generator creates professional designs using advanced machine learning, tailored to your industry and brand identity.'],
                ['icon' => 'fa-bolt', 'title' => 'Create in 30 Seconds', 'desc' => 'No design skills needed. Select a template, add your brand, and download — all in under 30 seconds.'],
                ['icon' => 'fa-palette', 'title' => '100,000+ Templates', 'desc' => 'Access the largest library of AI-generated business poster templates across 30+ industries.'],
                ['icon' => 'fa-language', 'title' => 'Multi-Language Support', 'desc' => 'Create AI-powered posters in Hindi, Marathi, Gujarati, Tamil, Telugu, and 10+ regional languages.'],
            ],
            'faq' => [
                ['question' => 'What is an AI poster maker?', 'answer' => 'An AI poster maker is a design tool that uses artificial intelligence to automatically generate professional posters, banners, and marketing graphics. Artera\'s AI poster maker creates business-specific designs by understanding your industry, brand colors, and marketing goals.'],
                ['question' => 'Is Artera\'s AI poster maker free?', 'answer' => 'Yes, Artera offers a free plan with access to thousands of AI-generated templates. Premium plans unlock unlimited downloads, advanced AI features, and custom branding tools.'],
                ['question' => 'How accurate is the AI poster generation?', 'answer' => 'Artera\'s AI generates highly relevant designs based on your business category, festival context, and brand identity. Each template is professionally crafted and then personalized by AI for your specific use case.'],
                ['question' => 'Can I edit AI-generated posters?', 'answer' => 'Absolutely. Every AI-generated poster is fully customizable. You can change text, colors, images, fonts, and layout elements using our built-in editor.'],
                ['question' => 'Which industries does the AI poster maker support?', 'answer' => 'Artera\'s AI poster maker supports 30+ business categories including Real Estate, Restaurant, Salon, Gym, Medical, Education, Jewellery, Fashion, Automobile, Electronics, and many more.'],
            ],
            'howto' => [
                'name' => 'How to Create a Poster with AI Poster Maker',
                'steps' => [
                    ['name' => 'Choose Your Category', 'text' => 'Select your business category from 30+ industries. The AI will tailor templates specifically for your niche.'],
                    ['name' => 'Select & Customize Template', 'text' => 'Browse AI-generated templates and pick one that matches your marketing goal. Customize text, colors, and images.'],
                    ['name' => 'Download & Share', 'text' => 'Download your poster in HD quality and share directly to WhatsApp, Instagram, Facebook, or any social platform.'],
                ],
            ],
            'related_categories' => ['real-estate', 'restaurant', 'salon', 'gym', 'medical', 'education'],
        ],

        'banner-maker' => [
            'title' => 'Business Banner Maker — Create Professional Banners & Ads Online | Artera',
            'h1' => 'Business Banner Maker — Stunning Banners in Seconds',
            'description' => 'Design professional business banners, offer banners, sale banners, and promotional advertisements with Artera\'s AI-powered banner maker. Perfect for social media marketing, event promotion, and business advertising.',
            'keywords' => 'business banner maker, offer banner maker, sale banner maker, event banner maker, advertisement design, promotional banner maker, online banner maker',
            'intro' => 'Create eye-catching business banners and promotional advertisements with Artera\'s intelligent banner maker. Whether you need offer banners for a seasonal sale, event banners for your business opening, or advertisement designs for social media campaigns — our AI-powered banner maker delivers professional results instantly.',
            'features' => [
                ['icon' => 'fa-image', 'title' => 'Professional Banner Templates', 'desc' => 'Choose from thousands of professionally designed banner templates for offers, sales, events, and promotions.'],
                ['icon' => 'fa-mobile-alt', 'title' => 'Social Media Ready', 'desc' => 'Banners automatically sized for Instagram, Facebook, WhatsApp Status, and other social platforms.'],
                ['icon' => 'fa-paint-brush', 'title' => 'Custom Brand Colors', 'desc' => 'Automatically apply your brand colors, logo, and contact details to every banner design.'],
                ['icon' => 'fa-download', 'title' => 'HD Download', 'desc' => 'Download banners in high resolution suitable for both digital marketing and print.'],
            ],
            'faq' => [
                ['question' => 'How do I create a business banner?', 'answer' => 'Select a banner template from Artera\'s library, customize it with your offer details, brand logo, and contact information, then download in HD quality.'],
                ['question' => 'Can I create sale and offer banners?', 'answer' => 'Yes, Artera has dedicated templates for sale banners, offer banners, discount promotions, BOGO deals, clearance sales, and seasonal offers.'],
                ['question' => 'Are the banners suitable for printing?', 'answer' => 'Yes, Artera provides high-resolution banner downloads suitable for both digital marketing and professional printing.'],
            ],
            'howto' => [
                'name' => 'How to Create a Business Banner',
                'steps' => [
                    ['name' => 'Select Banner Type', 'text' => 'Choose from offer banners, sale banners, event banners, or promotional banners based on your marketing need.'],
                    ['name' => 'Customize Design', 'text' => 'Add your offer details, pricing, product images, and brand elements. Customize colors and fonts to match your brand.'],
                    ['name' => 'Download & Promote', 'text' => 'Download your banner in HD quality and share across social media, WhatsApp groups, or print for in-store display.'],
                ],
            ],
        ],

        'flyer-maker' => [
            'title' => 'Flyer Maker & Brochure Creator — Design Marketing Flyers Online | Artera',
            'h1' => 'Flyer Maker & Brochure Creator for Business',
            'description' => 'Create professional business flyers, brochures, pamphlets, and marketing materials with Artera. Design stunning promotional flyers for events, products, services, and business promotions in minutes.',
            'keywords' => 'flyer maker, brochure maker, pamphlet maker, marketing flyer creator, business flyer design, promotional flyer maker, brochure creator online',
            'intro' => 'Design professional marketing flyers and brochures for your business with Artera\'s easy-to-use flyer maker. From product launches to event promotions, service advertisements to seasonal campaigns — create stunning printed and digital marketing materials that drive customer engagement.',
            'features' => [],
            'faq' => [],
            'howto' => null,
        ],

        'social-media-post-maker' => [
            'title' => 'AI Social Media Post Maker — Create Marketing Content for All Platforms | Artera',
            'h1' => 'AI Social Media Post Maker for Business',
            'description' => 'Create professional social media marketing content for Instagram, Facebook, WhatsApp, and more with Artera\'s AI-powered social media post maker. Automate your social media marketing with industry-specific templates.',
            'keywords' => 'ai social media post maker, social media marketing tool, social media content creator, business social media templates, social media automation, social media post generator',
            'intro' => 'Automate your social media marketing with Artera\'s AI-powered social media post maker. Create professional, branded content for Instagram, Facebook, WhatsApp Status, and other platforms in seconds. Our AI understands your business category and generates industry-specific social media content that engages your audience and drives business growth.',
            'features' => [],
            'faq' => [],
            'howto' => null,
        ],

        'festival-poster-maker' => [
            'title' => 'Festival Poster Maker — Create Branded Festival Greetings for Business | Artera',
            'h1' => 'Festival Poster Maker — Branded Festival Greetings',
            'description' => 'Create professional festival posters with your business branding. Artera\'s festival poster maker covers 365+ Indian festivals including Diwali, Navratri, Holi, Ganesh Chaturthi, and more. Add your logo and share instantly.',
            'keywords' => 'festival poster maker, festival post maker, indian festival templates, festival social media templates, diwali poster maker, navratri poster maker, festival greeting maker',
            'intro' => 'Never miss a festival marketing opportunity with Artera\'s dedicated festival poster maker. Our platform covers 365+ Indian festivals and cultural events with professionally designed templates that automatically include your business logo, contact details, and brand identity. From Diwali to Navratri, Holi to Eid — create branded festival greetings that strengthen your customer relationships.',
            'features' => [],
            'faq' => [],
            'howto' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Media Platform Pages (Phase 3)
    |--------------------------------------------------------------------------
    */
    'social_platform_pages' => [

        'instagram-post-maker' => [
            'title' => 'Instagram Post Maker — Create Stunning IG Posts for Business | Artera',
            'h1' => 'Instagram Post Maker for Business Marketing',
            'description' => 'Create professional Instagram posts, stories, and reels thumbnails for your business marketing. Artera\'s Instagram post maker provides industry-specific templates optimized for maximum engagement.',
            'keywords' => 'instagram post maker, instagram marketing posts, instagram post templates, instagram story maker, business instagram content, instagram design tool',
            'platform_color' => '#E4405F',
        ],

        'facebook-post-maker' => [
            'title' => 'Facebook Post Maker — Design Professional FB Marketing Content | Artera',
            'h1' => 'Facebook Post Maker for Business Pages',
            'description' => 'Design professional Facebook posts, cover photos, and ad creatives for your business page. Artera\'s Facebook post maker creates engaging social media content optimized for the Facebook algorithm.',
            'keywords' => 'facebook post maker, facebook marketing posts, facebook banner design, fb post creator, business facebook content, facebook ad design',
            'platform_color' => '#1877F2',
        ],

        'whatsapp-status-maker' => [
            'title' => 'WhatsApp Status Maker — Create Business Status Updates | Artera',
            'h1' => 'WhatsApp Status Maker for Business Marketing',
            'description' => 'Create professional WhatsApp status updates and business marketing content. Artera\'s WhatsApp status maker helps you promote your products, offers, and festivals through WhatsApp — India\'s #1 messaging platform.',
            'keywords' => 'whatsapp status maker, whatsapp marketing, business status templates, whatsapp business marketing, whatsapp poster maker, whatsapp status design',
            'platform_color' => '#25D366',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Industry Vanity URLs (Phase 4) — Maps slug → BusinessCategory slug
    |--------------------------------------------------------------------------
    */
    'industry_vanity_urls' => [
        'real-estate-poster-maker' => ['category_slug' => 'real-estate', 'primary_keyword' => 'Real Estate Marketing', 'h1' => 'Real Estate Poster Maker'],
        'restaurant-poster-maker' => ['category_slug' => 'food-beverage', 'primary_keyword' => 'Restaurant Marketing', 'h1' => 'Restaurant & Food Business Poster Maker'],
        'medical-poster-maker' => ['category_slug' => 'healthcare', 'primary_keyword' => 'Medical & Healthcare Marketing', 'h1' => 'Medical Poster Maker for Clinics & Hospitals'],
        'jewellery-poster-maker' => ['category_slug' => 'fashion-apparel', 'primary_keyword' => 'Jewellery Marketing', 'h1' => 'Jewellery Business Poster Maker'],
        'salon-poster-maker' => ['category_slug' => 'beauty-wellness', 'primary_keyword' => 'Salon & Spa Marketing', 'h1' => 'Salon & Beauty Poster Maker'],
        'gym-poster-maker' => ['category_slug' => 'sports-fitness', 'primary_keyword' => 'Gym & Fitness Marketing', 'h1' => 'Gym & Fitness Poster Maker'],
        'hotel-poster-maker' => ['category_slug' => 'travel-tourism', 'primary_keyword' => 'Hotel & Hospitality Marketing', 'h1' => 'Hotel & Cafe Poster Maker'],
        'education-poster-maker' => ['category_slug' => 'education', 'primary_keyword' => 'Education Marketing', 'h1' => 'Education & Coaching Poster Maker'],
        'automobile-poster-maker' => ['category_slug' => 'automotive', 'primary_keyword' => 'Automobile Marketing', 'h1' => 'Automobile Business Poster Maker'],
        'electronics-poster-maker' => ['category_slug' => 'electronics', 'primary_keyword' => 'Electronics Marketing', 'h1' => 'Electronics & Mobile Shop Poster Maker'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Festival Vanity URLs (Phase 4) — Maps slug → Festival slug
    |--------------------------------------------------------------------------
    */
    'festival_vanity_urls' => [
        'diwali-poster' => ['festival_slug' => 'diwali', 'primary_keyword' => 'Diwali Poster Maker 2026'],
        'navratri-poster' => ['festival_slug' => 'navratri', 'primary_keyword' => 'Navratri Poster Maker'],
        'ganesh-chaturthi-poster' => ['festival_slug' => 'ganesh-chaturthi', 'primary_keyword' => 'Ganesh Chaturthi Poster'],
        'raksha-bandhan-poster' => ['festival_slug' => 'raksha-bandhan', 'primary_keyword' => 'Raksha Bandhan Poster'],
        'independence-day-poster' => ['festival_slug' => 'independence-day', 'primary_keyword' => 'Independence Day Poster'],
        'republic-day-poster' => ['festival_slug' => 'republic-day', 'primary_keyword' => 'Republic Day Poster'],
        'janmashtami-poster' => ['festival_slug' => 'janmashtami', 'primary_keyword' => 'Janmashtami Poster'],
        'holi-poster' => ['festival_slug' => 'holi', 'primary_keyword' => 'Holi Poster Maker'],
        'eid-poster' => ['festival_slug' => 'eid', 'primary_keyword' => 'Eid Poster Maker'],
        'christmas-poster' => ['festival_slug' => 'christmas', 'primary_keyword' => 'Christmas Poster Maker'],
        'makar-sankranti-poster' => ['festival_slug' => 'makar-sankranti', 'primary_keyword' => 'Makar Sankranti Poster'],
        'pongal-poster' => ['festival_slug' => 'pongal', 'primary_keyword' => 'Pongal Poster Maker'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Long-Tail & Geo Pages (Phase 5)
    |--------------------------------------------------------------------------
    */
    'long_tail_pages' => [
        'poster-maker-india' => [
            'title' => 'AI Poster Maker India — #1 Business Marketing Platform for Indian Businesses | Artera',
            'h1' => 'AI Poster Maker India — Built for Indian Businesses',
            'description' => 'India\'s #1 AI poster maker for local businesses. Create festival posters, business marketing content, and social media designs in Hindi, Gujarati, Marathi, Tamil, and 10+ Indian languages.',
            'keywords' => 'poster maker india, ai branding india, business marketing india, indian festival poster maker, hindi poster maker',
        ],
        'festival-templates' => [
            'title' => 'Festival Templates — 365+ Indian Festival Poster Templates | Artera',
            'h1' => 'Festival Templates Collection',
            'description' => 'Browse 365+ Indian festival poster templates. Download professional Diwali, Holi, Navratri, Eid, and Christmas poster templates with your business branding. Updated for 2026.',
            'keywords' => 'festival templates, festival poster templates, indian festival templates, diwali templates, navratri templates, festival social media templates',
        ],
    ],

];
