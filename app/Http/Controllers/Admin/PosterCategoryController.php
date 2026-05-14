<?php

namespace App\Http\Controllers\Admin;

use App\Models\PosterMaker;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PosterCategory;
use App\Models\StorageSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PosterCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:PosterCategory');
    }

    public function index()
    {
        $index['data'] = PosterCategory::get();
        return view("poster_category.index", $index);
    }

    public function create()
    {
        return view("poster_category.create");
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } 
        else 
        {
            PosterCategory::create([
                "name" => $request->get("name"),
            ]);

            return redirect()->route("poster-category.index");
        }
    }

    public function poster_category_status(Request $request)
    {
        $category = PosterCategory::find($request->get("id"));
        $category->status = ($request->get("checked")=="true")?1:0;
        $category->save();
    }

    public function edit($id)
    {
        $category = PosterCategory::find($id);
        return view("poster_category.edit", compact("category"));
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } 
        else 
        {
            $category = PosterCategory::find($request->get("id"));
            $category->name = $request->get("name");
            $category->save();

            return redirect()->route('poster-category.index');
        }
    }

    public function destroy($id)
    {
        $posterMaker = PosterMaker::where('poster_category_id',$id)->get();
        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            foreach($posterMaker as $frame)
            {
                Storage::disk('spaces')->delete('uploads/'.$frame->post_thumb);
            }
        }
        else
        {
            foreach($posterMaker as $frame)
            {
                unlink('./uploads/'.$frame->post_thumb);
            }
        }

        PosterCategory::find($id)->delete();
        PosterMaker::where('poster_category_id',$id)->delete();
    
        return redirect()->route('poster-category.index');
    }
}
