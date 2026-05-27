<?php

namespace App\Http\Controllers\Admin;

use App\Models\Video;
use App\Models\Language;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\CategoryPost;
use App\Models\FestivalsPost;
use App\Models\StorageSetting;
use App\Models\CustomPostFrame;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LanguageController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Language');
    }

    public function index()
    {
        $index['data'] = Language::get();
        return view("language.index", $index);
    }

    public function create()
    {
        return view("language.create");
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'title' => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $id = Language::create([
                "title" => $request->get("title"),
            ])->id;

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $image = $request->file('image');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $l = Language::find($id);
                    $l->image = $file;
                    $l->save();
                }
            }
            else
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $this->upload_image($request->file("image"),"image", $id);
                }
            }

            return redirect()->route("language.index");
        }
    }

    public function language_status(Request $request)
    {
        $language = Language::find($request->get("id"));
        $language->status = ($request->get("checked")=="true")?1:0;
        $language->save();
    }

    public function edit($id)
    {
        $language = Language::find($id);
        return view("language.edit", compact("language"));
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'title' => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $language = Language::find($request->get("id"));
            $language->title = $request->get("title");
            $language->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $image = $request->file('image');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $l = Language::find($request->get("id"));
                    $l->image = $file;
                    $l->save();
                }
            }
            else
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $this->upload_image($request->file("image"),"image", $id);
                }
            }

            return redirect()->route('language.index');
        }
    }

    public function destroy($id)
    {
        $language = Language::find($id);
        $festivalsFrame = FestivalsPost::where("language_id",$id)->get();
        $categoryPost = CategoryPost::where("language_id",$id)->get();
        $customPostFrame = CustomPostFrame::where("language_id",$id)->get();
        $video = Video::where("language_id",$id)->get();

        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            Storage::disk('spaces')->delete('uploads/'.$language->image);
            foreach($festivalsFrame as $frame)
            {
                Storage::disk('spaces')->delete('uploads/'.$frame->frame_image);
            }
            foreach($categoryPost as $cat)
            {
                Storage::disk('spaces')->delete('uploads/'.$cat->frame_image);
            }
            foreach($customPostFrame as $catPost)
            {
                Storage::disk('spaces')->delete('uploads/'.$catPost->frame_image);
            }
            foreach($video as $v)
            {
                Storage::disk('spaces')->delete('uploads/video/'.$v->video);
            }
        }
        else
        {
            unlink(public_path('uploads/').$language->image);
            foreach($festivalsFrame as $frame)
            {
                unlink(public_path('uploads/').$frame->frame_image);
            }
            foreach($categoryPost as $cat)
            {
                unlink(public_path('uploads/').$cat->frame_image);
            }
            foreach($customPostFrame as $catPost)
            {
                unlink(public_path('uploads/').$catPost->frame_image);
            }
            foreach($video as $v)
            {
                unlink('./uploads/video/'.$v->video);
            }
        }

        FestivalsPost::where("language_id",$id)->delete();
        CategoryPost::where("language_id",$id)->delete();
        CustomPostFrame::where("language_id",$id)->delete();
        Video::where("language_id",$id)->delete();
        Language::find($id)->delete();

        return redirect()->route('language.index');
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = public_path('uploads');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = Language::find($id);
        $image->$field = $fileName;
        $image->save();
    }
}
