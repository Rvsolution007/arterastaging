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

        $name = $site->business_name ?? ($business ? $business->name : 'Your Business');
        $phone = $site->mobile_no ?? ($business ? $business->mobile_no : '1234567890');
        $email = $site->email ?? ($business ? $business->email : 'demo@example.com');
        $address = $site->address ?? ($business ? $business->address : 'Your Address Here');
        $website = $site->website ?? ($business ? $business->website : 'www.example.com');
        $aboutUs = $site->about_us ?? 'We provide the best services and products for our customers. Contact us today!';
        $productsServices = $site->products_services ?? 'Our Premium Services';
        $facebook = $site->facebook ?? '#';
        $twitter = $site->twitter ?? '#';
        $instagram = $site->instagram ?? '#';
        $youtube = $site->youtube ?? '#';
        $linkedin = $site->linkedin ?? '#';

        $logoUrl = '';
        if ($site->logo) {
            $logoUrl = asset('public/uploads/' . $site->logo);
        } else if ($business && $business->logo) {
            $logoUrl = asset('images/business/' . $business->logo);
        }

        $mapUrl = $site->map_url ?? '#';
        $whatsappNumber = $site->whatsapp_number ?? '';
        $clientsCount = $site->clients_count ?? '0';
        $yearsExperience = $site->years_experience ?? '0';

        $html = str_replace('[[BUSINESS_NAME]]', $name, $html);
        $html = str_replace('[[PHONE]]', $phone, $html);
        $html = str_replace('[[EMAIL]]', $email, $html);
        $html = str_replace('[[ADDRESS]]', $address, $html);
        $html = str_replace('[[WEBSITE]]', $website, $html);
        $html = str_replace('[[ABOUT_US]]', $aboutUs, $html);
        $html = str_replace('[[PRODUCTS_SERVICES]]', $productsServices, $html);
        $html = str_replace('[[FACEBOOK]]', $facebook, $html);
        $html = str_replace('[[TWITTER]]', $twitter, $html);
        $html = str_replace('[[INSTAGRAM]]', $instagram, $html);
        $html = str_replace('[[YOUTUBE]]', $youtube, $html);
        $html = str_replace('[[LINKEDIN]]', $linkedin, $html);
        $html = str_replace('[[LOGO_URL]]', $logoUrl, $html);
        $html = str_replace('[[MAP_URL]]', $mapUrl, $html);
        $html = str_replace('[[WHATSAPP_NUMBER]]', $whatsappNumber, $html);
        $html = str_replace('[[CLIENTS_COUNT]]', $clientsCount, $html);
        $html = str_replace('[[YEARS_EXPERIENCE]]', $yearsExperience, $html);

        // Auto-hide script for empty placeholders
        $hideScript = "
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hide elements with empty or placeholder hrefs
            document.querySelectorAll('a').forEach(function(a) {
                const href = a.getAttribute('href');
                if (!href || href === '#' || href === 'https://wa.me/' || href === 'mailto:' || href === 'tel:' || href.includes('[[')) {
                    a.style.display = 'none';
                }
            });
            // Hide elements that contain raw placeholders in their text
            const walk = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null, false);
            let n;
            const nodesToHide = new Set();
            while(n = walk.nextNode()) {
                if (n.nodeValue.includes('[[')) {
                    nodesToHide.add(n.parentElement);
                }
            }
            nodesToHide.forEach(el => el.style.display = 'none');
        });
        </script>
        ";

        $html = str_replace('</body>', $hideScript . '</body>', $html);

        return response($html)->header('Content-Type', 'text/html');
    }
}
