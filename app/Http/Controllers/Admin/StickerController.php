<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Models\Sticker;
use App\Models\Language;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\StorageSetting;
use App\Models\StickerCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StickerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Sticker');
    }

    public function index()
    {
        $index['category'] = StickerCategory::get();
        $index['data'] = Sticker::orderBy('id', 'DESC')->paginate(12);
        return view("sticker.index", $index);
    }

    public function create()
    {
        $index['category'] = StickerCategory::where('status',1)->get();
        return view("sticker.create", $index);
    }

    public function store(Request $request)
    {
        //dd($request->all());
        $validation = Validator::make($request->all(), [
            "sticker_category_id" => 'required',
            "image" => "required",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            if ($request->file("image")) 
            {
                $removedImages = json_decode($request->get("deleted_file_ids"), true);
                $images = $request->file('image');
                foreach($images as $image) 
                {
                    if($removedImages != null)
                    {
                        if (in_array($image->getClientOriginalName(), $removedImages)) {
                            continue;
                        }
                    }
                    
                    $id = Sticker::create([
                        "sticker_category_id" => $request->get("sticker_category_id"),
                    ])->id;

                    if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
                    {
                        $file = Str::uuid().'.'.$image->getClientOriginalExtension();
                
                        $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                        
                        $sticker = Sticker::find($id);
                        $sticker->image = $file;
                        $sticker->save();
                    }
                    else
                    {
                        $this->upload_image($image,"image", $id);
                    }
                }
            }

            return redirect()->route("sticker.index");
        }
    }

    public function sticker_status(Request $request)
    {
        $category = Sticker::find($request->get("id"));
        $category->status = ($request->get("checked")=="true")?1:0;
        $category->save();
    }

    public function sticker_action(Request $request)
    {
        $ids = explode(",",$request->select_post);
        if($request->select_post != null)
        {
            if($request->action_type == "enable")
            {
                foreach($ids as $id){
                    $category = Sticker::find($id);
                    $category->status = 1;
                    $category->save();
                }
            }
    
            if($request->action_type == "disable")
            {
                foreach($ids as $id){
                    $category = Sticker::find($id);
                    $category->status = 0;
                    $category->save();
                }
            }
    
            if($request->action_type == "delete")
            {
                foreach($ids as $id){
                    Sticker::find($id)->delete();
                }
            }
        }

        return redirect()->route("sticker.index");
    }

    public function sticker_category_get($id)
    {
        $index['category'] = StickerCategory::get();
        $index['data'] = Sticker::where('sticker_category_id',$id)->paginate(12);
        $c_name=StickerCategory::find($id);
        $index['name'] = $c_name->name;

        return view("sticker.index", $index);
    }

    public function edit($id)
    {
        $index['sticker'] = Sticker::find($id);
        $index['category'] = StickerCategory::get();
        return view("sticker.edit", $index);
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            "sticker_category_id" => 'required',
            "image" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $category = Sticker::find($request->get("id"));
            $category->sticker_category_id = $request->get("sticker_category_id");
            $category->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $image = $request->file('image');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $sticker = Sticker::find($request->get("id"));
                    $sticker->image = $file;
                    $sticker->save();
                }
            }
            else
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $this->upload_image($request->file("image"),"image", $id);
                }
            }

            return redirect()->route('sticker.index');
        }
    }

    public function destroy($id)
    {
        $sticker = Sticker::find($id);
        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            Storage::disk('spaces')->delete('uploads/'.$sticker->image);
        }
        else
        {
            unlink('./uploads/'.$sticker->image);
        }

        Sticker::find($id)->delete();
        return redirect()->route('sticker.index');
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = './uploads';
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = Sticker::find($id);
        $image->$field = $fileName;
        $image->save();
    }
}
