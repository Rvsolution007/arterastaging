<?php

namespace App\Http\Controllers\Admin;

use App\Models\Story;
use App\Models\CustomPost;
use App\Models\FeaturePost;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\StorageSetting;
use App\Models\CustomPostFrame;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CustomPostController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:CustomCategory');
    }

    public function index()
    {
        $index['data'] = CustomPost::get();
        return view("custom_post.index", $index);
    }

    public function create()
    {
        return view("custom_post.create");
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
            $id = CustomPost::create([
                "name" => $request->get("name"),
            ])->id;

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $image = $request->file('icon');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $c = CustomPost::find($id);
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

            return redirect()->route("custom-post.index");
        }
    }

    public function custom_post_status(Request $request)
    {
        $customPost = CustomPost::find($request->get("id"));
        $customPost->status = ($request->get("checked")=="true")?1:0;
        $customPost->save();
    }

    public function custom_feature_status(Request $request)
    {
        $post = FeaturePost::where("custom_id",$request->id)->get();
        if($post->isEmpty())
        {
            FeaturePost::create([
                "custom_id" => $request->id,
                "type" => "custom",
            ]);
            return 1;
        }
        else
        {
            FeaturePost::where("custom_id",$request->id)->delete();
            return 0;
        }
    }

    public function edit($id)
    {
        $customPost = CustomPost::find($id);
        return view("custom_post.edit", compact("customPost"));
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
            $customPost = CustomPost::find($request->get("id"));
            $customPost->name = $request->get("name");
            $customPost->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $image = $request->file('icon');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $c = CustomPost::find($request->get("id"));
                    $c->icon = $file;
                    $c->save();
                }
            }
            else
            {
                if($request->file("icon") && $request->file('icon')->isValid()) {
                    $this->upload_image($request->file("icon"),"icon", $id);
                }
            }

            return redirect()->route('custom-post.index');
        }
    }

    public function destroy($id)
    {
        $customPost = CustomPost::find($id);
        $customPostFrame = CustomPostFrame::where('custom_post_id',$id)->get();
        $story = Story::where("custom_category_id",$id)->get();

        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            Storage::disk('spaces')->delete('uploads/'.$customPost->icon);
            foreach($customPostFrame as $frame)
            {
                Storage::disk('spaces')->delete('uploads/'.$frame->frame_image);
            }
            foreach($story as $s)
            {
                Storage::disk('spaces')->delete('uploads/'.$s->image);
            }
        }
        else
        {
            unlink('./uploads/'.$customPost->icon);
            foreach($customPostFrame as $frame)
            {
                unlink('./uploads/'.$frame->frame_image);
            }
            foreach($story as $s)
            {
                unlink('./uploads/'.$s->image);
            }
        }

        CustomPost::find($id)->delete();
        CustomPostFrame::where('custom_post_id',$id)->delete();
        Story::where("custom_category_id",$id)->delete();

        return redirect()->route('custom-post.index');
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = './uploads';
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = CustomPost::find($id);
        $image->$field = $fileName;
        $image->save();
    }
}
