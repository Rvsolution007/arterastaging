<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Blog;

class LandingController extends Controller
{
    public function home()
    {
        return view('landing.home');
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
        $businessPosts = \App\Models\CategoryPost::with('category')->where('status', '1')->where('show_on_landing', 1)->latest()->take(12)->get();
        $customPosts = \App\Models\CustomPostFrame::with('custom_post')->where('status', '1')->where('show_on_landing', 1)->latest()->take(12)->get();
        return view('landing.templates', compact('festivals', 'businessPosts', 'customPosts'));
    }

    public function category($slug)
    {
        $category = \App\Models\BusinessCategory::where('id', $slug)->orWhere('name', 'like', '%' . str_replace('-', ' ', $slug) . '%')->firstOrFail();
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
