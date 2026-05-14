<?php

namespace App\Http\Controllers\Admin;

use App\Models\Video;
use App\Models\Category;
use App\Models\Language;
use App\Models\Festivals;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\StorageSetting;
use App\Models\BusinessCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VideoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Video');
    }

    public function index()
    {
        $index['data'] = Video::orderBy('id', 'DESC')->paginate(12);
        $index['sub_name'] = [];
        return view("video.index", $index);
    }

    public function create()
    {
        $index['category'] = Category::where('status',1)->get();
        $index['business_category'] = BusinessCategory::where('status',1)->get();
        $index['festival'] = Festivals::where('status',1)->get();
        $index['language'] = Language::where('status',1)->get();
        return view("video.create",$index);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'type' => 'required',
            'video' => 'required|mimes:mp4,ogx,oga,ogv,ogg,webm|max:30720',
            'language_id' => 'required',
        ]);
        //dd($request->all());
        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $id = Video::create([
                "type" => $request->get("type"),
                "festival_id" => $request->get("festival_id"),
                "category_id" => $request->get("category_id"),
                "language_id" => $request->get("language_id"),
                "business_category_id" => $request->get("business_category_id"),
            ])->id;

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("video") && $request->file('video')->isValid()) {
                    $image = $request->file('video');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/video/'.$file, file_get_contents($image),'public');
                    
                    $v = Video::find($id);
                    $v->video = $file;
                    $v->save();
                }
            }
            else
            {
                if ($request->file("video") && $request->file('video')->isValid()) {
                    $this->upload_video($request->file("video"),"video", $id);
                }
            }

            return redirect()->route("video.index");
        }
    }

    public function video_status(Request $request)
    {
        $video = Video::find($request->get("id"));
        $video->status = ($request->get("checked")=="true")?1:0;
        $video->save();
    }

    public function video_type(Request $request)
    {
        $video = Video::find($request->get("id"));
        $video->paid = ($request->get("checked")=="true")?1:0;
        $video->save();

        if($video->paid == 1)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    public function video_action(Request $request)
    {
        $ids = explode(",",$request->select_post);
        if($request->select_post != null)
        {
            if($request->action_type == "enable")
            {
                foreach($ids as $id){
                    $video = Video::find($id);
                    $video->status = 1;
                    $video->save();
                }
            }
    
            if($request->action_type == "disable")
            {
                foreach($ids as $id){
                    $video = Video::find($id);
                    $video->status = 0;
                    $video->save();
                }
            }
    
            if($request->action_type == "delete")
            {
                foreach($ids as $id){
                    Video::find($id)->delete();
                }
            }
        }

        return redirect()->route("video.index");
    }

    public function video_list($type)
    {
        $index['data'] = Video::where('type',$type)->orderBy('id', 'DESC')->paginate(12);
        if($type == "businessCategory")
        {
            $index['name'] = "Business Category";
            $index['sub_name'] = BusinessCategory::where('status',1)->get();
        }
        if($type == "category")
        {
            $index['name'] = "Category";
            $index['sub_name'] = Category::where('status',1)->get();
        }
        if($type == "festival")
        {
            $index['name'] = "Festival";
            $index['sub_name'] = Festivals::where('status',1)->get();
        }
        $index['type'] = $type;
        return view("video.index", $index);
    }

    public function video_list_id($type,$id)
    {
        if($type == "businessCategory")
        {
            $index['name'] = "Business Category";
            $index['sub_name'] = BusinessCategory::where('status',1)->get();
            $index['data'] = Video::where('type',$type)->where('business_category_id',$id)->orderBy('id', 'DESC')->paginate(12);
            $b_c = BusinessCategory::find($id);
            $index['sub_title'] = $b_c->name;
        }
        if($type == "category")
        {
            $index['name'] = "Category";
            $index['sub_name'] = Category::where('status',1)->get();
            $index['data'] = Video::where('type',$type)->where('category_id',$id)->orderBy('id', 'DESC')->paginate(12);
            $c = Category::find($id);
            $index['sub_title'] = $c->name;
        }
        if($type == "festival")
        {
            $index['name'] = "Festival";
            $index['sub_name'] = Festivals::where('status',1)->get();
            $index['data'] = Video::where('type',$type)->where('festival_id',$id)->orderBy('id', 'DESC')->paginate(12);
            $f = Festivals::find($id);
            $index['sub_title'] = $f->title;
        }
        $index['type'] = $type;
        return view("video.index", $index);
    }

    public function edit($id)
    {
        $index['video'] = Video::find($id);
        $index['category'] = Category::where('status',1)->get();
        $index['business_category'] = BusinessCategory::where('status',1)->get();
        $index['festival'] = Festivals::where('status',1)->get();
        $index['language'] = Language::where('status',1)->get();
        return view("video.edit",$index);
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'type' => 'required',
            'video' => 'mimes:mp4,ogx,oga,ogv,ogg,webm|max:30720',
            'language_id' => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $video = Video::find($request->get("id"));
            $video->type = $request->get("type");
            $video->festival_id = $request->get("festival_id");
            $video->category_id = $request->get("category_id");
            $video->language_id = $request->get("language_id");
            $video->business_category_id = $request->get("business_category_id");
            $video->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("video") && $request->file('video')->isValid()) {
                    $image = $request->file('video');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/video/'.$file, file_get_contents($image),'public');
                    
                    $v = Video::find($request->get("id"));
                    $v->video = $file;
                    $v->save();
                }
            }
            else
            {
                if ($request->file("video") && $request->file('video')->isValid()) {
                    $this->upload_video($request->file("video"),"video", $id);
                }
            }

            return redirect()->route('video.index');
        }
    }

    public function destroy($id)
    {
        $video = Video::find($id);
        if($video) {
            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                Storage::disk('spaces')->delete('uploads/video/'.$video->video);
            }
            else
            {
                $filePath = public_path('uploads/video/'.$video->video);
                if(file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            $video->delete();
        }
        return redirect()->route('video.index');
    }

    private function upload_video($file,$field,$id)
    {
        $destinationPath = './uploads/video';
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $video = Video::find($id);
        $video->$field = $fileName;
        $video->save();
    }
}
