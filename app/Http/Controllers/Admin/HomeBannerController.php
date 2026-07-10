<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HomeBanner;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class HomeBannerController extends Controller
{
    public function index()
    {
        $banners = HomeBanner::orderBy('column_index')->orderBy('sort_order')->orderBy('id', 'desc')->get()->groupBy('column_index');
        return view('admin.marketing.home-banners', compact('banners'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'column_index' => 'required|integer|in:1,2,3',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120'
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            $filename = Str::slug($originalName) . '-' . time() . '.webp';
            
            $directory = public_path('uploads/banners');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $destinationPath = $directory . '/' . $filename;
            
            try {
                $manager = new ImageManager(new Driver());
                $img = $manager->read($image->getPathname());
                
                // Convert and save to WebP
                $encoded = $img->toWebp(80);
                file_put_contents($destinationPath, (string) $encoded);

            } catch (\Exception $e) {
                return back()->with('error', 'Failed to process image: ' . $e->getMessage());
            }

            HomeBanner::create([
                'column_index' => $request->column_index,
                'image_path' => 'uploads/banners/' . $filename,
                'sort_order' => 0
            ]);

            return back()->with('success', 'Banner image uploaded and converted to WebP successfully.');
        }

        return back()->with('error', 'No image selected.');
    }

    public function updateSort(Request $request)
    {
        $order = $request->order;
        if (is_array($order)) {
            foreach ($order as $sortOrder => $id) {
                HomeBanner::where('id', $id)->update(['sort_order' => $sortOrder]);
            }
        }
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $banner = HomeBanner::findOrFail($id);
        
        $filePath = public_path($banner->image_path);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        $banner->delete();
        return back()->with('success', 'Banner image deleted successfully.');
    }
}
