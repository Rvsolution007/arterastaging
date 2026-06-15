<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MiniWebsiteApiController extends Controller
{
    public function templates()
    {
        $templates = \App\Models\MiniWebsiteTemplate::where('status', 1)->orderBy('id', 'desc')->get();
        
        // Add full URL to preview image
        foreach ($templates as $template) {
            if ($template->preview_image) {
                $template->preview_image = asset('public/uploads/' . $template->preview_image);
            }
            // we probably don't need to send full HTML in the list api to save bandwidth
            unset($template->html_content);
        }

        return response()->json([
            'status' => 'success',
            'data' => $templates
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'mini_website_template_id' => 'required'
        ]);

        $slug = \Illuminate\Support\Str::random(6) . '-' . time();
        
        $site = new \App\Models\UserMiniWebsite();
        $site->user_id = $request->user_id;
        $site->business_id = $request->business_id; // optional
        $site->mini_website_template_id = $request->mini_website_template_id;
        $site->slug = $slug;
        $site->views_count = 0;

        // Snapshot business details if provided
        if ($request->business_id) {
            $business = \App\Models\Business::find($request->business_id);
            if ($business) {
                $site->business_name = $business->name;
                $site->email = $business->email;
                $site->mobile_no = $business->mobile_no;
                $site->website = $business->website;
                $site->address = $business->address;
                $site->logo = $business->logo;
            }
        }

        $site->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Website generated successfully!',
            'data' => [
                'id' => $site->id,
                'slug' => $site->slug,
                'url' => url('/site/' . $site->slug)
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $site = \App\Models\UserMiniWebsite::find($id);
        if (!$site) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        $site->business_name = $request->business_name;
        $site->email = $request->email;
        $site->mobile_no = $request->mobile_no;
        $site->website = $request->website;
        $site->address = $request->address;
        $site->about_us = $request->about_us;
        $site->products_services = $request->products_services;
        $site->facebook = $request->facebook;
        $site->twitter = $request->twitter;
        $site->instagram = $request->instagram;
        $site->youtube = $request->youtube;
        $site->linkedin = $request->linkedin;
        $site->map_url = $request->map_url;
        $site->whatsapp_number = $request->whatsapp_number;
        $site->clients_count = $request->clients_count;
        $site->years_experience = $request->years_experience;

        if ($request->hasFile('logo')) {
            $destinationPath = public_path('uploads');
            $extension = $request->file('logo')->getClientOriginalExtension();
            $fileName = \Illuminate\Support\Str::uuid() . '.' . $extension;
            $request->file('logo')->move($destinationPath, $fileName);
            $site->logo = $fileName;
        }

        $site->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Website details updated successfully!'
        ]);
    }

    public function myLinks(Request $request)
    {
        $userId = $request->user_id;
        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'user_id is required']);
        }

        $sites = \App\Models\UserMiniWebsite::where('user_id', $userId)
            ->join('mini_website_templates', 'user_mini_websites.mini_website_template_id', '=', 'mini_website_templates.id')
            ->select('user_mini_websites.*', 'mini_website_templates.name as template_name', 'mini_website_templates.preview_image')
            ->orderBy('user_mini_websites.id', 'desc')
            ->get();

        foreach ($sites as $site) {
            if ($site->preview_image) {
                $site->preview_image = asset('uploads/' . $site->preview_image);
            }
            $site->url = url('/site/' . $site->slug);
        }

        return response()->json([
            'status' => 'success',
            'data' => $sites
        ]);
    }
    public function delete($id)
    {
        $site = \App\Models\UserMiniWebsite::find($id);
        if (!$site) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }
        $site->delete();
        return response()->json(['status' => 'success', 'message' => 'Website deleted successfully']);
    }
}
