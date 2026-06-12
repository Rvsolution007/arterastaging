<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function show($slug)
    {
        $site = \App\Models\UserMiniWebsite::where('slug', $slug)->firstOrFail();
        
        // Increment view count
        $site->increment('views_count');

        $template = \App\Models\MiniWebsiteTemplate::find($site->mini_website_template_id);
        if (!$template || $template->status == 0) {
            abort(404, 'Template not found or inactive');
        }

        // Fetch user and business data
        $user = \App\Models\User::find($site->user_id);
        $business = \App\Models\Business::find($site->business_id);

        if (!$business) {
            // Fallback if business isn't directly assigned, get the user's first business
            $business = \App\Models\Business::where('user_id', $site->user_id)->first();
        }

        $html = $template->html_content;

        if ($business) {
            $html = str_replace('[[BUSINESS_NAME]]', $business->name ?? '', $html);
            $html = str_replace('[[PHONE]]', $business->mobile_no ?? '', $html);
            $html = str_replace('[[EMAIL]]', $business->email ?? '', $html);
            $html = str_replace('[[ADDRESS]]', $business->address ?? '', $html);
            $html = str_replace('[[WEBSITE]]', $business->website ?? '', $html);
            $html = str_replace('[[ABOUT_US]]', $business->about_us ?? 'We provide the best services and products for our customers. Contact us today!', $html);
            $html = str_replace('[[FACEBOOK]]', $business->facebook ?? '#', $html);
            $html = str_replace('[[TWITTER]]', $business->twitter ?? '#', $html);
            $html = str_replace('[[INSTAGRAM]]', $business->instagram ?? '#', $html);
            $html = str_replace('[[YOUTUBE]]', $business->youtube ?? '#', $html);
            $html = str_replace('[[LINKEDIN]]', $business->linkedin ?? '#', $html);
            
            $logoUrl = $business->logo ? asset('images/business/' . $business->logo) : '';
            $html = str_replace('[[LOGO_URL]]', $logoUrl, $html);
        } else {
            // Empty if no business
            $html = str_replace('[[BUSINESS_NAME]]', 'Your Business', $html);
            $html = str_replace('[[PHONE]]', '1234567890', $html);
            $html = str_replace('[[EMAIL]]', 'demo@example.com', $html);
            $html = str_replace('[[ADDRESS]]', 'Your Address Here', $html);
            $html = str_replace('[[WEBSITE]]', 'www.example.com', $html);
            $html = str_replace('[[ABOUT_US]]', 'We provide the best services and products.', $html);
            $html = str_replace('[[FACEBOOK]]', '#', $html);
            $html = str_replace('[[TWITTER]]', '#', $html);
            $html = str_replace('[[INSTAGRAM]]', '#', $html);
            $html = str_replace('[[YOUTUBE]]', '#', $html);
            $html = str_replace('[[LINKEDIN]]', '#', $html);
            $html = str_replace('[[LOGO_URL]]', '', $html);
        }

        return response($html)->header('Content-Type', 'text/html');
    }
}
