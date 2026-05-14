<?php

namespace App\Http\Controllers\Admin;

use App\Models\Sticker;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\StorageSetting;
use App\Models\StickerCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StickerCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:StickerCategory');
    }

    public function index()
    {
        $index['data'] = StickerCategory::get();
        return view("sticker_category.index", $index);
    }

    public function create()
    {
        return view("sticker_category.create");
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $id = StickerCategory::create([
                "name" => $request->get("name"),
            ])->id;

            return redirect()->route("sticker-category.index");
        }
    }

    public function sticker_category_status(Request $request)
    {
        $category = StickerCategory::find($request->get("id"));
        $category->status = ($request->get("checked")=="true")?1:0;
        $category->save();
    }

    public function edit($id)
    {
        $category = StickerCategory::find($id);
        return view("sticker_category.edit", compact("category"));
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $category = StickerCategory::find($request->get("id"));
            $category->name = $request->get("name");
            $category->save();

            return redirect()->route('sticker-category.index');
        }
    }

    public function destroy($id)
    {
        $sticker = Sticker::where('sticker_category_id',$id)->get();
        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            foreach($sticker as $s)
            {
                Storage::disk('spaces')->delete('uploads/'.$s->image);
            }
        }
        else
        {
            foreach($sticker as $s)
            {
                unlink('./uploads/'.$s->image);
            }
        }

        StickerCategory::find($id)->delete();
        Sticker::where('sticker_category_id',$id)->delete();

        return redirect()->route('sticker-category.index');
    }
}
