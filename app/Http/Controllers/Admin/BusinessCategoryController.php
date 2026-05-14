<?php

namespace App\Http\Controllers\Admin;

use App\Models\Story;
use App\Models\Video;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\BusinessFrame;
use App\Models\StorageSetting;
use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BusinessCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:BusinessCategory');
    }

    public function index()
    {
        $index['data'] = BusinessCategory::get();
        return view("business_category.index", $index);
    }

    public function create()
    {
        return view("business_category.create");
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
            $id = BusinessCategory::create([
                "name" => $request->get("name"),
            ])->id;

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $image = $request->file('icon');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $c = BusinessCategory::find($id);
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

            return redirect()->route("business-category.index");
        }
    }

    public function business_category_status(Request $request)
    {
        $category = BusinessCategory::find($request->get("id"));
        $category->status = ($request->get("checked")=="true")?1:0;
        $category->save();
    }

    public function edit($id)
    {
        $category = BusinessCategory::find($id);
        return view("business_category.edit", compact("category"));
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
            $category = BusinessCategory::find($request->get("id"));
            $category->name = $request->get("name");
            $category->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $image = $request->file('icon');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $c = BusinessCategory::find($request->get('id'));
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

            return redirect()->route('business-category.index');
        }
    }

    public function destroy($id)
    {
        $businessCategory = BusinessCategory::find($id);
        $businessFrame = BusinessFrame::where('business_category_id',$id)->get();
        $video = Video::where("business_category_id",$id)->get();
        $businessSubCategory = BusinessSubCategory::where("business_category_id",$id)->get();

        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            Storage::disk('spaces')->delete('uploads/'.$businessCategory->icon);
            foreach($businessFrame as $frame)
            {
                Storage::disk('spaces')->delete('uploads/'.$frame->frame_image);
            }
            foreach($video as $v)
            {
                Storage::disk('spaces')->delete('uploads/video/'.$v->video);
            }
            foreach($businessSubCategory as $subCategory)
            {
                Storage::disk('spaces')->delete('uploads/'.$subCategory->icon);
            }
        }
        else
        {
            unlink('./uploads/'.$businessCategory->icon);
            foreach($businessFrame as $frame)
            {
                unlink('./uploads/'.$frame->frame_image);
            }
            foreach($video as $v)
            {
                unlink('./uploads/video/'.$v->video);
            }
            foreach($businessSubCategory as $subCategory)
            {
                unlink('./uploads/'.$subCategory->icon);
            }
        }

        BusinessCategory::find($id)->delete();
        BusinessFrame::where('business_category_id',$id)->delete();
        Video::where("business_category_id",$id)->delete();
        BusinessSubCategory::where("business_category_id",$id)->delete();

        return redirect()->route('business-category.index');
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = './uploads';
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = BusinessCategory::find($id);
        $image->$field = $fileName;
        $image->save();
    }
}
