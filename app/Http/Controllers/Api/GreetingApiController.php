<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GreetingCategory;
use App\Models\Greeting;
use App\Models\StorageSetting;
use Illuminate\Support\Facades\Storage;

class GreetingApiController extends Controller
{
    public function categories(Request $request)
    {
        $limit = $request->get('limit', 20);
        $offset = $request->get('offset', 0);

        $categories = GreetingCategory::where('status', 1)
            ->skip($offset)
            ->take($limit)
            ->get();

        $data = [];
        foreach ($categories as $category) {
            $icon = '';
            if ($category->icon) {
                if (StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                    $icon = Storage::disk('spaces')->url('uploads/' . $category->icon);
                } else {
                    $icon = asset('uploads/' . $category->icon);
                }
            }

            $data[] = [
                'customCategoryId' => $category->id,
                'customCategoryName' => $category->name,
                'customCategoryIcon' => $icon,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function get_greetings_by_category(Request $request)
    {
        $category_id = $request->get('id'); // using 'id' to match DetailListScreen GET param structure just in case
        if (!$category_id) {
             $category_id = $request->get('category_id');
        }
        
        $limit = $request->get('limit', 20);
        $offset = $request->get('offset', 0);

        if (!$category_id) {
            return response()->json(['status' => 'error', 'message' => 'id is required']);
        }

        $category = GreetingCategory::find($category_id);
        $item_name = $category ? $category->name : '';
        $item_image = '';
        if ($category && $category->icon) {
            $item_image = (StorageSetting::getStorageSetting('storage') == 'DigitalOcean') ? Storage::disk('spaces')->url('uploads/' . $category->icon) : asset('uploads/' . $category->icon);
        }

        $greetings = Greeting::where('greeting_category_id', $category_id)
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();

        $frames_data = [];
        foreach ($greetings as $item) {
            $frame_image = '';
            if ($item->frame_image) {
                if (StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                    $frame_image = Storage::disk('spaces')->url('uploads/' . $item->frame_image);
                } else {
                    $frame_image = asset('uploads/' . $item->frame_image);
                }
            }

            $zip_url = '';
            if ($item->greeting_type == 'editable' && $item->zip_name) {
                if (StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                    $zip_url = Storage::disk('spaces')->url('uploads/template/' . $item->zip_name); 
                } else {
                    $zip_url = asset('uploads/template/' . $item->zip_name);
                }
            }

            $frames_data[] = [
                'frameId' => $item->id,
                'type' => "greeting", 
                'image' => $frame_image,
                'language' => $item->language ? $item->language->title : 'All',
                'languageId' => $item->language_id ?? 0,
                'isPaid' => ($item->paid == 1) ? true : false,
                'isAi' => false,
                'height' => $item->height,
                'width' => $item->width,
                'imageType' => $item->image_type,
                'aspectRatio' => $item->aspect_ratio,
                'zip_url' => $zip_url,
                'zip_name' => $item->zip_name,
                'greeting_type' => $item->greeting_type, // 'simple' or 'editable'
            ];
        }

        return response()->json([
            'itemName' => $item_name,
            'itemImage' => $item_image,
            'frames' => $frames_data,
            'videos' => [],
            'totalFrames' => count($frames_data),
        ], 200);
    }
}
