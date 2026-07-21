<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use App\Models\BusinessType;
use App\Models\BusinessProduct;
use App\Models\Festivals;
use App\Models\FestivalsPost;
use App\Models\CategoryPost;
use App\Models\CustomPostFrame;
use App\Models\Category;

class ProgrammaticSeoController extends Controller
{
    /**
     * /poster-maker — Hub page listing all business categories
     */
    public function posterMakerHub()
    {
        $categories = BusinessCategory::where('status', 1)
            ->withCount('subCategories')
            ->get();

        $seo = [
            'title' => 'Poster Maker for Every Business — 30+ Categories | Artera',
            'description' => 'Create professional marketing posters for any business. Browse 30+ business categories including Real Estate, Restaurant, Healthcare, Gym, Salon, and more. Free AI-powered poster maker.',
            'canonical' => config('seo.site_url') . '/poster-maker',
            'keywords' => 'poster maker, business poster maker, ai poster maker, marketing poster, business categories poster',
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'Poster Maker'],
            ],
            'collection' => ['count' => $categories->count()],
            'speakable' => true,
            'faq' => [
                ['question' => 'What is Artera Poster Maker?', 'answer' => 'Artera is an AI-powered poster maker app that helps businesses create professional marketing posters, festival greetings, and social media content. It supports 30+ business categories with industry-specific templates.'],
                ['question' => 'How many business categories does Artera support?', 'answer' => 'Artera supports over 30 business categories including Real Estate, Healthcare, Restaurant, Salon, Gym, Education, and many more. Each category has dedicated poster templates designed for that industry.'],
                ['question' => 'Is Artera Poster Maker free?', 'answer' => 'Yes, Artera offers a free plan with access to thousands of poster templates. Premium plans unlock additional features like AI content generation, HD downloads, and exclusive templates.'],
                ['question' => 'Can I create posters without design skills?', 'answer' => 'Absolutely! Artera uses AI to automatically generate professional posters with your business details. Just select a template, add your logo and info, and download in seconds.'],
                ['question' => 'What types of posters can I create?', 'answer' => 'You can create festival posters, business marketing posters, social media posts, offer banners, event posters, product showcases, and more across all supported business categories.'],
            ],
            'howto' => [
                'name' => 'How to Create a Business Poster with Artera',
                'description' => 'Create professional marketing posters in 3 easy steps using Artera AI poster maker.',
                'total_time' => 'PT2M',
                'steps' => [
                    ['name' => 'Choose Your Business Category', 'text' => 'Select your business category from 30+ options like Real Estate, Restaurant, Healthcare, or Salon to see industry-specific poster templates.'],
                    ['name' => 'Customize Your Poster', 'text' => 'Add your business logo, contact details, and customize colors to match your brand. The AI automatically generates professional content.'],
                    ['name' => 'Download & Share', 'text' => 'Download your poster in HD quality and share directly on WhatsApp, Instagram, Facebook, or print it for your business.'],
                ],
            ],
        ];

        return view('landing.seo.poster-maker-hub', compact('categories', 'seo'));
    }

    /**
     * /poster-maker/{categorySlug} — Individual category landing page
     */
    public function categoryLanding($categorySlug)
    {
        $category = BusinessCategory::where('slug', $categorySlug)
            ->orWhere(function ($q) use ($categorySlug) {
                $q->whereRaw("LOWER(REPLACE(name, ' ', '-')) = ?", [strtolower($categorySlug)]);
            })
            ->firstOrFail();

        $subCategories = BusinessSubCategory::where('business_category_id', $category->id)
            ->where('status', 1)
            ->withCount('types')
            ->get();

        // Get templates for this category from category_post via category table
        $catIds = Category::where('name', 'like', '%' . $category->name . '%')->pluck('id');
        $templates = CategoryPost::whereIn('category_id', $catIds)
            ->where('status', '1')
            ->latest()
            ->take(24)
            ->get();

        $templateCount = CategoryPost::whereIn('category_id', $catIds)->where('status', '1')->count();

        // Related categories for sidebar
        $relatedCategories = BusinessCategory::where('status', 1)
            ->where('id', '!=', $category->id)
            ->inRandomOrder()
            ->take(10)
            ->get();

        $catName = $category->name;
        $seo = [
            'title' => $catName . ' Poster Maker — Free ' . $catName . ' Templates | Artera',
            'description' => 'Create stunning ' . strtolower($catName) . ' posters and marketing materials with Artera. Browse ' . ($templateCount ?: '100') . '+ free ' . strtolower($catName) . ' templates. AI-powered poster maker designed for ' . strtolower($catName) . ' businesses.',
            'canonical' => config('seo.site_url') . '/poster-maker/' . ($category->slug ?: Str::slug($catName)),
            'keywords' => strtolower($catName) . ' poster maker, ' . strtolower($catName) . ' poster, ' . strtolower($catName) . ' marketing, ' . strtolower($catName) . ' templates, ' . strtolower($catName) . ' banner maker',
            'og_image' => $templates->first() && $templates->first()->frame_image ? $templates->first()->seo_image : null,
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'Poster Maker', 'url' => '/poster-maker'],
                ['name' => $catName . ' Poster Maker'],
            ],
            'collection' => ['count' => $templateCount],
            'speakable' => true,
            'faq' => [
                ['question' => 'How to create ' . strtolower($catName) . ' posters?', 'answer' => 'With Artera, you can create professional ' . strtolower($catName) . ' posters in seconds. Simply select a ' . strtolower($catName) . ' template, add your business details and logo, and download the poster in HD quality.'],
                ['question' => 'Are ' . strtolower($catName) . ' poster templates free?', 'answer' => 'Yes, Artera offers many free ' . strtolower($catName) . ' poster templates. You can browse and customize them without any cost. Premium templates are available with a subscription.'],
                ['question' => 'What types of ' . strtolower($catName) . ' posters can I make?', 'answer' => 'You can create offer posters, promotional banners, social media posts, festival greetings, event announcements, and more — all designed specifically for ' . strtolower($catName) . ' businesses.'],
                ['question' => 'Can I add my ' . strtolower($catName) . ' business logo to posters?', 'answer' => 'Yes! You can easily add your business logo, contact information, address, and website to any ' . strtolower($catName) . ' poster template in Artera.'],
                ['question' => 'Best poster maker for ' . strtolower($catName) . ' business?', 'answer' => 'Artera is the best poster maker for ' . strtolower($catName) . ' businesses because it offers industry-specific templates, AI-powered content generation, and multi-language support designed for Indian businesses.'],
            ],
        ];

        return view('landing.seo.category-landing', compact('category', 'subCategories', 'templates', 'templateCount', 'relatedCategories', 'seo'));
    }

    /**
     * /poster-maker/{categorySlug}/{subSlug} — Sub-category or business type page
     */
    public function subCategoryLanding($categorySlug, $subSlug)
    {
        $category = BusinessCategory::where('slug', $categorySlug)
            ->orWhere(function ($q) use ($categorySlug) {
                $q->whereRaw("LOWER(REPLACE(name, ' ', '-')) = ?", [strtolower($categorySlug)]);
            })
            ->firstOrFail();

        // Try sub-category first, then business type
        $subCategory = BusinessSubCategory::where('business_category_id', $category->id)
            ->where(function ($q) use ($subSlug) {
                $q->where('slug', $subSlug)
                  ->orWhereRaw("LOWER(REPLACE(name, ' ', '-')) = ?", [strtolower($subSlug)]);
            })
            ->first();

        $businessType = null;
        if (!$subCategory) {
            $businessType = BusinessType::whereHas('business_sub_category', function ($q) use ($category) {
                $q->where('business_category_id', $category->id);
            })
            ->where(function ($q) use ($subSlug) {
                $q->where('slug', $subSlug)
                  ->orWhereRaw("LOWER(REPLACE(name, ' ', '-')) = ?", [strtolower($subSlug)]);
            })
            ->first();
        }

        if (!$subCategory && !$businessType) {
            abort(404);
        }

        $entity = $subCategory ?: $businessType;
        $entityName = $entity->name;
        $isSubCategory = $subCategory !== null;

        // Load child types if sub-category
        $childTypes = $isSubCategory
            ? BusinessType::where('business_sub_category_id', $entity->id)->where('status', 1)->get()
            : collect();

        // Load products
        $products = BusinessProduct::where(
            $isSubCategory ? 'business_sub_category_id' : 'business_type_id',
            $entity->id
        )->where('status', 1)->take(50)->get();

        // Fetch templates
        $catIds = Category::where('name', 'like', '%' . $entityName . '%')
            ->orWhere('name', 'like', '%' . $category->name . '%')
            ->pluck('id');
        $templates = CategoryPost::whereIn('category_id', $catIds)
            ->where('status', '1')
            ->latest()
            ->take(24)
            ->get();

        $relatedCategories = BusinessCategory::where('status', 1)
            ->where('id', '!=', $category->id)
            ->inRandomOrder()
            ->take(8)
            ->get();

        $seo = [
            'title' => $entityName . ' Poster Maker — Create ' . $entityName . ' Posters Free | Artera',
            'description' => 'Create professional ' . strtolower($entityName) . ' posters and marketing content with Artera. Free ' . strtolower($entityName) . ' templates with AI-powered customization. Best ' . strtolower($entityName) . ' poster maker app for ' . strtolower($category->name) . ' businesses.',
            'canonical' => config('seo.site_url') . '/poster-maker/' . ($category->slug ?: Str::slug($category->name)) . '/' . ($entity->slug ?: Str::slug($entityName)),
            'keywords' => strtolower($entityName) . ' poster maker, ' . strtolower($entityName) . ' poster, ' . strtolower($entityName) . ' marketing poster, ' . strtolower($entityName) . ' banner, ' . strtolower($entityName) . ' social media post',
            'og_image' => $templates->first() && $templates->first()->frame_image ? $templates->first()->seo_image : null,
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'Poster Maker', 'url' => '/poster-maker'],
                ['name' => $category->name, 'url' => '/poster-maker/' . ($category->slug ?: Str::slug($category->name))],
                ['name' => $entityName . ' Poster Maker'],
            ],
            'collection' => ['count' => $templates->count()],
            'speakable' => true,
            'faq' => [
                ['question' => 'How to make ' . strtolower($entityName) . ' posters?', 'answer' => 'Use Artera app to create professional ' . strtolower($entityName) . ' posters. Choose from ready-made templates, add your business details, and download in HD quality — all in under a minute.'],
                ['question' => 'Is ' . strtolower($entityName) . ' poster maker free?', 'answer' => 'Yes, Artera offers free ' . strtolower($entityName) . ' poster templates. Premium templates with advanced customization are available with a subscription.'],
                ['question' => 'Best app for ' . strtolower($entityName) . ' marketing posters?', 'answer' => 'Artera is the best app for creating ' . strtolower($entityName) . ' marketing posters because it offers AI-powered content specific to the ' . strtolower($category->name) . ' industry with multi-language support.'],
            ],
        ];

        return view('landing.seo.subcategory-landing', compact('category', 'entity', 'isSubCategory', 'childTypes', 'products', 'templates', 'relatedCategories', 'seo'));
    }

    /**
     * /festival-poster — Hub listing all festivals
     */
    public function festivalHub()
    {
        $festivals = Festivals::where('status', 1)->orderBy('festivals_date')->get();

        // Group festivals by month
        $grouped = $festivals->groupBy(function ($item) {
            return $item->festivals_date ? \Carbon\Carbon::parse($item->festivals_date)->format('F') : 'Other';
        });

        $seo = [
            'title' => 'Festival Poster Maker — 365+ Festival Templates | Artera',
            'description' => 'Create beautiful festival posters for Diwali, Navratri, Holi, Eid, Christmas, and 360+ more festivals. Free festival poster maker with AI-powered customization.',
            'canonical' => config('seo.site_url') . '/festival-poster',
            'keywords' => 'festival poster maker, festival poster, diwali poster, navratri poster, holi poster, festival greetings maker, festival social media post',
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'Festival Poster Maker'],
            ],
            'collection' => ['count' => $festivals->count()],
            'speakable' => true,
            'faq' => [
                ['question' => 'How to create festival posters?', 'answer' => 'With Artera, creating festival posters is easy. Select the festival, choose a template, add your business branding, and download. Supports all Indian festivals including Diwali, Navratri, Holi, Ganesh Chaturthi, and more.'],
                ['question' => 'Which festivals are supported in Artera?', 'answer' => 'Artera supports 365+ festivals including Diwali, Navratri, Holi, Ganesh Chaturthi, Eid, Christmas, Independence Day, Republic Day, Makar Sankranti, Raksha Bandhan, and many regional festivals.'],
                ['question' => 'Can I add my business logo to festival posters?', 'answer' => 'Yes! Every festival poster template in Artera supports full business branding — add your logo, business name, contact details, and website to create branded festival greetings.'],
                ['question' => 'Are festival poster templates free?', 'answer' => 'Yes, Artera offers hundreds of free festival poster templates. Premium designs with exclusive features are available with a subscription plan.'],
            ],
        ];

        return view('landing.seo.festival-hub', compact('grouped', 'festivals', 'seo'));
    }

    /**
     * /festival-poster/{festivalSlug} — Individual festival page
     */
    public function festivalLanding($festivalSlug)
    {
        // Find festival by matching slug against title
        $festival = Festivals::where('status', 1)->get()->first(function ($f) use ($festivalSlug) {
            return Str::slug($f->title) === $festivalSlug;
        });

        if (!$festival) abort(404);

        $templates = FestivalsPost::where('festivals_id', $festival->id)
            ->where('status', '1')
            ->where('show_on_landing', 1)
            ->latest()
            ->take(48)
            ->get();

        $templateCount = FestivalsPost::where('festivals_id', $festival->id)->where('status', '1')->count();

        // Related festivals
        $relatedFestivals = Festivals::where('status', 1)
            ->where('id', '!=', $festival->id)
            ->inRandomOrder()
            ->take(12)
            ->get();

        $festName = $festival->title;
        $year = now()->year;
        $seo = [
            'title' => $festName . ' Poster Maker ' . $year . ' — Free ' . $festName . ' Templates | Artera',
            'description' => 'Create stunning ' . $festName . ' ' . $year . ' posters for your business. Browse ' . ($templateCount ?: '50') . '+ free ' . $festName . ' templates. Send branded ' . $festName . ' greetings on WhatsApp, Instagram & Facebook.',
            'canonical' => config('seo.site_url') . '/festival-poster/' . Str::slug($festName),
            'keywords' => strtolower($festName) . ' poster maker, ' . strtolower($festName) . ' poster, ' . strtolower($festName) . ' ' . $year . ', ' . strtolower($festName) . ' wishes images, ' . strtolower($festName) . ' greetings, ' . strtolower($festName) . ' social media post',
            'og_image' => $templates->first() && $templates->first()->frame_image ? $templates->first()->seo_image : null,
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'Festival Poster Maker', 'url' => '/festival-poster'],
                ['name' => $festName . ' Poster Maker'],
            ],
            'collection' => ['count' => $templateCount],
            'speakable' => true,
            'faq' => [
                ['question' => 'How to create ' . $festName . ' poster?', 'answer' => 'Open Artera app, go to Festival Posters section, select ' . $festName . ', choose a template, add your business details and logo, and download the poster in HD quality. Takes less than 1 minute!'],
                ['question' => 'Are ' . $festName . ' poster templates free?', 'answer' => 'Yes, Artera offers many free ' . $festName . ' poster templates. You can customize and download them without any cost.'],
                ['question' => 'Can I share ' . $festName . ' posters on WhatsApp?', 'answer' => 'Absolutely! After creating your ' . $festName . ' poster in Artera, you can directly share it on WhatsApp, Instagram, Facebook, or any other platform.'],
                ['question' => 'When is ' . $festName . ' ' . $year . '?', 'answer' => $festName . ' ' . $year . ' is on ' . ($festival->festivals_date ? \Carbon\Carbon::parse($festival->festivals_date)->format('F j, Y') : 'the traditional date') . '. Start creating your ' . $festName . ' marketing posters early with Artera!'],
            ],
        ];

        return view('landing.seo.festival-landing', compact('festival', 'templates', 'templateCount', 'relatedFestivals', 'seo'));
    }

    /**
     * /template/{id}/{slug?} — Individual template page
     */
    public function templatePage($id, $slug = null)
    {
        $prefix = substr($id, 0, 1);
        $numericId = substr($id, 1);
        $template = null;
        $type = '';
        $parentName = '';
        $relatedTemplates = collect();

        switch ($prefix) {
            case 'f': // Festival post
                $template = FestivalsPost::with('festivals')->find($numericId);
                if ($template) {
                    $type = 'festival';
                    $parentName = $template->festivals ? $template->festivals->title : 'Festival';
                    $relatedTemplates = FestivalsPost::where('festivals_id', $template->festivals_id)
                        ->where('id', '!=', $template->id)
                        ->where('status', '1')
                        ->inRandomOrder()->take(12)->get();
                }
                break;

            case 'c': // Category post
                $template = CategoryPost::with('category')->find($numericId);
                if ($template) {
                    $type = 'category';
                    $parentName = $template->category ? $template->category->name : 'Business';
                    $relatedTemplates = CategoryPost::where('category_id', $template->category_id)
                        ->where('id', '!=', $template->id)
                        ->where('status', '1')
                        ->inRandomOrder()->take(12)->get();
                }
                break;

            case 'p': // Custom post frame
                $template = CustomPostFrame::with('custom_post')->find($numericId);
                if ($template) {
                    $type = 'custom';
                    $parentName = $template->custom_post ? $template->custom_post->name : 'Custom';
                    $relatedTemplates = CustomPostFrame::where('custom_post_id', $template->custom_post_id)
                        ->where('id', '!=', $template->id)
                        ->where('status', '1')
                        ->inRandomOrder()->take(12)->get();
                }
                break;

            default:
                abort(404);
        }

        if (!$template) abort(404);

        $templateName = $parentName . ' Poster Template';
        $imageUrl = $template->frame_image ? $template->seo_image : null;

        $seo = [
            'title' => $templateName . ' — Free Download & Customize | Artera',
            'description' => 'Download this professional ' . strtolower($templateName) . ' for free. Customize with your business logo, details, and branding. Perfect for WhatsApp, Instagram & Facebook marketing.',
            'canonical' => config('seo.site_url') . '/template/' . $id . '/' . Str::slug($templateName),
            'keywords' => strtolower($parentName) . ' poster, ' . strtolower($parentName) . ' poster template, ' . strtolower($parentName) . ' poster download, ' . strtolower($parentName) . ' marketing poster',
            'og_image' => $imageUrl,
            'og_type' => 'article',
            'breadcrumbs' => $this->getTemplateBreadcrumbs($type, $template, $parentName),
            'howto' => [
                'name' => 'How to Use This ' . $templateName,
                'description' => 'Customize and download this ' . strtolower($templateName) . ' in 3 easy steps.',
                'total_time' => 'PT1M',
                'steps' => [
                    ['name' => 'Open in Artera App', 'text' => 'Download the Artera app and find this ' . strtolower($parentName) . ' poster template in the template gallery.'],
                    ['name' => 'Customize With Your Brand', 'text' => 'Add your business logo, contact details, and customize colors to match your brand identity.'],
                    ['name' => 'Download & Share', 'text' => 'Download the poster in HD quality and share on WhatsApp, Instagram, Facebook, or print for your business.'],
                ],
            ],
        ];

        // ImageObject custom schema
        if ($imageUrl) {
            $seo['custom_schema'] = [
                '@context' => 'https://schema.org',
                '@type' => 'ImageObject',
                'name' => $templateName,
                'description' => 'Professional ' . strtolower($templateName) . ' designed for business marketing',
                'contentUrl' => $imageUrl,
                'thumbnailUrl' => $imageUrl,
                'uploadDate' => $template->created_at ? $template->created_at->toW3cString() : now()->toW3cString(),
                'author' => [
                    '@type' => 'Organization',
                    'name' => 'Artera',
                ],
            ];
        }

        return view('landing.seo.template-page', compact('template', 'type', 'parentName', 'templateName', 'imageUrl', 'relatedTemplates', 'seo'));
    }

    public function useCaseHub($slug)
    {
        $config = config('seo_pages.use_case_hubs.' . $slug);
        if (!$config) abort(404);

        $templates = \App\Models\CategoryPost::where('status', '1')->latest()->take(24)->get();
        $seo = $config;
        $seo['canonical'] = config('seo.site_url') . '/' . $slug;
        
        return view('landing.seo.use-case-hub', compact('templates', 'seo'));
    }

    public function socialPlatformPage($slug)
    {
        $config = config('seo_pages.social_platform_pages.' . $slug);
        if (!$config) abort(404);

        $templates = \App\Models\CategoryPost::where('status', '1')->latest()->take(24)->get();
        $seo = $config;
        $seo['canonical'] = config('seo.site_url') . '/' . $slug;
        
        return view('landing.seo.social-platform-page', compact('templates', 'seo'));
    }

    public function industryVanityPage($slug)
    {
        $config = config('seo_pages.industry_vanity_urls.' . $slug);
        if (!$config) abort(404);

        $categorySlug = $config['category_slug'];
        // Re-use category logic but override SEO
        $category = BusinessCategory::where('slug', $categorySlug)->firstOrFail();
        $subCategories = BusinessSubCategory::where('business_category_id', $category->id)->where('status', 1)->withCount('types')->get();
        $catIds = Category::where('name', 'like', '%' . $category->name . '%')->pluck('id');
        $templates = CategoryPost::whereIn('category_id', $catIds)->where('status', '1')->latest()->take(24)->get();
        $templateCount = CategoryPost::whereIn('category_id', $catIds)->where('status', '1')->count();
        $relatedCategories = BusinessCategory::where('status', 1)->where('id', '!=', $category->id)->inRandomOrder()->take(10)->get();

        $seo = [
            'title' => $config['primary_keyword'] . ' | Artera',
            'h1' => $config['h1'],
            'description' => 'Create stunning ' . strtolower($config['primary_keyword']) . ' posters. AI-powered poster maker.',
            'canonical' => config('seo.site_url') . '/' . $slug,
            'keywords' => strtolower($config['primary_keyword']) . ' poster maker, ' . strtolower($config['primary_keyword']),
            'speakable' => true,
        ];

        return view('landing.seo.industry-vanity', compact('category', 'subCategories', 'templates', 'templateCount', 'relatedCategories', 'seo'));
    }

    public function festivalVanityPage($slug)
    {
        $config = config('seo_pages.festival_vanity_urls.' . $slug);
        if (!$config) abort(404);

        $festivalSlug = $config['festival_slug'];
        $festival = Festivals::where('status', 1)->get()->first(function ($f) use ($festivalSlug) {
            return Str::slug($f->title) === $festivalSlug;
        });
        if (!$festival) abort(404);

        $templates = FestivalsPost::where('festivals_id', $festival->id)->where('status', '1')->latest()->take(48)->get();
        $templateCount = FestivalsPost::where('festivals_id', $festival->id)->where('status', '1')->count();
        $relatedFestivals = Festivals::where('status', 1)->where('id', '!=', $festival->id)->inRandomOrder()->take(12)->get();

        $seo = [
            'title' => $config['primary_keyword'] . ' | Artera',
            'description' => 'Create stunning ' . strtolower($config['primary_keyword']) . ' for your business.',
            'canonical' => config('seo.site_url') . '/' . $slug,
            'keywords' => strtolower($config['primary_keyword']),
            'speakable' => true,
        ];

        return view('landing.seo.festival-vanity', compact('festival', 'templates', 'templateCount', 'relatedFestivals', 'seo'));
    }

    public function longTailPage($slug)
    {
        $config = config('seo_pages.long_tail_pages.' . $slug);
        if (!$config) abort(404);

        $templates = \App\Models\CategoryPost::where('status', '1')->latest()->take(24)->get();
        $seo = $config;
        $seo['canonical'] = config('seo.site_url') . '/' . $slug;
        
        return view('landing.seo.long-tail', compact('templates', 'seo'));
    }

    /**
     * Build breadcrumbs for template page based on type
     */
    private function getTemplateBreadcrumbs($type, $template, $parentName)
    {
        $crumbs = [['name' => 'Home', 'url' => '/']];

        if ($type === 'festival') {
            $crumbs[] = ['name' => 'Festival Poster Maker', 'url' => '/festival-poster'];
            if ($template->festivals) {
                $crumbs[] = ['name' => $template->festivals->title, 'url' => '/festival-poster/' . Str::slug($template->festivals->title)];
            }
        } else {
            $crumbs[] = ['name' => 'Poster Maker', 'url' => '/poster-maker'];
            $crumbs[] = ['name' => $parentName, 'url' => '/poster-maker/' . Str::slug($parentName)];
        }

        $crumbs[] = ['name' => $parentName . ' Poster Template'];
        return $crumbs;
    }
}
