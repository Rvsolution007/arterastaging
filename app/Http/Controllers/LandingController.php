<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Blog;
use App\Models\StorageSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\HomeBanner;

class LandingController extends Controller
{
    public function home()
    {
        $today = \Carbon\Carbon::today();
        // Fetch upcoming festivals
        $upcomingFestivals = \App\Models\Festivals::where('status', 1)
            ->whereDate('festivals_date', '>=', $today)
            ->orderBy('festivals_date', 'asc')
            ->take(30)
            ->get();
            
        // Group by date, formatted like "4 Jul", "5 Jul"
        $festivalsByDate = [];
        foreach ($upcomingFestivals as $festival) {
            $date = \Carbon\Carbon::parse($festival->festivals_date)->format('j M');
            $year = \Carbon\Carbon::parse($festival->festivals_date)->format('Y');
            $key = $date;
            
            if (!isset($festivalsByDate[$key])) {
                $festivalsByDate[$key] = [
                    'date_string' => $date,
                    'year' => $year,
                    'festivals' => []
                ];
            }
            $festivalsByDate[$key]['festivals'][] = $festival;
        }
        
        // Take only the first 5 unique dates to match the UI screenshot
        $festivalsByDate = array_slice($festivalsByDate, 0, 5, true);

        // Fetch Home Banners
        $homeBanners = HomeBanner::orderBy('column_index')->orderBy('sort_order')->orderBy('id', 'desc')->get()->groupBy('column_index');

        $seo = [
            'title' => 'Artera — #1 AI Poster Maker for Business | Create Marketing Posts in Seconds',
            'description' => 'Artera is India\'s leading AI-powered poster maker and business marketing platform. Create stunning festival posters, business banners, social media content, and promotional designs for 30+ industries in 30 seconds. Free to start. Trusted by 5,00,000+ businesses.',
            'canonical' => config('seo.site_url'),
            'keywords' => 'ai poster maker, ai banner maker, festival poster maker, business poster maker, ai social media post maker, ai marketing platform, ai branding platform, business branding software, small business marketing tool, digital branding platform, poster maker app, social media marketing tool',
            'show_app_schema' => true,
            'speakable' => true,
            'faq' => [
                ['question' => 'What is Artera?', 'answer' => 'Artera is an AI-powered business poster maker and marketing platform that helps businesses create professional festival posters, promotional banners, social media content, and brand marketing materials in under 30 seconds — no design skills required.'],
                ['question' => 'Is Artera free to use?', 'answer' => 'Yes, Artera offers a free plan with access to thousands of templates, basic editing tools, and festival poster creation. Premium plans unlock unlimited downloads, AI-powered content generation, custom frames, and advanced branding features.'],
                ['question' => 'How does the AI poster maker work?', 'answer' => 'Simply select a template from 30+ business categories or 365+ festival events, add your business logo and details, and Artera\'s AI automatically generates professional marketing content. Download and share directly to WhatsApp, Instagram, or Facebook.'],
                ['question' => 'Which business categories does Artera support?', 'answer' => 'Artera supports 30+ business categories including Real Estate, Restaurant & Food, Salon & Beauty, Jewellery, Gym & Fitness, Medical & Healthcare, Education, Automobile, Electronics, Fashion, and many more.'],
                ['question' => 'Can I create posters in Hindi and regional languages?', 'answer' => 'Yes, Artera supports multi-language content including Hindi, Marathi, Gujarati, Tamil, Telugu, Bengali, Kannada, Malayalam, and Punjabi — perfect for local business marketing across India.'],
                ['question' => 'What makes Artera different from Canva?', 'answer' => 'Unlike generic design tools, Artera is purpose-built for business marketing. It offers AI-powered content generation, automated daily marketing posts, industry-specific templates for 30+ categories, and festival calendar integration — all optimized for Indian businesses.'],
            ],
            'howto' => [
                'name' => 'How to Create a Business Poster with Artera AI Poster Maker',
                'description' => 'Create professional marketing posters for your business in 3 simple steps using Artera\'s AI-powered design platform.',
                'total_time' => 'PT1M',
                'steps' => [
                    ['name' => 'Download Artera App & Register', 'text' => 'Download the free Artera app from Google Play Store. Register your business by selecting your industry category, adding your logo, and entering your brand details.'],
                    ['name' => 'Choose a Template & Customize', 'text' => 'Browse thousands of AI-generated templates across festivals, business categories, and social media formats. Select a template and customize colors, text, images, and branding elements.'],
                    ['name' => 'Download & Share to Social Media', 'text' => 'Download your professionally designed poster in HD quality. Share directly to WhatsApp Status, Instagram Stories, Facebook, or any social media platform with one tap.'],
                ],
            ],
        ];

        return view('landing.home', compact('festivalsByDate', 'homeBanners', 'seo'));
    }

