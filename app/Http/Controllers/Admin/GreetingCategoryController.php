<?php

namespace App\Http\Controllers\Admin;

use App\Models\GreetingCategory;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\StorageSetting;
use App\Models\Greeting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class GreetingCategoryController extends Controller
{
    public function __construct()
    {
        // $this->middleware('permission:GreetingCategory'); // Optional if you have roles/permissions set up
    }

    public function index()
    {
        $index['data'] = GreetingCategory::get();
        return view("greeting_category.index", $index);
    }

    public function create()
    {
        return view("greeting_category.create");
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
            $id = GreetingCategory::create([
                "name" => $request->get("name"),
            ])->id;

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $image = $request->file('icon');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $c = GreetingCategory::find($id);
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

            return redirect()->route("greeting-category.index");
        }
    }

    public function greeting_category_status(Request $request)
    {
        $category = GreetingCategory::find($request->get("id"));
        $category->status = ($request->get("checked")=="true")?1:0;
        $category->save();
    }

    public function edit($id)
    {
        $greetingCategory = GreetingCategory::find($id);
        return view("greeting_category.edit", compact("greetingCategory"));
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
            $category = GreetingCategory::find($request->get("id"));
            $category->name = $request->get("name");
            $category->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $image = $request->file('icon');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $c = GreetingCategory::find($request->get("id"));
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

            return redirect()->route('greeting-category.index');
        }
    }

    public function destroy($id)
    {
        $category = GreetingCategory::find($id);
        $greetings = Greeting::where('greeting_category_id',$id)->get();

        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            if ($category->icon) {
                Storage::disk('spaces')->delete('uploads/'.$category->icon);
            }
            foreach($greetings as $frame)
            {
                if ($frame->frame_image) {
                    Storage::disk('spaces')->delete('uploads/'.$frame->frame_image);
                }
            }
        }
        else
        {
            if ($category->icon && file_exists(base_path('uploads').$category->icon)) {
                unlink(base_path('uploads').$category->icon);
            }
            foreach($greetings as $frame)
            {
                if ($frame->frame_image && file_exists(base_path('uploads').$frame->frame_image)) {
                    unlink(base_path('uploads').$frame->frame_image);
                }
            }
        }

        GreetingCategory::find($id)->delete();
        Greeting::where('greeting_category_id',$id)->delete();

        return redirect()->route('greeting-category.index');
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = base_path('uploads');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = GreetingCategory::find($id);
        $image->$field = $fileName;
        $image->save();
    }
}
