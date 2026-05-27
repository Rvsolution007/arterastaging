<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Models\Inquiry;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\StorageSetting;
use App\Models\ProductCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Product');
    }

    public function index()
    {
        $index['data'] = Product::get();
        return view("product.index", $index);
    }

    public function create()
    {
        $index['productCategory'] = ProductCategory::where('status',1)->get();
        return view("product.create", $index);
    }

    public function product_status(Request $request)
    {
        $product = Product::find($request->get("id"));
        $product->status = ($request->get("checked")=="true")?1:0;
        $product->save();
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'title' => 'required',
            'price' => 'required',
            "discount_price" => 'required',
            'description' => 'required',
            "image" => "nullable|mimes:jpg,png,jpeg",
            'product_category_id' => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {

            $id = Product::create([
                "product_category_id" => $request->get("product_category_id"),
                "title" => $request->get("title"),
                "price" => $request->get("price"),
                "discount_price" => $request->get("discount_price"),
                "description" => $request->get("description"),
            ])->id;
           
            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $image = $request->file('image');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $product = Product::find($id);
                    $product->image = $file;
                    $product->save();
                }
            }
            else
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $this->upload_image($request->file("image"),"image", $id);
                }
            }

            return redirect()->route("product.index");
        }
    }

    public function edit($id)
    {
        $index['product'] = Product::find($id);
        $index['productCategory'] = ProductCategory::where('status',1)->get();
        return view("product.edit", $index);
    }

    public function update(Request $request, $id)
    {
        //dd($request->all());
        $validation = Validator::make($request->all(), [
            'title' => 'required',
            'price' => 'required',
            "discount_price" => 'required',
            'description' => 'required',
            "image" => "nullable|mimes:jpg,png,jpeg",
            'product_category_id' => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $product = Product::find($request->get("id"));
            $product->product_category_id = $request->product_category_id;
            $product->title = $request->title;
            $product->price = $request->price;
            $product->discount_price = $request->discount_price;
            $product->description = $request->description;
            $product->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $image = $request->file('image');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $product = Product::find($request->get("id"));
                    $product->image = $file;
                    $product->save();
                }
            }
            else
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $this->upload_image($request->file("image"),"image", $id);
                }
            }

            return redirect()->route('product.index');
        }
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            Storage::disk('spaces')->delete('uploads/'.$product->image);
        }
        else
        {
            unlink(public_path('uploads/').$product->image);
        }

        Inquiry::where('product_id',$id)->delete();
        Product::find($id)->delete();
        
        return redirect()->route('product.index');
    }

    private function upload_image($file, $field, $id)
    {
        $destinationPath = public_path('uploads');
        $fileName = Str::uuid() . '.webp';
        
        // Hostinger Storage Optimization: Compress to WebP (Phase 5 of SaaS Plan)
        // Restrict dimensions to 600px max (suitable for 1080px final flutter posters)
        $info = getimagesize($file->getPathname());
        $mime = $info['mime'];
        $width = $info[0];
        $height = $info[1];

        $maxWidth = 600;
        $maxHeight = 600;

        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }

        $imageCreateFunc = null;
        if ($mime == 'image/jpeg') $imageCreateFunc = 'imagecreatefromjpeg';
        elseif ($mime == 'image/png') $imageCreateFunc = 'imagecreatefrompng';
        elseif ($mime == 'image/webp') $imageCreateFunc = 'imagecreatefromwebp';

        if ($imageCreateFunc) {
            $srcImage = $imageCreateFunc($file->getPathname());
            $dstImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Handle transparency for PNG/WebP
            if ($mime == 'image/png' || $mime == 'image/webp') {
                imagealphablending($dstImage, false);
                imagesavealpha($dstImage, true);
                $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);
                imagefilledrectangle($dstImage, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            // Save as compressed Webp (75% quality is visually lossless but highly compressed ~80KB)
            imagewebp($dstImage, $destinationPath . '/' . $fileName, 75);
            
            imagedestroy($srcImage);
            imagedestroy($dstImage);
        } else {
            // Fallback if unsupported format somehow bypasses validation
            $file->move($destinationPath, $fileName);
        }
        
        $image = Product::find($id);
        $image->$field = $fileName;
        $image->save();
    }
}