    public function ajaxSearch(Request $request)
    {
        $query = $request->input('q');
        if(empty($query)) {
            return response()->json([]);
        }

        $results = [];
        $isDigitalOcean = StorageSetting::getStorageSetting('storage') == 'DigitalOcean';

        // Search Festivals
        $festivals = \App\Models\Festivals::where('title', 'like', '%' . $query . '%')
            ->where('status', 1)
            ->take(5)
            ->get();
            
        foreach($festivals as $f) {
            $results[] = [
                'type' => 'festival',
                'title' => $f->title,
                'image' => $f->image ? ($isDigitalOcean ? Storage::disk('spaces')->url('uploads/'.$f->image) : asset('uploads/'.$f->image)) : '',
                'url' => route('seo.festival', ['festivalSlug' => Str::slug($f->title)])
            ];
        }

        // Search Categories (Category Post Categories)
        $categories = \App\Models\Category::where('name', 'like', '%' . $query . '%')
            ->where('status', 1)
            ->take(5)
            ->get();
            
        foreach($categories as $c) {
            $results[] = [
                'type' => 'category',
                'title' => $c->name,
                'image' => $c->icon ? ($isDigitalOcean ? Storage::disk('spaces')->url('uploads/'.$c->icon) : asset('uploads/'.$c->icon)) : '',
                'url' => route('landing.search') . '?q=' . urlencode($c->name)
            ];
        }

        return response()->json($results);
    }

    public function searchPage(Request $request)
    {
        $query = $request->input('q');
        $isDigitalOcean = StorageSetting::getStorageSetting('storage') == 'DigitalOcean';
        
        $results = [];

        if (!empty($query)) {
            $festivals = \App\Models\Festivals::where('title', 'like', '%' . $query . '%')
                ->where('status', 1)
                ->get();
                
            foreach($festivals as $fest) {
                $festivalPosts = \App\Models\FestivalsPost::where('festivals_id', $fest->id)->where('status', 1)->take(20)->get();
                foreach($festivalPosts as $f) {
                    $results[] = (object)[
                        'type' => 'festival',
                        'title' => $fest->title,
                        'image' => $f->seo_image ?? '',
                        'url' => route('seo.template', ['id' => 'f_'.$f->id, 'slug' => Str::slug($fest->title)])
                    ];
                }
            }

            $categories = \App\Models\Category::where('name', 'like', '%' . $query . '%')
                ->where('status', 1)
                ->get();
                
            foreach($categories as $cat) {
                $categoryPosts = \App\Models\CategoryPost::where('category_id', $cat->id)->where('status', 1)->take(20)->get();
                foreach($categoryPosts as $c) {
                    $results[] = (object)[
                        'type' => 'category',
                        'title' => $cat->name,
                        'image' => $c->seo_image ?? '',
                        'url' => route('seo.template', ['id' => 'c_'.$c->id, 'slug' => Str::slug($cat->name)])
                    ];
                }
            }
        }

        $resultsCollection = collect($results);
        
        return view('landing.search-results', compact('resultsCollection', 'query'));
    }

