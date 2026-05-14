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

class BusinessSubCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:BusinessSubCategory');
    }

    public function index()
    {
        $index['data'] = BusinessSubCategory::get();
        return view("business_sub_category.index", $index);
    }

    public function create()
    {
        $index['category'] = BusinessCategory::where('status',1)->get();
        return view("business_sub_category.create",$index);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            "business_category_id" => 'required',
            "icon" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $id = BusinessSubCategory::create([
                "name" => $request->get("name"),
                "business_category_id" => $request->get("business_category_id"),
            ])->id;

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $image = $request->file('icon');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $c = BusinessSubCategory::find($id);
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

            return redirect()->route("business-sub-category.index");
        }
    }

    public function business_sub_category_status(Request $request)
    {
        $category = BusinessSubCategory::find($request->get("id"));
        $category->status = ($request->get("checked")=="true")?1:0;
        $category->save();
    }

    public function edit($id)
    {
        $category = BusinessSubCategory::find($id);
        $businessCategory = BusinessCategory::where('status',1)->get();
        return view("business_sub_category.edit", compact("category","businessCategory"));
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            "business_category_id" => 'required',
            "icon" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $category = BusinessSubCategory::find($request->get("id"));
            $category->name = $request->get("name");
            $category->business_category_id = $request->get("business_category_id");
            $category->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $image = $request->file('icon');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $c = BusinessSubCategory::find($request->get('id'));
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

            return redirect()->route('business-sub-category.index');
        }
    }

    public function destroy($id)
    {
        $businessSubCategory = BusinessSubCategory::find($id);
        $businessFrame = BusinessFrame::where('business_sub_category_id',$id)->get();

        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            Storage::disk('spaces')->delete('uploads/'.$businessSubCategory->icon);
            foreach($businessFrame as $frame)
            {
                Storage::disk('spaces')->delete('uploads/'.$frame->frame_image);
            }
        }
        else
        {
            unlink('./uploads/'.$businessSubCategory->icon);
            foreach($businessFrame as $frame)
            {
                unlink('./uploads/'.$frame->frame_image);
            }
        }

        BusinessSubCategory::find($id)->delete();
        BusinessFrame::where('business_sub_category_id',$id)->delete();

        return redirect()->route('business-sub-category.index');
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = './uploads';
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = BusinessSubCategory::find($id);
        $image->$field = $fileName;
        $image->save();
    }
}
