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

        return view('landing.home', compact('festivalsByDate', 'homeBanners'));
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
                        'image' => $f->frame_image ? ($isDigitalOcean ? Storage::disk('spaces')->url('uploads/'.$f->frame_image) : asset('uploads/'.$f->frame_image)) : '',
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
                        'image' => $c->frame_image ? ($isDigitalOcean ? Storage::disk('spaces')->url('uploads/'.$c->frame_image) : asset('uploads/'.$c->frame_image)) : '',
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
        return view('landing.about');
    }

    public function features()
    {
        return view('landing.features');
    }

    public function packages()
    {
        $packages = Subscription::where('status', '1')->get();
        return view('landing.packages', compact('packages'));
    }

    public function reviews()
    {
        return view('landing.reviews');
    }

    public function contact()
    {
        return view('landing.contact');
    }

    public function blogs()
    {
        // Assuming status 'published' is published
        $blogs = Blog::where('status', 'published')->latest()->paginate(9);
        return view('landing.blogs', compact('blogs'));
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

        return view('landing.blog-details', compact('blog', 'relatedBlogs'));
    }

    public function templates()
    {
        $festivals = \App\Models\FestivalsPost::with('festivals')->where('status', '1')->where('show_on_landing', 1)->latest()->take(12)->get();
        $categories = \App\Models\Category::where('status', '1')->latest()->take(12)->get();
        $customPosts = \App\Models\CustomPostFrame::with('custom_post')->where('status', '1')->where('show_on_landing', 1)->latest()->take(12)->get();
        return view('landing.templates', compact('festivals', 'categories', 'customPosts'));
    }

    public function category($slug)
    {
        $category = \App\Models\Category::where('id', $slug)->orWhere('name', 'like', '%' . str_replace('-', ' ', $slug) . '%')->firstOrFail();
        $posts = \App\Models\CategoryPost::where('category_id', $category->id)->latest()->paginate(12);
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