    public function authGate()
    {
        // Need Business categories because the register view needs it
        $categories = \App\Models\BusinessCategory::where('status', 1)->get();
        return view('landing.auth-gate', compact('categories'));
    }

    public function appGateway()
    {
        return view('landing.app-gateway');
    }

    public function about()
    {
        $seo = [
            'title' => 'About Artera — AI-Powered Business Marketing Platform | Our Story',
            'description' => 'Learn about Artera, the AI-powered poster maker and business marketing platform helping thousands of small businesses automate their digital branding. Based in India, built for the world.',
            'canonical' => config('seo.site_url') . '/about',
            'keywords' => 'about artera, ai marketing platform, business branding company, poster maker company, artera pixel, digital marketing startup india',
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'About Us'],
            ],
            'speakable' => true,
        ];

        return view('landing.about', compact('seo'));
    }

    public function features()
    {
        $seo = [
            'title' => 'Artera Features — AI Design Tools for Business Marketing Automation',
            'description' => 'Explore Artera\'s powerful features: AI poster generation, 100,000+ business templates, 30+ industry categories, multi-language support, festival calendar automation, and instant social media content creation.',
            'canonical' => config('seo.site_url') . '/features',
            'keywords' => 'ai design platform features, ai marketing automation, business poster features, ai graphic design tool, social media automation, festival poster automation, business marketing features',
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'Features'],
            ],
            'speakable' => true,
        ];

        return view('landing.features', compact('seo'));
    }

    public function packages()
    {
        $packages = Subscription::where('status', '1')->get();
        
        $seo = [
            'title' => 'Artera Pricing Plans — Affordable AI Marketing for Every Business',
            'description' => 'Choose the perfect Artera plan for your business. Free and premium subscription options with AI poster maker, unlimited templates, festival automation, and professional branding tools. Start free, upgrade anytime.',
            'canonical' => config('seo.site_url') . '/packages',
            'keywords' => 'artera pricing, poster maker plans, ai marketing pricing, business branding subscription, affordable marketing tool, poster maker app price',
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'Pricing Plans'],
            ],
            'speakable' => true,
            'faq' => [
                ['question' => 'How much does Artera cost?', 'answer' => 'Artera offers a free plan with basic features. Premium plans start at affordable monthly rates with yearly discounts of up to 20%. Visit the pricing page for current rates.'],
                ['question' => 'Can I try Artera before purchasing?', 'answer' => 'Yes, Artera offers a free plan that includes access to thousands of templates, basic editing tools, and festival poster creation. No credit card required to start.'],
                ['question' => 'What payment methods does Artera accept?', 'answer' => 'Artera accepts all major payment methods through the Google Play Store including UPI, credit cards, debit cards, and net banking.'],
            ],
        ];

        return view('landing.packages', compact('packages', 'seo'));
    }

    public function reviews()
    {
        $seo = [
            'title' => 'Artera Reviews — What Business Owners Say About Our AI Poster Maker',
            'description' => 'Read real testimonials from business owners using Artera\'s AI poster maker. See how small businesses across India are automating their marketing and growing their brand with professional designs.',
            'canonical' => config('seo.site_url') . '/reviews',
            'keywords' => 'artera reviews, poster maker app reviews, ai marketing tool reviews, business branding testimonials, artera user feedback',
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'Reviews'],
            ],
            'speakable' => true,
        ];

        return view('landing.reviews', compact('seo'));
    }

    public function contact()
    {
        $seo = [
            'title' => 'Contact Artera — Get Help with AI Poster Maker & Business Marketing',
            'description' => 'Contact Artera\'s support team for help with our AI poster maker, business marketing tools, subscription plans, or technical assistance. Available 24/7 via email, phone, and social media.',
            'canonical' => config('seo.site_url') . '/contact',
            'keywords' => 'contact artera, artera support, poster maker help, artera customer service, artera phone number, artera email',
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'Contact Us'],
            ],
            'speakable' => true,
        ];

        return view('landing.contact', compact('seo'));
    }

    public function blogs()
    {
        // Assuming status 'published' is published
        $blogs = Blog::where('status', 'published')->latest()->paginate(9);
        
        $seo = [
            'title' => 'Artera Blog — Marketing Tips, Design Guides & Business Branding Insights',
            'description' => 'Read the latest marketing tips, AI design tutorials, festival marketing guides, and business branding insights from Artera. Learn how to grow your brand with AI-powered marketing tools.',
            'canonical' => config('seo.site_url') . '/blogs',
            'keywords' => 'marketing blog, business branding tips, poster design guide, festival marketing, social media marketing tips, ai design tutorials, small business marketing blog',
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'Blog'],
            ],
            'speakable' => true,
        ];

        return view('landing.blogs', compact('blogs', 'seo'));
    }

    public function blogDetails($slug)
    {
        $blog = Blog::where('slug', $slug)->firstOrFail();
        
        // Fetch related posts for the footer of the blog
        $relatedBlogs = Blog::where('status', 'published')
                            ->where('id', '!=', $blog->id)
                            ->inRandomOrder()
                            ->take(3)
                            ->get();

        $seo = [
            'title' => $blog->title . ' — Artera Blog',
            'description' => Str::limit(strip_tags($blog->content), 155),
            'canonical' => config('seo.site_url') . '/blog/' . $blog->slug,
            'keywords' => 'artera blog, ' . Str::lower($blog->title),
            'og_image' => $blog->og_image ? asset($blog->og_image) : null,
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'Blog', 'url' => '/blogs'],
                ['name' => $blog->title],
            ],
            'speakable' => true,
            'article' => [
                'published_time' => $blog->created_at->toIso8601String(),
                'modified_time' => $blog->updated_at->toIso8601String(),
                'author' => 'Artera',
            ],
        ];

        return view('landing.blog-details', compact('blog', 'relatedBlogs', 'seo'));
    }

    public function templates()
    {
        $festivals = \App\Models\Festivals::where('status', '1')->latest()->take(12)->get();
        $categories = \App\Models\Category::where('status', '1')->latest()->take(12)->get();
        
        $totalFestivals = \App\Models\FestivalsPost::where('status', '1')->count();
        $totalCategories = \App\Models\CategoryPost::where('status', '1')->count();

        $seo = [
            'title' => 'Artera Templates Gallery — Festival Posters, Business Marketing & Social Media Designs',
            'description' => 'Browse Artera\'s extensive template gallery with thousands of professional designs for festivals, business marketing, social media content, and promotional materials across 30+ industries.',
            'canonical' => config('seo.site_url') . '/templates',
            'keywords' => 'poster templates, festival poster templates, business marketing templates, social media templates, banner templates, instagram post templates, whatsapp status templates',
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'Templates Gallery'],
            ],
            'speakable' => true,
            'collection' => [
                'name' => 'Artera Template Gallery',
                'description' => 'Browse thousands of professional poster and marketing templates',
            ],
        ];

        return view('landing.templates', compact('festivals', 'categories', 'totalFestivals', 'totalCategories', 'seo'));
    }

    public function category($slug)
    {
        $category = \App\Models\Category::where('id', $slug)->orWhere('name', 'like', '%' . str_replace('-', ' ', $slug) . '%')->firstOrFail();
        $posts = \App\Models\CategoryPost::where('category_id', $category->id)->where('status', '1')->where('show_on_landing', 1)->latest()->paginate(12);
        return view('landing.category', compact('category', 'posts'));
    }

    public function digitalBusinessCards()
    {
        return view('landing.digital-business-cards');
    }

    public function logoMaker()
    {
        return view('landing.logo-maker');
    }

    public function videoMaker()
    {
        return view('landing.video-maker');
    }
}
