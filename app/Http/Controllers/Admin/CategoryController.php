<?php

namespace App\Http\Controllers\Admin;

use App\Models\Story;
use App\Models\Video;
use App\Models\Category;
use App\Models\FeaturePost;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\CategoryPost;
use App\Models\StorageSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Category');
    }

    public function index()
    {
        $index['data'] = Category::get();
        return view("category.index", $index);
    }

    public function create()
    {
        return view("category.create");
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            "icon" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $id = Category::create([
                "name" => $request->get("name"),
            ])->id;

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $image = $request->file('icon');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $c = Category::find($id);
                    $c->icon = $file;
                    $c->save();
                }
            }
            else
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $this->upload_image($request->file("icon"),"icon", $id);
                }
            }

            return redirect()->route("category.index");
        }
    }

    public function category_status(Request $request)
    {
        $category = Category::find($request->get("id"));
        $category->status = ($request->get("checked")=="true")?1:0;
        $category->save();
    }

    public function category_feature_status(Request $request)
    {
        $post = FeaturePost::where("category_id",$request->get("id"))->get();
        if($post->isEmpty())
        {
            FeaturePost::create([
                "category_id" => $request->get("id"),
                "type" => "category",
            ]);
            return 1;
        }
        else
        {
            FeaturePost::where("category_id",$request->get("id"))->delete();
            return 0;
        }
    }

    public function edit($id)
    {
        $category = Category::find($id);
        return view("category.edit", compact("category"));
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            "icon" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $category = Category::find($request->get("id"));
            $category->name = $request->get("name");
            $category->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $image = $request->file('icon');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $c = Category::find($request->get("id"));
                    $c->icon = $file;
                    $c->save();
                }
            }
            else
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $this->upload_image($request->file("icon"),"icon", $id);
                }
            }

            return redirect()->route('category.index');
        }
    }

    public function destroy($id)
    {
        $category = Category::find($id);
        $categoryPost = CategoryPost::where('category_id',$id)->get();
        $story = Story::where("category_id",$id)->get();
        $video = Video::where("category_id",$id)->get();

        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            Storage::disk('spaces')->delete('uploads/'.$category->icon);
            foreach($categoryPost as $cat)
            {
                Storage::disk('spaces')->delete('uploads/'.$cat->frame_image);
            }
            foreach($story as $s)
            {
                Storage::disk('spaces')->delete('uploads/'.$s->image);
            }
            foreach($video as $v)
            {
                Storage::disk('spaces')->delete('uploads/video/'.$v->video);
            }
        }
        else
        {
            unlink('./uploads/'.$category->icon);
            foreach($categoryPost as $cat)
            {
                unlink('./uploads/'.$cat->frame_image);
            }
            foreach($story as $s)
            {
                unlink('./uploads/'.$s->image);
            }
            foreach($video as $v)
            {
                unlink('./uploads/video/'.$v->video);
            }
        }
        
        Category::find($id)->delete();
        CategoryPost::where('category_id',$id)->delete();
        FeaturePost::where('category_id',$id)->delete();
        Story::where("category_id",$id)->delete();
        Video::where("category_id",$id)->delete();

        return redirect()->route('category.index');
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = './uploads';
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = Category::find($id);
        $image->$field = $fileName;
        $image->save();
    }
}
