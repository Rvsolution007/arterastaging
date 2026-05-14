<?php

namespace App\Http\Controllers\Admin;

use App\Models\Inquiry;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\StorageSetting;
use App\Models\ProductCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ProductCategory');
    }

    public function index()
    {
        $index['data'] = ProductCategory::get();
        return view("product_category.index", $index);
    }

    public function create()
    {
        return view("product_category.create");
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            "image" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $id = ProductCategory::create([
                "name" => $request->get("name"),
            ])->id;

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $image = $request->file('image');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $p = ProductCategory::find($id);
                    $p->image = $file;
                    $p->save();
                }
            }
            else
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $this->upload_image($request->file("image"),"image", $id);
                }
            }

            return redirect()->route("product-category.index");
        }
    }

    public function product_category_status(Request $request)
    {
        $category = ProductCategory::find($request->get("id"));
        $category->status = ($request->get("checked")=="true")?1:0;
        $category->save();
    }

    public function edit($id)
    {
        $category = ProductCategory::find($id);
        return view("product_category.edit", compact("category"));
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            "image" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $category = ProductCategory::find($request->get("id"));
            $category->name = $request->get("name");
            $category->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $image = $request->file('image');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $c = ProductCategory::find($request->get("id"));
                    $c->image = $file;
                    $c->save();
                }
            }
            else
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $this->upload_image($request->file("image"),"image", $id);
                }
            }

            return redirect()->route('product-category.index');
        }
    }

    public function destroy($id)
    {
        $productCategory = ProductCategory::find($id);
        $product = Product::where('product_category_id',$id)->get();
        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            Storage::disk('spaces')->delete('uploads/'.$productCategory->image);
            foreach($product as $p)
            {
                Storage::disk('spaces')->delete('uploads/'.$p->image);
            }
        }
        else
        {
            unlink('./uploads/'.$productCategory->image);
            foreach($product as $p)
            {
                unlink('./uploads/'.$p->image);
            }
        }

        foreach($product as $p)
        {
            Product::find($p->id)->delete();
            Inquiry::where('product_id',$p->id)->delete();
        }
        ProductCategory::find($id)->delete();
    
        return redirect()->route('product-category.index');
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = './uploads';
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = ProductCategory::find($id);
        $image->$field = $fileName;
        $image->save();
    }
}
