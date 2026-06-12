<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CategoryBackgroundImageController extends Controller
{
    public function index()
    {
        $categories = \App\Models\BusinessCategory::where('status', '1')->get();
        $images = \App\Models\CategoryBackgroundImage::with('businessCategory')->orderBy('id', 'desc')->get();
        return view('admin.category_background_images.index', compact('categories', 'images'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'business_category_id' => 'required',
            'aspect_ratio' => 'required|in:1:1,16:9,9:16',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5048',
        ]);

        $imageName = time() . '_' . $request->image->getClientOriginalName();
        $request->image->move(public_path('uploads/background_images'), $imageName);

        \App\Models\CategoryBackgroundImage::create([
            'business_category_id' => $request->business_category_id,
            'aspect_ratio' => $request->aspect_ratio,
            'image' => 'uploads/background_images/' . $imageName,
        ]);

        return redirect()->back()->with('success', 'Background Image added successfully');
    }

    public function destroy($id)
    {
        $image = \App\Models\CategoryBackgroundImage::findOrFail($id);
        if (file_exists(public_path($image->image))) {
            unlink(public_path($image->image));
        }
        $image->delete();

        return redirect()->back()->with('success', 'Background Image deleted successfully');
    }
}
