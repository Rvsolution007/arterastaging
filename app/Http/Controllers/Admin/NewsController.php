<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\News;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\StorageSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class NewsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:News');
    }

    public function index()
    {
        $index['data'] = News::orderBy('id', 'DESC')->paginate(12);
        return view("news.index", $index);
    }

    public function create()
    {
        return view("news.create");
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
            "image" => "nullable|mimes:jpg,png,jpeg",
            "date" => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $id = News::create([
                "title" => $request->get("title"),
                "description" => $request->get("description"),
                "date" => Carbon::createFromFormat('d M, y',$request->get("date"))->format('Y-m-d'),
            ])->id;

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $image = $request->file('image');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $n = News::find($id);
                    $n->image = $file;
                    $n->save();
                }
            }
            else
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $this->upload_image($request->file("image"),"image", $id);
                }
            }

            return redirect()->route("news.index");
        }
    }

    public function edit($id)
    {
        $news = News::find($id);
        return view("news.edit", compact("news"));
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
            "image" => "nullable|mimes:jpg,png,jpeg",
            "date" => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $news = News::find($request->get("id"));
            $news->title = $request->get("title");
            $news->description = $request->get("description");
            $news->date = Carbon::createFromFormat('d M, y',$request->get("date"))->format('Y-m-d');
            $news->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $image = $request->file('image');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $n = News::find($request->get("id"));
                    $n->image = $file;
                    $n->save();
                }
            }
            else
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $this->upload_image($request->file("image"),"image", $id);
                }
            }

            return redirect()->route('news.index');
        }
    }

    public function destroy($id)
    {
        $news = News::find($id);
        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            Storage::disk('spaces')->delete('uploads/'.$news->image);
        }
        else
        {
            unlink('./uploads/'.$news->image);
        }

        News::find($id)->delete();
        return redirect()->route('news.index');
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = './uploads';
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = News::find($id);
        $image->$field = $fileName;
        $image->save();
    }
}
