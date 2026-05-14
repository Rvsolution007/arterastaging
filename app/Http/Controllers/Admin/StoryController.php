<?php

namespace App\Http\Controllers\Admin;

use Image;
use App\Models\Story;
use App\Models\Category;
use App\Models\Festivals;
use App\Models\CustomPost;
use Illuminate\Support\Str;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Models\StorageSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Stories');
    }

    public function index()
    {
        $index['data'] = Story::orderBy('sort_order', 'ASC')->orderBy('id', 'DESC')->paginate(12);
        return view("story.index", $index);
    }

    public function create()
    {
        $index['category'] = Category::where('status',1)->get();
        $index['custom'] = CustomPost::where('status',1)->get();
        $index['festival'] = Festivals::where('status',1)->get();
        $index['plan'] = Subscription::get();
        return view("story.create", $index);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "story_type" => 'required',
            "images.*" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            // Calculate Expiry
            $expires_at = null;
            if($request->expire_type && $request->expire_type != 'never' && $request->expire_value) {
                if($request->expire_type == 'hours') $expires_at = now()->addHours($request->expire_value);
                if($request->expire_type == 'days') $expires_at = now()->addDays($request->expire_value);
                if($request->expire_type == 'months') $expires_at = now()->addMonths($request->expire_value);
                if($request->expire_type == 'years') $expires_at = now()->addYears($request->expire_value);
            }

            $id = Story::create([
                "story_type" => $request->get("story_type"),
                "user_id" => $request->get("user_id"),
                "festival_id" => $request->get("festival_id"),
                "category_id" => $request->get("category_id"),
                "custom_category_id" => $request->get("custom_category_id"),
                "subscription_id" => $request->get("plan_id"),
                "external_link" => $request->get("external_link"),
                "external_link_title" => $request->get("external_link_title"),
                "expire_type" => $request->get("expire_type", 'never'),
                "expire_value" => $request->get("expire_value"),
                "expires_at" => $expires_at,
            ])->id;

            $this->processStoryImages($request, $id);

            return redirect()->route("story.index");
        }
    }

    public function story_status(Request $request)
    {
        $story = Story::find($request->get("id"));
        $story->status = ($request->get("checked")=="true")?1:0;
        $story->save();
    }

    public function updateSortOrder(Request $request)
    {
        $story = Story::find($request->id);
        if($story) {
            $story->sort_order = $request->sort_order;
            $story->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false]);
    }

    public function edit($id)
    {
        $index['story'] = Story::find($id);
        $index['category'] = Category::where('status',1)->get();
        $index['festival'] = Festivals::where('status',1)->get();
        $index['custom'] = CustomPost::where('status',1)->get();
        $index['plan'] = Subscription::get();
        return view("story.edit", $index);
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            "story_type" => 'required',
            "images.*" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $expires_at = null;
            if($request->expire_type && $request->expire_type != 'never' && $request->expire_value) {
                if($request->expire_type == 'hours') $expires_at = now()->addHours($request->expire_value);
                if($request->expire_type == 'days') $expires_at = now()->addDays($request->expire_value);
                if($request->expire_type == 'months') $expires_at = now()->addMonths($request->expire_value);
                if($request->expire_type == 'years') $expires_at = now()->addYears($request->expire_value);
            }

            $story = Story::find($request->get("id"));
            $story->story_type = $request->get("story_type");
            $story->festival_id = $request->get("festival_id");
            $story->category_id = $request->get("category_id");
            $story->custom_category_id = $request->get("custom_category_id");
            $story->subscription_id = $request->get("plan_id");
            $story->external_link = $request->get("external_link");
            $story->external_link_title = $request->get("external_link_title");
            $story->expire_type = $request->get("expire_type", 'never');
            $story->expire_value = $request->get("expire_value");
            $story->expires_at = $expires_at;
            $story->save();

            if ($request->hasFile("images")) {
                $this->processStoryImages($request, $story->id);
            }

            return redirect()->route('story.index');
        }
    }

    public function destroy($id)
    {
        $story = Story::find($id);
        
        $images = $story->story_images ?? [];
        if($story->image && !in_array($story->image, $images)) {
            $images[] = $story->image;
        }

        foreach($images as $img) {
            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                Storage::disk('spaces')->delete('uploads/'.$img);
            } else {
                if(file_exists('./uploads/'.$img)) {
                    unlink('./uploads/'.$img);
                }
            }
        }

        $story->delete();
        return redirect()->route('story.index');
    }

    private function processStoryImages(Request $request, $id)
    {
        $story = Story::find($id);
        $images_array = [];

        if ($request->hasFile("images")) {
            foreach($request->file("images") as $image) {
                if ($image->isValid()) {
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
                    if(StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                        Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    } else {
                        $image->move('./uploads', $file);
                    }
                    $images_array[] = $file;
                }
            }
        }

        if(count($images_array) > 0) {
            $story->image = $images_array[0];
            $story->story_images = $images_array;
            $story->save();
        }
    }
}
