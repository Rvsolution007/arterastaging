<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use App\Models\BusinessType;
use App\Models\Festivals;
use App\Models\FestivalsPost;
use App\Models\CategoryPost;
use App\Models\CustomPostFrame;
use App\Models\Blog;
use App\Models\Category;

class SeoController extends Controller
{
    private function xmlHeader()
    {
        return '<?xml version="1.0" encoding="UTF-8"?>';
    }

    /**
     * Sitemap Index — points to all sub-sitemaps
     */
    public function sitemapIndex()
    {
        $content = Cache::remember('sitemap_index', 86400, function () {
            $baseUrl = config('seo.site_url', 'https://arterapixel.com');
            $xml = $this->xmlHeader() . "\n";
            $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            $sitemaps = [
                'sitemap-usecases.xml',
                'sitemap-pages.xml',
                'sitemap-categories.xml',
                'sitemap-festivals.xml',
                'sitemap-templates.xml',
                'sitemap-blog.xml',
                'sitemap-images.xml',
            ];

            foreach ($sitemaps as $sitemap) {
                $xml .= '  <sitemap>' . "\n";
                $xml .= '    <loc>' . $baseUrl . '/' . $sitemap . '</loc>' . "\n";
                $xml .= '    <lastmod>' . now()->toW3cString() . '</lastmod>' . "\n";
                $xml .= '  </sitemap>' . "\n";
            }

            $xml .= '</sitemapindex>';
            return $xml;
        });

        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Usecases sitemap (from config)
     */
    public function sitemapUsecases()
    {
        $content = Cache::remember('sitemap_usecases', 86400, function () {
            $baseUrl = config('seo.site_url', 'https://arterapixel.com');
            $pages = [];
            $seoPages = config('seo_pages', []);
            
            $groups = ['use_case_hubs', 'social_platform_pages', 'industry_vanity_urls', 'festival_vanity_urls', 'long_tail_pages'];
            foreach ($groups as $group) {
                if (isset($seoPages[$group])) {
                    foreach ($seoPages[$group] as $slug => $data) {
                        $pages[] = [
                            'loc' => '/' . $slug,
                            'changefreq' => 'weekly',
                            'priority' => '0.9',
                        ];
                    }
                }
            }

            return $this->buildUrlSet($baseUrl, $pages);
        });

        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Static pages sitemap
     */
    public function sitemapPages()
    {
        $content = Cache::remember('sitemap_pages', 86400, function () {
            $baseUrl = config('seo.site_url', 'https://arterapixel.com');
            $pages = [
                ['loc' => '/', 'changefreq' => 'daily', 'priority' => '1.0'],
                ['loc' => '/poster-maker', 'changefreq' => 'weekly', 'priority' => '0.9'],
                ['loc' => '/festival-poster', 'changefreq' => 'weekly', 'priority' => '0.9'],
                ['loc' => '/templates', 'changefreq' => 'daily', 'priority' => '0.9'],
                ['loc' => '/features', 'changefreq' => 'monthly', 'priority' => '0.8'],
                ['loc' => '/about', 'changefreq' => 'monthly', 'priority' => '0.7'],
                ['loc' => '/packages', 'changefreq' => 'monthly', 'priority' => '0.8'],
                ['loc' => '/reviews', 'changefreq' => 'monthly', 'priority' => '0.7'],
                ['loc' => '/blogs', 'changefreq' => 'daily', 'priority' => '0.8'],
                ['loc' => '/contact', 'changefreq' => 'monthly', 'priority' => '0.6'],
                ['loc' => '/digital-business-cards', 'changefreq' => 'monthly', 'priority' => '0.7'],
                ['loc' => '/logo-maker', 'changefreq' => 'monthly', 'priority' => '0.7'],
                ['loc' => '/video-maker', 'changefreq' => 'monthly', 'priority' => '0.7'],
                ['loc' => '/privacy-policy', 'changefreq' => 'yearly', 'priority' => '0.3'],
                ['loc' => '/terms-condition', 'changefreq' => 'yearly', 'priority' => '0.3'],
                ['loc' => '/refund-policy', 'changefreq' => 'yearly', 'priority' => '0.3'],
                ['loc' => '/sitemap', 'changefreq' => 'weekly', 'priority' => '0.5'],
            ];

            return $this->buildUrlSet($baseUrl, $pages);
        });

        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Business categories, sub-categories, and types sitemap
     */
    public function sitemapCategories()
    {
        $content = Cache::remember('sitemap_categories', 86400, function () {
            $baseUrl = config('seo.site_url', 'https://arterapixel.com');
            $pages = [];

            // Business Categories
            $categories = BusinessCategory::where('status', 1)->get();
            foreach ($categories as $cat) {
                $slug = $cat->slug ?: Str::slug($cat->name);
                $pages[] = [
                    'loc' => '/poster-maker/' . $slug,
                    'changefreq' => 'weekly',
                    'priority' => '0.9',
                    'lastmod' => $cat->updated_at ? $cat->updated_at->toW3cString() : now()->toW3cString(),
                ];
            }

            // Sub-Categories
            $subCategories = BusinessSubCategory::where('status', 1)->with('business_category')->get();
            foreach ($subCategories as $sub) {
                $catSlug = $sub->business_category ? ($sub->business_category->slug ?: Str::slug($sub->business_category->name)) : 'general';
                $subSlug = $sub->slug ?: Str::slug($sub->name);
                $pages[] = [
                    'loc' => '/poster-maker/' . $catSlug . '/' . $subSlug,
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                    'lastmod' => $sub->updated_at ? $sub->updated_at->toW3cString() : now()->toW3cString(),
                ];
            }

            // Business Types
            $types = BusinessType::where('status', 1)->with('business_sub_category.business_category')->get();
            foreach ($types as $type) {
                $catSlug = 'general';
                if ($type->business_sub_category && $type->business_sub_category->business_category) {
                    $catSlug = $type->business_sub_category->business_category->slug ?: Str::slug($type->business_sub_category->business_category->name);
                }
                $typeSlug = $type->slug ?: Str::slug($type->name);
                $pages[] = [
                    'loc' => '/poster-maker/' . $catSlug . '/' . $typeSlug,
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                    'lastmod' => $type->updated_at ? $type->updated_at->toW3cString() : now()->toW3cString(),
                ];
            }

            return $this->buildUrlSet($baseUrl, $pages);
        });

        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Festivals sitemap
     */
    public function sitemapFestivals()
    {
        $content = Cache::remember('sitemap_festivals', 86400, function () {
            $baseUrl = config('seo.site_url', 'https://arterapixel.com');
            $pages = [];

            $festivals = Festivals::where('status', 1)->get();
            foreach ($festivals as $fest) {
                $slug = Str::slug($fest->title);
                $pages[] = [
                    'loc' => '/festival-poster/' . $slug,
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                    'lastmod' => $fest->updated_at ? $fest->updated_at->toW3cString() : now()->toW3cString(),
                ];
            }

            return $this->buildUrlSet($baseUrl, $pages);
        });

        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Templates sitemap
     */
    public function sitemapTemplates()
    {
        $content = Cache::remember('sitemap_templates', 86400, function () {
            $baseUrl = config('seo.site_url', 'https://arterapixel.com');
            $pages = [];

            // Festival templates
            $festivalPosts = FestivalsPost::where('status', '1')->with('festivals')->get();
            foreach ($festivalPosts as $post) {
                $name = $post->festivals ? Str::slug($post->festivals->title . ' poster template') : 'festival-poster-' . $post->id;
                $pages[] = [
                    'loc' => '/template/f' . $post->id . '/' . $name,
                    'changefreq' => 'monthly',
                    'priority' => '0.7',
                    'lastmod' => $post->updated_at ? $post->updated_at->toW3cString() : now()->toW3cString(),
                ];
            }

            // Category/Business templates
            $catPosts = CategoryPost::where('status', '1')->with('category')->get();
            foreach ($catPosts as $post) {
                $name = $post->category ? Str::slug($post->category->name . ' poster template') : 'business-poster-' . $post->id;
                $pages[] = [
                    'loc' => '/template/c' . $post->id . '/' . $name,
                    'changefreq' => 'monthly',
                    'priority' => '0.7',
                    'lastmod' => $post->updated_at ? $post->updated_at->toW3cString() : now()->toW3cString(),
                ];
            }

            // Custom post frames
            $customPosts = CustomPostFrame::where('status', '1')->with('custom_post')->get();
            foreach ($customPosts as $post) {
                $name = $post->custom_post ? Str::slug($post->custom_post->name . ' poster template') : 'custom-poster-' . $post->id;
                $pages[] = [
                    'loc' => '/template/p' . $post->id . '/' . $name,
                    'changefreq' => 'monthly',
                    'priority' => '0.7',
                    'lastmod' => $post->updated_at ? $post->updated_at->toW3cString() : now()->toW3cString(),
                ];
            }

            return $this->buildUrlSet($baseUrl, $pages);
        });

        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Blog sitemap
     */
    public function sitemapBlog()
    {
        $content = Cache::remember('sitemap_blog', 86400, function () {
            $baseUrl = config('seo.site_url', 'https://arterapixel.com');
            $pages = [];

            $blogs = Blog::where('status', 'published')->get();
            foreach ($blogs as $blog) {
                $pages[] = [
                    'loc' => '/blog/' . $blog->slug,
                    'changefreq' => 'weekly',
                    'priority' => '0.6',
                    'lastmod' => $blog->updated_at ? $blog->updated_at->toW3cString() : now()->toW3cString(),
                ];
            }

            return $this->buildUrlSet($baseUrl, $pages);
        });

        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Image sitemap for template thumbnails
     */
    public function sitemapImages()
    {
        $content = Cache::remember('sitemap_images', 86400, function () {
            $baseUrl = config('seo.site_url', 'https://arterapixel.com');
            $xml = $this->xmlHeader() . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
            $xml .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

            // Festival post images
            $festivalPosts = FestivalsPost::where('status', '1')->with('festivals')->take(5000)->get();
            foreach ($festivalPosts as $post) {
                if (!$post->frame_image) continue;
                $name = $post->festivals ? $post->festivals->title . ' Poster Template' : 'Festival Poster Template';
                $slug = Str::slug($name);
                $imageUrl = $baseUrl . '/uploads/' . $post->frame_image;
                $pageUrl = $baseUrl . '/template/f' . $post->id . '/' . $slug;

                $xml .= '  <url>' . "\n";
                $xml .= '    <loc>' . htmlspecialchars($pageUrl) . '</loc>' . "\n";
                $xml .= '    <image:image>' . "\n";
                $xml .= '      <image:loc>' . htmlspecialchars($imageUrl) . '</image:loc>' . "\n";
                $xml .= '      <image:title>' . htmlspecialchars($name) . '</image:title>' . "\n";
                $xml .= '      <image:caption>Professional ' . htmlspecialchars($name) . ' - Free download and customize with Artera</image:caption>' . "\n";
                $xml .= '    </image:image>' . "\n";
                $xml .= '  </url>' . "\n";
            }

            // Category post images
            $catPosts = CategoryPost::where('status', '1')->with('category')->take(5000)->get();
            foreach ($catPosts as $post) {
                if (!$post->frame_image) continue;
                $name = $post->category ? $post->category->name . ' Poster Template' : 'Business Poster Template';
                $slug = Str::slug($name);
                $imageUrl = $baseUrl . '/uploads/' . $post->frame_image;
                $pageUrl = $baseUrl . '/template/c' . $post->id . '/' . $slug;

                $xml .= '  <url>' . "\n";
                $xml .= '    <loc>' . htmlspecialchars($pageUrl) . '</loc>' . "\n";
                $xml .= '    <image:image>' . "\n";
                $xml .= '      <image:loc>' . htmlspecialchars($imageUrl) . '</image:loc>' . "\n";
                $xml .= '      <image:title>' . htmlspecialchars($name) . '</image:title>' . "\n";
                $xml .= '      <image:caption>Professional ' . htmlspecialchars($name) . ' for business marketing - Artera</image:caption>' . "\n";
                $xml .= '    </image:image>' . "\n";
                $xml .= '  </url>' . "\n";
            }

            $xml .= '</urlset>';
            return $xml;
        });

        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * RSS Feed — Latest blog posts
     */
    public function rssFeed()
    {
        $content = Cache::remember('rss_feed', 3600, function () {
            $baseUrl = config('seo.site_url', 'https://arterapixel.com');
            $blogs = Blog::where('status', 'published')->latest()->take(50)->get();

            $xml = $this->xmlHeader() . "\n";
            $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
            $xml .= '<channel>' . "\n";
            $xml .= '  <title>Artera Blog — Poster Design &amp; Marketing Tips</title>' . "\n";
            $xml .= '  <link>' . $baseUrl . '/blogs</link>' . "\n";
            $xml .= '  <description>Latest poster design tips, marketing ideas, and business branding guides from Artera.</description>' . "\n";
            $xml .= '  <language>en-in</language>' . "\n";
            $xml .= '  <atom:link href="' . $baseUrl . '/feed" rel="self" type="application/rss+xml"/>' . "\n";

            foreach ($blogs as $blog) {
                $xml .= '  <item>' . "\n";
                $xml .= '    <title>' . htmlspecialchars($blog->title ?? '') . '</title>' . "\n";
                $xml .= '    <link>' . $baseUrl . '/blog/' . ($blog->slug ?? '') . '</link>' . "\n";
                $xml .= '    <description>' . htmlspecialchars(Str::limit(strip_tags($blog->content ?? ''), 300)) . '</description>' . "\n";
                $xml .= '    <pubDate>' . ($blog->created_at ? $blog->created_at->toRfc2822String() : '') . '</pubDate>' . "\n";
                $xml .= '    <guid>' . $baseUrl . '/blog/' . ($blog->slug ?? '') . '</guid>' . "\n";
                $xml .= '  </item>' . "\n";
            }

            $xml .= '</channel>' . "\n";
            $xml .= '</rss>';
            return $xml;
        });

        return response($content, 200)->header('Content-Type', 'application/rss+xml');
    }

    /**
     * HTML Sitemap — User-facing, SEO-friendly sitemap page
     */
    public function htmlSitemap()
    {
        $categories = BusinessCategory::where('status', 1)->with('subCategories')->get();
        $festivals = Festivals::where('status', 1)->orderBy('festivals_date')->get();
        $blogs = Blog::where('status', 'published')->latest()->take(50)->get();

        $seo = [
            'title' => 'Sitemap — All Pages | Artera',
            'description' => 'Browse all pages on Artera. Find poster makers by business category, festival templates, blog posts, and more.',
            'canonical' => config('seo.site_url') . '/sitemap',
            'robots' => 'index, follow',
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'Sitemap'],
            ],
        ];

        return view('landing.sitemap', compact('categories', 'festivals', 'blogs', 'seo'));
    }

    /**
     * Clear all sitemap caches
     */
    public function clearSitemapCache()
    {
        $keys = ['sitemap_index', 'sitemap_usecases', 'sitemap_pages', 'sitemap_categories', 'sitemap_festivals', 'sitemap_templates', 'sitemap_blog', 'sitemap_images', 'rss_feed'];
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        return response()->json(['message' => 'Sitemap cache cleared']);
    }

    /**
     * Build a standard URL set XML string
     */
    private function buildUrlSet($baseUrl, $pages)
    {
        $xml = $this->xmlHeader() . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($pages as $page) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($baseUrl . $page['loc']) . '</loc>' . "\n";
            if (!empty($page['lastmod'])) {
                $xml .= '    <lastmod>' . $page['lastmod'] . '</lastmod>' . "\n";
            }
            $xml .= '    <changefreq>' . ($page['changefreq'] ?? 'weekly') . '</changefreq>' . "\n";
            $xml .= '    <priority>' . ($page['priority'] ?? '0.5') . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }
}
