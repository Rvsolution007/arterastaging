<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site Identity
    |--------------------------------------------------------------------------
    */
    'site_name' => 'Artera',
    'site_tagline' => 'AI-Powered Business Poster Maker',
    'site_url' => env('APP_URL', 'https://arterapixel.com'),
    'site_logo' => '/assets/images/logo.png',
    'site_favicon' => '/assets/images/favicon.png',

    /*
    |--------------------------------------------------------------------------
    | Default Meta Tags
    |--------------------------------------------------------------------------
    */
    'default_title' => 'Artera — AI-Powered Business Poster Maker App',
    'default_description' => 'Create stunning marketing posters, festival greetings, and social media content in seconds with Artera. AI-powered poster maker with 100,000+ templates for 30+ business categories.',
    'default_keywords' => 'poster maker, ai poster maker, business poster maker, festival poster maker, social media poster, marketing poster, banner maker, artera',
    'default_og_image' => '/assets/images/og-default.jpg',

    /*
    |--------------------------------------------------------------------------
    | Organization Schema
    |--------------------------------------------------------------------------
    */
    'organization' => [
        'name' => 'Artera',
        'legal_name' => 'Artera Pixel',
        'description' => 'AI-Powered Business Poster Maker Platform that helps small businesses create professional marketing posters, festival greetings, and social media content.',
        'url' => env('APP_URL', 'https://arterapixel.com'),
        'logo' => '/assets/images/logo.png',
        'email' => 'arterapixel7@gmail.com',
        'phone' => '',
        'address' => [
            'street' => 'RK Empire, Near Mavdi Circle',
            'city' => 'Rajkot',
            'state' => 'Gujarat',
            'zip' => '360004',
            'country' => 'IN',
        ],
        'founder' => [
            'name' => 'Brijesh Vaghasiya',
            'title' => 'CEO & Founder',
            'url' => 'https://www.linkedin.com/in/brijeshvaghasiya',
        ],
        'founding_date' => '2024',
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Profiles (for Organization Schema sameAs)
    |--------------------------------------------------------------------------
    */
    'social_profiles' => [
        'instagram' => 'https://www.instagram.com/arterapixel',
        'facebook' => 'https://www.facebook.com/arterapixel',
        'twitter' => 'https://x.com/arterapixel',
        'linkedin' => 'https://www.linkedin.com/company/arterapixel',
        'youtube' => 'https://www.youtube.com/@arterapixel',
        'pinterest' => 'https://www.pinterest.com/arterapixel',
        'github' => 'https://github.com/arterapixel',
    ],

    /*
    |--------------------------------------------------------------------------
    | App Store Links
    |--------------------------------------------------------------------------
    */
    'app_links' => [
        'android' => 'https://play.google.com/store/apps/details?id=com.arterapixel.pro&hl=en_IN',
        'ios' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Software Application Schema
    |--------------------------------------------------------------------------
    */
    'app' => [
        'name' => 'Artera - Business Poster Maker',
        'category' => 'DesignApplication',
        'operating_system' => 'Android',
        'price' => '0',
        'currency' => 'INR',
        'rating_value' => '4.8',
        'rating_count' => '10000',
        'download_count' => '500000',
    ],

    /*
    |--------------------------------------------------------------------------
    | Blog Categories
    |--------------------------------------------------------------------------
    */
    'blog_categories' => [
        'poster-design' => 'Poster Design Tips',
        'marketing' => 'Marketing Ideas',
        'festival-marketing' => 'Festival Marketing',
        'business-branding' => 'Business Branding',
        'social-media' => 'Social Media Marketing',
        'industry-guides' => 'Industry Guides',
        'ai-design' => 'AI Design',
        'case-studies' => 'Case Studies',
        'comparisons' => 'Comparisons',
        'tutorials' => 'How-To Tutorials',
        'seasonal' => 'Seasonal Content',
        'glossary' => 'Glossary',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemap Configuration
    |--------------------------------------------------------------------------
    */
    'sitemap' => [
        'items_per_page' => 5000,    // Max URLs per sitemap file
        'change_frequency' => [
            'homepage' => 'daily',
            'categories' => 'weekly',
            'festivals' => 'weekly',
            'templates' => 'monthly',
            'blog' => 'weekly',
            'static' => 'monthly',
        ],
        'priority' => [
            'homepage' => 1.0,
            'categories' => 0.9,
            'festivals' => 0.8,
            'templates' => 0.7,
            'blog' => 0.6,
            'static' => 0.5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics & Webmaster
    |--------------------------------------------------------------------------
    */
    'google_analytics_id' => env('GOOGLE_ANALYTICS_ID', ''),
    'google_tag_manager_id' => env('GTM_ID', ''),
    'google_site_verification' => env('GOOGLE_SITE_VERIFICATION', ''),
    'bing_site_verification' => env('BING_SITE_VERIFICATION', ''),

];
